<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

$email = $_SESSION['email'] ?? null;
if (!$email) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Ensure tasks table exists (safety)
$createSql = "CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATETIME DEFAULT NULL,
    status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
    assigned_to_email VARCHAR(100) NOT NULL,
    assigned_by_email VARCHAR(100) NOT NULL,
    attachment_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
)";
try { $pdo->exec($createSql); } catch (PDOException $e) {}

$status = $_GET['status'] ?? null; // optional filter
try {
    if ($status) {
        $stmt = $pdo->prepare('SELECT id, title, description, due_date, status, assigned_to_email, assigned_by_email, attachment_path, submission_file_path, submission_note, completed_at, ack_note, ack_at, created_at FROM tasks WHERE assigned_to_email = ? AND status = ? ORDER BY due_date IS NULL, due_date ASC, id DESC');
        $stmt->execute([$email, $status]);
    } else {
        $stmt = $pdo->prepare('SELECT id, title, description, due_date, status, assigned_to_email, assigned_by_email, attachment_path, submission_file_path, submission_note, completed_at, ack_note, ack_at, created_at FROM tasks WHERE assigned_to_email = ? ORDER BY due_date IS NULL, due_date ASC, id DESC');
        $stmt->execute([$email]);
    }
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'tasks' => $tasks]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
