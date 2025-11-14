<?php
// api/super_admin_users.php
require_once '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$data = json_decode(file_get_contents('php://input'), true);
	$action = $data['action'] ?? '';
	if ($action === 'add') {
		// Trim and validate inputs
		$lastname = trim($data['lastname'] ?? '');
		$firstname = trim($data['firstname'] ?? '');
		$mi = trim($data['mi'] ?? '');
		$department = trim($data['department'] ?? '');
		$position = trim($data['position'] ?? '');
		$role = trim($data['role'] ?? 'employee');
		$contact_no = preg_replace('/\s+/', '', $data['contact_no'] ?? '');
		$email = trim($data['email'] ?? '');
		$password = $data['password'] ?? '';
		$status = trim($data['status'] ?? 'pending');

		$nameRegex = "/^[A-Za-z\s\-']+$/";
		$miRegex = "/^[A-Za-z]?$/";
		$phoneRegex = "/^09\d{9}$/";
		$allowedPositions = ['Permanent','Casual','JO','OJT'];
		$allowedRoles = ['employee','department_head','hr'];

		if (!preg_match($nameRegex, $lastname) || !preg_match($nameRegex, $firstname)) {
			http_response_code(400); echo json_encode(['error' => 'Names must contain letters only.']); exit;
		}
		if ($mi !== '' && !preg_match($miRegex, $mi)) {
			http_response_code(400); echo json_encode(['error' => 'Middle initial must be a single letter.']); exit;
		}
		if ($contact_no !== '' && !preg_match($phoneRegex, $contact_no)) {
			http_response_code(400); echo json_encode(['error' => 'Contact number must start with 09 and be 11 digits.']); exit;
		}
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			http_response_code(400); echo json_encode(['error' => 'Invalid email address.']); exit;
		}
		if (!in_array($position, $allowedPositions)) {
			http_response_code(400); echo json_encode(['error' => 'Invalid position selected.']); exit;
		}
		if (!in_array($role, $allowedRoles)) {
			http_response_code(400); echo json_encode(['error' => 'Invalid role selected.']); exit;
		}
		if (strlen($password) < 6) {
			http_response_code(400); echo json_encode(['error' => 'Password must be at least 6 characters.']); exit;
		}
		// Check duplicate email
		$chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
		$chk->execute([$email]);
		if ($chk->fetch()) { http_response_code(400); echo json_encode(['error' => 'Email already registered.']); exit; }

		$stmt = $pdo->prepare('INSERT INTO users (lastname, firstname, mi, department, position, role, contact_no, status, email, password, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
		$hash = password_hash($password, PASSWORD_DEFAULT);
		$stmt->execute([
			$lastname, $firstname, $mi, $department, $position, $role, ($contact_no ?: null), $status, $email, $hash
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
		// Validate editable fields
		$lastname = trim($data['lastname'] ?? '');
		$firstname = trim($data['firstname'] ?? '');
		$mi = trim($data['mi'] ?? '');
		$department = trim($data['department'] ?? '');
		$position = trim($data['position'] ?? '');
		$role = trim($data['role'] ?? 'employee');
		$contact_no = preg_replace('/\s+/', '', $data['contact_no'] ?? '');
		$email = trim($data['email'] ?? '');
		$nameRegex = "/^[A-Za-z\s\-']+$/";
		$miRegex = "/^[A-Za-z]?$/";
		$phoneRegex = "/^09\d{9}$/";
		$allowedPositions = ['Permanent','Casual','JO','OJT'];
		$allowedRoles = ['employee','department_head','hr'];
		if (!preg_match($nameRegex, $lastname) || !preg_match($nameRegex, $firstname)) { http_response_code(400); echo json_encode(['error' => 'Names must contain letters only.']); exit; }
		if ($mi !== '' && !preg_match($miRegex, $mi)) { http_response_code(400); echo json_encode(['error' => 'Middle initial must be a single letter.']); exit; }
		if ($contact_no !== '' && !preg_match($phoneRegex, $contact_no)) { http_response_code(400); echo json_encode(['error' => 'Contact number must start with 09 and be 11 digits.']); exit; }
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['error' => 'Invalid email address.']); exit; }
		if (!in_array($position, $allowedPositions)) { http_response_code(400); echo json_encode(['error' => 'Invalid position selected.']); exit; }
		if (!in_array($role, $allowedRoles)) { http_response_code(400); echo json_encode(['error' => 'Invalid role selected.']); exit; }
		// If email changed, ensure uniqueness
		if (!empty($data['id'])) {
			$chk = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
			$chk->execute([$email, (int)$data['id']]);
			if ($chk->fetch()) { http_response_code(400); echo json_encode(['error' => 'Email already in use by another account.']); exit; }
		}
		$fields = ['lastname', 'firstname', 'mi', 'department', 'position', 'role', 'contact_no', 'status', 'email'];
		$set = [];
		$params = [];
		foreach ($fields as $f) {
			$set[] = "$f = ?";
			$params[] = ${$f} ?? null;
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