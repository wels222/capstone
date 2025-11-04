<?php
header('Content-Type: application/json');
require_once '../db.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo json_encode(['error'=>'No data']); exit; }
$title = $data['title'] ?? '';
$date = $data['date'] ?? '';
$time = $data['time'] ?? '';
$location = $data['location'] ?? '';
$description = $data['description'] ?? '';
if (!$title || !$date || !$location || !$description) { http_response_code(400); echo json_encode(['error'=>'Missing required fields']); exit; }
try {
    $stmt = $pdo->prepare("INSERT INTO events (title, date, time, location, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $date, $time, $location, $description]);
    // After event creation, create notifications for all users (best-effort)
    try {
        // Ensure notifications table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient_email VARCHAR(150),
            recipient_role VARCHAR(100),
            message TEXT NOT NULL,
            type VARCHAR(50) DEFAULT 'event',
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $noteMsg = sprintf('New event: %s on %s at %s', $title, $date, $location);
        // Fetch all user emails
        $ustmt = $pdo->query('SELECT email FROM users WHERE email IS NOT NULL');
        $users = $ustmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($users)) {
            $ins = $pdo->prepare('INSERT INTO notifications (recipient_email, recipient_role, message, type) VALUES (?, ?, ?, ?)');
            foreach ($users as $email) {
                try { $ins->execute([$email, null, $noteMsg, 'event']); } catch (PDOException $e) { /* non-fatal */ }
            }
        }
    } catch (PDOException $ne) {
        // ignore notification failures
    }

    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['error'=>'DB insert failed']);
}