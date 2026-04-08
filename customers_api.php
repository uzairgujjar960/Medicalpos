<?php
// customers_api.php
header('Content-Type: application/json');
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM customers ORDER BY id DESC");
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $customers]);
    } catch(Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} 
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    try {
        if ($data['action'] === 'save') {
            if (isset($data['id']) && $data['id'] != '') {
                // UPDATE Customer
                $stmt = $pdo->prepare("UPDATE customers SET name=?, phone=?, email=?, address=?, gender=?, status=? WHERE id=?");
                $stmt->execute([$data['name'], $data['phone'], $data['email'], $data['address'], $data['gender'], $data['status'], $data['id']]);
                echo json_encode(['status' => 'success', 'message' => 'Customer updated']);
            } else {
                // INSERT Naya Customer
                $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address, gender, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$data['name'], $data['phone'], $data['email'], $data['address'], $data['gender'], $data['status']]);
                echo json_encode(['status' => 'success', 'message' => 'Customer added']);
            }
        } 
        elseif ($data['action'] === 'delete') {
            // DELETE Customer
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id=?");
            $stmt->execute([$data['id']]);
            echo json_encode(['status' => 'success', 'message' => 'Customer deleted']);
        }
    } catch(Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>