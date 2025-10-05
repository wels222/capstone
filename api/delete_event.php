<?php
header('Content-Type: application/json');
require_once '../db.php';
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
if (!$id) { http_response_code(400); echo json_encode(['error'=>'Missing event id']); exit; }
try {
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success'=>true]);
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['error'=>'Delete failed']);
}
?>
