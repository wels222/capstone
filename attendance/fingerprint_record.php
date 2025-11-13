<?php
// fingerprint_record.php
// Records attendance for a user identified by employee_id, using the same logic as QR.
// Writes to last_scan.json so scanner displays update instantly.

header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/qr_utils.php';

// Accept employee_id from JSON body or query params
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
$employeeId = null;
if (is_array($body) && isset($body['employee_id']) && $body['employee_id'] !== '') {
    $employeeId = $body['employee_id'];
} elseif (isset($_POST['employee_id'])) {
    $employeeId = $_POST['employee_id'];
} elseif (isset($_GET['employee_id'])) {
    $employeeId = $_GET['employee_id'];
}

if (!$employeeId) {
    echo json_encode(['success' => false, 'message' => 'Missing employee_id']);
    exit;
}

try {
    // Find approved user by employee_id
    $stmt = $pdo->prepare('SELECT id FROM users WHERE employee_id = ? AND status = "approved" LIMIT 1');
    $stmt->execute([$employeeId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['id'])) {
        echo json_encode(['success' => false, 'message' => 'User not found or not approved']);
        exit;
    }

    $res = qr_record_attendance_for_user($pdo, $user['id']);
    echo json_encode($res);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
