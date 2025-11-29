<?php
require_once __DIR__ . '/_bootstrap.php';
// Secure download for task attachments; requires employee to have acknowledged (note submitted)
session_start();
require_once __DIR__ . '/../db.php';

$email = $_SESSION['email'] ?? null;
if (!$email) { http_response_code(401); echo 'Not authenticated'; exit; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo 'Invalid task id'; exit; }

try {
    $stmt = $pdo->prepare('SELECT attachment_path, submission_note FROM tasks WHERE id = ? AND assigned_to_email = ?');
    $stmt->execute([$id, $email]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) { http_response_code(404); echo 'Task not found'; exit; }
    if (empty($task['attachment_path'])) { http_response_code(404); echo 'No attachment'; exit; }
    if (empty($task['submission_note'])) { http_response_code(403); echo 'Please add a description before downloading.'; exit; }

    $relPath = $task['attachment_path'];
    $base = realpath(__DIR__ . '/..');
    $file = realpath($base . DIRECTORY_SEPARATOR . $relPath);
    if (!$file || strpos($file, $base) !== 0 || !is_file($file)) { http_response_code(404); echo 'File not found'; exit; }

    // Stream the file
    $filename = basename($file);
    $mime = function_exists('mime_content_type') ? mime_content_type($file) : 'application/octet-stream';
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    readfile($file);
    exit;
} catch (PDOException $e) {
    http_response_code(500); echo 'Server error';
}
