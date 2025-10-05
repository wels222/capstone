<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    $stmt = $pdo->query('SELECT id, lastname, firstname, mi, department, position, status, email, profile_picture FROM users');
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'employees' => $employees]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
