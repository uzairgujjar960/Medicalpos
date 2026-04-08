<?php
// sales_api.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

require 'db.php'; // Ensure this file correctly initializes your $pdo connection

$input = file_get_contents("php://input");
$data = json_decode($input, true);
$method = $_SERVER['REQUEST_METHOD'];

try {
    // --- GET METHOD: FETCH DATA ---
    if ($method === 'GET') {
        if (isset($_GET['invoice_no'])) {
            // Fetch items for a specific invoice (View Details)
            $stmt = $pdo->prepare("SELECT si.*, m.name FROM sale_items si JOIN medicines m ON si.medicine_id = m.id WHERE si.invoice_no = ?");
            $stmt->execute([$_GET['invoice_no']]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } else {
            // Fetch all sales for History table
            $stmt = $pdo->query("SELECT * FROM sales ORDER BY id DESC");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
    } 
    
    // --- POST METHOD: SAVE OR RETURN ---
    elseif ($method === 'POST') {
        
        // --- ACTION: RETURN SALE ---
        if (isset($data['action']) && $data['action'] === 'return_sale') {
            $pdo->beginTransaction();

            // 1. Check if already returned
            $stmtCheck = $pdo->prepare("SELECT status FROM sales WHERE invoice_no = ?");
            $stmtCheck->execute([$data['invoice_no']]);
            $currentStatus = $stmtCheck->fetchColumn();

            if ($currentStatus === 'Returned') {
                throw new Exception("This sale has already been returned.");
            }

            // 2. Update status to 'Returned'
            $stmtUpdate = $pdo->prepare("UPDATE sales SET status = 'Returned' WHERE invoice_no = ?");
            $stmtUpdate->execute([$data['invoice_no']]);

            // 3. Restore stock to medicines table
            $stmtItems = $pdo->prepare("SELECT medicine_id, quantity FROM sale_items WHERE invoice_no = ?");
            $stmtItems->execute([$data['invoice_no']]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                $stmtStock = $pdo->prepare("UPDATE medicines SET stock = stock + ? WHERE id = ?");
                $stmtStock->execute([$item['quantity'], $item['medicine_id']]);
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Sale returned and stock restored successfully.']);
        }

        // --- ACTION: SAVE SALE ---
        elseif (isset($data['action']) && $data['action'] === 'save_sale') {
            $pdo->beginTransaction();

            // 1. Insert into sales table
            $sqlSale = "INSERT INTO sales (invoice_no, customer_name, total_amount, discount, tax, payment_method, status, sale_date) VALUES (?, ?, ?, ?, ?, ?, 'Completed', ?)";
            $stmtSale = $pdo->prepare($sqlSale);
            $stmtSale->execute([
                $data['invoice_no'], 
                $data['customer'], 
                $data['total'], 
                $data['discount'] ?? 0, 
                $data['tax'] ?? 0, 
                $data['payment_method'] ?? 'Cash', // Matches frontend key
                $data['date'] // Uses date sent from POS
            ]);

            // 2. Insert items and update stock
            foreach ($data['items'] as $item) {
                // Insert into sale_items
                $sqlItem = "INSERT INTO sale_items (invoice_no, medicine_id, quantity, price_at_sale) VALUES (?, ?, ?, ?)";
                $stmtItem = $pdo->prepare($sqlItem);
                $stmtItem->execute([$data['invoice_no'], $item['id'], $item['qty'], $item['price']]);

                // Deduct from medicines stock
                $sqlStock = "UPDATE medicines SET stock = stock - ? WHERE id = ?";
                $stmtStock = $pdo->prepare($sqlStock);
                $stmtStock->execute([$item['qty'], $item['id']]);
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Sale saved and stock updated!']);
        }
    }
} catch (Exception $e) {
    // Rollback changes if any error occurs
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>