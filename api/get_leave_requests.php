<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    // Optional filters for performance: status, month, year (based on applied_at)
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;
    $month  = isset($_GET['month']) ? (int)$_GET['month'] : 0; // 1-12
    $year   = isset($_GET['year']) ? (int)$_GET['year'] : 0;   // e.g., 2025

    $sql = "SELECT lr.*, u.firstname, u.lastname, u.department, u.position
            FROM leave_requests lr
            JOIN users u ON lr.employee_email = u.email";
    $where = [];
    $params = [];

    if ($status !== null && $status !== '') {
        $where[] = "lr.status = ?";
        $params[] = $status;
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