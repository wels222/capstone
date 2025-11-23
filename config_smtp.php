<?php
// SMTP Configuration for email sending
// Edit these values with your actual Gmail credentials

// Gmail account that will send verification codes
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'altlangqq@gmail.com');
define('SMTP_PASS', 'rekl yrkz hdjm hwka');  // PALITAN MO ng App Password (16 chars from Google)
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('APP_NAME', 'Mabini HR System');

// Instructions:
// 1. Create a Gmail account for your system (e.g., mabini.hr.system@gmail.com)
// 2. Enable 2-Step Verification on that account
// 3. Generate App Password: https://myaccount.google.com/apppasswords
// 4. Replace SMTP_USER with your Gmail address
// 5. Replace SMTP_PASS with the 16-character App Password
