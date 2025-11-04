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
    // Check if employee has attendance record for today
    $check = $pdo->prepare('SELECT id, time_in FROM attendance WHERE employee_id = ? AND date = ?');
    $check->execute([$empId, $today]);
    $record = $check->fetch();
    
    if (!$record) {
        // No record = Insert as Absent
        $insert = $pdo->prepare('INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, ?)');
        $insert->execute([$empId, $today, 'Absent']);
        $absentCount++;
    }
}

echo json_encode([
    'success' => true,
    'date' => $today,
    'total_employees' => count($allEmployees),
    'marked_absent' => $absentCount,
    'message' => "Marked {$absentCount} employees as absent for {$today}"
]);
?>
