<?php
// api/super_admin_users.php
require_once '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$data = json_decode(file_get_contents('php://input'), true);
	$action = $data['action'] ?? '';
	if ($action === 'add') {
		$stmt = $pdo->prepare('INSERT INTO users (lastname, firstname, mi, department, position, status, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
		$hash = password_hash($data['password'], PASSWORD_DEFAULT);
		$stmt->execute([
			$data['lastname'], $data['firstname'], $data['mi'], $data['department'], $data['position'], $data['status'], $data['email'], $hash
		]);
		echo json_encode(['success' => true]);
		exit;
	} elseif ($action === 'edit') {
		$fields = ['lastname', 'firstname', 'mi', 'department', 'position', 'status', 'email'];
		$set = [];
		$params = [];
		foreach ($fields as $f) {
			$set[] = "$f = ?";
			$params[] = $data[$f];
		}
		if (!empty($data['password'])) {
			$set[] = "password = ?";
			$params[] = password_hash($data['password'], PASSWORD_DEFAULT);
		}
		$params[] = $data['id'];
		$sql = 'UPDATE users SET ' . implode(', ', $set) . ' WHERE id = ?';
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
		echo json_encode(['success' => true]);
		exit;
	} elseif ($action === 'delete') {
		$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
		$stmt->execute([$data['id']]);
		echo json_encode(['success' => true]);
		exit;
	} elseif ($action === 'approve') {
		$stmt = $pdo->prepare('UPDATE users SET status = "Permanent" WHERE id = ?');
		$stmt->execute([$data['id']]);
		echo json_encode(['success' => true]);
		exit;
	} elseif ($action === 'decline') {
		$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
		$stmt->execute([$data['id']]);
		echo json_encode(['success' => true]);
		exit;
	}
}
// Default: GET all users
$users = $pdo->query('SELECT * FROM users')->fetchAll();
echo json_encode($users);