<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'department_head', 'employee']);
require_once '../db.php';

// Ensure columns exist (silent checks)
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM leave_requests LIKE 'approved_by_municipal'");
    if ($checkCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE leave_requests ADD COLUMN approved_by_municipal TINYINT(1) NOT NULL DEFAULT 0");
    }
} catch (PDOException $e) {
    // Silently fail - will be caught by main query if still broken
}
try {
    $cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'leave_requests'")->fetchAll(PDO::FETCH_COLUMN);
    $lower = array_map('strtolower', $cols ?: []);
    if (!in_array('is_archived', $lower)) { $pdo->exec("ALTER TABLE leave_requests ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0"); }
    if (!in_array('archived_at', $lower)) { $pdo->exec("ALTER TABLE leave_requests ADD COLUMN archived_at DATETIME NULL DEFAULT NULL"); }
} catch (PDOException $e) {
    // ignore
}

try {
    // Optional filters for performance: status, month, year (based on applied_at)
    // include_archived: when truthy (1/true), include archived items; otherwise hide them system-wide
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;
    $month  = isset($_GET['month']) ? (int)$_GET['month'] : 0; // 1-12
    $year   = isset($_GET['year']) ? (int)$_GET['year'] : 0;   // e.g., 2025
    $includeArchived = isset($_GET['include_archived']) ? strtolower(trim((string)$_GET['include_archived'])) : '';
    $includeArchived = in_array($includeArchived, ['1','true','yes'], true);

    $sql = "SELECT lr.*, u.firstname, u.lastname, u.department, u.position
            FROM leave_requests lr
            JOIN users u ON lr.employee_email = u.email";
    $where = [];
    $params = [];

    if ($status !== null && $status !== '') {
        $where[] = "lr.status = ?";
        $params[] = $status;
    }
    if (!$includeArchived) {
        $where[] = "lr.is_archived = 0";
    }
    if ($month >= 1 && $month <= 12 && $year >= 1970) {
        // Filter by applied_at month/year to reduce payload (note: display still uses inclusive leave dates on client side)
        $where[] = "MONTH(lr.applied_at) = ? AND YEAR(lr.applied_at) = ?";
        $params[] = $month;
        $params[] = $year;
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY lr.applied_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $leaveRequests
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch leave requests',
        'details' => $e->getMessage()
    ]);
}