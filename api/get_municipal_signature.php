<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';

// Check if municipal admin is logged in
if (!isset($_SESSION['municipal_logged_in']) || $_SESSION['municipal_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$municipal_email = 'municipaladmin@gmail.com';

// Ensure municipal_signatures table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS municipal_signatures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        file_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // Table might already exist, continue
}

try {
    $stmt = $pdo->prepare('SELECT file_path FROM municipal_signatures WHERE email = ?');
    $stmt->execute([$municipal_email]);
    $signature = $stmt->fetchColumn();
    
    if ($signature) {
        // Check if file exists
        $absPath = __DIR__ . '/../' . ltrim($signature, '/');
        if (file_exists($absPath)) {
            echo json_encode(['success' => true, 'signature_path' => $signature]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No signature found']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No signature found']);
    }
} catch (PDOException $e) {
    // If table doesn't exist or any error, return no signature found (not critical)
    echo json_encode(['success' => false, 'error' => 'No signature found']);
}
?>
