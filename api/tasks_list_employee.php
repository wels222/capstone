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
    // Best-effort migrations: ensure due_date is DATETIME and status enum includes 'missed'
    try {
        $colCheck = $pdo->prepare("SELECT DATA_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tasks' AND column_name = 'due_date'");
        $colCheck->execute();
        $row = $colCheck->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['DATA_TYPE']) && strtolower($row['DATA_TYPE']) === 'date') {
            $pdo->exec("ALTER TABLE tasks MODIFY COLUMN due_date DATETIME DEFAULT NULL");
        }
    } catch (PDOException $__e) { }
    try { $pdo->exec("ALTER TABLE tasks MODIFY COLUMN status ENUM('pending','in_progress','completed','missed') NOT NULL DEFAULT 'pending'"); } catch (PDOException $__e) {}

    // Ensure archive columns exist
    try {
        $cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tasks'")->fetchAll(PDO::FETCH_COLUMN);
        $lower = array_map('strtolower', $cols ?: []);
        if (!in_array('is_archived', $lower)) { $pdo->exec("ALTER TABLE tasks ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0"); }
        if (!in_array('archived_at', $lower)) { $pdo->exec("ALTER TABLE tasks ADD COLUMN archived_at DATETIME NULL DEFAULT NULL"); }
    } catch (Throwable $__e) { }

    if ($status) {
        $stmt = $pdo->prepare('SELECT id, title, description, due_date, status, assigned_to_email, assigned_by_email, attachment_path, submission_file_path, submission_note, completed_at, is_archived, archived_at, created_at FROM tasks WHERE assigned_to_email = ? AND status = ? AND (is_archived = 0 OR is_archived IS NULL) ORDER BY due_date IS NULL, due_date ASC, id DESC');
        $stmt->execute([$email, $status]);
    } else {
        $stmt = $pdo->prepare('SELECT id, title, description, due_date, status, assigned_to_email, assigned_by_email, attachment_path, submission_file_path, submission_note, completed_at, is_archived, archived_at, created_at FROM tasks WHERE assigned_to_email = ? AND (is_archived = 0 OR is_archived IS NULL) ORDER BY due_date IS NULL, due_date ASC, id DESC');
        $stmt->execute([$email]);
    }
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'tasks' => $tasks]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
