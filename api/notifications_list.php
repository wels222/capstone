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
        recipient_email VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'task',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) { /* ignore */ }

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
try {
    $stmt = $pdo->prepare('SELECT id, message, type, is_read, created_at FROM notifications WHERE recipient_email = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->execute([$email, $limit]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $countStmt = $pdo->prepare('SELECT COUNT(*) as unread FROM notifications WHERE recipient_email = ? AND is_read = 0');
    $countStmt->execute([$email]);
    $un = $countStmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'notifications' => $notes, 'unread' => (int)($un['unread'] ?? 0)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
