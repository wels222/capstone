<?php
// Utility functions for QR rotating token generation and verification
// and a helper to record attendance for a logged-in user.
require_once __DIR__ . '/../db.php';

function qr_generate_token_for_min($minuteTimestamp = null) {
    // Use minute precision (floor(time()/60))
    if ($minuteTimestamp === null) $minuteTimestamp = floor(time() / 60);
    $payload = (string)$minuteTimestamp;
    $sig = hash_hmac('sha256', $payload, QR_SECRET);
    $token = base64_encode($payload . ':' . $sig);
    // Make token URL-safe
    return rtrim(strtr($token, '+/', '-_'), '=');
}

function qr_decode_token($token) {
    $token = strtr($token, '-_', '+/');
    $pad = strlen($token) % 4;
    if ($pad) $token .= str_repeat('=', 4 - $pad);
    $decoded = base64_decode($token);
    if (!$decoded) return false;
    $parts = explode(':', $decoded);
    if (count($parts) !== 2) return false;
    return ['minute' => (int)$parts[0], 'sig' => $parts[1]];
}

function qr_verify_token($token, $allowedSkewMinutes = 0) {
    $data = qr_decode_token($token);
    if (!$data) return false;
    $minute = $data['minute'];
    $sig = $data['sig'];
    // Check within allowed skew minutes. Default is 0 so only the current minute is accepted
    $nowMin = floor(time() / 60);
    for ($i = -$allowedSkewMinutes; $i <= $allowedSkewMinutes; $i++) {
        $cand = (string)($nowMin + $i);
        $expected = hash_hmac('sha256', $cand, QR_SECRET);
        if (hash_equals($expected, $sig) && $minute == (int)$cand) {
            return true;
        }
    }
    return false;
}

function qr_build_attendance_url($token) {
    // Builds full URL to index.php with qr token
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
        // Insert time in
        $timeInTimestamp = strtotime($now);
        $present_start = strtotime($today . ' 05:30:00');
        $present_end = strtotime($today . ' 07:00:00');
        $late_end = strtotime($today . ' 12:00:00');
        if ($timeInTimestamp >= $present_start && $timeInTimestamp <= $present_end) {
            $time_in_status = 'Present';
        } elseif ($timeInTimestamp > $present_end && $timeInTimestamp <= $late_end) {
            $time_in_status = 'Late';
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
        // Record time out
        $time_out = $now;
        $time_out_timestamp = strtotime($time_out);
        $undertime_start = strtotime($today . ' 07:30:00');
        $undertime_end = strtotime($today . ' 16:59:59');
        $ontime_start = strtotime($today . ' 17:00:00');
        $ontime_end = strtotime($today . ' 17:05:00');
        $overtime_start = strtotime($today . ' 17:06:00');

        if ($time_out_timestamp >= $undertime_start && $time_out_timestamp <= $undertime_end) {
            $time_out_status = 'Undertime';
        } elseif ($time_out_timestamp >= $ontime_start && $time_out_timestamp <= $ontime_end) {
            $time_out_status = 'On-time';
        } elseif ($time_out_timestamp >= $overtime_start) {
            $time_out_status = 'Overtime';
        } else {
            $time_out_status = 'On-time';
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
