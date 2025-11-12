<?php
header('Content-Type: application/json');
require_once '../db.php';
session_start();

// Get HR user email from session
$hr_email = $_SESSION['email'] ?? null;

if (!$hr_email) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

try {
    // Create table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS hr_signatures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        file_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Get HR signature
    $stmt = $pdo->prepare('SELECT file_path FROM hr_signatures WHERE email = ? LIMIT 1');
    $stmt->execute([$hr_email]);
    $signature_path = $stmt->fetchColumn();
    
    if ($signature_path) {
        echo json_encode([
            'success' => true,
            'signature_path' => $signature_path
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'signature_path' => null
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'details' => $e->getMessage()
    ]);
}
?>
