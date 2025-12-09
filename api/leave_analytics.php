<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

// Enhanced Leave Analytics API with Decision Support System
// Returns comprehensive analytics and intelligent recommendations

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

try {
    // Fetch leave requests for the specified month
    $stmt = $pdo->prepare("
        SELECT 
            lr.*,
            u.firstname,
            u.lastname,
            u.department,
            u.position
        FROM leave_requests lr
        LEFT JOIN users u ON lr.employee_email = u.email
        WHERE YEAR(lr.applied_at) = ? AND MONTH(lr.applied_at) = ?
        ORDER BY lr.applied_at DESC
    ");
    $stmt->execute([$year, $month]);
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Also get previous month for trend comparison
    $prevMonth = $month - 1;
    $prevYear = $year;
    if ($prevMonth < 1) {
        $prevMonth = 12;
        $prevYear--;
    }
    
    $prevStmt = $pdo->prepare("
        SELECT lr.*, u.department, u.position
        FROM leave_requests lr
        LEFT JOIN users u ON lr.employee_email = u.email
        WHERE YEAR(lr.applied_at) = ? AND MONTH(lr.applied_at) = ?
    ");
    $prevStmt->execute([$prevYear, $prevMonth]);
    $prevLeaves = $prevStmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate analytics
    $analytics = [
        'month' => $month,
        'year' => $year,
        'total_requests' => count($leaves),
        'by_status' => [
            'approved' => 0,
            'pending' => 0,
            'declined' => 0
        ],
        'by_type' => [],
        'by_department' => [],
        'by_category' => [],
        'peak_days' => [],
        'trends' => [],
        'decision_support' => []
    ];

    // Count by status
    foreach ($leaves as $leave) {
        $status = strtolower($leave['status'] ?? 'pending');
        if (isset($analytics['by_status'][$status])) {
            $analytics['by_status'][$status]++;
        }
        
        // Count by type
        $type = $leave['leave_type'] ?? 'Unknown';
        if (!isset($analytics['by_type'][$type])) {
            $analytics['by_type'][$type] = 0;
        }
        $analytics['by_type'][$type]++;
        
        // Count by department (skip if department is not set)
        $dept = $leave['department'] ?? null;
        if ($dept && trim($dept) !== '') {
            if (!isset($analytics['by_department'][$dept])) {
                $analytics['by_department'][$dept] = [
                    'total' => 0,
                    'approved' => 0,
                    'pending' => 0,
                    'declined' => 0
                ];
            }
            $analytics['by_department'][$dept]['total']++;
            $analytics['by_department'][$dept][$status]++;
        }
        
        // Count by employee category (position)
        $cat = $leave['position'] ?? 'Unknown';
        if (!isset($analytics['by_category'][$cat])) {
            $analytics['by_category'][$cat] = 0;
        }
        $analytics['by_category'][$cat]++;
    }

    // Sort types by frequency
    arsort($analytics['by_type']);
    
    // Calculate peak days (days with most leaves)
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
    $analytics['peak_days'] = array_slice($dayCount, 0, 10, true);

    // Calculate trends (compare with previous month)
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
    
    // === DECISION SUPPORT SYSTEM ===
    $dss = [];

    // 1. Staffing Alert: Check for peak leave days
    if (count($dayCount) > 0) {
        $maxLeaves = max($dayCount);
        $criticalDays = array_filter($dayCount, function($count) use ($maxLeaves) {
            return $count >= ($maxLeaves * 0.7); // 70% of peak
        });
        
        if ($maxLeaves >= 5) {
            $dss[] = [
                'type' => 'warning',
                'priority' => 'high',
                'category' => 'staffing',
                'title' => 'Critical Staffing Alert',
                'message' => count($criticalDays) . ' day(s) with high leave volume (peak: ' . $maxLeaves . ' employees)',
                'recommendation' => 'Consider limiting additional leave approvals for these dates or arrange backup staff.',
                'dates' => array_keys($criticalDays)
            ];
        }
    }

    // 2. Pending Queue Alert
    if ($analytics['by_status']['pending'] > 10) {
        $dss[] = [
            'type' => 'warning',
            'priority' => 'medium',
            'category' => 'workflow',
            'title' => 'Large Pending Queue',
            'message' => $analytics['by_status']['pending'] . ' leave requests awaiting approval',
            'recommendation' => 'Review and process pending requests to avoid employee dissatisfaction and operational delays.'
        ];
    }

    // 3. Department Imbalance
    if (count($analytics['by_department']) > 1) {
        $deptCounts = array_map(function($d) { return $d['total']; }, $analytics['by_department']);
        $avgDept = array_sum($deptCounts) / count($deptCounts);
        $maxDept = max($deptCounts);
        $maxDeptName = array_search($maxDept, $deptCounts);
        
        if ($maxDept > ($avgDept * 1.5)) {
            $dss[] = [
                'type' => 'info',
                'priority' => 'medium',
                'category' => 'department',
                'title' => 'Department Leave Imbalance',
                'message' => $maxDeptName . ' has significantly higher leave requests (' . $maxDept . ' vs avg ' . round($avgDept, 1) . ')',
                'recommendation' => 'Investigate potential issues: workload, morale, or seasonal factors affecting this department.'
            ];
        }
    }

    // 4. Approval Rate Analysis
    if ($approvalRate < 70 && $totalProcessed > 5) {
        $dss[] = [
            'type' => 'warning',
            'priority' => 'high',
            'category' => 'policy',
            'title' => 'Low Approval Rate',
            'message' => 'Only ' . round($approvalRate, 1) . '% of leave requests are being approved',
            'recommendation' => 'Review leave policies and denial reasons. High rejection rates may indicate policy issues or insufficient leave credits.'
        ];
    } elseif ($approvalRate > 95 && $totalProcessed > 10) {
        $dss[] = [
            'type' => 'info',
            'priority' => 'low',
            'category' => 'policy',
            'title' => 'High Approval Rate',
            'message' => round($approvalRate, 1) . '% approval rate indicates flexible leave policy',
            'recommendation' => 'Ensure high approval rate doesn\'t compromise operational continuity. Monitor staffing levels.'
        ];
    }

    // 5. Trend Analysis
    if (abs($trend) > 30 && $prevTotal > 3) {
        $dss[] = [
            'type' => $trend > 0 ? 'warning' : 'info',
            'priority' => abs($trend) > 50 ? 'high' : 'medium',
            'category' => 'trend',
            'title' => 'Significant Leave Trend Change',
            'message' => abs(round($trend, 1)) . '% ' . ($trend > 0 ? 'increase' : 'decrease') . ' compared to previous month',
            'recommendation' => $trend > 0 ? 
                'Investigate reasons for increased leave requests. May indicate burnout, seasonal factors, or upcoming holidays.' :
                'Decreased leave requests may indicate understaffing concerns or unused leave credits accumulating.'
        ];
    }

    // 6. Leave Type Insights
    if (isset($analytics['by_type']['Sick Leave'])) {
        $sickLeavePercent = ($analytics['by_type']['Sick Leave'] / $currentTotal) * 100;
        if ($sickLeavePercent > 40) {
            $dss[] = [
                'type' => 'warning',
                'priority' => 'high',
                'category' => 'health',
                'title' => 'High Sick Leave Rate',
                'message' => round($sickLeavePercent, 1) . '% of requests are sick leave',
                'recommendation' => 'Consider wellness programs, workplace safety review, or health insurance benefits assessment.'
            ];
        }
    }

    // 7. Seasonal Pattern Detection
    $summerMonths = [3, 4, 5]; // March-May
    $holidayMonths = [11, 12]; // November-December
    
    if (in_array($month, $summerMonths) && $currentTotal > $prevTotal) {
        $dss[] = [
            'type' => 'info',
            'priority' => 'low',
            'category' => 'seasonal',
            'title' => 'Summer Leave Pattern',
            'message' => 'Increased leave requests typical for summer months',
            'recommendation' => 'Plan ahead for summer peak: consider staggered approvals and ensure adequate coverage.'
        ];
    }
    
    if (in_array($month, $holidayMonths) && $currentTotal > $prevTotal) {
        $dss[] = [
            'type' => 'info',
            'priority' => 'low',
            'category' => 'seasonal',
            'title' => 'Holiday Season Pattern',
            'message' => 'Increased leave requests typical for holiday season',
            'recommendation' => 'Coordinate with departments to ensure essential services remain operational during holidays.'
        ];
    }

    // 8. Efficiency Metric
    $avgProcessingTime = 0; // Could calculate if we track approval timestamps
    if ($analytics['by_status']['pending'] === 0 && $currentTotal > 0) {
        $dss[] = [
            'type' => 'success',
            'priority' => 'low',
            'category' => 'efficiency',
            'title' => 'Excellent Processing Efficiency',
            'message' => 'All leave requests have been processed',
            'recommendation' => 'Maintain current workflow efficiency. No action required.'
        ];
    }

    $analytics['decision_support'] = $dss;
    $analytics['approval_rate'] = round($approvalRate, 1);

    // Generate interpretations for each metric
    $analytics['interpretations'] = [
        'status' => generateStatusInterpretation($analytics['by_status'], $currentTotal),
        'type' => generateTypeInterpretation($analytics['by_type'], $currentTotal),
        'peak_days' => generatePeakDaysInterpretation($analytics['peak_days']),
        'trend' => generateTrendInterpretation($analytics['trends']),
        'department' => generateDepartmentInterpretation($analytics['by_department'])
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
function generateStatusInterpretation($byStatus, $total) {
    $approved = $byStatus['approved'];
    $pending = $byStatus['pending'];
    $declined = $byStatus['declined'];
    
    if ($total === 0) {
        return 'No leave requests submitted this month.';
    }
    
    $approvedPct = round(($approved / $total) * 100);
    $pendingPct = round(($pending / $total) * 100);
    
    $interpretation = "Out of $total requests, $approved were approved ($approvedPct%), $pending pending ($pendingPct%), and $declined declined. ";
    
    if ($pendingPct > 30) {
        $interpretation .= "High pending rate suggests need for faster processing.";
    } elseif ($approvedPct > 80) {
        $interpretation .= "High approval rate indicates good leave management.";
    } else {
        $interpretation .= "Processing rate is within normal range.";
    }
    
    return $interpretation;
}

function generateTypeInterpretation($byType, $total) {
    if (empty($byType)) {
        return 'No leave type data available.';
    }
    
    $topType = array_key_first($byType);
    $topCount = $byType[$topType];
    $topPct = round(($topCount / $total) * 100);
    
    return "$topType is the most common leave type with $topCount requests ($topPct%). This helps identify employee needs and plan resources accordingly.";
}

function generatePeakDaysInterpretation($peakDays) {
    if (empty($peakDays)) {
        return 'No peak leave days identified - leave requests are well distributed.';
    }
    
    $dates = array_keys($peakDays);
    $counts = array_values($peakDays);
    $maxCount = max($counts);
    $peakDate = $dates[0];
    
    $criticalDays = count(array_filter($counts, function($c) use ($maxCount) {
        return $c >= ($maxCount * 0.7);
    }));
    
    return "Peak leave day is $peakDate with $maxCount employees on leave. $criticalDays day(s) have high leave volume requiring staffing attention.";
}

function generateTrendInterpretation($trends) {
    $change = $trends['change_percentage'];
    $direction = $trends['direction'];
    $current = $trends['current_month'];
    $previous = $trends['previous_month'];
    
    if ($direction === 'stable') {
        return "Leave requests remain stable at $current compared to $previous last month. Indicates consistent leave patterns.";
    }
    
    $absChange = abs($change);
    $dirText = $direction === 'increase' ? 'increased' : 'decreased';
    
    return "Leave requests $dirText by $absChange% ($current vs $previous). " . 
           ($direction === 'increase' ? 
            "Monitor for potential staffing challenges." : 
            "May indicate unused leave credits or staffing concerns.");
}

function generateDepartmentInterpretation($byDept) {
    if (empty($byDept)) {
        return 'No department data available.';
    }
    
    $deptCounts = array_map(function($d) { return $d['total']; }, $byDept);
    arsort($deptCounts);
    $topDept = array_key_first($deptCounts);
    $topCount = $deptCounts[$topDept];
    
    return "$topDept has the highest leave volume with $topCount requests. Ensure adequate coverage and consider department-specific leave policies.";
}
