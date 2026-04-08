<?php
// dashboard_api.php
header('Content-Type: application/json');
require 'db.php'; 

try {
    // 1. Stats (Sirf Completed Sales)
    $totalRevenue = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE status = 'Completed'")->fetchColumn() ?: 0;
    $todayRevenue = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE(sale_date) = CURDATE() AND status = 'Completed'")->fetchColumn() ?: 0;
    $totalMeds = $pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn() ?: 0;
    $totalCust = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn() ?: 0;

    // 2. Recent Sales (All status shown)
    $recentSales = $pdo->query("SELECT * FROM sales ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Top Selling Medicines (SIRF COMPLETED SALES SE)
    $sqlTop = "SELECT m.name, SUM(si.quantity) as total_qty 
               FROM sale_items si 
               JOIN medicines m ON si.medicine_id = m.id 
               JOIN sales s ON si.invoice_no = s.invoice_no 
               WHERE s.status = 'Completed' 
               GROUP BY si.medicine_id 
               ORDER BY total_qty DESC LIMIT 5";
    $topSelling = $pdo->query($sqlTop)->fetchAll(PDO::FETCH_ASSOC);

    // 4. Low Selling Medicines (SIRF COMPLETED SALES SE)
    $sqlLow = "SELECT m.name, IFNULL(SUM(CASE WHEN s.status = 'Completed' THEN si.quantity ELSE 0 END), 0) as total_qty 
               FROM medicines m 
               LEFT JOIN sale_items si ON m.id = si.medicine_id 
               LEFT JOIN sales s ON si.invoice_no = s.invoice_no 
               GROUP BY m.id 
               ORDER BY total_qty ASC LIMIT 5";
    $lowSelling = $pdo->query($sqlLow)->fetchAll(PDO::FETCH_ASSOC);

    // 5. Weekly Sales Data (SIRF COMPLETED SALES SE)
    $weeklySales = $pdo->query("SELECT DATE(sale_date) as date, SUM(total_amount) as total FROM sales WHERE status = 'Completed' AND sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(sale_date) ORDER BY date ASC")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'stats' => ['total_revenue' => $totalRevenue, 'today_revenue' => $todayRevenue, 'total_medicines' => $totalMeds, 'total_customers' => $totalCust],
        'recent_sales' => $recentSales,
        'top_selling' => $topSelling,
        'low_selling' => $lowSelling,
        'weekly_sales' => $weeklySales
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>