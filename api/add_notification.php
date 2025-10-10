<?php
header('Content-Type: application/json');
require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$recipient = isset($data['recipient_email']) ? $data['recipient_email'] : null;
$message = isset($data['message']) ? $data['message'] : null;
$type = isset($data['type']) ? $data['type'] : 'recall';

if (!$recipient || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing recipient or message']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO notifications (recipient_email, message, type) VALUES (?, ?, ?)");
    $stmt->execute([$recipient, $message, $type]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
