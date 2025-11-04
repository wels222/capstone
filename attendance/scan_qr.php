<?php
session_start();
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

// Accept JSON input
$payload = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$employee_code = trim($payload['employee_code'] ?? '');

if (!$employee_code) {
    echo json_encode(['success' => false, 'message' => 'No employee code provided']);
    exit;
}

// Find employee by employee_id
$stmt = $pdo->prepare('SELECT id, CONCAT(firstname, " ", lastname) as name, department, profile_picture, employee_id FROM users WHERE employee_id = ? AND status = "approved"');
$stmt->execute([$employee_code]);
$emp = $stmt->fetch();

if (!$emp) {
    echo json_encode(['success' => false, 'message' => 'Employee not found or not approved']);
    exit;
}

$today = date('Y-m-d');
date_default_timezone_set('Asia/Manila');
$now = date('Y-m-d H:i:s');

// Check if attendance record exists for today
$stmt = $pdo->prepare('SELECT * FROM attendance WHERE employee_id = ? AND date = ?');
$stmt->execute([$employee_code, $today]);
$att = $stmt->fetch();

if (!$att) {
    // First scan = Time In
    $time_in = $now;
    $cutoff = strtotime($today . ' 07:30:00'); // 7:30 AM cutoff for absent
    
    if (strtotime($time_in) <= strtotime($today . ' 07:00:00')) {
        $time_in_status = 'Present'; // On time or early
    } elseif (strtotime($time_in) <= $cutoff) {
        $time_in_status = 'Late'; // Between 7:01 AM and 7:30 AM
    } else {
        $time_in_status = 'Absent'; // After 7:30 AM = considered absent
    }

    $ins = $pdo->prepare('INSERT INTO attendance (employee_id, date, time_in, time_in_status) VALUES (?, ?, ?, ?)');
    $ins->execute([$employee_code, $today, $time_in, $time_in_status]);

    echo json_encode([
        'success' => true,
        'action' => 'time_in',
        'employee' => [
            'employee_id' => $employee_code,
            'name' => $emp['name'],
            'department' => $emp['department'],
            'profile_pic' => $emp['profile_picture'] ? ('../' . $emp['profile_picture']) : null,
        ],
        'time' => $time_in,
        'status' => $time_in_status,
    ]);
} else {
    // Already has time_in, check for time_out
    if ($att['time_out']) {
        echo json_encode(['success' => false, 'message' => 'Time Out already recorded for today']);
        exit;
    }

    // Second scan = Time Out
    $time_out = $now;
    
    // Calculate time_out status
    $time_out_timestamp = strtotime($time_out);
    $before_5pm = strtotime($today . ' 17:00:00'); // 5:00 PM
    $ontime_end = strtotime($today . ' 17:05:00'); // 5:05 PM
    $overtime_start = strtotime($today . ' 17:30:00'); // 5:30 PM
    
    if ($time_out_timestamp < $before_5pm) {
        // Before 5:00 PM = Undertime
        $time_out_status = 'Undertime';
    } elseif ($time_out_timestamp >= $before_5pm && $time_out_timestamp <= $ontime_end) {
        // Between 5:00 PM and 5:05 PM = On-time
        $time_out_status = 'On-time';
    } elseif ($time_out_timestamp >= $overtime_start) {
        // 5:30 PM or later = Overtime
        $time_out_status = 'Overtime';
    } else {
        // Between 5:06 PM and 5:29 PM = On-time (grace period)
        $time_out_status = 'On-time';
    }
    
    // Update time_out and time_out_status (keep time_in_status unchanged)
    $upd = $pdo->prepare('UPDATE attendance SET time_out = ?, time_out_status = ? WHERE id = ?');
    $upd->execute([$time_out, $time_out_status, $att['id']]);

    echo json_encode([
        'success' => true,
        'action' => 'time_out',
        'employee' => [
            'employee_id' => $employee_code,
            'name' => $emp['name'],
            'department' => $emp['department'],
            'profile_pic' => $emp['profile_picture'] ? ('../' . $emp['profile_picture']) : null,
        ],
        'time' => $time_out,
        'status' => $time_out_status,
        'time_in_status' => $att['time_in_status'], // Include original time in status
    ]);
}
