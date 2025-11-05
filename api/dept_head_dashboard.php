<?php
session_start();
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

// Check if user is logged in and is a dept head
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

try {
    // Get the department head's department
    $stmt = $pdo->prepare('SELECT department FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $deptHead = $stmt->fetch();
    
    if (!$deptHead) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }
    
    $department = $deptHead['department'];
    
    // Get counts by employee type for this department
    $categories = ['Permanent', 'Casual', 'JO', 'OJT'];
    $stats = [];
    
    foreach ($categories as $category) {
        // Total count for this category in this department
        $totalSql = 'SELECT COUNT(*) FROM users WHERE status = "approved" AND department = ? AND position = ?';
        $totalStmt = $pdo->prepare($totalSql);
        $totalStmt->execute([$department, $category]);
        $total = $totalStmt->fetchColumn();
        
        // Active count (Present + Late) for this category today
        $activeSql = 'SELECT COUNT(DISTINCT a.employee_id) 
                      FROM attendance a 
                      JOIN users u ON a.employee_id = u.employee_id 
                      WHERE a.date = ? 
                      AND a.time_in_status IN ("Present", "Late")
                      AND u.department = ? 
                      AND u.position = ?
                      AND u.status = "approved"';
        $activeStmt = $pdo->prepare($activeSql);
        $activeStmt->execute([$today, $department, $category]);
        $active = $activeStmt->fetchColumn();
        
        $stats[$category] = [
            'total' => (int)$total,
            'active' => (int)$active
        ];
    }
    
    // Get overall totals for the department
    $totalEmployeesSql = 'SELECT COUNT(*) FROM users WHERE status = "approved" AND department = ?';
    $totalStmt = $pdo->prepare($totalEmployeesSql);
    $totalStmt->execute([$department]);
    $totalEmployees = $totalStmt->fetchColumn();
    
    // Get overall active count (Present + Late) for the department
    $overallActiveSql = 'SELECT COUNT(DISTINCT a.employee_id) 
                         FROM attendance a 
                         JOIN users u ON a.employee_id = u.employee_id 
                         WHERE a.date = ? 
                         AND a.time_in_status IN ("Present", "Late")
                         AND u.department = ?
                         AND u.status = "approved"';
    $overallActiveStmt = $pdo->prepare($overallActiveSql);
    $overallActiveStmt->execute([$today, $department]);
    $overallActive = $overallActiveStmt->fetchColumn();
    
    // Get present count (only Present status)
    $presentSql = 'SELECT COUNT(DISTINCT a.employee_id) 
                   FROM attendance a 
                   JOIN users u ON a.employee_id = u.employee_id 
                   WHERE a.date = ? 
                   AND a.time_in_status = "Present"
                   AND u.department = ?
                   AND u.status = "approved"';
    $presentStmt = $pdo->prepare($presentSql);
    $presentStmt->execute([$today, $department]);
    $present = $presentStmt->fetchColumn();
    
    // Get late count
    $lateSql = 'SELECT COUNT(DISTINCT a.employee_id) 
                FROM attendance a 
                JOIN users u ON a.employee_id = u.employee_id 
                WHERE a.date = ? 
                AND a.time_in_status = "Late"
                AND u.department = ?
                AND u.status = "approved"';
    $lateStmt = $pdo->prepare($lateSql);
    $lateStmt->execute([$today, $department]);
    $late = $lateStmt->fetchColumn();
    
    // Absent count
    $absent = (int)$totalEmployees - (int)$overallActive;
    
    echo json_encode([
        'success' => true,
        'department' => $department,
        'date' => $today,
        'categories' => $stats,
        'overall' => [
            'total' => (int)$totalEmployees,
            'active' => (int)$overallActive,
            'present' => (int)$present,
            'late' => (int)$late,
            'absent' => max(0, $absent)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
