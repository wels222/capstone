<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$recipient = isset($data['recipient_email']) ? $data['recipient_email'] : null;
// optional: notify by role (e.g. 'HR', 'Dept Head', 'Employee')
$recipient_role = isset($data['recipient_role']) ? $data['recipient_role'] : null;
$message = isset($data['message']) ? $data['message'] : null;
$type = isset($data['type']) ? $data['type'] : 'recall';

if ((!$recipient && !$recipient_role) || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing recipient (email or role) or message']);
    exit;
}

try {
    // Insert both recipient_email and recipient_role (one of them may be null)
    $stmt = $pdo->prepare("INSERT INTO notifications (recipient_email, recipient_role, message, type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$recipient, $recipient_role, $message, $type]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
