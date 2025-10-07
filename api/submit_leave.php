<?php
header('Content-Type: application/json');
require_once '../db.php';

// If you want to avoid storing uploaded files on disk (to save storage),
// set this to false. When false, signatures will be returned as a data URI
// in the response under 'signature_data' and will NOT be written to uploads/.
$SAVE_UPLOADS = false;

// Ensure the leave_requests table exists (safety net in case database.sql wasn't applied)
$createTableSql = "CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_email VARCHAR(100) NOT NULL,
    dept_head_email VARCHAR(100) NOT NULL,
    leave_type VARCHAR(255) NOT NULL,
    dates VARCHAR(255) NOT NULL,
    reason TEXT,
    signature_path VARCHAR(255) DEFAULT NULL,
    details TEXT DEFAULT NULL,
    status ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending',
    applied_at DATETIME NOT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);";
try {
  $pdo->exec($createTableSql);
} catch (PDOException $e) {
  // If table creation fails, return error immediately
  http_response_code(500); echo json_encode(['error' => 'Failed to ensure leave_requests table exists', 'details' => $e->getMessage()]); exit;
}

// Ensure 'details' column exists (in case the live table was created earlier without it)
try {
  $colStmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'leave_requests' AND column_name = 'details'");
  $colStmt->execute();
  $colRes = $colStmt->fetch(PDO::FETCH_ASSOC);
  if (!$colRes || intval($colRes['cnt']) === 0) {
    // add the column
    try {
      $pdo->exec("ALTER TABLE leave_requests ADD COLUMN details TEXT DEFAULT NULL");
    } catch (PDOException $ae) {
      // intentionally not logging to disk to avoid creating a logs/ folder.
      // Keep silent and allow the subsequent insert to surface any issues.
    }
  }
} catch (PDOException $e) {
  // ignore and continue; the insert will report more details if necessary
}

// Support both JSON body and multipart/form-data (for file upload)
$employee_email = $_POST['employee_email'] ?? null;
$dept_head_email = $_POST['deptHead'] ?? ($_POST['dept_head'] ?? null);
$leave_type = $_POST['leave_type'] ?? null;
$dates = $_POST['dates'] ?? null;
$reason = $_POST['reason'] ?? null;
$status = 'pending';
$applied_at = date('Y-m-d H:i:s');
$signature_path = null;
// details: JSON string (6.A/6.B selections and other structured fields)
$details_json = null;

// If JSON body was provided
if (!$employee_email) {
  $data = json_decode(file_get_contents('php://input'), true);
  if ($data) {
    $employee_email = $data['employee_email'] ?? $employee_email;
    $dept_head_email = $data['deptHead'] ?? $dept_head_email;
    $leave_type = $data['leave_type'] ?? $leave_type;
    $dates = $data['dates'] ?? $dates;
    $reason = $data['reason'] ?? $reason;
    if (isset($data['details'])) $details_json = json_encode($data['details']);
  }
}

// Handle file upload if present
// Note: we will NOT create an uploads/ folder. Instead we always read the
// uploaded file into memory and prepare a data URI as a fallback. If
// $SAVE_UPLOADS is true we will attempt to move the uploaded file, but we
// will NOT call mkdir(). If the move fails, we silently fall back to the
// in-memory data URI. This prevents automatic creation of any folders.
if (!empty($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
  $tmpPath = $_FILES['signature']['tmp_name'];
  $origName = basename($_FILES['signature']['name']);
  $ext = pathinfo($origName, PATHINFO_EXTENSION);
  $allowed = ['png','jpg','jpeg','gif'];
  if (!in_array(strtolower($ext), $allowed)) {
    http_response_code(400); echo json_encode(['error'=>'Invalid file type']); exit;
  }

  // Read the raw file into memory so we can always return a data URI
  $raw = @file_get_contents($tmpPath);
  if ($raw !== false) {
    $mime = mime_content_type($tmpPath) ?: ('image/' . strtolower($ext));
    $b64 = base64_encode($raw);
    $signature_data = 'data:' . $mime . ';base64,' . $b64;
    $signature_path = null; // default: not saved to disk

    // If configured to save uploads, attempt move WITHOUT creating directories.
    if ($SAVE_UPLOADS) {
      $targetDir = __DIR__ . '/../uploads/signatures/';
      $fileName = uniqid('sig_') . '.' . $ext;
      $dest = $targetDir . $fileName;
      // attempt move; if it fails (e.g., directory missing) we silently keep signature_data
      if (@move_uploaded_file($tmpPath, $dest)) {
        $signature_path = 'uploads/signatures/' . $fileName;
        // optionally free signature_data if you prefer not to return the inline data
        // $signature_data = null;
      }
    }
  }
}

if (isset($_POST['details'])) {
    $posted = $_POST['details'];
    if (is_string($posted)) {
        json_decode($posted);
        if (json_last_error() === JSON_ERROR_NONE) $details_json = $posted;
    }
}

if (!$employee_email || !$dept_head_email || !$leave_type) {
  http_response_code(400); echo json_encode(['error'=>'Missing required fields']); exit;
}

try {
  $stmt = $pdo->prepare("INSERT INTO leave_requests (employee_email, dept_head_email, leave_type, dates, reason, signature_path, details, status, applied_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->execute([$employee_email, $dept_head_email, $leave_type, $dates, $reason, $signature_path, $details_json, $status, $applied_at]);
  $insertId = $pdo->lastInsertId();
  $res = ['success'=>true, 'id' => $insertId, 'signature_path' => $signature_path, 'details' => $details_json, 'applied_at' => $applied_at];
  if (!empty($signature_data)) $res['signature_data'] = $signature_data;
  echo json_encode($res);
} catch (PDOException $e) {
  http_response_code(500); echo json_encode(['error'=>'DB insert failed', 'details' => $e->getMessage()]);
}