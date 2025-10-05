<?php
header('Content-Type: application/json');
require_once '../db.php';
try {
	$stmt = $pdo->query("SELECT email, name, department FROM dept_heads");
	$heads = $stmt->fetchAll();
	echo json_encode($heads);
} catch (PDOException $e) {
	http_response_code(500); echo json_encode(['error'=>'DB connection failed']);
}