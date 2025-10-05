<?php
// api/super_admin_events.php
require_once '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$data = json_decode(file_get_contents('php://input'), true);
	$action = $data['action'] ?? '';
	if ($action === 'add') {
		$stmt = $pdo->prepare('INSERT INTO events (title, date, time, location, description) VALUES (?, ?, ?, ?, ?)');
		$stmt->execute([
			$data['title'], $data['date'], $data['time'], $data['location'], $data['description']
		]);
		echo json_encode(['success' => true]);
		exit;
	} elseif ($action === 'edit') {
		$stmt = $pdo->prepare('UPDATE events SET title=?, date=?, time=?, location=?, description=? WHERE id=?');
		$stmt->execute([
			$data['title'], $data['date'], $data['time'], $data['location'], $data['description'], $data['id']
		]);
		echo json_encode(['success' => true]);
		exit;
	} elseif ($action === 'delete') {
		$stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
		$stmt->execute([$data['id']]);
		echo json_encode(['success' => true]);
		exit;
	}
}
// Default: GET all events
$events = $pdo->query('SELECT * FROM events')->fetchAll();
echo json_encode($events);