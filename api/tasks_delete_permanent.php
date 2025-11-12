<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

$byEmail = $_SESSION['email'] ?? null;
if (!$byEmail) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit; }

$data = $_POST; if (empty($data)) { $data = json_decode(file_get_contents('php://input'), true) ?? []; }
$id = isset($data['id']) ? (int)$data['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'Invalid id']); exit; }

try {
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ? AND assigned_by_email = ?');
    $stmt->execute([$id, $byEmail]);
    if ($stmt->rowCount() === 0) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Not allowed or task not found']); exit; }
    echo json_encode(['success'=>true, 'deleted'=>true]);
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['success'=>false,'error'=>'Database error']);
}
