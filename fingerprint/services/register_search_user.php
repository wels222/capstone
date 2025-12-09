<?php
header("Content-Type: application/json");

// Database credentials
$host = '127.0.0.1';
$db   = 'capstone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Helper function to return JSON
function respond($log, $status, $value) {
    echo json_encode([
        'log' => $log,
        'status' => $status,
        'value' => $value
    ]);
    exit;
}

// Get search string (employee_id or email) from GET or POST
$search = $_REQUEST['search_string'] ?? null;
if (!$search) {
    respond('invalid_request', 'error', 'No search_string provided');
}

try {
    // Connect to database
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // -------------------
    // 1️⃣ Search by employee_id first
    // -------------------
    $stmt = $pdo->prepare("SELECT employee_id FROM users WHERE employee_id = ?");
    $stmt->execute([$search]);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($results) === 1) {
        $employee_id = $results[0];

        // Check if user already has fingerprint
        $stmtCheck = $pdo->prepare("SELECT id FROM fingerprints WHERE employee_id = ?");
        $stmtCheck->execute([$employee_id]);
        if ($stmtCheck->rowCount() > 0) {
            respond('existing_user', 'error', 'user_exists');
        }

        respond('user_found', 'success', $employee_id);
    } elseif (count($results) > 1) {
        respond('duplicate_user', 'error', 'duplicate_id');
    }

    // -------------------
    // 2️⃣ If not found by employee_id, search by email
    // -------------------
    $stmt = $pdo->prepare("SELECT employee_id FROM users WHERE email = ?");
    $stmt->execute([$search]);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($results) === 1) {
        $employee_id = $results[0];

        // Check if user already has fingerprint
        $stmtCheck = $pdo->prepare("SELECT id FROM fingerprints WHERE employee_id = ?");
        $stmtCheck->execute([$employee_id]);
        if ($stmtCheck->rowCount() > 0) {
            respond('existing_user', 'error', 'user_exists');
        }

        respond('user_found', 'success', $employee_id);
    } elseif (count($results) > 1) {
        respond('duplicate_user_email', 'error', $results); // array of employee_ids
    }

    // -------------------
    // 3️⃣ Not found
    // -------------------
    respond('user_not_found', 'error', null);

} catch (PDOException $e) {
    respond('db_error', 'error', $e->getMessage());
}
