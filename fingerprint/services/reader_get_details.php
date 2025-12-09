<?php
header("Content-Type: application/json");

// Database credentials
$host = '127.0.0.1';
$db   = 'capstone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Check if employee_id is provided
if (!isset($_GET['employee_id']) || empty($_GET['employee_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing employee_id parameter'
    ]);
    exit;
}

$employee_id = $_GET['employee_id'];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE employee_id = :employee_id LIMIT 1");
    $stmt->execute(['employee_id' => $employee_id]);

    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        echo json_encode([
            'success' => true,
            'data' => $userData
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No user found with this employee_id'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
