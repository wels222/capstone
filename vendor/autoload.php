<?php
// Simple autoloader for PHPMailer (manual install)
spl_autoload_register(function ($class) {
    // Check if it's a PHPMailer class
    if (strpos($class, 'PHPMailer\\PHPMailer\\') === 0) {
        $classPath = str_replace('PHPMailer\\PHPMailer\\', '', $class);
        $file = __DIR__ . '/phpmailer/phpmailer/src/' . $classPath . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
