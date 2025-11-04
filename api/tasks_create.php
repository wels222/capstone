<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

// Verify dept head session
$byEmail = $_SESSION['email'] ?? null;
if (!$byEmail) {
	http_response_code(401);
	echo json_encode(['success' => false, 'error' => 'Not authenticated']);
	exit;
}

// Ensure tasks table exists (include 'missed' status)
$createSql = "CREATE TABLE IF NOT EXISTS tasks (
	id INT AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(255) NOT NULL,
	description TEXT,
	due_date DATETIME DEFAULT NULL,
	status ENUM('pending','in_progress','completed','missed') NOT NULL DEFAULT 'pending',
	assigned_to_email VARCHAR(100) NOT NULL,
	assigned_by_email VARCHAR(100) NOT NULL,
	attachment_path VARCHAR(255) DEFAULT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);";
try {
	$pdo->exec($createSql);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => 'Failed to ensure tasks table']);
	exit;
}

// Support multipart/form-data for file upload
$title = $_POST['title'] ?? null;
$description = $_POST['description'] ?? null;
$due_date = $_POST['due_date'] ?? null; // may be 'YYYY-MM-DDTHH:MM' or 'YYYY-MM-DD HH:MM[:SS]'

// Normalize due_date: convert 'T' separator to space and ensure seconds are present
if ($due_date) {
	// Replace HTML5 datetime-local 'T' with space
	$due_date = str_replace('T', ' ', $due_date);
	// If seconds are missing (format 'YYYY-MM-DD HH:MM'), append ':00'
	if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $due_date)) {
		$due_date .= ':00';
	}
	// Optionally, further validate by attempting to create a DateTime
	try {
		$dt = new DateTime($due_date);
		$due_date = $dt->format('Y-m-d H:i:s');
	} catch (Exception $e) {
		// If parsing fails, set to null so DB stores null
		$due_date = null;
	}
}
$assigned_to_email = $_POST['assigned_to_email'] ?? null;

if (!$title || !$assigned_to_email) {
	http_response_code(400);
	echo json_encode(['success' => false, 'error' => 'Missing required fields']);
	exit;
}

// Security: ensure the assignee is in the same department as the creator (session user)
try {
	$deptStmt = $pdo->prepare('SELECT department FROM users WHERE email = ?');
	$deptStmt->execute([$byEmail]);
	$creator = $deptStmt->fetch(PDO::FETCH_ASSOC);
	$creatorDept = $creator['department'] ?? null;
	if ($creatorDept) {
		$aStmt = $pdo->prepare('SELECT department FROM users WHERE email = ?');
		$aStmt->execute([$assigned_to_email]);
		$assignee = $aStmt->fetch(PDO::FETCH_ASSOC);
		$assigneeDept = $assignee['department'] ?? null;
		if (!$assigneeDept || $assigneeDept !== $creatorDept) {
			http_response_code(403);
			echo json_encode(['success' => false, 'error' => 'Assignee must be in your department']);
			exit;
		}
	}
} catch (PDOException $__e) {
	// If DB check fails for some reason, block the create as a precaution
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => 'Failed to validate assignee department']);
	exit;
}

// Handle optional file upload. We will not create directories automatically.
$attachment_path = null;
if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
	$tmpPath = $_FILES['attachment']['tmp_name'];
	$origName = basename($_FILES['attachment']['name']);
	$ext = pathinfo($origName, PATHINFO_EXTENSION);
	$safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
	$targetDir = __DIR__ . '/../uploads/tasks/';
	$fileName = uniqid('task_') . ($safeExt ? ('.' . $safeExt) : '');
	$dest = $targetDir . $fileName;
	// Try to move file if folder exists; if not, keep null path silently
	if (@is_dir($targetDir) && @is_writable($targetDir) && @move_uploaded_file($tmpPath, $dest)) {
		$attachment_path = 'uploads/tasks/' . $fileName;
	}
}

// Determine initial status: if due_date is in the past and not completed, mark as 'missed'
$initialStatus = 'pending';
if ($due_date) {
	try {
		$checkDt = new DateTime($due_date);
		$now = new DateTime();
		if ($checkDt < $now) {
			$initialStatus = 'missed';
		}
	} catch (Exception $e) {
		// ignore and leave as pending
	}
}

try {
	$stmt = $pdo->prepare('INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
	$stmt->execute([$title, $description, $due_date ?: null, $initialStatus, $assigned_to_email, $byEmail, $attachment_path]);
	$lastId = $pdo->lastInsertId();

	// Create an in-app notification for the assignee
	try {
			// Ensure notifications table exists (best-effort)
			$pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
				id INT AUTO_INCREMENT PRIMARY KEY,
				recipient_email VARCHAR(150),
				recipient_role VARCHAR(100),
				message TEXT NOT NULL,
				type VARCHAR(50) DEFAULT 'task',
				is_read TINYINT(1) DEFAULT 0,
				created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
			)");

			$noteMsg = 'You have been assigned a new task: ' . ($title ?: 'Untitled');
			$noteStmt = $pdo->prepare("INSERT INTO notifications (recipient_email, recipient_role, message, type) VALUES (?, ?, ?, ?)");
			$noteStmt->execute([$assigned_to_email, null, $noteMsg, 'task']);
	} catch (PDOException $ne) {
		// Non-fatal: allow task creation to succeed even if notification insert fails
	}

	echo json_encode(['success' => true, 'id' => $lastId, 'attachment_path' => $attachment_path, 'status' => $initialStatus]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => 'Database insert error']);
}

