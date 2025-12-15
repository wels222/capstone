<?php
require_once __DIR__ . '/_bootstrap.php';
// api/super_admin_users.php
require_once __DIR__ . '/../auth_guard.php';
require_api_auth('super_admin');
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
		$gender = trim($data['gender'] ?? '');
		$employee_id = trim($data['employee_id'] ?? '');
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
		// Position-based role validation: OJT and JO can only be Employee
		if (($position === 'OJT' || $position === 'JO') && $role !== 'employee') {
			http_response_code(400); echo json_encode(['error' => 'OJT and JO positions can only have Employee role.']); exit;
		}
		// Department Head and HR must be Permanent or Casual
		if (($role === 'department_head' || $role === 'hr') && !in_array($position, ['Permanent', 'Casual'])) {
			http_response_code(400); echo json_encode(['error' => 'Department Head and HR roles require Permanent or Casual position.']); exit;
		}
		if (empty($gender) || !in_array($gender, ['M', 'F'])) {
			http_response_code(400); echo json_encode(['error' => 'Please select a valid gender (M=Male, F=Female).']); exit;
		}
		if (empty($employee_id)) {
			http_response_code(400); echo json_encode(['error' => 'Employee ID is required.']); exit;
		}
		if (strlen($password) < 6) {
			http_response_code(400); echo json_encode(['error' => 'Password must be at least 6 characters.']); exit;
		}
		// Enforce only one Department Head per department (server-side)
		if ($role === 'department_head') {
			$chkHead = $pdo->prepare("SELECT id FROM users WHERE role = 'department_head' AND department = ? LIMIT 1");
			$chkHead->execute([$department]);
			if ($chkHead->fetch()) {
				http_response_code(400); echo json_encode(['error' => 'A Department Head is already assigned to this department.']); exit;
			}
		}

		// Check duplicate email
		$chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
		$chk->execute([$email]);
		if ($chk->fetch()) { http_response_code(400); echo json_encode(['error' => 'Email already registered.']); exit; }
		
		// Check duplicate employee_id
		$chkEmp = $pdo->prepare('SELECT id FROM users WHERE employee_id = ?');
		$chkEmp->execute([$employee_id]);
		if ($chkEmp->fetch()) { http_response_code(400); echo json_encode(['error' => 'Employee ID already exists. Please use a unique ID.']); exit; }

		$stmt = $pdo->prepare('INSERT INTO users (lastname, firstname, mi, department, position, role, contact_no, gender, employee_id, status, email, password, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
		$hash = password_hash($password, PASSWORD_DEFAULT);
		$stmt->execute([
			$lastname, $firstname, $mi, $department, $position, $role, ($contact_no ?: null), $gender, $employee_id, $status, $email, $hash
		]);
		$insertId = (int)$pdo->lastInsertId();
		echo json_encode(['success' => true, 'id' => $insertId, 'employee_id' => $employee_id]);
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
		$gender = trim($data['gender'] ?? '');
		$employee_id = trim($data['employee_id'] ?? '');
		$email = trim($data['email'] ?? '');
		// Status (missing previously) so edits defaulted incorrectly
		$status = trim($data['status'] ?? '');
		$allowedStatuses = ['pending','approved','declined'];
		if ($status === '' || !in_array($status, $allowedStatuses)) { $status = 'pending'; }
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
		// Position-based role validation: OJT and JO can only be Employee
		if (($position === 'OJT' || $position === 'JO') && $role !== 'employee') {
			http_response_code(400); echo json_encode(['error' => 'OJT and JO positions can only have Employee role.']); exit;
		}
		// Department Head and HR must be Permanent or Casual
		if (($role === 'department_head' || $role === 'hr') && !in_array($position, ['Permanent', 'Casual'])) {
			http_response_code(400); echo json_encode(['error' => 'Department Head and HR roles require Permanent or Casual position.']); exit;
		}
		if (!empty($gender) && !in_array($gender, ['M', 'F'])) { http_response_code(400); echo json_encode(['error' => 'Please select a valid gender (M=Male, F=Female).']); exit; }
		// Enforce only one Department Head per department on edit (exclude the same user)
		if ($role === 'department_head' && !empty($data['id'])) {
			$chkHead = $pdo->prepare("SELECT id FROM users WHERE role = 'department_head' AND department = ? AND id <> ? LIMIT 1");
			$chkHead->execute([$department, (int)$data['id']]);
			if ($chkHead->fetch()) { http_response_code(400); echo json_encode(['error' => 'A Department Head is already assigned to this department.']); exit; }
		}

		// If email changed, ensure uniqueness
		if (!empty($data['id'])) {
			$chk = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
			$chk->execute([$email, (int)$data['id']]);
			if ($chk->fetch()) { http_response_code(400); echo json_encode(['error' => 'Email already in use by another account.']); exit; }
			
			// If employee_id provided and changed, ensure uniqueness
			if (!empty($employee_id)) {
				$chkEmp = $pdo->prepare('SELECT id FROM users WHERE employee_id = ? AND id <> ?');
				$chkEmp->execute([$employee_id, (int)$data['id']]);
				if ($chkEmp->fetch()) { http_response_code(400); echo json_encode(['error' => 'Employee ID already exists. Please use a unique ID.']); exit; }
			}
		}
		$fields = ['lastname', 'firstname', 'mi', 'department', 'position', 'role', 'contact_no', 'gender', 'employee_id', 'status', 'email'];
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