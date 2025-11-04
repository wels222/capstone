<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

try {
    // Aggregate tasks by department (based on the assigned user's department)
    $sql = "SELECT COALESCE(u.department, 'Unknown') AS department,
        COUNT(t.id) AS total,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN t.status IN ('pending','in_progress') THEN 1 ELSE 0 END) AS backlog,
        MAX(t.updated_at) AS last_updated,
        (SELECT CONCAT(firstname, ' ', lastname) FROM users uh WHERE uh.role = 'department_head' AND uh.department = COALESCE(u.department, 'Unknown') LIMIT 1) AS department_head_name,
        (SELECT uh.email FROM users uh WHERE uh.role = 'department_head' AND uh.department = COALESCE(u.department, 'Unknown') LIMIT 1) AS department_head_email
        FROM tasks t
        LEFT JOIN users u ON u.email = t.assigned_to_email
        GROUP BY COALESCE(u.department, 'Unknown')
        ORDER BY backlog DESC, total DESC";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // compute progress percent for each row
    $out = [];
    foreach ($rows as $r) {
        $total = (int)$r['total'];
        $completed = (int)$r['completed'];
        $backlog = (int)$r['backlog'];
        $percent = $total ? round(($completed / $total) * 100) : 0;
        $out[] = [
            'department' => $r['department'],
            'department_head_name' => $r['department_head_name'] ?? null,
            'department_head_email' => $r['department_head_email'] ?? null,
            'total' => $total,
            'completed' => $completed,
            'backlog' => $backlog,
            'progress_percent' => $percent,
            'last_updated' => $r['last_updated'] ?? null,
        ];
    }

    echo json_encode(['success' => true, 'departments' => $out]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error', 'detail' => $e->getMessage()]);
}

