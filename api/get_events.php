<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'department_head', 'employee', 'super_admin']);
require_once '../db.php';
try {
	// Ensure archive columns exist (best-effort)
	try {
		$cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'events'")->fetchAll(PDO::FETCH_COLUMN);
		$lower = array_map('strtolower', $cols ?: []);
		if (!in_array('is_archived', $lower)) { $pdo->exec("ALTER TABLE events ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0"); }
		if (!in_array('archived_at', $lower)) { $pdo->exec("ALTER TABLE events ADD COLUMN archived_at DATETIME NULL DEFAULT NULL"); }
	} catch (Throwable $__e) { /* ignore */ }

	$stmt = $pdo->query("SELECT id, title, date, time, location, description, is_archived, archived_at FROM events ORDER BY is_archived ASC, date DESC, time DESC, id DESC");
	$events = $stmt->fetchAll();
	echo json_encode($events);
} catch (PDOException $e) {
	http_response_code(500); echo json_encode(['error'=>'DB connection failed']);
}