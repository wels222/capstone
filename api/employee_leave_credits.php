<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

// Fixed entitlements (in days); months converted as 30 days per month
$ENTITLEMENTS = [
    'Vacation Leave' => 15,
    'Mandatory / Forced Leave' => 5,
    'Sick Leave' => 15,
    'Maternity Leave' => 105,
    'Paternity Leave' => 7,
    'Special Privilege Leave' => 3,
    'Solo Parent Leave' => 7,
    'Study Leave' => 180, // 6 months ~ 180 days
    '10-Day VAWC Leave' => 10,
    'Rehabilitation Leave' => 180, // 6 months ~ 180 days
    'Special Leave Benefits for Women' => 60,
    'Special Emergency (Calamity) Leave' => 5,
    'Adoption Leave' => 60,
];

// Map for loose matching of leave_type strings in DB to our keys
$TYPE_ALIASES = [
    'vacation' => 'Vacation Leave',
    'mandatory' => 'Mandatory / Forced Leave',
    'forced' => 'Mandatory / Forced Leave',
    'sick' => 'Sick Leave',
    'maternity' => 'Maternity Leave',
    'paternity' => 'Paternity Leave',
    'privilege' => 'Special Privilege Leave',
    'solo parent' => 'Solo Parent Leave',
    'study' => 'Study Leave',
    'vawc' => '10-Day VAWC Leave',
    'rehabilitation' => 'Rehabilitation Leave',
    'women' => 'Special Leave Benefits for Women',
    'emergency' => 'Special Emergency (Calamity) Leave',
    'calamity' => 'Special Emergency (Calamity) Leave',
    'adoption' => 'Adoption Leave',
];

function normalize_leave_type($raw, $ENTITLEMENTS, $TYPE_ALIASES) {
    $r = trim((string)$raw);
    if ($r === '') return null;
    // Exact match first
    foreach ($ENTITLEMENTS as $k => $_) {
        if (strcasecmp($r, $k) === 0) return $k;
    }
    // Alias/contains matching
    $low = strtolower($r);
    foreach ($TYPE_ALIASES as $needle => $mapped) {
        if (strpos($low, $needle) !== false) return $mapped;
    }
    return null; // unknown type; ignore in tally
}

function parse_dates_and_count_days($dates_str, $details) {
    // If structured num_working_days present, trust it
    if (is_array($details) && isset($details['num_working_days'])) {
        $n = (float)$details['num_working_days'];
        if ($n > 0) return (int)round($n);
    }

    $s = trim((string)$dates_str);
    if ($s === '') return 0;

    // Common formats: "YYYY-MM-DD - YYYY-MM-DD" or "YYYY-MM-DD to YYYY-MM-DD"
    if (strpos($s, ' - ') !== false) {
        [$a, $b] = explode(' - ', $s, 2);
        try {
            $d1 = new DateTime(trim($a));
            $d2 = new DateTime(trim($b));
            return max(0, $d1->diff($d2)->days + 1);
        } catch (Exception $e) { /* fall through */ }
    }
    if (stripos($s, ' to ') !== false) {
        $parts = preg_split('/\s+to\s+/i', $s);
        if (count($parts) >= 2) {
            try {
                $d1 = new DateTime(trim($parts[0]));
                $d2 = new DateTime(trim($parts[1]));
                return max(0, $d1->diff($d2)->days + 1);
            } catch (Exception $e) { /* fall through */ }
        }
    }

    // Comma-separated dates
    if (strpos($s, ',') !== false) {
        $parts = array_filter(array_map('trim', explode(',', $s)), function($x){ return $x !== ''; });
        return count($parts);
    }

    // Single date
    try {
        $d = new DateTime($s);
        if ($d) return 1;
    } catch (Exception $e) { /* ignore */ }
    return 0;
}

try {
    // Prefer explicit email param, otherwise fall back to session email if logged in.
    $email = isset($_GET['email']) ? trim($_GET['email']) : '';
    if ($email === '') {
        $email = isset($_SESSION['email']) ? trim($_SESSION['email']) : '';
    }
    if ($email === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing email parameter or not authenticated']);
        exit;
    }

    // Initialize tallies
    $used = [];
    foreach ($ENTITLEMENTS as $k => $v) { $used[$k] = 0; }

    // Fetch leave requests that are approved by HR only (only these affect credits)
    $stmt = $pdo->prepare("SELECT leave_type, dates, details, status FROM leave_requests WHERE employee_email = ? AND status = 'approved' AND approved_by_hr = 1");
    $stmt->execute([$email]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $lt = normalize_leave_type($r['leave_type'] ?? '', $ENTITLEMENTS, $TYPE_ALIASES);
        if (!$lt || !isset($ENTITLEMENTS[$lt])) continue;
        $details = [];
        if (!empty($r['details'])) {
            $d = json_decode($r['details'], true);
            if (is_array($d)) $details = $d;
        }
        $days = parse_dates_and_count_days($r['dates'] ?? '', $details);
        if ($days > 0) {
            $used[$lt] += $days;
        }
    }

    $items = [];
    $totalAll = 0;
    $usedAll = 0;
    foreach ($ENTITLEMENTS as $label => $total) {
        $u = max(0, (int)($used[$label] ?? 0));
        $a = max(0, $total - $u);
        $items[] = [
            'type' => $label,
            'total' => $total,
            'used' => $u,
            'available' => $a,
            'unit' => 'days',
        ];
        $totalAll += $total;
        $usedAll += min($u, $total);
    }

    echo json_encode([
        'success' => true,
        'data' => $items,
        'summary' => [
            'totalDays' => $totalAll,
            'usedDays' => $usedAll,
            'availableDays' => max(0, $totalAll - $usedAll),
        ],
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to compute leave credits',
        'details' => $e->getMessage(),
    ]);
}
?>
