<?php
// hr_analytics_dashboard.php - Comprehensive analytics API for HR dashboard
session_start();
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

// Check if user is logged in and is HR
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

date_default_timezone_set('Asia/Manila');

try {
    // Special endpoint to get all departments for filter
    if (isset($_GET['getDepartments'])) {
        $deptSql = 'SELECT DISTINCT department FROM users WHERE status = "approved" AND role != "hr" ORDER BY department';
        $deptResult = $pdo->query($deptSql);
        $allDepts = [];
        while ($dept = $deptResult->fetch(PDO::FETCH_ASSOC)) {
            $allDepts[] = $dept['department'];
        }
        echo json_encode([
            'success' => true,
            'all_departments' => $allDepts
        ]);
        exit();
    }
    
    // Get filter parameters
    $timeRange = $_GET['timeRange'] ?? 'month';
    $departmentFilter = $_GET['departmentFilter'] ?? 'all';
    $metricType = $_GET['metricType'] ?? 'all';
    
    // Calculate date ranges based on timeRange
    $today = date('Y-m-d');
    $startDate = $today;
    
    switch ($timeRange) {
        case 'today':
            $startDate = $today;
            break;
        case 'week':
            $startDate = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'month':
            $startDate = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'quarter':
            $startDate = date('Y-m-d', strtotime('-90 days'));
            break;
        case 'year':
            $startDate = date('Y-m-d', strtotime('-365 days'));
            break;
    }
    
    // ===== OVERVIEW METRICS =====
    
    // Build WHERE clause for department filter
    $deptWhereClause = '';
    $deptWhereParams = [];
    if ($departmentFilter !== 'all') {
        $deptWhereClause = ' AND u.department = ?';
        $deptWhereParams = [$departmentFilter];
    }
    
    // Total employees (approved)
    $totalEmployeesSql = 'SELECT COUNT(*) FROM users u WHERE u.status = "approved" AND u.role != "hr"' . $deptWhereClause;
    if ($departmentFilter !== 'all') {
        $stmt = $pdo->prepare($totalEmployeesSql);
        $stmt->execute($deptWhereParams);
        $totalEmployees = (int)$stmt->fetchColumn();
    } else {
        $totalEmployees = (int)$pdo->query($totalEmployeesSql)->fetchColumn();
    }
    
    // Active today (Present + Late)
    $activeTodaySql = 'SELECT COUNT(DISTINCT a.employee_id) 
                       FROM attendance a 
                       JOIN users u ON a.employee_id = u.employee_id 
                       WHERE a.date = ? 
                       AND a.time_in_status IN ("Present", "Late")
                       AND u.status = "approved"' . $deptWhereClause;
    $activeStmt = $pdo->prepare($activeTodaySql);
    $activeParams = [$today];
    if ($departmentFilter !== 'all') {
        $activeParams[] = $departmentFilter;
    }
    $activeStmt->execute($activeParams);
    $activeToday = (int)$activeStmt->fetchColumn();
    
    // Attendance rate
    $attendanceRate = $totalEmployees > 0 ? ($activeToday / $totalEmployees) * 100 : 0;
    
    // Pending leave requests
    $pendingLeavesSql = 'SELECT COUNT(*) FROM leave_requests lr
                         JOIN users u ON lr.employee_email = u.email
                         WHERE lr.status = "pending"' . $deptWhereClause;
    if ($departmentFilter !== 'all') {
        $stmt = $pdo->prepare($pendingLeavesSql);
        $stmt->execute($deptWhereParams);
        $pendingLeaves = (int)$stmt->fetchColumn();
    } else {
        $pendingLeaves = (int)$pdo->query($pendingLeavesSql)->fetchColumn();
    }
    
    // Average leave days per employee this month
    $avgLeaveDaysSql = 'SELECT COALESCE(AVG(days_count), 0) as avg_days
                        FROM (
                            SELECT lr.employee_email, 
                                   SUM(DATEDIFF(
                                       STR_TO_DATE(SUBSTRING_INDEX(lr.dates, " to ", -1), "%Y-%m-%d"),
                                       STR_TO_DATE(SUBSTRING_INDEX(lr.dates, " to ", 1), "%Y-%m-%d")
                                   ) + 1) as days_count
                            FROM leave_requests lr
                            JOIN users u ON lr.employee_email = u.email
                            WHERE lr.applied_at >= ?
                            AND lr.status = "approved"' . $deptWhereClause . '
                            GROUP BY lr.employee_email
                        ) as leave_stats';
    $stmt = $pdo->prepare($avgLeaveDaysSql);
    $avgParams = [$startDate];
    if ($departmentFilter !== 'all') {
        $avgParams[] = $departmentFilter;
    }
    $stmt->execute($avgParams);
    $avgLeaveDays = (float)$stmt->fetchColumn();
    
    
    // ===== DEPARTMENT ANALYTICS =====
    
    // Get all unique departments (filtered if needed)
    $deptSql = 'SELECT DISTINCT department FROM users WHERE status = "approved" AND role != "hr"';
    if ($departmentFilter !== 'all') {
        $deptSql .= ' AND department = ?';
    }
    $deptSql .= ' ORDER BY department';
    
    if ($departmentFilter !== 'all') {
        $deptStmt = $pdo->prepare($deptSql);
        $deptStmt->execute([$departmentFilter]);
        $deptResult = $deptStmt;
    } else {
        $deptResult = $pdo->query($deptSql);
    }
    
    $departments = [];
    
    while ($dept = $deptResult->fetch(PDO::FETCH_ASSOC)) {
        $deptName = $dept['department'];
        
        // Employee count
        $empCountSql = 'SELECT COUNT(*) FROM users WHERE department = ? AND status = "approved" AND role != "hr"';
        $empStmt = $pdo->prepare($empCountSql);
        $empStmt->execute([$deptName]);
        $empCount = (int)$empStmt->fetchColumn();
        
        // Active today in department
        $deptActiveSql = 'SELECT COUNT(DISTINCT a.employee_id) 
                          FROM attendance a 
                          JOIN users u ON a.employee_id = u.employee_id 
                          WHERE a.date = ? 
                          AND a.time_in_status IN ("Present", "Late")
                          AND u.department = ?
                          AND u.status = "approved"';
        $deptActiveStmt = $pdo->prepare($deptActiveSql);
        $deptActiveStmt->execute([$today, $deptName]);
        $deptActive = (int)$deptActiveStmt->fetchColumn();
        
        // Department attendance rate
        $deptAttendanceRate = $empCount > 0 ? ($deptActive / $empCount) * 100 : 0;
        
        // Pending leaves in department
        $deptLeavesSql = 'SELECT COUNT(*) FROM leave_requests lr
                          JOIN users u ON lr.employee_email = u.email
                          WHERE u.department = ? AND lr.status = "pending"';
        $deptLeavesStmt = $pdo->prepare($deptLeavesSql);
        $deptLeavesStmt->execute([$deptName]);
        $deptPendingLeaves = (int)$deptLeavesStmt->fetchColumn();
        
        // Average leave days in department (using time range filter)
        $deptAvgLeavesSql = 'SELECT COALESCE(AVG(days_count), 0) as avg_days
                             FROM (
                                 SELECT lr.employee_email, 
                                        SUM(DATEDIFF(
                                            STR_TO_DATE(SUBSTRING_INDEX(lr.dates, " to ", -1), "%Y-%m-%d"),
                                            STR_TO_DATE(SUBSTRING_INDEX(lr.dates, " to ", 1), "%Y-%m-%d")
                                        ) + 1) as days_count
                                 FROM leave_requests lr
                                 JOIN users u ON lr.employee_email = u.email
                                 WHERE u.department = ?
                                 AND lr.applied_at >= ?
                                 AND lr.status = "approved"
                                 GROUP BY lr.employee_email
                             ) as dept_leave_stats';
        $deptAvgLeavesStmt = $pdo->prepare($deptAvgLeavesSql);
        $deptAvgLeavesStmt->execute([$deptName, $startDate]);
        $deptAvgLeaves = (float)$deptAvgLeavesStmt->fetchColumn();
        
        // Calculate risk level
        $riskScore = 0;
        if ($deptAttendanceRate < 70) $riskScore += 3;
        elseif ($deptAttendanceRate < 85) $riskScore += 2;
        elseif ($deptAttendanceRate < 95) $riskScore += 1;
        
        if ($empCount > 0 && ($deptPendingLeaves / $empCount) > 0.3) $riskScore += 2;
        elseif ($empCount > 0 && ($deptPendingLeaves / $empCount) > 0.15) $riskScore += 1;
        
        if ($deptAvgLeaves > 3) $riskScore += 1;
        
        $riskLevel = 'Low';
        if ($riskScore >= 4) $riskLevel = 'High';
        elseif ($riskScore >= 2) $riskLevel = 'Medium';
        
        $departments[] = [
            'department' => $deptName,
            'employee_count' => $empCount,
            'active_today' => $deptActive,
            'attendance_rate' => round($deptAttendanceRate, 2),
            'pending_leaves' => $deptPendingLeaves,
            'avg_leave_days' => round($deptAvgLeaves, 2),
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore
        ];
    }
    
    // Count high risk departments
    $highRiskDepts = count(array_filter($departments, function($d) {
        return $d['risk_level'] === 'High';
    }));
    
    
    // ===== RISK ALERTS =====
    
    $riskAlerts = [];
    
    // Check for departments with low attendance
    $lowAttendanceDepts = array_filter($departments, function($d) {
        return $d['attendance_rate'] < 70;
    });
    
    if (count($lowAttendanceDepts) > 0) {
        $deptNames = array_column($lowAttendanceDepts, 'department');
        $riskAlerts[] = [
            'severity' => 'high',
            'title' => 'Critical Attendance Alert',
            'message' => 'Departments with critically low attendance (<70%): ' . implode(', ', $deptNames)
        ];
    }
    
    // Check for high pending leave requests
    $highPendingDepts = array_filter($departments, function($d) {
        return $d['employee_count'] > 0 && ($d['pending_leaves'] / $d['employee_count']) > 0.3;
    });
    
    if (count($highPendingDepts) > 0) {
        $deptNames = array_column($highPendingDepts, 'department');
        $riskAlerts[] = [
            'severity' => 'medium',
            'title' => 'High Pending Leave Requests',
            'message' => 'Departments with >30% employees having pending leaves: ' . implode(', ', $deptNames)
        ];
    }
    
    // Check for excessive leave usage
    $excessiveLeaveDepts = array_filter($departments, function($d) {
        return $d['avg_leave_days'] > 5;
    });
    
    if (count($excessiveLeaveDepts) > 0) {
        $deptNames = array_column($excessiveLeaveDepts, 'department');
        $riskAlerts[] = [
            'severity' => 'medium',
            'title' => 'Excessive Leave Usage',
            'message' => 'Departments with high average leave days this month: ' . implode(', ', $deptNames)
        ];
    }
    
    // Overall attendance warning
    if ($attendanceRate < 80) {
        $riskAlerts[] = [
            'severity' => 'high',
            'title' => 'Overall Low Attendance',
            'message' => 'Overall attendance rate is ' . round($attendanceRate, 1) . '%. Immediate action recommended.'
        ];
    }
    
    
    // ===== ATTENDANCE TREND (Last 7 days) =====
    
    $attendanceTrend = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        
        // Present count
        $presentSql = 'SELECT COUNT(DISTINCT a.employee_id) 
                       FROM attendance a 
                       JOIN users u ON a.employee_id = u.employee_id 
                       WHERE a.date = ? 
                       AND a.time_in_status = "Present"
                       AND u.status = "approved"' . $deptWhereClause;
        $presentStmt = $pdo->prepare($presentSql);
        $presentParams = [$date];
        if ($departmentFilter !== 'all') {
            $presentParams[] = $departmentFilter;
        }
        $presentStmt->execute($presentParams);
        $present = (int)$presentStmt->fetchColumn();
        
        // Late count
        $lateSql = 'SELECT COUNT(DISTINCT a.employee_id) 
                    FROM attendance a 
                    JOIN users u ON a.employee_id = u.employee_id 
                    WHERE a.date = ? 
                    AND a.time_in_status = "Late"
                    AND u.status = "approved"' . $deptWhereClause;
        $lateStmt = $pdo->prepare($lateSql);
        $lateParams = [$date];
        if ($departmentFilter !== 'all') {
            $lateParams[] = $departmentFilter;
        }
        $lateStmt->execute($lateParams);
        $late = (int)$lateStmt->fetchColumn();
        
        // Absent count
        $absent = $totalEmployees - ($present + $late);
        
        $attendanceTrend[] = [
            'date' => date('M d', strtotime($date)),
            'present' => $present,
            'late' => $late,
            'absent' => max(0, $absent)
        ];
    }
    
    
    // ===== LEAVE TYPES DISTRIBUTION =====
    
    $leaveTypesSql = 'SELECT lr.leave_type, COUNT(*) as count
                      FROM leave_requests lr
                      JOIN users u ON lr.employee_email = u.email
                      WHERE lr.applied_at >= ?' . $deptWhereClause . '
                      GROUP BY lr.leave_type
                      ORDER BY count DESC
                      LIMIT 6';
    $leaveTypesStmt = $pdo->prepare($leaveTypesSql);
    $leaveTypesParams = [$startDate];
    if ($departmentFilter !== 'all') {
        $leaveTypesParams[] = $departmentFilter;
    }
    $leaveTypesStmt->execute($leaveTypesParams);
    $leaveTypes = $leaveTypesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    // ===== POSITION DISTRIBUTION =====
    
    $positionSql = 'SELECT position, COUNT(*) as count
                    FROM users u
                    WHERE u.status = "approved" AND u.role != "hr"' . $deptWhereClause . '
                    GROUP BY position
                    ORDER BY count DESC';
    if ($departmentFilter !== 'all') {
        $positionStmt = $pdo->prepare($positionSql);
        $positionStmt->execute($deptWhereParams);
        $positionDistribution = $positionStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $positionResult = $pdo->query($positionSql);
        $positionDistribution = $positionResult->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    // ===== RESPONSE =====
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'filters' => [
            'timeRange' => $timeRange,
            'departmentFilter' => $departmentFilter,
            'metricType' => $metricType
        ],
        'overview' => [
            'total_employees' => $totalEmployees,
            'active_today' => $activeToday,
            'attendance_rate' => round($attendanceRate, 2),
            'pending_leaves' => $pendingLeaves,
            'high_risk_departments' => $highRiskDepts,
            'avg_leave_days_per_employee' => round($avgLeaveDays, 2)
        ],
        'departments' => $departments,
        'risk_alerts' => $riskAlerts,
        'attendance_trend' => $attendanceTrend,
        'leave_types' => $leaveTypes,
        'position_distribution' => $positionDistribution
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
