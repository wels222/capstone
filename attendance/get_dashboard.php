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

// Present count (time_in_status = 'Present' only)
$presentSql = 'SELECT COUNT(DISTINCT a.employee_id) FROM attendance a 
               JOIN users u ON a.employee_id = u.employee_id 
               WHERE a.date = ? AND a.time_in_status = "Present"';
$presentParams = [$today];
if ($dept) {
    $presentSql .= ' AND u.department = ?';
    $presentParams[] = $dept;
}
$presentStmt = $pdo->prepare($presentSql);
$presentStmt->execute($presentParams);
$present = $presentStmt->fetchColumn();

// Late count (time_in_status = 'Late')
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

// Undertime-at-time-in count (time_in_status = 'Undertime')
$uiSql = 'SELECT COUNT(DISTINCT a.employee_id) FROM attendance a 
            JOIN users u ON a.employee_id = u.employee_id 
            WHERE a.date = ? AND a.time_in_status = "Undertime"';
$uiParams = [$today];
if ($dept) { $uiSql .= ' AND u.department = ?'; $uiParams[] = $dept; }
$uiStmt = $pdo->prepare($uiSql);
$uiStmt->execute($uiParams);
$timeinUndertime = $uiStmt->fetchColumn();

// Active = Present + Late + Time-in Undertime (anyone who timed in by 5:00 PM)
$active = intval($present) + intval($late) + intval($timeinUndertime);

// Absent = total - active
$absent = max(0, intval($total) - intval($active));

echo json_encode([
    'success' => true,
    'total_employees' => intval($total),
    'present' => intval($present),
    'late' => intval($late),
    'timein_undertime' => intval($timeinUndertime),
    'active' => intval($active),
    'absent' => intval($absent),
    'date' => $today,
]);
