<?php
// Verifies a registration email code stored in session.
// Expects JSON: { email: string, code: string }
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
if (!isset($_SESSION['verification_email']) || !isset($_SESSION['verification_code'])) {
    respond('error', 'No verification code requested for this session.');
}
if ($_SESSION['verification_email'] !== $email) {
    respond('error', 'Email mismatch. Request a new code.');
}
if (time() > (int)$_SESSION['verification_expires']) {
    respond('error', 'Verification code expired. Request a new one.');
}
if ($_SESSION['verification_code'] !== $code) {
    respond('error', 'Incorrect verification code.');
}

// Success: mark verified
$_SESSION['verified_email'] = $email;
// Optionally clear code to prevent reuse
unset($_SESSION['verification_code']);
unset($_SESSION['verification_expires']);

respond('ok', 'Email verified successfully.', ['email' => $email]);
