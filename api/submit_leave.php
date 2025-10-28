<?php
header('Content-Type: application/json');
require_once '../db.php';

// Persist signatures to disk so they can be reused across submissions
$SAVE_UPLOADS = true; // kept for compatibility; we now always keep ONE file per employee

// Ensure the leave_requests table exists (safety net in case database.sql wasn't applied)
$createTableSql = "CREATE TABLE IF NOT EXISTS leave_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_email VARCHAR(100) NOT NULL,
  dept_head_email VARCHAR(100) NOT NULL,
  leave_type VARCHAR(255) NOT NULL,
  dates VARCHAR(255) NOT NULL,
  reason TEXT,
  signature_path VARCHAR(255) DEFAULT NULL,
  details LONGTEXT DEFAULT NULL,
  request_token VARCHAR(100) NULL,
  status ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending',
  applied_at DATETIME NOT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_request_token (request_token)
);";
try {
  $pdo->exec($createTableSql);
} catch (PDOException $e) {
  // If table creation fails, return error immediately
  http_response_code(500); echo json_encode(['error' => 'Failed to ensure leave_requests table exists', 'details' => $e->getMessage()]); exit;
}

// Ensure 'details' LONGTEXT and request_token columns exist and unique key
try {
  $pdo->exec("ALTER TABLE leave_requests MODIFY COLUMN details LONGTEXT");
  // request_token column
  $colStmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'leave_requests' AND column_name = 'request_token'");
  $colStmt->execute();
  $colRes = $colStmt->fetch(PDO::FETCH_ASSOC);
  if (!$colRes || intval($colRes['cnt']) === 0) {
    try { $pdo->exec("ALTER TABLE leave_requests ADD COLUMN request_token VARCHAR(100) NULL"); } catch(PDOException $e) { }
  }
  // unique key
  try { $pdo->exec("ALTER TABLE leave_requests ADD UNIQUE KEY uq_request_token (request_token)"); } catch(PDOException $e) { }
} catch (PDOException $e) {
  // ignore and continue; the insert will report more details if necessary
}

// Ensure employee_signatures table exists
try {
  $pdo->exec("CREATE TABLE IF NOT EXISTS employee_signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_email VARCHAR(100) NOT NULL UNIQUE,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) { }

// Support both JSON body and multipart/form-data (for file upload)
$employee_email = $_POST['employee_email'] ?? null;
$dept_head_email = $_POST['dept_head_email'] ?? ($_POST['deptHead'] ?? ($_POST['dept_head'] ?? null));
$leave_type = $_POST['leave_type'] ?? null;
$dates = $_POST['dates'] ?? null;
$reason = $_POST['reason'] ?? null;
$status = 'pending';
$applied_at = date('Y-m-d H:i:s');
$signature_path = null;
// details: JSON string (6.A/6.B selections and other structured fields)
$details_json = null;
$request_token = $_POST['request_token'] ?? null;
$signature_data_uri = null;

// If JSON body was provided
if (!$employee_email) {
  $data = json_decode(file_get_contents('php://input'), true);
  if ($data) {
    $employee_email = $data['employee_email'] ?? $employee_email;
    $dept_head_email = $data['dept_head_email'] ?? ($data['deptHead'] ?? ($data['dept_head'] ?? $dept_head_email));
    $leave_type = $data['leave_type'] ?? $leave_type;
    $dates = $data['dates'] ?? $dates;
    $reason = $data['reason'] ?? $reason;
    if (isset($data['details'])) $details_json = json_encode($data['details']);
    $request_token = $data['request_token'] ?? $request_token;
    $signature_data_uri = $data['signature_data_uri'] ?? null;
  }
}

// Capture any uploaded signature or data URI into memory; we will later store
// a SINGLE deterministic file per employee and overwrite it if needed.
$uploaded_bin = null; $uploaded_ext = null;
if (!empty($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
  $tmpPath = $_FILES['signature']['tmp_name'];
  $origName = basename($_FILES['signature']['name']);
  $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
  $allowed = ['png','jpg','jpeg','gif','webp'];
  if (!in_array($ext, $allowed)) { http_response_code(400); echo json_encode(['error'=>'Invalid file type']); exit; }
  $raw = @file_get_contents($tmpPath);
  if ($raw !== false) { $uploaded_bin = $raw; $uploaded_ext = $ext === 'jpeg' ? 'jpg' : $ext; }
}

if (isset($_POST['details'])) {
    $posted = $_POST['details'];
    if (is_string($posted)) {
        json_decode($posted);
        if (json_last_error() === JSON_ERROR_NONE) $details_json = $posted;
    }
}

// Normalize leave_type: accept array or CSV
if (is_array($leave_type)) { $leave_type = implode(', ', $leave_type); }

if (!$employee_email || !$dept_head_email || !$leave_type) {
  http_response_code(400); echo json_encode(['error'=>'Missing required fields']); exit;
}

try {
  // Reuse existing employee signature if present
  $existingSig = null;
  try {
    $st = $pdo->prepare('SELECT file_path FROM employee_signatures WHERE employee_email = ?');
    $st->execute([$employee_email]);
    $existingSig = $st->fetchColumn();
  } catch (PDOException $e) {}

  // If signature data URI is provided, decode into uploaded_bin
  if (!$uploaded_bin && !empty($signature_data_uri) && strpos($signature_data_uri, 'data:image/') === 0) {
    $parts = explode(',', $signature_data_uri, 2);
    if (count($parts) === 2) {
      $meta = $parts[0]; $b64 = $parts[1];
      $ext = 'png';
      if (preg_match('#data:image/(\w+);base64#i', $meta, $m)) { $ext = strtolower($m[1]); }
      $bin = base64_decode($b64);
      if ($bin !== false) { $uploaded_bin = $bin; $uploaded_ext = $ext === 'jpeg' ? 'jpg' : $ext; }
    }
  }

  // Determine deterministic signature path (one file per employee)
  $emailKey = strtolower(trim($employee_email));
  $hash = sha1($emailKey);
  $final_ext = $uploaded_ext ?: (pathinfo((string)$existingSig, PATHINFO_EXTENSION) ?: 'png');
  $fileName = 'sig_' . substr($hash, 0, 24) . '.' . $final_ext;
  $relPath = 'uploads/signatures/' . $fileName;
  $absPath = __DIR__ . '/../' . $relPath;

  if ($uploaded_bin) {
    // Ensure folders exist
    if (!is_dir(dirname($absPath))) { @mkdir(dirname($absPath), 0777, true); }
    if (@file_put_contents($absPath, $uploaded_bin) !== false) {
      $signature_path = $relPath;
      // Update mapping table
      try {
        $ins = $pdo->prepare('INSERT INTO employee_signatures (employee_email, file_path) VALUES (?, ?) ON DUPLICATE KEY UPDATE file_path = VALUES(file_path)');
        $ins->execute([$employee_email, $signature_path]);
      } catch (PDOException $e) { }
      // Optionally remove old file if different
      if ($existingSig && $existingSig !== $signature_path) {
        $oldAbs = __DIR__ . '/../' . ltrim($existingSig, '/');
        if (strpos(realpath($oldAbs) ?: '', realpath(__DIR__ . '/../uploads/signatures')) === 0) { @unlink($oldAbs); }
      }
    }
  } else {
    // No new signature provided; reuse existing if any
    if ($existingSig) { $signature_path = $existingSig; }
  }

  // Idempotent insert
  $stmt = $pdo->prepare("INSERT INTO leave_requests (employee_email, dept_head_email, leave_type, dates, reason, signature_path, details, request_token, status, applied_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->execute([$employee_email, $dept_head_email, $leave_type, $dates, $reason, $signature_path, $details_json, $request_token, $status, $applied_at]);
  $insertId = $pdo->lastInsertId();
  echo json_encode(['success'=>true, 'id'=>$insertId, 'signature_path'=>$signature_path, 'applied_at'=>$applied_at]);
} catch (PDOException $e) {
  // On duplicate request_token, fetch and return existing
  if (strpos($e->getMessage(), 'uq_request_token') !== false || strpos($e->getMessage(), 'Duplicate entry') !== false) {
    try {
      $sel = $pdo->prepare('SELECT id, applied_at, signature_path FROM leave_requests WHERE request_token = ?');
      $sel->execute([$request_token]);
      $row = $sel->fetch(PDO::FETCH_ASSOC);
      if ($row) { echo json_encode(['success'=>true, 'id'=>$row['id'], 'signature_path'=>$row['signature_path'], 'applied_at'=>$row['applied_at']]); return; }
    } catch (PDOException $e2) {}
  }
  http_response_code(500); echo json_encode(['error'=>'DB insert failed', 'details' => $e->getMessage()]);
}