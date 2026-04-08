<?php
// get_medicines.php
header('Content-Type: application/json');
require 'db.php';

try {
    $stmt = $pdo->query("SELECT * FROM medicines");
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $medicines]);
} catch(Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>