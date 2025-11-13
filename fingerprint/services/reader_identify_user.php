<?php
header("Content-Type: application/json");

// ---------------------------
// Database credentials
// ---------------------------
$host = 'localhost';
$db   = 'capstone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    // ---------------------------
    // Connect to database
    // ---------------------------
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => "Database connection failed: " . $e->getMessage()
    ]);
    exit;
}

// ---------------------------
// Get fingerprint ID from request
// ---------------------------
$fingerprintId = $_GET['id'] ?? null;

if ($fingerprintId === null || !is_numeric($fingerprintId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing or invalid fingerprint ID'
    ]);
    exit;
}

// ---------------------------
// Query the employee_id from fingerprint table
// ---------------------------
try {
    $stmt = $pdo->prepare("SELECT employee_id FROM fingerprints WHERE id = ?");
    $stmt->execute([$fingerprintId]);
    $result = $stmt->fetch();

    if ($result) {
        echo json_encode([
            'success' => true,
            'fingerprint_id' => (int)$fingerprintId,
            'employee_id' => $result['employee_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Fingerprint ID not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => "Query failed: " . $e->getMessage()
    ]);
}
