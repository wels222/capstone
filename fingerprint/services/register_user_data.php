<?php
header("Content-Type: application/json");

// Database credentials
$host = 'localhost';
$db   = 'capstone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['employee_id']) || empty($data['employee_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "employee_id is required"
    ]);
    exit;
}

$employee_id = $data['employee_id'];
$fingerprint_path = __DIR__ . '/../server/fingerprint.bmp';

if (!file_exists($fingerprint_path)) {
    echo json_encode([
        "success" => false,
        "message" => "Fingerprint file not found at $fingerprint_path"
    ]);
    exit;
}

// Read fingerprint BMP as binary
$fingerprint_data = file_get_contents($fingerprint_path);

try {
    $stmt = $pdo->prepare("INSERT INTO fingerprints (employee_id, template, created_at) VALUES (:employee_id, :template, NOW())");
    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->bindParam(':template', $fingerprint_data, PDO::PARAM_LOB);
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Fingerprint registered successfully",
        "id" => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database insert failed: " . $e->getMessage()
    ]);
}

//modal, onRead, not OnInit()