<?php
/**
 * QR Token Cleanup Script
 * 
 * This script removes expired QR tokens from the database.
 * Can be run via:
 * 1. Cron job (recommended for hosting): Run every 5-10 minutes
 * 2. Manual execution: php cleanup_qr_tokens.php
 * 3. Auto-called by generate_qr.php (already implemented)
 * 
 * For hosting setup, add this to your cron jobs:
 * Run every 5 minutes: php /path/to/your/site/cleanup_qr_tokens.php
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/attendance/qr_utils.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Run cleanup
$deleted = qr_cleanup_expired_tokens($pdo);

// Log result (optional)
$logMessage = date('Y-m-d H:i:s') . " - Cleaned up {$deleted} expired QR tokens\n";

// Output for cron job logs
echo $logMessage;

// Optionally write to log file
$logFile = __DIR__ . '/logs/qr_cleanup.log';
$logDir = dirname($logFile);

if (!file_exists($logDir)) {
    @mkdir($logDir, 0755, true);
}

if (is_writable($logDir) || file_exists($logFile)) {
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Success response
http_response_code(200);
echo "Cleanup completed successfully.\n";
