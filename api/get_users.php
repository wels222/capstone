<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'department_head', 'super_admin']);
require_once '../db.php';

try {
    // Ensure archive columns exist (best-effort)
    try {
        $cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users'")->fetchAll(PDO::FETCH_COLUMN);
        $lower = array_map('strtolower', $cols ?: []);
        if (!in_array('is_archived', $lower)) { $pdo->exec("ALTER TABLE users ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0"); }
        if (!in_array('archived_at', $lower)) { $pdo->exec("ALTER TABLE users ADD COLUMN archived_at DATETIME NULL DEFAULT NULL"); }
    } catch (Throwable $__e) { /* ignore */ }

    $stmt = $pdo->query("SELECT id, firstname, lastname, email FROM users WHERE (is_archived = 0 OR is_archived IS NULL) ORDER BY firstname, lastname");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true,
        'data' => $users
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch users',
        'details' => $e->getMessage()
    ]);
}
