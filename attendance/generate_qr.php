<?php
// Returns a JSON object with the current rotating QR URL (main index.php?qr=TOKEN)
require_once __DIR__ . '/qr_utils.php';

header('Content-Type: application/json');

// Get current server time for synchronization
date_default_timezone_set('Asia/Manila');
$serverTime = time();
$currentMinute = floor($serverTime / 60);

$token = qr_generate_token_for_min($currentMinute);

// Build the URL for QR login
// The QR should point to the main index.php (login page) directly with the QR token
// This ensures it works on both localhost and hosted environments
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// BASE_PATH contains the application root (e.g., '/capstone' or '' for root)
$base = rtrim($scheme . '://' . $host . BASE_PATH, '/');

// Point directly to main index.php for maximum hosting compatibility
$url = $base . '/index.php?qr=' . urlencode($token);

// Return server time for client synchronization (keeps 1-minute rotation working on hosting)
echo json_encode([
    'token' => $token, 
    'url' => $url,
    'serverTime' => $serverTime,
    'currentMinute' => $currentMinute
]);
