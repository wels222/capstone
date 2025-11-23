<?php
// Verifies password reset code stored in session
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

function respond($status, $message, $extra = []) {
    http_response_code($status === 'ok' ? 200 : 400);
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

$raw = file_get_contents('php://input');
$data = [];
if ($raw) {
    $tmp = json_decode($raw, true);
    if (is_array($tmp)) $data = $tmp;
}

$email = trim($data['email'] ?? ($_POST['email'] ?? ''));
$code  = trim($data['code'] ?? ($_POST['code'] ?? ''));

if (!$email || !$code) respond('error', 'Email and code are required.');
if (!preg_match('/^[0-9]{6}$/', $code)) respond('error', 'Invalid code format.');
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_code'])) {
    respond('error', 'No reset code requested for this session. Please request a new code.');
}
if ($_SESSION['reset_email'] !== $email) {
    respond('error', 'Email mismatch. Request a new code.');
}
if (time() > (int)$_SESSION['reset_expires']) {
    respond('error', 'Reset code expired. Request a new one.');
}
if ($_SESSION['reset_code'] !== $code) {
    // Don't clear the session on wrong code - allow retries
    respond('error', 'Incorrect reset code. Please try again.');
}

// Success: mark verified
$_SESSION['reset_verified_email'] = $email;
// Clear code to prevent reuse after successful verification
unset($_SESSION['reset_code']);
unset($_SESSION['reset_expires']);

respond('ok', 'Code verified successfully.', ['email' => $email]);
