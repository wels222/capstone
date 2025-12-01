<?php
header("Content-Type: application/json");

// ---------------------------
// Database credentials
// ---------------------------
$host = '127.0.0.1';
$db   = 'capstone';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// ---------------------------
// Get parameters
// ---------------------------
$employee_id = $_GET['employee_id'] ?? null;
$status = $_GET['status'] ?? null; // "in" or "out"
$timestamp = $_GET['timestamp'] ?? null; // "YYYY-MM-DD HH:MM:SS"

if (!$employee_id || !$status || !$timestamp) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing employee_id, status, or timestamp parameter'
    ]);
    exit;
}

// ---------------------------
// Connect to database
// ---------------------------
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
// Determine date for attendance (YYYY-MM-DD)
// ---------------------------
$dateOnly = date('Y-m-d', strtotime($timestamp));
$timeOnly = date('H:i', strtotime($timestamp)); // HH:MM in 24-hour format
$ninePm = '21:00';

// ---------------------------
// Determine time_in_status / time_out_status
// ---------------------------
$time_in_status = null;
$time_out_status = null;

if ($status === 'in') {
    // Time-in windows: Present 06:00-08:00 (and earlier treated as Present), Late 08:01-12:00, Undertime 12:01-17:00, else Absent
    if ($timeOnly < '06:00') {
        $time_in_status = 'Present';
    } elseif ($timeOnly <= '08:00') {
        $time_in_status = 'Present';
    } elseif ($timeOnly <= '12:00') {
        $time_in_status = 'Late';
    } elseif ($timeOnly <= '17:00') {
        $time_in_status = 'Undertime';
    } else {
        $time_in_status = 'Absent';
    }
} elseif ($status === 'out') {
    // Time-out windows: Undertime <= 16:59, Out 17:00-17:59, Overtime >= 18:00
    if ($timeOnly <= '16:59') {
        $time_out_status = 'Undertime';
    } elseif ($timeOnly >= '18:00') {
        $time_out_status = 'Overtime';
    } else {
        $time_out_status = 'Out';
    }
}

try {
    if ($status === 'in') {
        // If after 5:00 PM, lock as Absent and do not record time_in
        if ($timeOnly > '17:00') {
            // Upsert with Absent and NULL time_in
            $stmt = $pdo->prepare("
                INSERT INTO attendance (employee_id, date, time_in, time_in_status)
                VALUES (:employee_id, :date, NULL, 'Absent')
                ON DUPLICATE KEY UPDATE 
                    time_in = NULL,
                    time_in_status = 'Absent'
            ");
            $stmt->execute([
                'employee_id' => $employee_id,
                'date' => $dateOnly
            ]);
            echo json_encode([
                'success' => true,
                'message' => 'Marked as Absent after 5:00 PM',
                'status' => 'in',
                'time_in_status' => 'Absent'
            ]);
            exit;
        }

        // Before 5:00 PM: insert/update normal time_in
        $stmt = $pdo->prepare("
            INSERT INTO attendance (employee_id, date, time_in, time_in_status)
            VALUES (:employee_id, :date, :time_in, :time_in_status)
            ON DUPLICATE KEY UPDATE 
                time_in = :time_in,
                time_in_status = :time_in_status
        ");
        $stmt->execute([
            'employee_id' => $employee_id,
            'date' => $dateOnly,
            'time_in' => $timestamp,
            'time_in_status' => $time_in_status
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Time In recorded successfully',
            'status' => 'in',
            'time_in_status' => $time_in_status
        ]);

    } elseif ($status === 'out') {
        // Enforce: cannot time out without an existing time-in record for today
        $chk = $pdo->prepare("SELECT id, time_in_status, time_out FROM attendance WHERE employee_id = :employee_id AND date = :date LIMIT 1");
        $chk->execute(['employee_id' => $employee_id, 'date' => $dateOnly]);
        $row = $chk->fetch();
        if (!$row) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot time out without a time in record for today.'
            ]);
            exit;
        }

        // Block timeout if already marked Absent
        if (strtolower($row['time_in_status'] ?? '') === 'absent') {
            echo json_encode([
                'success' => true,
                'message' => 'Attendance locked as Absent; time out ignored.',
                'status' => 'out',
                'time_out_status' => 'Absent'
            ]);
            exit;
        }

        // After 9:00 PM, mark as Forgotten and do not set time_out
        if ($timeOnly >= $ninePm) {
            $stmt = $pdo->prepare("UPDATE attendance SET time_out_status = 'Forgotten' WHERE id = :id");
            $stmt->execute(['id' => $row['id']]);
            echo json_encode([
                'success' => true,
                'message' => 'Marked as Forgotten after 9:00 PM',
                'status' => 'out',
                'time_out_status' => 'Forgotten'
            ]);
            exit;
        }

        // Update time_out and time_out_status for today's record
        $stmt = $pdo->prepare("UPDATE attendance SET time_out = :time_out, time_out_status = :time_out_status WHERE id = :id");
        $stmt->execute([
            'time_out' => $timestamp,
            'time_out_status' => $time_out_status,
            'id' => $row['id']
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Time Out updated successfully',
            'status' => 'out',
            'time_out_status' => $time_out_status
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid status value'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => "Query failed: " . $e->getMessage()
    ]);
}
?>
