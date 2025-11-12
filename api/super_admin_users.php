<?php
// api/super_admin_users.php
require_once '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$data = json_decode(file_get_contents('php://input'), true);
	$action = $data['action'] ?? '';
	if ($action === 'add') {
		$stmt = $pdo->prepare('INSERT INTO users (lastname, firstname, mi, department, position, role, contact_no, status, email, password, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
		$hash = password_hash($data['password'], PASSWORD_DEFAULT);
		$role = $data['role'] ?? 'employee';
		$contact_no = $data['contact_no'] ?? null;
		$status = $data['status'] ?? 'pending';
		$stmt->execute([
			$data['lastname'], $data['firstname'], $data['mi'], $data['department'], $data['position'], $role, $contact_no, $status, $data['email'], $hash
		]);
		// Generate automatic employee_id based on the inserted numeric id
		try {
			$insertId = (int)$pdo->lastInsertId();
			if ($insertId > 0) {
				$year = date('Y');
				$employeeId = sprintf('EMP-%s-%06d', $year, $insertId);
				$up = $pdo->prepare('UPDATE users SET employee_id = ? WHERE id = ?');
				$up->execute([$employeeId, $insertId]);
				echo json_encode(['success' => true, 'id' => $insertId, 'employee_id' => $employeeId]);
				exit;
			}
		} catch (Throwable $e) {
			// Fall through to generic success if employee_id generation fails
		}
		echo json_encode(['success' => true]);
		exit;
	} elseif ($action === 'edit') {
		$fields = ['lastname', 'firstname', 'mi', 'department', 'position', 'role', 'contact_no', 'status', 'email'];
		$set = [];
		$params = [];
		foreach ($fields as $f) {
			$set[] = "$f = ?";
			$params[] = $data[$f] ?? null;
		}
		if (!empty($data['password'])) {
			$set[] = "password = ?";
			$params[] = password_hash($data['password'], PASSWORD_DEFAULT);
		}
		$set[] = "updated_at = NOW()";
		$params[] = $data['id'];
		$sql = 'UPDATE users SET ' . implode(', ', $set) . ' WHERE id = ?';
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
		echo json_encode(['success' => true]);
		exit;
	} elseif ($action === 'archive') {
		// Ensure columns exist
		try {
			$cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users'")->fetchAll(PDO::FETCH_COLUMN);
			$lower = array_map('strtolower', $cols ?: []);
			if (!in_array('is_archived', $lower)) { $pdo->exec("ALTER TABLE users ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0"); }
			if (!in_array('archived_at', $lower)) { $pdo->exec("ALTER TABLE users ADD COLUMN archived_at DATETIME NULL DEFAULT NULL"); }
		} catch (Throwable $__e) { /* ignore */ }
		$stmt = $pdo->prepare('UPDATE users SET is_archived = 1, archived_at = NOW() WHERE id = ?');
		$stmt->execute([$data['id']]);
		echo json_encode(['success' => true, 'archived' => true]);
		exit;
	} elseif ($action === 'restore') {
		$stmt = $pdo->prepare('UPDATE users SET is_archived = 0, archived_at = NULL WHERE id = ?');
		$stmt->execute([$data['id']]);
		echo json_encode(['success' => true, 'restored' => true]);
		exit;
	} elseif ($action === 'delete_permanent') {
		$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
		$stmt->execute([$data['id']]);
		echo json_encode(['success' => true, 'deleted' => true]);
		exit;
	} elseif ($action === 'approve') {
		$stmt = $pdo->prepare('UPDATE users SET status = "approved" WHERE id = ?');
		$stmt->execute([$data['id']]);
		echo json_encode(['success' => true]);
		exit;
	} elseif ($action === 'decline') {
		// mark declined instead of deleting so admin can review
		$stmt = $pdo->prepare('UPDATE users SET status = "declined" WHERE id = ?');
		$stmt->execute([$data['id']]);
		echo json_encode(['success' => true]);
		exit;
	}
}
// Default: GET all users (including archived) so admin can manage restore/delete.
$users = $pdo->query('SELECT * FROM users ORDER BY id DESC')->fetchAll();
echo json_encode($users);