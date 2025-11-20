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

// Base path for the application. Automatically detects the correct path for both local and hosted environments.
if (!defined('BASE_PATH')) {
    // Detect the base path dynamically from the current script location
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // If we're in a subdirectory like /capstone/something/file.php or /myapp/something/file.php
    // Extract the first directory level as the base path
    if ($scriptName) {
        // Remove the filename and any subdirectories
        $scriptDir = dirname($scriptName);
        
        // For files in root subdirectories like /capstone/index.php, scriptDir will be /capstone
        // For files in nested dirs like /capstone/api/file.php, we need to go up to /capstone
        // For files at root like /index.php, scriptDir will be /
        
        // Find the first directory level after root
        $parts = explode('/', trim($scriptDir, '/'));
        
        if (!empty($parts) && $parts[0] !== '') {
            // We're in a subdirectory - use the first part
            $basePath = '/' . $parts[0];
        } else {
            // We're at root level
            $basePath = '';
        }
    } else {
        // Fallback: empty means root
        $basePath = '';
    }
    
    define('BASE_PATH', $basePath);
}
