<?php
// Test PHPMailer installation and SMTP configuration
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config_smtp.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$mail = new PHPMailer(true);

try {
    $smtpHost = SMTP_HOST;
    $smtpUser = SMTP_USER;
    $smtpPass = SMTP_PASS;
    $smtpPort = SMTP_PORT;
    $smtpSecure = SMTP_SECURE;
    
    if ($smtpUser === 'your-system-email@gmail.com' || $smtpPass === 'your-app-password-here') {
        echo json_encode([
            'status' => 'error',
            'message' => 'SMTP credentials not configured in config_smtp.php',
            'instructions' => [
                '1. Edit config_smtp.php file',
                '2. Set SMTP_USER to your Gmail address',
                '3. Set SMTP_PASS to your App Password',
                '4. Get App Password: https://myaccount.google.com/apppasswords'
            ]
        ]);
        exit;
    }
    
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = $smtpSecure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpPort;
    
    $mail->setFrom($smtpUser, APP_NAME);
    $mail->addAddress($smtpUser); // Send test to yourself
    
    $mail->Subject = 'PHPMailer Test - ' . date('Y-m-d H:i:s');
    $mail->isHTML(true);
    $mail->Body = '<h2>PHPMailer is working!</h2><p>This is a test email from your Capstone system.</p>';
    $mail->AltBody = 'PHPMailer is working! This is a test email.';
    
    $mail->send();
    
    echo json_encode([
        'status' => 'ok',
        'message' => 'Test email sent successfully!',
        'to' => $smtpUser,
        'smtp_host' => $smtpHost,
        'smtp_port' => $smtpPort
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'PHPMailer error: ' . $mail->ErrorInfo,
        'exception' => $e->getMessage()
    ]);
}
