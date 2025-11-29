<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once '../db.php';

try {
    // Ensure archive columns exist (silent/idempotent)
    try {
        $cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'leave_requests'")->fetchAll(PDO::FETCH_COLUMN);
        $lower = array_map('strtolower', $cols ?: []);
        if (!in_array('is_archived', $lower)) { $pdo->exec("ALTER TABLE leave_requests ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0"); }
        if (!in_array('archived_at', $lower)) { $pdo->exec("ALTER TABLE leave_requests ADD COLUMN archived_at DATETIME NULL DEFAULT NULL"); }
    } catch (Throwable $__e) { /* ignore */ }

    // HR only sees leave requests that are already approved by department head (status = 'approved' but approved_by_hr = 0)
    // and not archived
    $stmt = $pdo->query("SELECT lr.*, u.firstname, u.lastname FROM leave_requests lr JOIN users u ON lr.employee_email = u.email WHERE lr.status = 'approved' AND lr.approved_by_hr = 0 AND lr.is_archived = 0 ORDER BY lr.applied_at DESC");
    $leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $leaveRequests
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch HR leave requests',
        'details' => $e->getMessage()
    ]);
}