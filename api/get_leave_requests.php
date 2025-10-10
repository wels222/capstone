<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    $stmt = $pdo->query("SELECT lr.*, u.firstname, u.lastname FROM leave_requests lr JOIN users u ON lr.employee_email = u.email ORDER BY lr.applied_at DESC");
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