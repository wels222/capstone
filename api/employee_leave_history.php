<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

function parse_dates_emp_hist($dates_str, $details) {
    $result = [
        'startDate' => null,
        'endDate' => null,
        'duration' => null,
    ];

    // Prefer structured fields if provided
    if (is_array($details)) {
        if (!empty($details['inclusive_dates'])) {
            $dates_str = $details['inclusive_dates'];
        }
        if (!empty($details['num_working_days'])) {
            $result['duration'] = trim((string)$details['num_working_days']);
        }
    }

    $s = trim((string)$dates_str);
    if ($s !== '') {
        // Common formats: "YYYY-MM-DD - YYYY-MM-DD" or "YYYY-MM-DD to YYYY-MM-DD"
        $parts = null;
        if (strpos($s, ' - ') !== false) {
            $parts = explode(' - ', $s);
        } elseif (stripos($s, ' to ') !== false) {
            $parts = preg_split('/\s+to\s+/i', $s);
        }
        if ($parts && count($parts) >= 2) {
            $result['startDate'] = trim($parts[0]);
            $result['endDate'] = trim($parts[1]);
        } else {
            // Single date or unparsed string
            $result['startDate'] = $s;
            $result['endDate'] = $s;
        }
    }

    foreach (['startDate','endDate'] as $k) {
        if (empty($result[$k])) $result[$k] = '';
    }

    return $result;
}

try {
    // Determine the email: prefer session; fall back to explicit param (for legacy cases)
    $email = $_SESSION['email'] ?? null;
    if (!$email) {
        $email = isset($_GET['email']) ? trim($_GET['email']) : null;
    }
    if (!$email) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }

    // Fetch user basic info
    $stmtU = $pdo->prepare('SELECT firstname, lastname, contact_no FROM users WHERE email = ?');
    $stmtU->execute([$email]);
    $user = $stmtU->fetch(PDO::FETCH_ASSOC) ?: ['firstname' => '', 'lastname' => '', 'contact_no' => ''];

    // Fetch leave requests for this user
    // Only include leaves that have been fully approved by HR.
    // This ensures the employee's leave history only shows entries after HR approval.
    $stmt = $pdo->prepare("SELECT id, leave_type, dates, status, details, applied_at, approved_by_hr FROM leave_requests WHERE employee_email = ? AND status = 'approved' AND approved_by_hr = 1 ORDER BY applied_at DESC");
    $stmt->execute([$email]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        $details = [];
        if (!empty($r['details'])) {
            $d = json_decode($r['details'], true);
            if (is_array($d)) $details = $d;
        }
        $parsed = parse_dates_emp_hist($r['dates'] ?? '', $details);

        // If duration missing, try to compute inclusive day count
        if (empty($parsed['duration']) && !empty($parsed['startDate']) && !empty($parsed['endDate'])) {
            try {
                $d1 = new DateTime($parsed['startDate']);
                $d2 = new DateTime($parsed['endDate']);
                if ($d1 && $d2) {
                    $diff = $d1->diff($d2)->days;
                    $parsed['duration'] = (string)($diff + 1);
                }
            } catch (Exception $e) { /* ignore compute errors */ }
        }

        // Normalize status casing for display
        $status = strtolower((string)($r['status'] ?? 'pending'));
        $statusDisp = $status === 'approved' ? 'Approved' : ($status === 'declined' ? 'Declined' : 'Pending');

        // Build name "Firstname Lastname"
        $fullName = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));

        $data[] = [
            'formId'    => (int)$r['id'],
            'name'      => $fullName,
            'leaveType' => (string)($r['leave_type'] ?? ''),
            'duration'  => (string)($parsed['duration'] ?? ''),
            'startDate' => (string)($parsed['startDate'] ?? ''),
            'endDate'   => (string)($parsed['endDate'] ?? ''),
            'contactNo' => (string)($user['contact_no'] ?? ''),
            'status'    => $statusDisp,
            'appliedAt' => (string)($r['applied_at'] ?? ''),
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch leave history',
        'details' => $e->getMessage()
    ]);
}
