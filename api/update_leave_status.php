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
        // notify the employee that HR approved their leave
        try {
            $r = $pdo->prepare('SELECT employee_email FROM leave_requests WHERE id = ? LIMIT 1');
            $r->execute([$id]);
            $row = $r->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['employee_email'])) {
                $emp = $row['employee_email'];
                $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    recipient_email VARCHAR(150),
                    recipient_role VARCHAR(100),
                    message TEXT NOT NULL,
                    type VARCHAR(50) DEFAULT 'leave',
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $msg = 'Your leave request has been approved by HR.';
                $ins = $pdo->prepare('INSERT INTO notifications (recipient_email, recipient_role, message, type) VALUES (?, ?, ?, ?)');
                $ins->execute([$emp, null, $msg, 'leave_approved_by_hr']);
            }
        } catch (PDOException $e) { /* ignore */ }
    } else if ($status === 'approved') {
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = :status, approved_by_hr = 0, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
        // Notify HR that the Department Head approved this leave, include department info
        try {
            $r = $pdo->prepare('SELECT lr.employee_email, u.department, u.firstname, u.lastname FROM leave_requests lr JOIN users u ON lr.employee_email = u.email WHERE lr.id = ? LIMIT 1');
            $r->execute([$id]);
            $row = $r->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['employee_email'])) {
                $empEmail = $row['employee_email'];
                $dept = $row['department'] ?? 'Unknown';
                $fullname = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')) ?: $empEmail;
                // Ensure notifications table exists
                $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    recipient_email VARCHAR(150),
                    recipient_role VARCHAR(100),
                    message TEXT NOT NULL,
                    type VARCHAR(50) DEFAULT 'leave',
                    is_read TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $msg = sprintf('Leave approved by Department Head — Employee: %s; Department: %s', $fullname, $dept);
                $ins = $pdo->prepare('INSERT INTO notifications (recipient_email, recipient_role, message, type) VALUES (?, ?, ?, ?)');
                // send to role 'hr' (all HR users)
                $ins->execute([null, 'hr', $msg, 'leave_approved_by_dept_head']);
            }
        } catch (PDOException $e) { /* ignore notification errors */ }
    } else if ($status === 'declined' && isset($data['reason'])) {
        // If declined by HR, set approved_by_hr=1, else 0
        $approved_by_hr = isset($data['declined_by_hr']) && $data['declined_by_hr'] ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = :status, decline_reason = :reason, approved_by_hr = :approved_by_hr, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':status' => $status, ':reason' => $data['reason'], ':approved_by_hr' => $approved_by_hr, ':id' => $id]);
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
