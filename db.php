<?php
// db.php - Database connection (env-aware for hosting)
$host    = getenv('DB_HOST') ?: 'localhost';
$db      = getenv('DB_NAME') ?: 'capstone';
$user    = getenv('DB_USER') ?: 'root';
$pass    = getenv('DB_PASS') ?: 'damnson';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$port    = getenv('DB_PORT') ?: null;

$dsn = $port ? "mysql:host=$host;port=$port;dbname=$db;charset=$charset" : "mysql:host=$host;dbname=$db;charset=$charset";
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

// Helper: Generate next employee ID in format EMPYYYY-####
if (!function_exists('getNextEmployeeId')) {
    function getNextEmployeeId(PDO $pdo, ?string $year = null): string {
        $yr = $year ?: date('Y');
        // Use a short transaction to reduce race conditions
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT employee_id FROM users WHERE employee_id LIKE ? ORDER BY employee_id DESC LIMIT 1 FOR UPDATE");
            $like = sprintf('EMP%s-%%', $yr);
            $stmt->execute([$like]);
            $last = $stmt->fetchColumn();
            if ($last && preg_match('/EMP\d{4}-(\d{1,})$/', $last, $m)) {
                $seq = ((int)$m[1]) + 1;
            } else {
                $seq = 1;
            }
            $next = sprintf('EMP%s-%04d', $yr, $seq);
            $pdo->commit();
            return $next;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            // Fallback without transaction
            $stmt = $pdo->prepare("SELECT employee_id FROM users WHERE employee_id LIKE ? ORDER BY employee_id DESC LIMIT 1");
            $like = sprintf('EMP%s-%%', $yr);
            $stmt->execute([$like]);
            $last = $stmt->fetchColumn();
            if ($last && preg_match('/EMP\d{4}-(\d{1,})$/', $last, $m)) {
                $seq = ((int)$m[1]) + 1;
            } else {
                $seq = 1;
            }
            return sprintf('EMP%s-%04d', $yr, $seq);
        }
    }
}

// QR token secret - stored in database for hosting compatibility (no .env files needed)
// This approach works on any hosting provider without file permission issues
if (!defined('QR_SECRET')) {
    try {
        // Try to get QR_SECRET from database
        $stmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'QR_SECRET' LIMIT 1");
        $row = $stmt->fetch();
        
        if ($row && !empty($row['config_value'])) {
            define('QR_SECRET', $row['config_value']);
        } else {
            // Generate new secret and store in database
            try {
                $generated = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                $generated = bin2hex(openssl_random_pseudo_bytes(32));
            }
            
            // Store in database for future use
            $pdo->exec("INSERT INTO system_config (config_key, config_value) VALUES ('QR_SECRET', '{$generated}') 
                       ON DUPLICATE KEY UPDATE config_value = '{$generated}'");
            
            define('QR_SECRET', $generated);
        }
    } catch (PDOException $e) {
        // Fallback if system_config table doesn't exist yet
        // Generate temporary secret (will be stored once table is created)
        try {
            $generated = bin2hex(random_bytes(32));
        } catch (Exception $ex) {
            $generated = bin2hex(openssl_random_pseudo_bytes(32));
        }
        define('QR_SECRET', $generated);
    }
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
