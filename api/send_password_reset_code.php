<?php
require_once __DIR__ . '/_bootstrap.php';
// Sends password reset code to user's email
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config_smtp.php';
require_once __DIR__ . '/../email_templates.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
$mailerAvailable = file_exists($autoload);
if ($mailerAvailable) {
    require_once $autoload;
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

if (!$email) respond('error', 'Email is required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond('error', 'Invalid email format.');

// Check if user exists and is approved
try {
    $stmt = $pdo->prepare('SELECT id, status FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        respond('error', 'No account found with this email address.');
    }
    
    $status = strtolower($user['status'] ?? '');
    if ($status === 'pending') {
        respond('error', 'Your account is still pending approval. Please wait for admin approval before resetting your password.');
    } elseif ($status === 'declined') {
        respond('error', 'Your account registration was declined. Please contact the administrator.');
    } elseif ($status !== 'approved') {
        respond('error', 'Your account is not active. Please contact the administrator.');
    }
} catch (Exception $e) {
    respond('error', 'Database error.');
}

// Rate limiting
if (isset($_SESSION['reset_last_sent']) && isset($_SESSION['reset_email']) && $_SESSION['reset_email'] === $email) {
    $elapsed = time() - (int)$_SESSION['reset_last_sent'];
    if ($elapsed < 60) respond('error', 'Please wait '.(60 - $elapsed).'s before requesting a new code.');
}

// Generate code
try {
    $code = (string)random_int(100000, 999999);
} catch (Exception $e) {
    $code = (string)mt_rand(100000, 999999);
}

// Store in session
$_SESSION['reset_email'] = $email;
$_SESSION['reset_code'] = $code;
$_SESSION['reset_expires'] = time() + 300; // 5 minutes
$_SESSION['reset_last_sent'] = time();

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
        $mail->Subject = 'Password Reset Code - ' . APP_NAME;
        $mail->isHTML(true);
        $mail->Body = getPasswordResetEmailTemplate($code, $email);
        $mail->AltBody = getPlainTextPasswordResetEmail($code, $email);
        $mail->CharSet = 'UTF-8';
        
        if ($smtpUser === 'your-system-email@gmail.com' || $smtpPass === 'your-app-password-here') {
            $_SESSION['reset_simulated'] = true;
            respond('ok', 'Code generated (SMTP not configured, simulated). Code: '.$code, ['email' => $email, 'code' => $code]);
        }
        
        $mail->send();
        respond('ok', 'Password reset code sent to your email.', ['email' => $email]);
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        respond('error', 'Failed to send email: '.$e->getMessage());
    }
} else {
    $_SESSION['reset_simulated'] = true;
    respond('ok', 'Code generated (PHPMailer not installed, simulated). Code: '.$code, ['email' => $email, 'code' => $code]);
}
