<?php
/**
 * Mark Absent Script
 * This script marks all employees who didn't time in today as "Absent".
 * Run this at or after 5:00 PM daily (Asia/Manila) via scheduler or manually.
 */

require_once __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Manila');

$today = date('Y-m-d');
$nowTime = strtotime(date('H:i:s'));
$cutoff = strtotime('17:00:00'); // 5:00 PM

if ($nowTime < $cutoff) {
    echo json_encode([
        'success' => false,
        'date' => $today,
        'marked_absent' => 0,
        'message' => 'It is not yet 5:00 PM Manila time. No action taken.'
    ]);
    exit;
}

// Get all approved employees
$stmt = $pdo->prepare('SELECT employee_id FROM users WHERE status = "approved" AND employee_id IS NOT NULL');
$stmt->execute();
$allEmployees = $stmt->fetchAll(PDO::FETCH_COLUMN);

$absentCount = 0;

foreach ($allEmployees as $empId) {
        // Check if employee has attendance record for today and whether they have a valid time_in
        $check = $pdo->prepare('SELECT id, time_in, time_in_status FROM attendance WHERE employee_id = ? AND date = ? LIMIT 1');
        $check->execute([$empId, $today]);
        $record = $check->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            // No record at all = Insert as Absent (use time_in_status to keep consistency)
            $insert = $pdo->prepare('INSERT INTO attendance (employee_id, date, time_in, time_in_status, created_at) VALUES (?, ?, NULL, ?, NOW())');
            $insert->execute([$empId, $today, 'Absent']);
            $absentCount++;
        } else {
            // If a record exists but has no time_in and not already Absent, update time_in_status
            $timeIn = $record['time_in'];
            $curStatus = strtolower($record['time_in_status'] ?? '');
            if (empty($timeIn) && $curStatus !== 'absent') {
                $update = $pdo->prepare('UPDATE attendance SET time_in_status = ?, time_in = NULL WHERE id = ?');
                $update->execute(['Absent', $record['id']]);
                $absentCount++;
            }
        }
}

echo json_encode([
    'success' => true,
    'date' => $today,
    'total_employees' => count($allEmployees),
    'marked_absent' => $absentCount,
    'message' => "Marked {$absentCount} employees as absent for {$today}"
]);
// record last run date so web pages can avoid re-running multiple times per day
$lastRunFile = __DIR__ . '/last_absent_run.txt';
@file_put_contents($lastRunFile, $today);
?>
