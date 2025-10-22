<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

// Get current user (department head)
$byEmail = $_SESSION['email'] ?? null;
if (!$byEmail) {
	echo json_encode(['success' => false, 'error' => 'Not authenticated']);
	exit;
}

// Ensure tasks table exists (safety net). Use DATETIME for due_date so times are preserved.
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
try {
	$pdo->exec($createSql);
	// Ensure optional submission columns exist
	try {
		$colStmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tasks'");
		$colStmt->execute();
		$cols = array_map(function($r){ return $r['column_name']; }, $colStmt->fetchAll(PDO::FETCH_ASSOC));
		if (!in_array('submission_file_path', $cols)) {
			$pdo->exec("ALTER TABLE tasks ADD COLUMN submission_file_path VARCHAR(255) DEFAULT NULL");
		}
		if (!in_array('submission_note', $cols)) {
			$pdo->exec("ALTER TABLE tasks ADD COLUMN submission_note TEXT DEFAULT NULL");
		}
		if (!in_array('completed_at', $cols)) {
			$pdo->exec("ALTER TABLE tasks ADD COLUMN completed_at DATETIME DEFAULT NULL");
		}
	} catch (PDOException $ie) { /* ignore to not block listing */ }
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => 'Failed to ensure tasks table']);
	exit;
}

// Optional filters
$status = $_GET['status'] ?? null; // pending|in_progress|completed

try {
	// If due_date column still exists as DATE in older tables, alter it to DATETIME so times are returned properly.
	try {
		$colCheck = $pdo->prepare("SELECT DATA_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tasks' AND column_name = 'due_date'");
		$colCheck->execute();
		$row = $colCheck->fetch(PDO::FETCH_ASSOC);
		if ($row && isset($row['DATA_TYPE']) && strtolower($row['DATA_TYPE']) === 'date') {
			// alter to DATETIME (keeps existing date values; time will be 00:00:00)
			$pdo->exec("ALTER TABLE tasks MODIFY COLUMN due_date DATETIME DEFAULT NULL");
		}
	} catch (PDOException $__e) {
		// ignore migration errors
	}

	if ($status) {
		$stmt = $pdo->prepare('SELECT id, title, description, due_date, status, assigned_to_email, assigned_by_email, attachment_path, submission_file_path, submission_note, completed_at, created_at FROM tasks WHERE assigned_by_email = ? AND status = ? ORDER BY due_date IS NULL, due_date ASC, id DESC');
		$stmt->execute([$byEmail, $status]);
	} else {
		$stmt = $pdo->prepare('SELECT id, title, description, due_date, status, assigned_to_email, assigned_by_email, attachment_path, submission_file_path, submission_note, completed_at, created_at FROM tasks WHERE assigned_by_email = ? ORDER BY due_date IS NULL, due_date ASC, id DESC');
		$stmt->execute([$byEmail]);
	}
	$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['success' => true, 'tasks' => $tasks]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => 'Database error']);
}

