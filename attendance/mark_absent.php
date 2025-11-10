<?php
/**
 * Mark Absent Script
 * This script marks all employees who didn't time in today as "Absent"
 * Run this at the end of the day (e.g., 11:59 PM) via cron job or manually
 */

require_once __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Manila');

$today = date('Y-m-d');

// Get all approved employees
$stmt = $pdo->prepare('SELECT employee_id FROM users WHERE status = "approved" AND employee_id IS NOT NULL');
$stmt->execute();
$allEmployees = $stmt->fetchAll(PDO::FETCH_COLUMN);

$absentCount = 0;

foreach ($allEmployees as $empId) {
        // Check if employee has attendance record for today and whether they have a valid time_in
        $check = $pdo->prepare('SELECT id, time_in, status FROM attendance WHERE employee_id = ? AND date = ? LIMIT 1');
        $check->execute([$empId, $today]);
        $record = $check->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            // No record at all = Insert as Absent
            $insert = $pdo->prepare('INSERT INTO attendance (employee_id, date, status, created_at) VALUES (?, ?, ?, NOW())');
            $insert->execute([$empId, $today, 'Absent']);
            $absentCount++;
        } else {
            // If a record exists but has no time_in (NULL/empty) and is not already marked Absent, update it
            $timeIn = $record['time_in'];
            $curStatus = strtolower($record['status'] ?? '');
            if (empty($timeIn) && $curStatus !== 'absent') {
                $update = $pdo->prepare('UPDATE attendance SET status = ?, time_in = NULL WHERE id = ?');
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
