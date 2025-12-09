<?php
require_once __DIR__ . '/_bootstrap.php';
// Sends a 6-digit verification code to a Gmail address for registration (or other purposes).
// Expects JSON: { email: string, purpose: 'registration' }
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config_smtp.php';
require_once __DIR__ . '/../email_templates.php';

// Optional: load PHPMailer if available; else simulate send in dev.
$autoload = __DIR__ . '/../vendor/autoload.php';
$mailerAvailable = file_exists($autoload);
if ($mailerAvailable) {
    require_once $autoload; // Composer autoload
}

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
$purpose = trim($data['purpose'] ?? ($_POST['purpose'] ?? 'registration'));

if (!$email) respond('error', 'Email is required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond('error', 'Invalid email format.');
if (!preg_match('/@gmail\.com$/i', $email)) respond('error', 'Email must be a Gmail address.');

if ($purpose === 'registration') {
    try {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) respond('error', 'Email already registered.');
    } catch (Exception $e) {
        respond('error', 'Database error checking email.');
    }
}

if (isset($_SESSION['verification_last_sent']) && isset($_SESSION['verification_email']) && $_SESSION['verification_email'] === $email) {
    $elapsed = time() - (int)$_SESSION['verification_last_sent'];
    if ($elapsed < 60) respond('error', 'Please wait '.(60 - $elapsed).'s before requesting a new code.');
}

try {
    $code = (string)random_int(100000, 999999);
} catch (Exception $e) {
    $code = (string)mt_rand(100000, 999999);
}

$_SESSION['verification_email'] = $email;
$_SESSION['verification_code'] = $code; // Consider hashing in production
$_SESSION['verification_expires'] = time() + 300; // 5 minutes
$_SESSION['verification_last_sent'] = time();
$_SESSION['verification_purpose'] = $purpose;

if ($mailerAvailable && class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $smtpHost = SMTP_HOST;
    $smtpUser = SMTP_USER;
    $smtpPass = SMTP_PASS;
    $smtpPort = SMTP_PORT;
    $smtpSecure = SMTP_SECURE;
    try {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = $smtpSecure === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;
        $fromEmail = $smtpUser;
        $fromName = APP_NAME;
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($email);
        $mail->Subject = 'Email Verification - ' . APP_NAME;
        $mail->isHTML(true);
        $mail->Body = getVerificationEmailTemplate($code, $email);
        $mail->AltBody = getPlainTextVerificationEmail($code, $email);
        $mail->CharSet = 'UTF-8';
        
        // Check if SMTP credentials are configured
        if ($smtpUser === 'your-system-email@gmail.com' || $smtpPass === 'your-app-password-here') {
            $_SESSION['verification_simulated'] = true;
            respond('ok', 'Code generated (SMTP not configured yet, simulated). Edit config_smtp.php', ['email' => $email, 'code' => $code]);
        }
        
        $mail->send();
        respond('ok', 'Verification code sent to your email.', ['email' => $email]);
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        respond('error', 'Failed to send email: '.$e->getMessage());
    }
} else {
    // Dev fallback if PHPMailer not installed
    $_SESSION['verification_simulated'] = true;
    respond('ok', 'Code generated (PHPMailer not installed, simulated). Code: '.$code, ['email' => $email, 'code' => $code]);
}
