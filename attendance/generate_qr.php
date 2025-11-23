<?php
// Generates a new QR token with 60-second expiration (DATABASE-BASED APPROACH)
// Hosting-compatible: Works on any hosting provider without .env files
// Auto-expires after exactly 60 seconds from creation time
require_once __DIR__ . '/qr_utils.php';

header('Content-Type: application/json');

// Generate new token and store in database with automatic 60-second expiration
$tokenData = qr_generate_new_token($pdo);

if (!$tokenData['success']) {
    echo json_encode([
        'success' => false,
        'message' => $tokenData['message'] ?? 'Failed to generate QR token'
    ]);
    exit;
}

$token = $tokenData['token'];

// Build the URL for QR login (hosting-compatible)
// Points to main index.php with QR token parameter
// Automatically detects correct domain and path for any hosting environment
$url = qr_build_attendance_url($token);

// Get current server time for client synchronization
date_default_timezone_set('Asia/Manila');
$serverTime = time();
$created = strtotime($tokenData['created_at']);
$expires = strtotime($tokenData['expires_at']);

// Return token data with precise expiration info
echo json_encode([
    'success' => true,
    'token' => $token,
    'url' => $url,
    'created_at' => $tokenData['created_at'],
    'expires_at' => $tokenData['expires_at'],
    'server_time' => $serverTime,
    'server_time_formatted' => date('Y-m-d H:i:s', $serverTime),
    'expires_in_seconds' => max(0, $expires - $serverTime), // Remaining seconds
    'valid_duration' => 60 // Token is valid for 60 seconds
]);
