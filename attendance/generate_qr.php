<?php
// Returns a JSON object with the current rotating QR URL (attendance/index.php?qr=...)
require_once __DIR__ . '/qr_utils.php';

header('Content-Type: application/json');


$token = qr_generate_token_for_min();

// Build explicit attendance path (compatibility for scanners that expect attendance/index.php)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Compute application root (strip trailing /attendance from BASE_PATH if present)
$appRoot = preg_replace('#/attendance$#', '', BASE_PATH);
$base = rtrim($scheme . '://' . $host . $appRoot, '/');

// Final target should be {host}{appRoot}/attendance/index.php
$url = $base . '/attendance/index.php?qr=' . urlencode($token);

echo json_encode(['token' => $token, 'url' => $url]);
