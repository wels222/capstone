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

// Ensure notifications table exists (best-effort)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient_email VARCHAR(150),
        recipient_role VARCHAR(100),
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'task',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) { /* ignore */ }

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
try {
    // determine current user's role from users table
    $role = null;
    try {
        $rstmt = $pdo->prepare('SELECT role FROM users WHERE email = ?');
        $rstmt->execute([$email]);
        $rrow = $rstmt->fetch(PDO::FETCH_ASSOC);
        if ($rrow && isset($rrow['role'])) $role = $rrow['role'];
    } catch (Exception $ee) { /* ignore */ }

    // Fetch notifications addressed to this email OR to this role
    $stmt = $pdo->prepare('SELECT id, recipient_email, recipient_role, message, type, is_read, created_at FROM notifications WHERE (recipient_email = ? OR (recipient_role IS NOT NULL AND recipient_role = ?)) ORDER BY created_at DESC LIMIT ?');
    $stmt->execute([$email, $role, $limit]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $pdo->prepare('SELECT COUNT(*) as unread FROM notifications WHERE (recipient_email = ? OR (recipient_role IS NOT NULL AND recipient_role = ?)) AND is_read = 0');
    $countStmt->execute([$email, $role]);
    $un = $countStmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'notifications' => $notes, 'unread' => (int)($un['unread'] ?? 0)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
