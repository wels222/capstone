<?php
// db.php - Database connection
$host = 'localhost';
$db   = 'capstone'; // <- ensure this is "capstone"
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

// Load environment variables from .env (simple loader)
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!strpos($line, '=')) continue;
        list($k, $v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ($k === '') continue;
        // export to environment so getenv can pick it up
        putenv("$k=$v");
        $_ENV[$k] = $v;
    }
}

// QR token secret used to sign rotating QR tokens. Prefer environment variable QR_SECRET.
$envSecret = getenv('QR_SECRET');
if ($envSecret && $envSecret !== '') {
    if (!defined('QR_SECRET')) define('QR_SECRET', $envSecret);
} else {
    // If none provided, generate a secure secret and persist to .env for future runs
    try {
        $generated = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // Fallback to less-secure unique id
        $generated = bin2hex(openssl_random_pseudo_bytes(32));
    }
    // Append or create .env with QR_SECRET
    if (!file_exists($envPath) || strpos(file_get_contents($envPath), 'QR_SECRET=') === false) {
        file_put_contents($envPath, "QR_SECRET={$generated}\n", FILE_APPEND | LOCK_EX);
    }
    putenv("QR_SECRET={$generated}");
    $_ENV['QR_SECRET'] = $generated;
    if (!defined('QR_SECRET')) define('QR_SECRET', $generated);
}

// Base path for the application. If this file is used behind a different URL, adjust accordingly.
if (!defined('BASE_PATH')) {
    // Try to detect automatically, fallback to '/capstone'
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '') ?: '/capstone';
    if ($scriptDir === '/' || $scriptDir === '') $scriptDir = '/capstone';
    define('BASE_PATH', $scriptDir);
}
