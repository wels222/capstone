<?php
header('Content-Type: application/json');
require_once '../db.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo json_encode(['error'=>'No data']); exit; }
// Required fields: employee_email, dept_head_email, leave_type, dates, reason, etc.
$employee_email = $data['employee_email'] ?? '';
$dept_head_email = $data['deptHead'] ?? '';
$leave_type = $data['leave_type'] ?? '';
$dates = $data['dates'] ?? '';
$reason = $data['reason'] ?? '';
$status = 'pending';
$applied_at = date('Y-m-d H:i:s');
if (!$employee_email || !$dept_head_email || !$leave_type) {
  http_response_code(400); echo json_encode(['error'=>'Missing required fields']); exit;
}
try {
    $stmt = $pdo->prepare("INSERT INTO leave_requests (employee_email, dept_head_email, leave_type, dates, reason, status, applied_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$employee_email, $dept_head_email, $leave_type, $dates, $reason, $status, $applied_at]);
    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['error'=>'DB insert failed']);
}