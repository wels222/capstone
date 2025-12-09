<?php
require_once __DIR__ . '/_bootstrap.php';
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
    $selectedMonth = $_GET['month'] ?? null;
    $selectedYear = $_GET['year'] ?? date('Y');
    
    // Calculate EXACT date ranges based on timeRange
    // This ensures accurate filtering - only data within the selected period is shown
    $today = date('Y-m-d');
    $startDate = $today;
    $endDate = $today;
    
    switch ($timeRange) {
        case 'today':
            $startDate = $today;
            $endDate = $today;
            break;
        case 'week':
            $startDate = date('Y-m-d', strtotime('monday this week'));
            $endDate = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'month':
            // Use selected month if provided, otherwise use current month
            if ($selectedMonth !== null && $selectedYear !== null) {
                $monthStr = str_pad($selectedMonth, 2, '0', STR_PAD_LEFT);
                $startDate = $selectedYear . '-' . $monthStr . '-01';
                $endDate = date('Y-m-t', strtotime($startDate));
            } else {
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
            }
            break;
        case 'quarter':
            $currentMonth = (int)date('n');
            $quarterStartMonth = (floor(($currentMonth - 1) / 3) * 3) + 1;
            $startDate = date('Y-' . str_pad($quarterStartMonth, 2, '0', STR_PAD_LEFT) . '-01');
            $endDate = date('Y-m-t', strtotime($startDate . ' +2 months'));
            break;
        case 'year':
            $startDate = date('Y-01-01');
            $endDate = date('Y-12-31');
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
    
    // Active in selected period (Present + Late)
    // For "today", use today's date. For other periods, calculate average or use most recent data
    $activePeriodSql = 'SELECT COUNT(DISTINCT a.employee_id) 
                       FROM attendance a 
                       JOIN users u ON a.employee_id = u.employee_id 
                       WHERE a.date >= ? AND a.date <= ?
                       AND a.time_in_status IN ("Present", "Late")
                       AND u.status = "approved"' . $deptWhereClause;
    $activeStmt = $pdo->prepare($activePeriodSql);
    $activeParams = [$startDate, $endDate];
    if ($departmentFilter !== 'all') {
        $activeParams[] = $departmentFilter;
    }
    $activeStmt->execute($activeParams);
    $activePeriod = (int)$activeStmt->fetchColumn();
    
    // Calculate attendance rate for the period
    // Get total attendance records in period
    $totalAttendanceSql = 'SELECT COUNT(DISTINCT CONCAT(a.employee_id, "-", a.date)) 
                          FROM attendance a 
                          JOIN users u ON a.employee_id = u.employee_id 
                          WHERE a.date >= ? AND a.date <= ?
                          AND u.status = "approved"' . $deptWhereClause;
    $totalAttendanceStmt = $pdo->prepare($totalAttendanceSql);
    $totalAttendanceParams = [$startDate, $endDate];
    if ($departmentFilter !== 'all') {
        $totalAttendanceParams[] = $departmentFilter;
    }
    $totalAttendanceStmt->execute($totalAttendanceParams);
    $totalAttendanceRecords = (int)$totalAttendanceStmt->fetchColumn();
    
    // Calculate expected attendance (employees * working days in range)
    $workingDaysSql = 'SELECT COUNT(DISTINCT date) FROM attendance WHERE date >= ? AND date <= ?';
    $workingDaysStmt = $pdo->prepare($workingDaysSql);
    $workingDaysStmt->execute([$startDate, $endDate]);
    $workingDays = (int)$workingDaysStmt->fetchColumn();
    
    $expectedAttendance = $totalEmployees * max($workingDays, 1);
    $attendanceRate = $expectedAttendance > 0 ? ($totalAttendanceRecords / $expectedAttendance) * 100 : 0;
    
    // Pending leave requests within time range
    $pendingLeavesSql = 'SELECT COUNT(*) FROM leave_requests lr
                         JOIN users u ON lr.employee_email = u.email
                         WHERE lr.status = "pending"
                         AND DATE(lr.applied_at) >= ? AND DATE(lr.applied_at) <= ?' . $deptWhereClause;
    $stmt = $pdo->prepare($pendingLeavesSql);
    $pendingParams = [$startDate, $endDate];
    if ($departmentFilter !== 'all') {
        $pendingParams[] = $departmentFilter;
    }
    $stmt->execute($pendingParams);
    $pendingLeaves = (int)$stmt->fetchColumn();
    
    // Average leave days per employee in selected time range
    $avgLeaveDaysSql = 'SELECT COALESCE(AVG(days_count), 0) as avg_days
                        FROM (
                            SELECT lr.employee_email, 
                                   SUM(DATEDIFF(
                                       STR_TO_DATE(SUBSTRING_INDEX(lr.dates, " to ", -1), "%Y-%m-%d"),
                                       STR_TO_DATE(SUBSTRING_INDEX(lr.dates, " to ", 1), "%Y-%m-%d")
                                   ) + 1) as days_count
                            FROM leave_requests lr
                            JOIN users u ON lr.employee_email = u.email
                            WHERE DATE(lr.applied_at) >= ? AND DATE(lr.applied_at) <= ?
                            AND lr.status = "approved"' . $deptWhereClause . '
                            GROUP BY lr.employee_email
                        ) as leave_stats';
    $stmt = $pdo->prepare($avgLeaveDaysSql);
    $avgParams = [$startDate, $endDate];
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
        
        // Active in period in department
        $deptActiveSql = 'SELECT COUNT(DISTINCT a.employee_id) 
                          FROM attendance a 
                          JOIN users u ON a.employee_id = u.employee_id 
                          WHERE a.date >= ? AND a.date <= ?
                          AND a.time_in_status IN ("Present", "Late")
                          AND u.department = ?
                          AND u.status = "approved"';
        $deptActiveStmt = $pdo->prepare($deptActiveSql);
        $deptActiveStmt->execute([$startDate, $endDate, $deptName]);
        $deptActive = (int)$deptActiveStmt->fetchColumn();
        
        // Department attendance rate (based on actual attendance records in period)
        $deptTotalAttendanceSql = 'SELECT COUNT(DISTINCT CONCAT(a.employee_id, "-", a.date)) 
                                   FROM attendance a 
                                   JOIN users u ON a.employee_id = u.employee_id 
                                   WHERE a.date >= ? AND a.date <= ?
                                   AND u.department = ?
                                   AND u.status = "approved"';
        $deptTotalAttendanceStmt = $pdo->prepare($deptTotalAttendanceSql);
        $deptTotalAttendanceStmt->execute([$startDate, $endDate, $deptName]);
        $deptTotalAttendanceRecords = (int)$deptTotalAttendanceStmt->fetchColumn();
        
        $deptExpectedAttendance = $empCount * max($workingDays, 1);
        $deptAttendanceRate = $deptExpectedAttendance > 0 ? ($deptTotalAttendanceRecords / $deptExpectedAttendance) * 100 : 0;
        
        // Pending leaves in department within time range
        $deptLeavesSql = 'SELECT COUNT(*) FROM leave_requests lr
                          JOIN users u ON lr.employee_email = u.email
                          WHERE u.department = ? 
                          AND lr.status = "pending"
                          AND DATE(lr.applied_at) >= ? AND DATE(lr.applied_at) <= ?';
        $deptLeavesStmt = $pdo->prepare($deptLeavesSql);
        $deptLeavesStmt->execute([$deptName, $startDate, $endDate]);
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
                                 AND DATE(lr.applied_at) >= ? AND DATE(lr.applied_at) <= ?
                                 AND lr.status = "approved"
                                 GROUP BY lr.employee_email
                             ) as dept_leave_stats';
        $deptAvgLeavesStmt = $pdo->prepare($deptAvgLeavesSql);
        $deptAvgLeavesStmt->execute([$deptName, $startDate, $endDate]);
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
    
    
    // ===== ATTENDANCE TREND =====
    $attendanceTrend = [];
    
    if ($timeRange === 'year') {
        // For year view, show all 12 months
        $year = $selectedYear ?? date('Y');
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            
            // Present count for the month
            $presentSql = 'SELECT COUNT(DISTINCT CONCAT(a.employee_id, "-", a.date)) 
                           FROM attendance a 
                           JOIN users u ON a.employee_id = u.employee_id 
                           WHERE a.date >= ? AND a.date <= ?
                           AND a.time_in_status = "Present"
                           AND u.status = "approved"' . $deptWhereClause;
            $presentStmt = $pdo->prepare($presentSql);
            $presentParams = [$monthStart, $monthEnd];
            if ($departmentFilter !== 'all') {
                $presentParams[] = $departmentFilter;
            }
            $presentStmt->execute($presentParams);
            $present = (int)$presentStmt->fetchColumn();
            
            // Late count for the month
            $lateSql = 'SELECT COUNT(DISTINCT CONCAT(a.employee_id, "-", a.date)) 
                        FROM attendance a 
                        JOIN users u ON a.employee_id = u.employee_id 
                        WHERE a.date >= ? AND a.date <= ?
                        AND a.time_in_status = "Late"
                        AND u.status = "approved"' . $deptWhereClause;
            $lateStmt = $pdo->prepare($lateSql);
            $lateParams = [$monthStart, $monthEnd];
            if ($departmentFilter !== 'all') {
                $lateParams[] = $departmentFilter;
            }
            $lateStmt->execute($lateParams);
            $late = (int)$lateStmt->fetchColumn();
            
            // Count working days in this month
            $workingDaysInMonth = 0;
            $checkDate = new DateTime($monthStart);
            $endCheck = new DateTime($monthEnd);
            while ($checkDate <= $endCheck) {
                $dayOfWeek = $checkDate->format('N');
                if ($dayOfWeek < 6) { // Monday = 1, Sunday = 7
                    $workingDaysInMonth++;
                }
                $checkDate->modify('+1 day');
            }
            
            $expectedAttendanceMonth = $totalEmployees * $workingDaysInMonth;
            $absent = max(0, $expectedAttendanceMonth - ($present + $late));
            
            $attendanceTrend[] = [
                'date' => date('M', strtotime($monthStart)),
                'present' => $present,
                'late' => $late,
                'absent' => $absent
            ];
        }
    } elseif ($timeRange === 'quarter') {
        // For quarter view, show each month in the quarter (3 months)
        $currentDate = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        
        while ($currentDate <= $endDateTime) {
            $monthStart = $currentDate->format('Y-m-01');
            $monthEnd = $currentDate->format('Y-m-t');
            
            // Present count for the month
            $presentSql = 'SELECT COUNT(DISTINCT CONCAT(a.employee_id, "-", a.date)) 
                           FROM attendance a 
                           JOIN users u ON a.employee_id = u.employee_id 
                           WHERE a.date >= ? AND a.date <= ?
                           AND a.time_in_status = "Present"
                           AND u.status = "approved"' . $deptWhereClause;
            $presentStmt = $pdo->prepare($presentSql);
            $presentParams = [$monthStart, $monthEnd];
            if ($departmentFilter !== 'all') {
                $presentParams[] = $departmentFilter;
            }
            $presentStmt->execute($presentParams);
            $present = (int)$presentStmt->fetchColumn();
            
            // Late count for the month
            $lateSql = 'SELECT COUNT(DISTINCT CONCAT(a.employee_id, "-", a.date)) 
                        FROM attendance a 
                        JOIN users u ON a.employee_id = u.employee_id 
                        WHERE a.date >= ? AND a.date <= ?
                        AND a.time_in_status = "Late"
                        AND u.status = "approved"' . $deptWhereClause;
            $lateStmt = $pdo->prepare($lateSql);
            $lateParams = [$monthStart, $monthEnd];
            if ($departmentFilter !== 'all') {
                $lateParams[] = $departmentFilter;
            }
            $lateStmt->execute($lateParams);
            $late = (int)$lateStmt->fetchColumn();
            
            // Count working days
            $workingDaysInMonth = 0;
            $checkDate = new DateTime($monthStart);
            $endCheck = new DateTime($monthEnd);
            while ($checkDate <= $endCheck) {
                $dayOfWeek = $checkDate->format('N');
                if ($dayOfWeek < 6) {
                    $workingDaysInMonth++;
                }
                $checkDate->modify('+1 day');
            }
            
            $expectedAttendanceMonth = $totalEmployees * $workingDaysInMonth;
            $absent = max(0, $expectedAttendanceMonth - ($present + $late));
            
            $attendanceTrend[] = [
                'date' => date('M Y', strtotime($monthStart)),
                'present' => $present,
                'late' => $late,
                'absent' => $absent
            ];
            
            $currentDate->modify('first day of next month');
        }
    } else {
        // For today, week, and month - show all days in the range
        $currentDate = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        $interval = new DateInterval('P1D');
        $dateRange = new DatePeriod($currentDate, $interval, $endDateTime->modify('+1 day'));
        
        $allDates = [];
        foreach ($dateRange as $date) {
            $allDates[] = $date->format('Y-m-d');
        }
        
        foreach ($allDates as $date) {
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
    }
    
    
    // ===== LEAVE TYPES DISTRIBUTION =====
    
    $leaveTypesSql = 'SELECT lr.leave_type, COUNT(*) as count
                      FROM leave_requests lr
                      JOIN users u ON lr.employee_email = u.email
                      WHERE DATE(lr.applied_at) >= ? AND DATE(lr.applied_at) <= ?' . $deptWhereClause . '
                      GROUP BY lr.leave_type
                      ORDER BY count DESC
                      LIMIT 6';
    $leaveTypesStmt = $pdo->prepare($leaveTypesSql);
    $leaveTypesParams = [$startDate, $endDate];
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
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ],
        'overview' => [
            'total_employees' => $totalEmployees,
            'active_today' => $activePeriod,
            'attendance_rate' => round($attendanceRate, 2),
            'pending_leaves' => $pendingLeaves,
            'high_risk_departments' => $highRiskDepts,
            'avg_leave_days_per_employee' => round($avgLeaveDays, 2),
            'working_days' => $workingDays
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
