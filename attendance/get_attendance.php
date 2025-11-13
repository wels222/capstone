<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

try {
    $dept = $_GET['department'] ?? '';
    $search = $_GET['search'] ?? '';
    $date = $_GET['date'] ?? date('Y-m-d');
    $status = $_GET['status'] ?? '';

    if ($status === 'Absent') {
        // Special handling for Absent - show employees WITHOUT attendance records
        $params = [$date];
        $sql = 'SELECT 
                    u.employee_id,
                    CONCAT(u.firstname, " ", u.lastname) as name,
                    u.department,
                    ? as date,
                    NULL as time_in,
                    NULL as time_out,
                    "Absent" as time_in_status,
                    NULL as time_out_status,
                    u.id
                FROM users u
                WHERE u.status = "approved" 
                AND u.employee_id IS NOT NULL
                AND u.employee_id NOT IN (
                    SELECT employee_id FROM attendance WHERE date = ?
                )';
        
        $params[] = $date;
        
        if ($dept) {
            $sql .= ' AND u.department = ?';
            $params[] = $dept;
        }
        
        if ($search) {
            $sql .= ' AND (CONCAT(u.firstname, " ", u.lastname) LIKE ? OR u.employee_id LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= ' ORDER BY u.lastname ASC';
    } else if ($status === 'Present' || $status === 'Late') {
        // Filter by time_in_status only
        $params = [$date];
        $sql = 'SELECT a.*, 
                CONCAT(u.firstname, " ", u.lastname) as name, 
                u.department, 
                a.employee_id,
                a.time_in_status,
                a.time_out_status
                FROM attendance a 
                JOIN users u ON u.employee_id = a.employee_id 
                WHERE a.date = ? AND a.time_in_status = ?';
        
        $params[] = $status;

        if ($dept) {
            $sql .= ' AND u.department = ?';
            $params[] = $dept;
        }

        if ($search) {
            $sql .= ' AND (CONCAT(u.firstname, " ", u.lastname) LIKE ? OR u.employee_id LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= ' ORDER BY a.time_in DESC';
    } else if ($status === 'Out' || $status === 'On-time' || $status === 'Undertime' || $status === 'Overtime') {
        // Filter by time_out_status only
        $params = [$date];
        $sql = 'SELECT a.*, 
                CONCAT(u.firstname, " ", u.lastname) as name, 
                u.department, 
                a.employee_id,
                a.time_in_status,
                a.time_out_status
                FROM attendance a 
                JOIN users u ON u.employee_id = a.employee_id 
                WHERE a.date = ? AND a.time_out_status = ?';
        
        $params[] = $status;

        if ($dept) {
            $sql .= ' AND u.department = ?';
            $params[] = $dept;
        }

        if ($search) {
            $sql .= ' AND (CONCAT(u.firstname, " ", u.lastname) LIKE ? OR u.employee_id LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= ' ORDER BY a.time_out DESC';
    } else {
        // Normal query for other statuses
        $params = [$date];
        $sql = 'SELECT a.*, 
                CONCAT(u.firstname, " ", u.lastname) as name, 
                u.department, 
                a.employee_id,
                a.time_in_status,
                a.time_out_status
                FROM attendance a 
                JOIN users u ON u.employee_id = a.employee_id 
                WHERE a.date = ?';

        if ($dept) {
            $sql .= ' AND u.department = ?';
            $params[] = $dept;
        }

        if ($search) {
            $sql .= ' AND (CONCAT(u.firstname, " ", u.lastname) LIKE ? OR u.employee_id LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($status && $status !== 'all') {
            // Check both time_in_status and time_out_status
            $sql .= ' AND (a.time_in_status = ? OR a.time_out_status = ?)';
            $params[] = $status;
            $params[] = $status;
        }

        $sql .= ' ORDER BY a.time_in DESC, a.time_out DESC';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'date' => $date,
        'records' => $rows
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'records' => []
    ]);
}
