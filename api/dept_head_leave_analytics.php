<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

// Department Head Leave Analytics API with Employee-level insights
// Returns analytics filtered by department head's department

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$deptHeadEmail = $_SESSION['email'];
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

try {
    // Get department head's department
    $deptStmt = $pdo->prepare("SELECT department FROM users WHERE email = ?");
    $deptStmt->execute([$deptHeadEmail]);
    $deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$deptRow || !$deptRow['department']) {
        echo json_encode(['success' => false, 'error' => 'Department not found']);
        exit;
    }
    
    $department = $deptRow['department'];

    // Fetch leave requests for the department in specified month
    $stmt = $pdo->prepare("
        SELECT 
            lr.*,
            u.firstname,
            u.lastname,
            u.department,
            u.position
        FROM leave_requests lr
        INNER JOIN users u ON lr.employee_email = u.email
        WHERE u.department = ? 
        AND YEAR(lr.applied_at) = ? 
        AND MONTH(lr.applied_at) = ?
        ORDER BY lr.applied_at DESC
    ");
    $stmt->execute([$department, $year, $month]);
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Previous month for trends
    $prevMonth = $month - 1;
    $prevYear = $year;
    if ($prevMonth < 1) {
        $prevMonth = 12;
        $prevYear--;
    }
    
    $prevStmt = $pdo->prepare("
        SELECT lr.*, u.firstname, u.lastname
        FROM leave_requests lr
        INNER JOIN users u ON lr.employee_email = u.email
        WHERE u.department = ? 
        AND YEAR(lr.applied_at) = ? 
        AND MONTH(lr.applied_at) = ?
    ");
    $prevStmt->execute([$department, $prevYear, $prevMonth]);
    $prevLeaves = $prevStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate analytics
    $analytics = [
        'month' => $month,
        'year' => $year,
        'department' => $department,
        'total_requests' => count($leaves),
        'by_status' => [
            'approved' => 0,
            'pending' => 0,
            'declined' => 0
        ],
        'by_type' => [],
        'by_employee' => [],
        'by_category' => [],
        'peak_days' => [],
        'trends' => [],
        'decision_support' => []
    ];

    // Count by status, type, and employee
    foreach ($leaves as $leave) {
        $status = strtolower($leave['status'] ?? 'pending');
        if (isset($analytics['by_status'][$status])) {
            $analytics['by_status'][$status]++;
        }
        
        // By type
        $type = $leave['leave_type'] ?? 'Unknown';
        if (!isset($analytics['by_type'][$type])) {
            $analytics['by_type'][$type] = 0;
        }
        $analytics['by_type'][$type]++;
        
        // By employee
        $empName = trim(($leave['firstname'] ?? '') . ' ' . ($leave['lastname'] ?? ''));
        if (!$empName) $empName = $leave['employee_email'] ?? 'Unknown';
        
        if (!isset($analytics['by_employee'][$empName])) {
            $analytics['by_employee'][$empName] = [
                'total' => 0,
                'approved' => 0,
                'pending' => 0,
                'declined' => 0
            ];
        }
        $analytics['by_employee'][$empName]['total']++;
        $analytics['by_employee'][$empName][$status]++;
        
        // By category (position)
        $cat = $leave['position'] ?? 'Unknown';
        if (!isset($analytics['by_category'][$cat])) {
            $analytics['by_category'][$cat] = 0;
        }
        $analytics['by_category'][$cat]++;
    }

    // Sort types by frequency
    arsort($analytics['by_type']);
    
    // Calculate peak days
    $dayCount = [];
    foreach ($leaves as $leave) {
        if ($leave['status'] !== 'approved') continue;
        
        $dates = $leave['dates'] ?? '';
        preg_match_all('/\d{4}-\d{2}-\d{2}/', $dates, $matches);
        
        if (count($matches[0]) >= 2) {
            $start = new DateTime($matches[0][0]);
            $end = new DateTime($matches[0][1]);
            
            while ($start <= $end) {
                $dateKey = $start->format('Y-m-d');
                if (!isset($dayCount[$dateKey])) {
                    $dayCount[$dateKey] = 0;
                }
                $dayCount[$dateKey]++;
                $start->modify('+1 day');
            }
        }
    }
    
    arsort($dayCount);
    $analytics['peak_days'] = array_slice($dayCount, 0, 31, true); // Full month

    // Calculate trends
    $prevTotal = count($prevLeaves);
    $currentTotal = count($leaves);
    $trend = $prevTotal > 0 ? (($currentTotal - $prevTotal) / $prevTotal) * 100 : 0;
    
    $analytics['trends'] = [
        'current_month' => $currentTotal,
        'previous_month' => $prevTotal,
        'change_percentage' => round($trend, 1),
        'direction' => $trend > 0 ? 'increase' : ($trend < 0 ? 'decrease' : 'stable')
    ];

    // Calculate approval rate
    $totalProcessed = $analytics['by_status']['approved'] + $analytics['by_status']['declined'];
    $approvalRate = $totalProcessed > 0 ? ($analytics['by_status']['approved'] / $totalProcessed) * 100 : 0;

    // === DECISION SUPPORT SYSTEM for Department Head ===
    $dss = [];

    // 1. Employee-specific alerts
    foreach ($analytics['by_employee'] as $empName => $empData) {
        if ($empData['total'] >= 3) {
            $dss[] = [
                'type' => 'info',
                'priority' => 'medium',
                'category' => 'employee',
                'title' => 'Frequent Leave Requests',
                'message' => "$empName has submitted {$empData['total']} leave requests this month",
                'recommendation' => 'Monitor for potential burnout or personal issues. Consider a check-in conversation.'
            ];
        }
    }

    // 2. Staffing Alert for department
    if (count($dayCount) > 0) {
        $maxLeaves = max($dayCount);
        if ($maxLeaves >= 3) {
            $criticalDays = array_filter($dayCount, function($count) use ($maxLeaves) {
                return $count >= ($maxLeaves * 0.7);
            });
            
            $dss[] = [
                'type' => 'warning',
                'priority' => 'high',
                'category' => 'staffing',
                'title' => 'Department Staffing Alert',
                'message' => count($criticalDays) . " day(s) with {$maxLeaves}+ employees on leave from your department",
                'recommendation' => 'Coordinate workload distribution and consider deferring non-critical tasks during these dates.',
                'dates' => array_keys($criticalDays)
            ];
        }
    }

    // 3. Pending approvals
    if ($analytics['by_status']['pending'] > 0) {
        $dss[] = [
            'type' => 'warning',
            'priority' => 'high',
            'category' => 'workflow',
            'title' => 'Pending Department Approvals',
            'message' => $analytics['by_status']['pending'] . ' leave requests from your team awaiting your approval',
            'recommendation' => 'Review and approve/decline pending requests promptly to help employees plan their schedules.'
        ];
    }

    // 4. Trend analysis for department
    if (abs($trend) > 30 && $prevTotal > 2) {
        $dss[] = [
            'type' => $trend > 0 ? 'warning' : 'info',
            'priority' => 'medium',
            'category' => 'trend',
            'title' => 'Department Leave Trend Change',
            'message' => abs(round($trend, 1)) . '% ' . ($trend > 0 ? 'increase' : 'decrease') . ' in your department',
            'recommendation' => $trend > 0 ? 
                'Investigate if increased leave requests indicate team workload or morale issues.' :
                'Decreased leave requests may indicate staff reluctant to take time off - encourage work-life balance.'
        ];
    }

    $analytics['decision_support'] = $dss;
    $analytics['approval_rate'] = round($approvalRate, 1);

    // Generate interpretations
    $analytics['interpretations'] = [
        'status' => generateStatusInterpretation($analytics['by_status'], $currentTotal, $department),
        'type' => generateTypeInterpretation($analytics['by_type'], $currentTotal),
        'trend' => generateTrendInterpretation($analytics['trends'], $department),
        'employee' => generateEmployeeInterpretation($analytics['by_employee'], $department)
    ];

    echo json_encode([
        'success' => true,
        'analytics' => $analytics
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'details' => $e->getMessage()
    ]);
}

// Interpretation generators
function generateStatusInterpretation($byStatus, $total, $dept) {
    if ($total === 0) {
        return "No leave requests from $dept this month.";
    }
    
    $approved = $byStatus['approved'];
    $pending = $byStatus['pending'];
    $approvedPct = round(($approved / $total) * 100);
    
    return "Your department has $total leave request" . ($total > 1 ? 's' : '') . " this month. $approved approved ($approvedPct%), $pending pending. " .
           ($pending > $approved ? "High pending queue - prompt review recommended." : "Processing is up to date.");
}

function generateTypeInterpretation($byType, $total) {
    if (empty($byType)) {
        return 'No leave type data available.';
    }
    
    $topType = array_key_first($byType);
    $topCount = $byType[$topType];
    
    return "$topType is most common with $topCount request" . ($topCount > 1 ? 's' : '') . ". Understanding leave patterns helps with team scheduling.";
}

function generateTrendInterpretation($trends, $dept) {
    $change = $trends['change_percentage'];
    $direction = $trends['direction'];
    $current = $trends['current_month'];
    $previous = $trends['previous_month'];
    
    if ($direction === 'stable') {
        return "$dept leave requests stable at $current (vs $previous last month).";
    }
    
    $absChange = abs($change);
    return "$dept leave requests $direction by $absChange% ($current vs $previous last month). " . 
           ($direction === 'increase' ? "Monitor for potential team fatigue." : "Staff may be accumulating leave credits.");
}

function generateEmployeeInterpretation($byEmployee, $dept) {
    if (empty($byEmployee)) {
        return "No employee leave data for $dept.";
    }
    
    $empCounts = array_map(function($e) { return $e['total']; }, $byEmployee);
    arsort($empCounts);
    $topEmp = array_key_first($empCounts);
    $topCount = $empCounts[$topEmp];
    $totalEmps = count($byEmployee);
    
    return "$totalEmps team member" . ($totalEmps > 1 ? 's' : '') . " requested leave. " .
           ($topCount > 1 ? "$topEmp has the most requests ($topCount)." : "Leave requests are evenly distributed.");
}
