<?php
header('Content-Type: application/json');
require_once '../db.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { http_response_code(400); echo json_encode(['error'=>'No data']); exit; }
$title = $data['title'] ?? '';
$date = $data['date'] ?? '';
$time = $data['time'] ?? '';
$location = $data['location'] ?? '';
$description = $data['description'] ?? '';
if (!$title || !$date || !$location || !$description) { http_response_code(400); echo json_encode(['error'=>'Missing required fields']); exit; }
try {
    $stmt = $pdo->prepare("INSERT INTO events (title, date, time, location, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $date, $time, $location, $description]);
    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['error'=>'DB insert failed']);
}