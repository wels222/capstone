<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

// Current session info (optional). We allow public access so tasks remain visible
// even when there is no logged-in user. Session values may be empty.
$byEmail = $_SESSION['email'] ?? null;
$roleNorm = strtolower($_SESSION['role'] ?? $_SESSION['position'] ?? '');

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
		// ensure adjustment_note column exists (used by dept head to add notes)
		if (!in_array('adjustment_note', $cols)) {
			try { $pdo->exec("ALTER TABLE tasks ADD COLUMN adjustment_note TEXT DEFAULT NULL"); } catch (PDOException $__e) { /* ignore */ }
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

		// Try to ensure status enum includes 'missed' (best-effort; ignore errors)
		try {
			$pdo->exec("ALTER TABLE tasks MODIFY COLUMN status ENUM('pending','in_progress','completed','missed') NOT NULL DEFAULT 'pending'");
		} catch (PDOException $__e) { /* ignore */ }

	// Return all tasks regardless of who is logged in. Keep status filter if provided.
	if ($status) {
		$stmt = $pdo->prepare('SELECT id, title, description, due_date, status, assigned_to_email, assigned_by_email, attachment_path, submission_file_path, submission_note, adjustment_note, completed_at, created_at FROM tasks WHERE status = ? ORDER BY due_date IS NULL, due_date ASC, id DESC');
		$stmt->execute([$status]);
	} else {
		$stmt = $pdo->prepare('SELECT id, title, description, due_date, status, assigned_to_email, assigned_by_email, attachment_path, submission_file_path, submission_note, adjustment_note, completed_at, created_at FROM tasks ORDER BY due_date IS NULL, due_date ASC, id DESC');
		$stmt->execute();
	}
	$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['success' => true, 'tasks' => $tasks]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => 'Database error']);
}

