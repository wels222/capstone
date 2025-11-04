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
    // determine current user's role from users table
    $role = null;
    try {
        $rstmt = $pdo->prepare('SELECT role FROM users WHERE email = ?');
        $rstmt->execute([$email]);
        $rrow = $rstmt->fetch(PDO::FETCH_ASSOC);
        if ($rrow && isset($rrow['role'])) $role = $rrow['role'];
    } catch (Exception $ee) { /* ignore */ }

    if ($id > 0) {
        // Only allow marking notification addressed to this email or this role
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND (recipient_email = ? OR (recipient_role IS NOT NULL AND recipient_role = ?))');
        $stmt->execute([$id, $email, $role]);
        echo json_encode(['success' => true]);
        exit;
    } else {
        // mark all read for this user (by email or role)
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE (recipient_email = ? OR (recipient_role IS NOT NULL AND recipient_role = ?))');
        $stmt->execute([$email, $role]);
        echo json_encode(['success' => true]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
