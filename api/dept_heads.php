<?php
header('Content-Type: application/json');
require_once '../db.php';
try {
	// Return users who have the role of department_head
	$stmt = $pdo->query("SELECT id, CONCAT(firstname, ' ', lastname) AS name, department, email FROM users WHERE role = 'department_head'");
	$heads = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode($heads);
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(['error' => 'DB connection failed']);
}
