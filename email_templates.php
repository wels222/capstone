<?php
/**
 * Professional Email Templates for MABINIHUB
 * Provides beautifully designed, responsive email templates
 */

/**
 * Generate a professional email template with OTP code
 * 
 * @param string $title Email title
 * @param string $greeting Greeting message
 * @param string $message Main message content
 * @param string $code OTP code
 * @param string $footer Footer message
 * @param string $type Type of email (verification, reset, notification)
 * @return string HTML email template
 */
function getEmailTemplate($title, $greeting, $message, $code = '', $footer = '', $type = 'verification') {
    $systemName = 'MABINIHUB';
    $currentYear = date('Y');
    
    // Color scheme based on type
    $colors = [
        'verification' => ['primary' => '#4F46E5', 'secondary' => '#818CF8', 'accent' => '#EEF2FF'],
        'reset' => ['primary' => '#DC2626', 'secondary' => '#F87171', 'accent' => '#FEF2F2'],
        'notification' => ['primary' => '#059669', 'secondary' => '#34D399', 'accent' => '#ECFDF5']
    ];
    
    $color = $colors[$type] ?? $colors['verification'];
    $primaryColor = $color['primary'];
    $secondaryColor = $color['secondary'];
    $accentColor = $color['accent'];
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F3F4F6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #FFFFFF;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, {$primaryColor} 0%, {$secondaryColor} 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .logo-container {
            margin-bottom: 20px;
        }
        .logo-circle {
            width: 80px;
            height: 80px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }
        .logo-text {
            color: #FFFFFF;
            font-size: 32px;
            font-weight: bold;
        }
        .header-title {
            color: #FFFFFF;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .email-body {
            padding: 40px 30px;
        }
        .greeting {
            color: #1F2937;
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 20px 0;
        }
        .message {
            color: #4B5563;
            font-size: 16px;
            line-height: 1.6;
            margin: 0 0 30px 0;
        }
        .code-container {
            background: linear-gradient(135deg, {$accentColor} 0%, #FFFFFF 100%);
            border: 2px solid {$primaryColor};
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .code-label {
            color: #6B7280;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        .code {
            font-size: 42px;
            font-weight: 800;
            color: {$primaryColor};
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .code-info {
            color: #9CA3AF;
            font-size: 13px;
            margin-top: 15px;
            font-style: italic;
        }
        .info-box {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px 20px;
            border-radius: 6px;
            margin: 25px 0;
        }
        .info-box p {
            color: #92400E;
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }
        .footer-message {
            color: #6B7280;
            font-size: 14px;
            line-height: 1.6;
            margin: 20px 0;
        }
        .email-footer {
            background-color: #F9FAFB;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #E5E7EB;
        }
        .footer-text {
            color: #9CA3AF;
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
        }
        .footer-links {
            margin-top: 15px;
        }
        .footer-link {
            color: {$primaryColor};
            text-decoration: none;
            margin: 0 10px;
            font-size: 12px;
        }
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #E5E7EB, transparent);
            margin: 25px 0;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 20px;
                border-radius: 8px;
            }
            .email-header {
                padding: 30px 20px;
            }
            .email-body {
                padding: 30px 20px;
            }
            .header-title {
                font-size: 24px;
            }
            .code {
                font-size: 36px;
                letter-spacing: 6px;
            }
            .logo-circle {
                width: 60px;
                height: 60px;
            }
            .logo-text {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1 class="header-title">{$systemName}</h1>
        </div>
        
        <div class="email-body">
            <p class="greeting">{$greeting}</p>
            <p class="message">{$message}</p>
HTML;

    if (!empty($code)) {
        $html .= <<<HTML
            
            <div class="code-container">
                <div class="code-label">Your Verification Code</div>
                <h2 class="code">{$code}</h2>
                <p class="code-info">⏱️ This code expires in 5 minutes</p>
            </div>
            
            <div class="info-box">
                <p><strong>⚠️ Security Notice:</strong> Never share this code with anyone. Our team will never ask for your verification code.</p>
            </div>
HTML;
    }

    if (!empty($footer)) {
        $html .= <<<HTML
            
            <div class="divider"></div>
            <p class="footer-message">{$footer}</p>
HTML;
    }

    $html .= <<<HTML
        </div>
        
        <div class="email-footer">
            <p class="footer-text">
                <strong>{$systemName}</strong><br>
                Municipality of Mabini<br>
                © {$currentYear} All rights reserved.
            </p>
            <p class="footer-text" style="margin-top: 15px; font-size: 11px; color: #D1D5DB;">
                This is an automated message, please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>
HTML;

    return $html;
}

/**
 * Get verification code email template
 */
function getVerificationEmailTemplate($code, $email) {
    return getEmailTemplate(
        'Email Verification',
        'Hello!',
        'Thank you for registering with MABINIHUB. To complete your registration, please use the verification code below:',
        $code,
        'Once verified, your account will be reviewed by our administrators. You\'ll receive a notification once your account is approved.',
        'verification'
    );
}

/**
 * Get password reset email template
 */
function getPasswordResetEmailTemplate($code, $email) {
    return getEmailTemplate(
        'Password Reset',
        'Password Reset Request',
        'We received a request to reset your password. Please use the verification code below to proceed:',
        $code,
        '<strong>If you didn\'t request this password reset, please ignore this email.</strong> Your password will remain unchanged and your account is secure.',
        'verification'
    );
}

/**
 * Get plain text version of email
 */
function getPlainTextEmail($title, $greeting, $message, $code = '', $footer = '') {
    $systemName = 'MABINIHUB';
    $currentYear = date('Y');
    
    $text = "{$systemName}\n";
    $text .= str_repeat('=', 50) . "\n\n";
    $text .= "{$greeting}\n\n";
    $text .= "{$message}\n\n";
    
    if (!empty($code)) {
        $text .= "YOUR VERIFICATION CODE:\n";
        $text .= ">>> {$code} <<<\n\n";
        $text .= "This code expires in 5 minutes.\n\n";
        $text .= "SECURITY NOTICE: Never share this code with anyone.\n\n";
    }
    
    if (!empty($footer)) {
        $text .= str_repeat('-', 50) . "\n";
        $text .= strip_tags($footer) . "\n\n";
    }
    
    $text .= str_repeat('=', 50) . "\n";
    $text .= "{$systemName}\n";
    $text .= "Municipality of Mabini\n";
    $text .= "© {$currentYear} All rights reserved.\n";
    $text .= "This is an automated message, please do not reply.\n";
    
    return $text;
}

/**
 * Get plain text verification email
 */
function getPlainTextVerificationEmail($code, $email) {
    return getPlainTextEmail(
        'Email Verification',
        'Hello!',
        'Thank you for registering with MABINIHUB. To complete your registration, please use the verification code below:',
        $code,
        'Once verified, your account will be reviewed by our administrators.'
    );
}

/**
 * Get plain text password reset email
 */
function getPlainTextPasswordResetEmail($code, $email) {
    return getPlainTextEmail(
        'Password Reset',
        'Password Reset Request',
        'We received a request to reset your password. Please use the verification code below to proceed:',
        $code,
        'If you didn\'t request this password reset, please ignore this email. Your password will remain unchanged.'
    );
}
