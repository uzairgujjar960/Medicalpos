<?php
// medicines_api.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Port specify karna zaroori hai kyunke aapka MySQL 3307 par hai
$servername = "127.0.0.1"; // 'localhost' ki jagah 127.0.0.1 behtar hai port ke sath
$username = "root";
$password = "";
$dbname = "pos_system";
$port = 3307; // Yeh woh port hai jo aapki config file mein hai

$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM medicines ORDER BY id DESC";
    $result = $conn->query($sql);
    
    $medicines = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $medicines[] = $row;
        }
    }
    
    echo json_encode(["status" => "success", "data" => $medicines]);
    $conn->close();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['action'])) {
        echo json_encode(["status" => "error", "message" => "Invalid data received from frontend."]);
        $conn->close();
        exit();
    }

    $action = $data['action'];

    if ($action === 'save') {
        $id = isset($data['id']) ? $data['id'] : '';
        $barcode = isset($data['barcode']) ? $conn->real_escape_string($data['barcode']) : '';
        $name = isset($data['name']) ? $conn->real_escape_string($data['name']) : '';
        $category = isset($data['category']) ? $conn->real_escape_string($data['category']) : '';
        $price = isset($data['price']) ? (float)$data['price'] : 0;
        $cost = isset($data['cost']) && $data['cost'] !== '' ? (float)$data['cost'] : 0;
        $stock = isset($data['stock']) ? (int)$data['stock'] : 0;
        $expiry = isset($data['expiry']) ? $conn->real_escape_string($data['expiry']) : '';
        $status = isset($data['status']) ? $conn->real_escape_string($data['status']) : 'Active';

        if (empty($id)) {
            $sql = "INSERT INTO medicines (barcode, name, category, price, cost, stock, expiry, status) 
                    VALUES ('$barcode', '$name', '$category', '$price', '$cost', '$stock', '$expiry', '$status')";
        } else {
            $id = (int)$id;
            $sql = "UPDATE medicines SET 
                    barcode='$barcode', name='$name', category='$category', 
                    price='$price', cost='$cost', stock='$stock', 
                    expiry='$expiry', status='$status' 
                    WHERE id=$id";
        }

        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Medicine saved successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database Error: " . $conn->error]);
        }
    } 
    elseif ($action === 'delete') {
        if (!isset($data['id'])) {
            echo json_encode(["status" => "error", "message" => "No ID provided for deletion."]);
            $conn->close();
            exit();
        }
        
        $id = (int)$data['id'];
        $sql = "DELETE FROM medicines WHERE id=$id";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Medicine deleted successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database Error: " . $conn->error]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Unknown action requested."]);
    }
    
    $conn->close();
    exit();
}

echo json_encode(["status" => "error", "message" => "Invalid request method."]);
?>