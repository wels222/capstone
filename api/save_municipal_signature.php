<?php
require_once __DIR__ . '/_bootstrap.php';
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
} catch (PDOException $e) {}

// Get signature data URI from request
$data = json_decode(file_get_contents('php://input'), true);
$signature_data_uri = $data['signature_data_uri'] ?? null;

if (!$signature_data_uri || strpos($signature_data_uri, 'data:image/') !== 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid signature data']);
    exit;
}

try {
    // Delete old signature file if exists
    $oldStmt = $pdo->prepare('SELECT file_path FROM municipal_signatures WHERE email = ?');
    $oldStmt->execute([$municipal_email]);
    $oldPath = $oldStmt->fetchColumn();
    
    if ($oldPath) {
        $oldAbsPath = __DIR__ . '/../' . ltrim($oldPath, '/');
        if (file_exists($oldAbsPath) && strpos(realpath($oldAbsPath) ?: '', realpath(__DIR__ . '/../uploads/signatures')) === 0) {
            @unlink($oldAbsPath);
        }
    }

    // Save new signature
    $parts = explode(',', $signature_data_uri, 2);
    if (count($parts) !== 2) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid data URI format']);
        exit;
    }

    $meta = $parts[0];
    $b64 = $parts[1];
    $ext = 'png';
    
    if (preg_match('#data:image/(\w+);base64#i', $meta, $m)) {
        $ext = strtolower($m[1]);
    }
    
    $bin = base64_decode($b64);
    if ($bin === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Failed to decode signature data']);
        exit;
    }

    $ext = $ext === 'jpeg' ? 'jpg' : $ext;
    $allowed = ['png','jpg','jpeg','gif','webp'];
    
    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit;
    }

    // Deterministic signature path
    $hash = sha1($municipal_email);
    $fileName = 'municipal_sig_' . substr($hash, 0, 24) . '.' . $ext;
    $relPath = 'uploads/signatures/' . $fileName;
    $absPath = __DIR__ . '/../' . $relPath;
    
    if (!is_dir(dirname($absPath))) {
        @mkdir(dirname($absPath), 0777, true);
    }
    
    if (@file_put_contents($absPath, $bin) === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save signature file']);
        exit;
    }

    // Save to database
    $stmt = $pdo->prepare('INSERT INTO municipal_signatures (email, file_path) VALUES (?, ?) ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), updated_at = CURRENT_TIMESTAMP');
    $stmt->execute([$municipal_email, $relPath]);

    echo json_encode([
        'success' => true,
        'signature_path' => $relPath,
        'old_file_deleted' => !empty($oldPath)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error', 'details' => $e->getMessage()]);
}
?>
