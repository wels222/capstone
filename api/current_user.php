<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

$email = $_SESSION['email'] ?? null;
if (!$email) {
    echo json_encode(['logged_in' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT firstname, lastname, mi, department, position, email FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        echo json_encode(array_merge(['logged_in' => true], $u));
        exit;
    }
    echo json_encode(['logged_in' => false]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
}
