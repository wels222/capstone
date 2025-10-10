<?php
header('Content-Type: application/json');
require_once '../db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? $data['id'] : null;
$status = isset($data['status']) ? $data['status'] : null;

if (!$id || !$status) {
    echo json_encode(['success' => false, 'error' => 'Missing id or status']);
    exit;
}

try {
    if ($status === 'approved' && isset($data['approved_by_hr']) && $data['approved_by_hr']) {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = :status, approved_by_hr = 1, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
    } else if ($status === 'approved') {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = :status, approved_by_hr = 0, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
    } else if ($status === 'declined' && isset($data['reason'])) {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = :status, decline_reason = :reason, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':status' => $status, ':reason' => $data['reason'], ':id' => $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = :status, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
    }
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update leave request',
        'details' => $e->getMessage()
    ]);
}
