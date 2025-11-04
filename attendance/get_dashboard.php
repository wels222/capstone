<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$dept = $_GET['department'] ?? '';
$today = date('Y-m-d');

// Total employees (approved only)
$totSql = 'SELECT COUNT(*) FROM users WHERE status = "approved"';
$totParams = [];
if ($dept) {
    $totSql .= ' AND department = ?';
    $totParams[] = $dept;
}
$totStmt = $pdo->prepare($totSql);
$totStmt->execute($totParams);
$total = $totStmt->fetchColumn();

// Present count (has time_in today)
$presentSql = 'SELECT COUNT(DISTINCT a.employee_id) FROM attendance a 
               JOIN users u ON a.employee_id = u.employee_id 
               WHERE a.date = ? AND a.time_in IS NOT NULL';
$presentParams = [$today];
if ($dept) {
    $presentSql .= ' AND u.department = ?';
    $presentParams[] = $dept;
}
$presentStmt = $pdo->prepare($presentSql);
$presentStmt->execute($presentParams);
$present = $presentStmt->fetchColumn();

// Late count
$lateSql = 'SELECT COUNT(DISTINCT a.employee_id) FROM attendance a 
            JOIN users u ON a.employee_id = u.employee_id 
            WHERE a.date = ? AND a.time_in_status = "Late"';
$lateParams = [$today];
if ($dept) {
    $lateSql .= ' AND u.department = ?';
    $lateParams[] = $dept;
}
$lateStmt = $pdo->prepare($lateSql);
$lateStmt->execute($lateParams);
$late = $lateStmt->fetchColumn();

// Absent = total - present
// Also include employees who are explicitly marked as "Absent" in attendance table
$absent = max(0, intval($total) - intval($present));

echo json_encode([
    'success' => true,
    'total_employees' => intval($total),
    'present' => intval($present),
    'late' => intval($late),
    'absent' => intval($absent),
    'date' => $today,
]);
