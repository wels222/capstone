<?php
// notifications.php - Employee notification list with actions
require_once __DIR__ . '/../auth_guard.php';
require_role('employee');
header('Content-Type: application/json');
require_once '../db.php';

// Get employee email from session (assume login system)
$email = isset($_SESSION['email']) ? $_SESSION['email'] : null;
if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Helper: check if a column exists in the notifications table
function columnExists($pdo, $columnName) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = ?");
    $stmt->execute([$columnName]);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    // POST actions: mark_all_read, clear_all
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
        $action = $_GET['action'];

        if ($action === 'mark_all_read') {
            // ensure there is an is_read column
            if (!columnExists($pdo, 'is_read')) {
                $pdo->exec("ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0");
            }
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_email = ?");
            $stmt->execute([$email]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'clear_all') {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE recipient_email = ?");
            $stmt->execute([$email]);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    // Default: return notifications for the user. If is_read exists, alias it to `read` for frontend.
    $hasIsRead = columnExists($pdo, 'is_read');
    if ($hasIsRead) {
        $stmt = $pdo->prepare("SELECT id, recipient_email, message, type, created_at, is_read AS `read` FROM notifications WHERE recipient_email = ? ORDER BY created_at DESC");
    } else {
        $stmt = $pdo->prepare("SELECT id, recipient_email, message, type, created_at FROM notifications WHERE recipient_email = ? ORDER BY created_at DESC");
    }
    $stmt->execute([$email]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$hasIsRead) {
        // ensure a `read` key exists for frontend (0 = unread)
        foreach ($notifications as &$n) {
            $n['read'] = 0;
        }
        unset($n);
    }

    echo json_encode(['success' => true, 'data' => $notifications]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
