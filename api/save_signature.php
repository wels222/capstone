<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'department_head', 'employee']);
require_once __DIR__ . '/../db.php';

// Determine employee email from session when possible
$employee_email = $_SESSION['email'] ?? null;
// Allow specifying via POST body for admin/HR with proper session
$input = json_decode(file_get_contents('php://input'), true) ?: [];
if (!$employee_email && !empty($input['employee_email'])) $employee_email = $input['employee_email'];

$signature_data_uri = $input['signature_data_uri'] ?? null;
if (!$signature_data_uri) { http_response_code(400); echo json_encode(['success'=>false, 'error'=>'No signature_data_uri provided']); exit; }

// accept only image data URIs
if (strpos($signature_data_uri, 'data:image/') !== 0) { http_response_code(400); echo json_encode(['success'=>false, 'error'=>'signature_data_uri must be a data:image/* URI']); exit; }

// decode
$parts = explode(',', $signature_data_uri, 2);
if (count($parts) !== 2) { http_response_code(400); echo json_encode(['success'=>false, 'error'=>'Invalid data URI']); exit; }
$meta = $parts[0]; $b64 = $parts[1];
$ext = 'png';
if (preg_match('#data:image/(\w+);base64#i', $meta, $m)) { $ext = strtolower($m[1]); }
$bin = base64_decode($b64);
if ($bin === false) { http_response_code(400); echo json_encode(['success'=>false, 'error'=>'Base64 decode failed']); exit; }

// normalize ext
$ext = $ext === 'jpeg' ? 'jpg' : $ext;
$allowed = ['png','jpg','jpeg','gif','webp'];
if (!in_array($ext, $allowed)) { http_response_code(400); echo json_encode(['success'=>false, 'error'=>'Invalid image type']); exit; }

if (!$employee_email) { http_response_code(400); echo json_encode(['success'=>false, 'error'=>'No employee email in session. Please log in.']); exit; }

try {
  // ensure table exists
  $pdo->exec("CREATE TABLE IF NOT EXISTS employee_signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_email VARCHAR(100) NOT NULL UNIQUE,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) { /* non-blocking */ }

// Check if there's an existing signature for this user and delete the old file
$oldFilePath = null;
try {
  $stmt = $pdo->prepare('SELECT file_path FROM employee_signatures WHERE employee_email = ? LIMIT 1');
  $stmt->execute([$employee_email]);
  $oldFilePath = $stmt->fetchColumn();
  
  if ($oldFilePath) {
    // Delete the old signature file
    $oldAbsPath = __DIR__ . '/../' . $oldFilePath;
    if (file_exists($oldAbsPath)) {
      @unlink($oldAbsPath);
    }
  }
} catch (PDOException $e) { /* non-blocking */ }

// compute deterministic filename per employee
$emailKey = strtolower(trim($employee_email));
$hash = sha1($emailKey);
$fileName = 'sig_' . substr($hash,0,24) . '.' . $ext;
$relPath = 'uploads/signatures/' . $fileName;
$absPath = __DIR__ . '/../' . $relPath;

// write file
if (!is_dir(dirname($absPath))) { @mkdir(dirname($absPath), 0777, true); }
$ok = @file_put_contents($absPath, $bin);
if ($ok === false) { http_response_code(500); echo json_encode(['success'=>false,'error'=>'Failed to write file']); exit; }

// update DB mapping
try {
  $ins = $pdo->prepare('INSERT INTO employee_signatures (employee_email, file_path) VALUES (?, ?) ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), updated_at = NOW()');
  $ins->execute([$employee_email, $relPath]);
} catch (PDOException $e) {
  // non-fatal for file save
}

$url = '../' . ltrim($relPath, '/');
echo json_encode(['success'=>true, 'file_path'=>$relPath, 'url'=>$url, 'old_file_deleted'=>($oldFilePath ? true : false)]);

