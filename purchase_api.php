<?php
// purchase_api.php
header('Content-Type: application/json');
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Saare orders fetch karna
        $stmt = $pdo->query("SELECT * FROM purchase_orders ORDER BY id DESC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } 
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $data['action'];

        if ($action === 'save_po') {
            // Naya Order Save Karna
            $stmt = $pdo->prepare("INSERT INTO purchase_orders (po_number, supplier_name, medicine_name, quantity, order_date, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$data['po_number'], $data['supplier'], $data['medicine'], $data['qty'], $data['date']]);
            echo json_encode(['status' => 'success', 'message' => 'Purchase Order created']);
        } 
        elseif ($action === 'mark_received') {
            $pdo->beginTransaction();

            // 1. Order status update karein
            $stmt = $pdo->prepare("UPDATE purchase_orders SET status = 'Received' WHERE id = ?");
            $stmt->execute([$data['id']]);

            // 2. Medicines table mein stock plus (+) karein
            // Note: Hum medicine_name se match kar rahe hain jaisa aapne front-end par rakha hai
            $stmtStock = $pdo->prepare("UPDATE medicines SET stock = stock + ? WHERE name = ?");
            $stmtStock->execute([$data['qty'], $data['medicine_name']]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Stock updated successfully']);
        }
        elseif ($action === 'delete_po') {
            $stmt = $pdo->prepare("DELETE FROM purchase_orders WHERE id = ? AND status = 'Pending'");
            $stmt->execute([$data['id']]);
            echo json_encode(['status' => 'success', 'message' => 'Order deleted']);
        }
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>