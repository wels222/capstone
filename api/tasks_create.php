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

// Ensure tasks table exists
$createSql = "CREATE TABLE IF NOT EXISTS tasks (
	id INT AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(255) NOT NULL,
	description TEXT,
	due_date DATE,
	status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
	assigned_to_email VARCHAR(100) NOT NULL,
	assigned_by_email VARCHAR(100) NOT NULL,
	attachment_path VARCHAR(255) DEFAULT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
)";
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
$due_date = $_POST['due_date'] ?? null; // YYYY-MM-DD
$assigned_to_email = $_POST['assigned_to_email'] ?? null;

if (!$title || !$assigned_to_email) {
	http_response_code(400);
	echo json_encode(['success' => false, 'error' => 'Missing required fields']);
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

try {
	$stmt = $pdo->prepare('INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
	$stmt->execute([$title, $description, $due_date ?: null, 'pending', $assigned_to_email, $byEmail, $attachment_path]);
	echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'attachment_path' => $attachment_path]);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => 'Database insert error']);
}

