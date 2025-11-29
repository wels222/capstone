<?php
require_once __DIR__ . '/_bootstrap.php';
// api/super_admin_dept_heads.php
require_once '../db.php';
header('Content-Type: application/json');
$heads = $pdo->query('SELECT * FROM dept_heads')->fetchAll();
echo json_encode($heads);