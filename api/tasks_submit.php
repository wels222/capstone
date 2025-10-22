<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

$email = $_SESSION['email'] ?? null; // employee email
if (!$email) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Ensure tasks table and new columns exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
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
    )");
    // Add submission columns if missing
    $checkCols = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tasks'");
    $checkCols->execute();
    $cols = array_map(fn($r) => $r['column_name'], $checkCols->fetchAll(PDO::FETCH_ASSOC));
    if (!in_array('submission_file_path', $cols)) {
        $pdo->exec("ALTER TABLE tasks ADD COLUMN submission_file_path VARCHAR(255) DEFAULT NULL");
    }
    if (!in_array('submission_note', $cols)) {
        $pdo->exec("ALTER TABLE tasks ADD COLUMN submission_note TEXT DEFAULT NULL");
    }
    if (!in_array('completed_at', $cols)) {
        $pdo->exec("ALTER TABLE tasks ADD COLUMN completed_at DATETIME DEFAULT NULL");
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare tasks table']);
    exit;
}

$taskId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$note = trim($_POST['note'] ?? '');
if ($taskId <= 0 || $note === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Task and description are required']);
    exit;
}

// Validate ownership
try {
    $s = $pdo->prepare('SELECT id FROM tasks WHERE id = ? AND assigned_to_email = ?');
    $s->execute([$taskId, $email]);
    if (!$s->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Task not found or not assigned to you']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

// Require a file upload
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File is required']);
    exit;
}

$submission_path = null;
$tmpPath = $_FILES['file']['tmp_name'];
$origName = basename($_FILES['file']['name']);
$ext = pathinfo($origName, PATHINFO_EXTENSION);
$safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
$targetDir = __DIR__ . '/../uploads/task_submissions/';
$fileName = uniqid('submission_') . ($safeExt ? ('.' . $safeExt) : '');
$dest = $targetDir . $fileName;

// Move if directory exists and writable
if (@is_dir($targetDir) && @is_writable($targetDir) && @move_uploaded_file($tmpPath, $dest)) {
    $submission_path = 'uploads/task_submissions/' . $fileName;
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Upload directory not available']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE tasks SET status = "completed", submission_file_path = ?, submission_note = ?, completed_at = NOW() WHERE id = ? AND assigned_to_email = ?');
    $stmt->execute([$submission_path, $note, $taskId, $email]);
    echo json_encode(['success' => true, 'file' => $submission_path]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update task']);
}
