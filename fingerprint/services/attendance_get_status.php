<?php
// attendance_get_status.php
header("Content-Type: application/json");

// ---------------------------
// Set timezone
// ---------------------------
date_default_timezone_set('Asia/Manila'); // <- Fixes "today" mismatch

// ---------------------------
// Database credentials
// ---------------------------
$host = '127.0.0.1';
$db   = 'capstone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => "Database connection failed: " . $e->getMessage()
    ]);
    exit;
}

// ---------------------------
// Get employee_id from GET
// ---------------------------
$employee_id = isset($_GET['employee_id']) ? trim($_GET['employee_id']) : null;
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

if (!$employee_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing employee_id parameter'
    ]);
    exit;
}

// ---------------------------
// Get current date (YYYY-MM-DD)
// ---------------------------
$today = date('Y-m-d');

try {
    // Fetch the most recent attendance record for this employee today
    $sql = "
        SELECT id, employee_id, date, time_in, time_out, created_at
        FROM attendance
        WHERE employee_id = :employee_id
          AND (date = :today OR DATE(created_at) = :today)
        ORDER BY id DESC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'employee_id' => $employee_id,
        'today' => $today
    ]);

    $record = $stmt->fetch(); // returns false if none

    // default status
    $status = "in";

    if (!$record) {
        // No record today → allow "in"
        $status = "in";
    } else {
        $time_in  = isset($record['time_in'])  ? $record['time_in']  : null;
        $time_out = isset($record['time_out']) ? $record['time_out'] : null;

        $has_time_in  = !in_array($time_in, [null, '', '0000-00-00 00:00:00']);
        $has_time_out = !in_array($time_out, [null, '', '0000-00-00 00:00:00']);

        if ($has_time_in && !$has_time_out) {
            $status = "out";      // can clock out
        } elseif ($has_time_out) {
            $status = "already";  // already completed attendance
        } else {
            $status = "in";       // fallback: allow clock in
        }
    }

    $response = [
        'success' => true,
        'status' => $status
    ];

    if ($debug) {
        $response['debug'] = [
            'today' => $today,
            'sql_used' => $sql,
            'params' => ['employee_id' => $employee_id, 'today' => $today],
            'record' => $record ?: null,
            'computed' => [
                'has_record' => (bool)$record,
                'has_time_in' => $has_time_in ?? false,
                'has_time_out' => $has_time_out ?? false
            ]
        ];
    }

    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => "Query failed: " . $e->getMessage()
    ]);
}
