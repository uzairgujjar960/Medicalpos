<?php
// db.php
$host = '127.0.0.1'; // Port ke sath '127.0.0.1' zyada stable rehta hai
$port = '3307';      // Aapki config file wala port
$dbname = 'pos_system';
$username = 'root'; 
$password = ''; // Make sure yeh wahi password hai jo phpMyAdmin mein set hai

try {
    // Port ko connection string (DSN) mein is tarah add karein:
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['status' => 'error', 'message' => "Database Connection Failed: " . $e->getMessage()]));
}
?>