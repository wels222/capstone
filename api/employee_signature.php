<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once '../db.php';

session_start();
$email = null;
if (!empty($_SESSION['email'])) {
  $email = $_SESSION['email'];
} else {
  // try current_user endpoint style
  if (!empty($_SESSION['user_email'])) $email = $_SESSION['user_email'];
}

if (!$email) { echo json_encode(['success'=>true, 'hasSignature'=>false]); exit; }

try {
  // Prefer employee_signatures table
  $stmt = $pdo->prepare('SELECT file_path FROM employee_signatures WHERE employee_email = ?');
  $stmt->execute([$email]);
  $path = $stmt->fetchColumn();
  if ($path) {
    $url = '../' . ltrim($path, '/');
    echo json_encode(['success'=>true, 'hasSignature'=>true, 'url'=>$url]);
    exit;
  }
} catch (PDOException $e) {}

try {
  // Fallback to most recent leave_requests signature_path
  $stmt = $pdo->prepare('SELECT signature_path FROM leave_requests WHERE employee_email = ? AND signature_path IS NOT NULL ORDER BY applied_at DESC, id DESC LIMIT 1');
  $stmt->execute([$email]);
  $path = $stmt->fetchColumn();
  if ($path) {
    $url = '../' . ltrim($path, '/');
    echo json_encode(['success'=>true, 'hasSignature'=>true, 'url'=>$url]);
    exit;
  }
} catch (PDOException $e) {}

echo json_encode(['success'=>true, 'hasSignature'=>false]);
