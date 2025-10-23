<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

$email = $_SESSION['email'] ?? null;
if (!$email) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$id = isset($data['id']) ? (int)$data['id'] : 0;
try {
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND recipient_email = ?');
        $stmt->execute([$id, $email]);
        echo json_encode(['success' => true]);
        exit;
    } else {
        // mark all read
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE recipient_email = ?');
        $stmt->execute([$email]);
        echo json_encode(['success' => true]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
