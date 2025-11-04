<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';

try {
    $email = $_SESSION['email'] ?? null;
    $dept = null;
    if ($email) {
        try {
            $s = $pdo->prepare('SELECT department FROM users WHERE email = ?');
            $s->execute([$email]);
            $r = $s->fetch(PDO::FETCH_ASSOC);
            $dept = $r['department'] ?? null;
        } catch (PDOException $__e) { /* ignore */ }
    }

    if ($dept) {
        $stmt = $pdo->prepare('SELECT id, lastname, firstname, mi, department, position, role, status, email, profile_picture, contact_no FROM users WHERE department = ?');
        $stmt->execute([$dept]);
    } else {
        $stmt = $pdo->query('SELECT id, lastname, firstname, mi, department, position, role, status, email, profile_picture, contact_no FROM users');
    }

    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'employees' => $employees]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
