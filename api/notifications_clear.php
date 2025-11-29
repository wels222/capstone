<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

$email = $_SESSION['email'] ?? null;
if (!$email) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// determine current user's role from users table
$role = null;
try {
    $rstmt = $pdo->prepare('SELECT role FROM users WHERE email = ?');
    $rstmt->execute([$email]);
    $rrow = $rstmt->fetch(PDO::FETCH_ASSOC);
    if ($rrow && isset($rrow['role'])) $role = $rrow['role'];
} catch (Exception $ee) { /* ignore */ }

try {
    // Only delete read notifications that belong to this user (by email) or to this role
    $stmt = $pdo->prepare('DELETE FROM notifications WHERE is_read = 1 AND (recipient_email = ? OR (recipient_role IS NOT NULL AND recipient_role = ?))');
    $stmt->execute([$email, $role]);
    echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

