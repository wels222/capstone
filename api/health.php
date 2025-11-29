<?php
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');

$result = [
    'ok' => true,
    'time' => date('c'),
    'env' => [
        'APP_ENV' => getenv('APP_ENV') ?: null,
        'TIMEZONE' => getenv('TIMEZONE') ?: null,
    ],
];

try {
    require_once __DIR__ . '/../db.php';
    // Lightweight DB ping
    $pdo->query('SELECT 1');
    $result['db'] = 'connected';
} catch (Throwable $e) {
    http_response_code(500);
    $result['db'] = 'error';
    $result['error'] = 'Database connection failed';
}

echo json_encode($result);