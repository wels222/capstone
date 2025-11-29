<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
session_start();
require_once '../db.php';

try {
    $email = $_SESSION['email'] ?? null;
    $role = null;
    $dept = null;
    // Best-effort ensure archive columns exist on users
    try {
        $cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users'")->fetchAll(PDO::FETCH_COLUMN);
        $lower = array_map('strtolower', $cols ?: []);
        if (!in_array('is_archived', $lower)) { $pdo->exec("ALTER TABLE users ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0"); }
        if (!in_array('archived_at', $lower)) { $pdo->exec("ALTER TABLE users ADD COLUMN archived_at DATETIME NULL DEFAULT NULL"); }
    } catch (Throwable $__e) { /* ignore */ }
    if ($email) {
        try {
            $s = $pdo->prepare('SELECT role, department FROM users WHERE email = ?');
            $s->execute([$email]);
            $r = $s->fetch(PDO::FETCH_ASSOC);
            $role = strtolower($r['role'] ?? '');
            $dept = $r['department'] ?? null;
        } catch (PDOException $__e) { /* ignore */ }
    }

    // HR users can see ALL employees regardless of department
    if ($role === 'hr' || $role === 'human resources') {
        $stmt = $pdo->query('SELECT id, lastname, firstname, mi, department, position, role, status, email, profile_picture, contact_no, is_archived FROM users WHERE (is_archived = 0 OR is_archived IS NULL)');
    } elseif ($dept) {
        $stmt = $pdo->prepare('SELECT id, lastname, firstname, mi, department, position, role, status, email, profile_picture, contact_no, is_archived FROM users WHERE department = ? AND (is_archived = 0 OR is_archived IS NULL)');
        $stmt->execute([$dept]);
    } else {
        $stmt = $pdo->query('SELECT id, lastname, firstname, mi, department, position, role, status, email, profile_picture, contact_no, is_archived FROM users WHERE (is_archived = 0 OR is_archived IS NULL)');
    }

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'employees' => $employees]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
