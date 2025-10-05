<?php
header('Content-Type: application/json');
require_once '../db.php';
try {
	$stmt = $pdo->query("SELECT id, title, date, time, location, description FROM events ORDER BY date DESC, time DESC");
	$events = $stmt->fetchAll();
	echo json_encode($events);
} catch (PDOException $e) {
	http_response_code(500); echo json_encode(['error'=>'DB connection failed']);
}