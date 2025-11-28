<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'department_head']);
require_once __DIR__ . '/../db.php';

$byEmail = $_SESSION['email'] ?? null;
if (!$byEmail) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$title = isset($_POST['title']) ? $_POST['title'] : null;
$description = isset($_POST['description']) ? $_POST['description'] : null;
$due_date = isset($_POST['due_date']) ? $_POST['due_date'] : null;
$assigned_to_email = isset($_POST['assigned_to_email']) ? $_POST['assigned_to_email'] : null;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid id']);
    exit;
}

// Normalize due_date
if ($due_date) {
    $due_date = str_replace('T', ' ', $due_date);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $due_date)) {
        $due_date .= ':00';
    }
    try { $dt = new DateTime($due_date); $due_date = $dt->format('Y-m-d H:i:s'); } catch (Exception $e) { $due_date = null; }
}

// Attachment optional
$attachment_path = null;
if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $tmpPath = $_FILES['attachment']['tmp_name'];
    $origName = basename($_FILES['attachment']['name']);
    $ext = pathinfo($origName, PATHINFO_EXTENSION);
    $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
    $targetDir = __DIR__ . '/../uploads/tasks/';
    $fileName = uniqid('task_') . ($safeExt ? ('.' . $safeExt) : '');
    $dest = $targetDir . $fileName;
    if (@is_dir($targetDir) && @is_writable($targetDir) && @move_uploaded_file($tmpPath, $dest)) {
        $attachment_path = 'uploads/tasks/' . $fileName;
    }
}

try {
    // Only allow updates by the original assigning department head
    // Build dynamic update query based on provided fields
    $fields = [];
    $params = [];

    // Normalize and handle due_date if present
    $statusToSet = null;
    if ($due_date !== null) {
        $due_date = str_replace('T', ' ', $due_date);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $due_date)) {
            $due_date .= ':00';
        }
        try { $dt = new DateTime($due_date); $due_date = $dt->format('Y-m-d H:i:s'); } catch (Exception $e) { $due_date = null; }
        $fields[] = 'due_date = ?';
        $params[] = $due_date ?: null;

        // If due_date provided and in the past, set missed
        if ($due_date) {
            try {
                $checkDt = new DateTime($due_date);
                $now = new DateTime();
                if ($checkDt < $now) {
                    $statusToSet = 'missed';
                }
            } catch (Exception $e) {}
        }
    }

    if ($title !== null) { $fields[] = 'title = ?'; $params[] = $title; }
    if ($description !== null) { $fields[] = 'description = ?'; $params[] = $description; }
    if ($assigned_to_email !== null) { $fields[] = 'assigned_to_email = ?'; $params[] = $assigned_to_email; }

    // If changing assignee, ensure the new assignee is in the same department as the updater
    if ($assigned_to_email !== null) {
        try {
            $deptStmt = $pdo->prepare('SELECT department FROM users WHERE email = ?');
            $deptStmt->execute([$byEmail]);
            $creator = $deptStmt->fetch(PDO::FETCH_ASSOC);
            $creatorDept = $creator['department'] ?? null;
            if ($creatorDept) {
                $aStmt = $pdo->prepare('SELECT department FROM users WHERE email = ?');
                $aStmt->execute([$assigned_to_email]);
                $assignee = $aStmt->fetch(PDO::FETCH_ASSOC);
                $assigneeDept = $assignee['department'] ?? null;
                if (!$assigneeDept || $assigneeDept !== $creatorDept) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'error' => 'Assignee must be in your department']);
                    exit;
                }
            }
        } catch (PDOException $__e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to validate assignee department']);
            exit;
        }
    }

    if ($attachment_path) { $fields[] = 'attachment_path = ?'; $params[] = $attachment_path; }

    if ($statusToSet) { $fields[] = 'status = ?'; $params[] = $statusToSet; }

    if (count($fields) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        exit;
    }

    $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = ? AND assigned_by_email = ?';
    $params[] = $id;
    $params[] = $byEmail;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Not allowed or task not found']);
        exit;
    }
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
