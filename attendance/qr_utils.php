<?php
// Utility functions for QR token generation and verification (DATABASE-BASED APPROACH)
// This hosting-compatible approach stores tokens in database with automatic 60-second expiration
// Works on any hosting provider without .env files or file permission issues
require_once __DIR__ . '/../db.php';

/**
 * Generate a new QR token and store in database with 60-second expiration
 * Uses device real-time for accurate expiration tracking
 * @param PDO $pdo Database connection
 * @return array Token data including token string, created_at, expires_at
 */
function qr_generate_new_token($pdo) {
    // Clean up expired tokens first (automatic cleanup)
    qr_cleanup_expired_tokens($pdo);
    
    // Generate unique token using random bytes + timestamp for uniqueness
    try {
        $randomPart = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $randomPart = bin2hex(openssl_random_pseudo_bytes(16));
    }
    
    // Add timestamp for uniqueness and sorting
    $timestamp = microtime(true);
    $token = $randomPart . '_' . str_replace('.', '', $timestamp);
    
    // Make URL-safe
    $token = rtrim(strtr($token, '+/', '-_'), '=');
    
    // Set expiration to exactly 60 seconds from now (uses device real-time)
    date_default_timezone_set('Asia/Manila');
    $created_at = date('Y-m-d H:i:s');
    $expires_at = date('Y-m-d H:i:s', strtotime($created_at) + 60); // 60 seconds expiration
    
    // Store in database
    try {
        $stmt = $pdo->prepare("INSERT INTO qr_tokens (token, created_at, expires_at, is_used) VALUES (?, ?, ?, 0)");
        $stmt->execute([$token, $created_at, $expires_at]);
        
        return [
            'success' => true,
            'token' => $token,
            'created_at' => $created_at,
            'expires_at' => $expires_at,
            'expires_in_seconds' => 60
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Failed to generate token: ' . $e->getMessage()
        ];
    }
}

/**
 * Verify QR token from database
 * Checks if token exists, not expired, and not already used
 * @param PDO $pdo Database connection
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function qr_verify_token($pdo, $token) {
    if (empty($token)) return false;
    
    date_default_timezone_set('Asia/Manila');
    $now = date('Y-m-d H:i:s');
    
    try {
        // Check if token exists, not expired, and not used
        $stmt = $pdo->prepare("
            SELECT * FROM qr_tokens 
            WHERE token = ? 
            AND expires_at > ? 
            AND is_used = 0 
            LIMIT 1
        ");
        $stmt->execute([$token, $now]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Mark token as used after successful attendance recording
 * @param PDO $pdo Database connection
 * @param string $token Token to mark as used
 * @param int $userId User ID who used the token
 * @return bool Success status
 */
function qr_mark_token_used($pdo, $token, $userId = null) {
    try {
        $stmt = $pdo->prepare("UPDATE qr_tokens SET is_used = 1, used_by_user_id = ? WHERE token = ?");
        $stmt->execute([$userId, $token]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Clean up expired tokens from database (automatic maintenance)
 * Removes tokens older than 5 minutes to keep database clean
 * @param PDO $pdo Database connection
 * @return int Number of deleted tokens
 */
function qr_cleanup_expired_tokens($pdo) {
    date_default_timezone_set('Asia/Manila');
    $cutoff = date('Y-m-d H:i:s', strtotime('-5 minutes')); // Keep last 5 minutes for logging
    
    try {
        $stmt = $pdo->prepare("DELETE FROM qr_tokens WHERE created_at < ?");
        $stmt->execute([$cutoff]);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Build full attendance URL for QR code (hosting-compatible)
 * Automatically detects correct URL for any hosting environment
 * @param string $token Token to include in URL
 * @return string Full URL
 */
function qr_build_attendance_url($token) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim($scheme . '://' . $host . BASE_PATH, '/');
    return $base . '/index.php?qr=' . urlencode($token);
}

function qr_record_attendance_for_user($pdo, $userId) {
    // Lookup employee id
    $stmt = $pdo->prepare('SELECT employee_id FROM users WHERE id = ? AND status = "approved"');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row || empty($row['employee_id'])) return ['success'=>false, 'message'=>'No employee id found or user not approved'];

    $employee_code = $row['employee_id'];
    date_default_timezone_set('Asia/Manila');
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');

    // Check existing record
    $stmt = $pdo->prepare('SELECT * FROM attendance WHERE employee_id = ? AND date = ?');
    $stmt->execute([$employee_code, $today]);
    $att = $stmt->fetch();

    if (!$att) {
        // Insert time in (apply unified rules)
        $timeInTimestamp = strtotime($now);
        $present_start = strtotime($today . ' 06:00:00'); // 6:00 AM
        $present_end = strtotime($today . ' 08:00:00');   // 8:00 AM
        $late_end = strtotime($today . ' 12:00:00');      // 12:00 PM
        $undertime_end = strtotime($today . ' 17:00:00'); // 5:00 PM

        if ($timeInTimestamp < $present_start) {
            $time_in_status = 'Present';
        } elseif ($timeInTimestamp <= $present_end) {
            $time_in_status = 'Present';
        } elseif ($timeInTimestamp <= $late_end) {
            $time_in_status = 'Late';
        } elseif ($timeInTimestamp <= $undertime_end) {
            $time_in_status = 'Undertime';
        } else {
            $time_in_status = 'Absent';
        }

        $ins = $pdo->prepare('INSERT INTO attendance (employee_id, date, time_in, time_in_status) VALUES (?, ?, ?, ?)');
        $ins->execute([$employee_code, $today, $now, $time_in_status]);
        $result = ['success'=>true, 'action'=>'time_in', 'time'=>$now, 'status'=>$time_in_status];
        // Save last scan info for scanner station
        try {
            $u = $pdo->prepare('SELECT firstname, lastname, profile_picture FROM users WHERE id = ?');
            $u->execute([$userId]);
            $usr = $u->fetch(PDO::FETCH_ASSOC);
            $fullName = $usr ? trim(($usr['firstname'] ?? '') . ' ' . ($usr['lastname'] ?? '')) : '';
            $profilePic = $usr && !empty($usr['profile_picture']) ? $usr['profile_picture'] : '';
            $last = [
                'user_id' => $userId,
                'employee_id' => $employee_code,
                'name' => $fullName,
                'profile_picture' => $profilePic,
                'action' => 'time_in',
                'status' => $time_in_status,
                'time' => $now,
                'recorded_at' => date('c')
            ];
            file_put_contents(__DIR__ . '/last_scan.json', json_encode($last));
        } catch (Exception $e) {
            // ignore file write errors
        }
        return $result;
    } else {
        if ($att['time_out']) {
            // Save last scan attempt (already timed out)
            try {
                $u = $pdo->prepare('SELECT firstname, lastname, profile_picture FROM users WHERE id = ?');
                $u->execute([$userId]);
                $usr = $u->fetch(PDO::FETCH_ASSOC);
                $fullName = $usr ? trim(($usr['firstname'] ?? '') . ' ' . ($usr['lastname'] ?? '')) : '';
                $profilePic = $usr && !empty($usr['profile_picture']) ? $usr['profile_picture'] : '';
                $last = [
                    'user_id' => $userId,
                    'employee_id' => $employee_code,
                    'name' => $fullName,
                    'profile_picture' => $profilePic,
                    'action' => 'already_timedout',
                    'status' => '',
                    'time' => date('Y-m-d H:i:s'),
                    'recorded_at' => date('c')
                ];
                file_put_contents(__DIR__ . '/last_scan.json', json_encode($last));
            } catch (Exception $e) {}
            return ['success'=>false, 'message'=>'Time out already recorded for today'];
        }
        // Record time out (apply unified rules)
        $time_out = $now;
        $time_out_timestamp = strtotime($time_out);
        $undertime_end = strtotime($today . ' 16:59:59');   // 4:59:59 PM
        $out_start = strtotime($today . ' 17:00:00');       // 5:00 PM
        $out_end = strtotime($today . ' 17:05:00');         // 5:05 PM
        $overtime_start = strtotime($today . ' 18:00:00');  // 6:00 PM

        if ($time_out_timestamp <= $undertime_end) {
            $time_out_status = 'Undertime';
        } elseif ($time_out_timestamp >= $out_start && $time_out_timestamp <= $out_end) {
            $time_out_status = 'Out';
        } elseif ($time_out_timestamp >= $overtime_start) {
            $time_out_status = 'Overtime';
        } else {
            $time_out_status = 'Out'; // 5:05:01 PM - 5:59:59 PM
        }

        $upd = $pdo->prepare('UPDATE attendance SET time_out = ?, time_out_status = ? WHERE id = ?');
        $upd->execute([$time_out, $time_out_status, $att['id']]);

        $result = ['success'=>true, 'action'=>'time_out', 'time'=>$time_out, 'status'=>$time_out_status, 'time_in_status'=>$att['time_in_status']];
        // Save last scan info for scanner station
        try {
            $u = $pdo->prepare('SELECT firstname, lastname, profile_picture FROM users WHERE id = ?');
            $u->execute([$userId]);
            $usr = $u->fetch(PDO::FETCH_ASSOC);
            $fullName = $usr ? trim(($usr['firstname'] ?? '') . ' ' . ($usr['lastname'] ?? '')) : '';
            $profilePic = $usr && !empty($usr['profile_picture']) ? $usr['profile_picture'] : '';
            $last = [
                'user_id' => $userId,
                'employee_id' => $employee_code,
                'name' => $fullName,
                'profile_picture' => $profilePic,
                'action' => 'time_out',
                'status' => $time_out_status,
                'time' => $time_out,
                'time_in_status' => $att['time_in_status'] ?? '',
                'recorded_at' => date('c')
            ];
            file_put_contents(__DIR__ . '/last_scan.json', json_encode($last));
        } catch (Exception $e) {
            // ignore
        }
        return $result;
    }
}

// End of file
