<?php
header('Content-Type: application/json');
require_once '../db.php';

// Returns { success:true, hasSignature:bool, url: string|null }
$email = $_GET['email'] ?? null;
if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Missing email']);
    exit;
}

try {
    // Prefer employee_signatures table
    $stmt = $pdo->prepare('SELECT file_path FROM employee_signatures WHERE employee_email = ? LIMIT 1');
    $stmt->execute([$email]);
    $path = $stmt->fetchColumn();
    if ($path) {
        $url = '../' . ltrim($path, '/');
        echo json_encode(['success' => true, 'hasSignature' => true, 'url' => $url]);
        exit;
    }
} catch (PDOException $e) {
    // non-fatal, continue to fallback
}

try {
    // Fallback to most recent leave_requests signature_path for that email
    $stmt = $pdo->prepare('SELECT signature_path FROM leave_requests WHERE employee_email = ? AND signature_path IS NOT NULL ORDER BY applied_at DESC, id DESC LIMIT 1');
    $stmt->execute([$email]);
    $path = $stmt->fetchColumn();
    if ($path) {
        $url = '../' . ltrim($path, '/');
        echo json_encode(['success' => true, 'hasSignature' => true, 'url' => $url]);
        exit;
    }
} catch (PDOException $e) {
    // ignore
}

// No signature found
echo json_encode(['success' => true, 'hasSignature' => false, 'url' => null]);
