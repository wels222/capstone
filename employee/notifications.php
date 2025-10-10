<?php
// notifications.php - Employee notification list
session_start();
require_once '../db.php';

// Get employee email from session (assume login system)
$email = isset($_SESSION['email']) ? $_SESSION['email'] : null;
if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE recipient_email = ? ORDER BY created_at DESC");
    $stmt->execute([$email]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $notifications]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
