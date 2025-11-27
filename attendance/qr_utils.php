<?php
require_once __DIR__ . '/../db.php';

function qr_generate_new_token($pdo) {
    qr_cleanup_expired_tokens($pdo);
    try { $randomPart = bin2hex(random_bytes(16)); } catch (Exception $e) { $randomPart = bin2hex(openssl_random_pseudo_bytes(16)); }
    $timestamp = microtime(true);
    $token = rtrim(strtr($randomPart . '_' . str_replace('.', '', $timestamp), '+/', '-_'), '=');
    date_default_timezone_set('Asia/Manila');
    $created_at = date('Y-m-d H:i:s');
    $expires_at = date('Y-m-d H:i:s', strtotime($created_at) + 60);
    try {
        $stmt = $pdo->prepare('INSERT INTO qr_tokens (token, created_at, expires_at, is_used) VALUES (?, ?, ?, 0)');
        $stmt->execute([$token, $created_at, $expires_at]);
        return ['success'=>true,'token'=>$token,'created_at'=>$created_at,'expires_at'=>$expires_at,'expires_in_seconds'=>60];
    } catch (PDOException $e) {
        return ['success'=>false,'message'=>'Failed to generate token: '.$e->getMessage()];
    }
}

function qr_verify_token($pdo, $token) {
    if (!$token) return false;
    date_default_timezone_set('Asia/Manila');
    $now = date('Y-m-d H:i:s');
    try {
        $stmt = $pdo->prepare('SELECT id FROM qr_tokens WHERE token = ? AND expires_at > ? LIMIT 1');
        $stmt->execute([$token, $now]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { return false; }
}

function qr_mark_token_used($pdo, $token, $userId = null) { return true; }

function qr_cleanup_expired_tokens($pdo) {
    date_default_timezone_set('Asia/Manila');
    $cutoff = date('Y-m-d H:i:s', strtotime('-5 minutes'));
    try {
        $stmt = $pdo->prepare('DELETE FROM qr_tokens WHERE created_at < ?');
        $stmt->execute([$cutoff]);
        return $stmt->rowCount();
    } catch (PDOException $e) { return 0; }
}

function qr_build_attendance_url($token) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    $base = rtrim($scheme . '://' . $host . $basePath, '/');
    return $base . '/index.php?qr=' . urlencode($token);
}

function qr_record_attendance_for_user($pdo, $userId) {
    $stmt = $pdo->prepare('SELECT employee_id FROM users WHERE id = ? AND status = "approved"');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['employee_id'])) return ['success'=>false,'message'=>'No employee id found or user not approved'];
    $employee_code = $row['employee_id'];
    date_default_timezone_set('Asia/Manila');
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare('SELECT * FROM attendance WHERE employee_id = ? AND date = ?');
    $stmt->execute([$employee_code, $today]);
    $att = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$att) {
        $ts = strtotime($now);
        $present_start = strtotime($today.' 06:00:00');
        $present_end   = strtotime($today.' 08:00:00');
        $late_end      = strtotime($today.' 12:00:00');
        $undertime_end = strtotime($today.' 17:00:00');
        if ($ts < $present_start) {
            $time_in_status = 'Present';
            $ins = $pdo->prepare('INSERT INTO attendance (employee_id, date, time_in, time_in_status) VALUES (?, ?, ?, ?)');
            $ins->execute([$employee_code, $today, $now, $time_in_status]);
        } elseif ($ts <= $present_end) {
            $time_in_status = 'Present';
            $ins = $pdo->prepare('INSERT INTO attendance (employee_id, date, time_in, time_in_status) VALUES (?, ?, ?, ?)');
            $ins->execute([$employee_code, $today, $now, $time_in_status]);
        } elseif ($ts <= $late_end) {
            $time_in_status = 'Late';
            $ins = $pdo->prepare('INSERT INTO attendance (employee_id, date, time_in, time_in_status) VALUES (?, ?, ?, ?)');
            $ins->execute([$employee_code, $today, $now, $time_in_status]);
        } elseif ($ts <= $undertime_end) {
            $time_in_status = 'Undertime';
            $ins = $pdo->prepare('INSERT INTO attendance (employee_id, date, time_in, time_in_status) VALUES (?, ?, ?, ?)');
            $ins->execute([$employee_code, $today, $now, $time_in_status]);
        } else {
            // After 5:00 PM, mark as Absent and lock the record (no time_in)
            $time_in_status = 'Absent';
            $ins = $pdo->prepare('INSERT INTO attendance (employee_id, date, time_in, time_in_status) VALUES (?, ?, NULL, ?)');
            $ins->execute([$employee_code, $today, $time_in_status]);
        }
        $result = ['success'=>true,'action'=>'time_in','time'=>$now,'status'=>$time_in_status];
    } else {
        // If already marked Absent for today, keep it locked as Absent regardless of attempts
        $curInStatus = strtolower($att['time_in_status'] ?? '');
        if ($curInStatus === 'absent') {
            return ['success'=>true,'action'=>'time_in','time'=>$now,'status'=>'Absent'];
        }

        if (!empty($att['time_out'])) return ['success'=>false,'message'=>'Time out already recorded for today'];

        $time_out = $now;
        $out_ts = strtotime($time_out);
        $nine_pm      = strtotime($today.' 21:00:00');
        $undertime_end = strtotime($today.' 16:59:59');
        $out_start     = strtotime($today.' 17:00:00');
        $out_end       = strtotime($today.' 17:05:00');
        $overtime_start= strtotime($today.' 18:00:00');

        // After 9:00 PM, mark as Forgotten and do not set time_out
        if ($out_ts >= $nine_pm) {
            $upd = $pdo->prepare('UPDATE attendance SET time_out_status = ? WHERE id = ?');
            $upd->execute(['Forgotten', $att['id']]);
            return ['success'=>true,'action'=>'time_out','time'=>$time_out,'status'=>'Forgotten','time_in_status'=>$att['time_in_status']];
        }

        if ($out_ts <= $undertime_end) $time_out_status = 'Undertime';
        elseif ($out_ts >= $out_start && $out_ts <= $out_end) $time_out_status = 'Out';
        elseif ($out_ts >= $overtime_start) $time_out_status = 'Overtime';
        else $time_out_status = 'Out';
        $upd = $pdo->prepare('UPDATE attendance SET time_out = ?, time_out_status = ? WHERE id = ?');
        $upd->execute([$time_out, $time_out_status, $att['id']]);
        $result = ['success'=>true,'action'=>'time_out','time'=>$time_out,'status'=>$time_out_status,'time_in_status'=>$att['time_in_status']];
    }
    try {
        $u = $pdo->prepare('SELECT firstname, lastname, profile_picture FROM users WHERE id = ?');
        $u->execute([$userId]);
        $usr = $u->fetch(PDO::FETCH_ASSOC);
        $fullName = $usr ? trim(($usr['firstname'] ?? '').' '.($usr['lastname'] ?? '')) : '';
        $profilePic = ($usr && !empty($usr['profile_picture'])) ? $usr['profile_picture'] : '';
        $last = [
            'user_id'=>$userId,
            'employee_id'=>$employee_code,
            'name'=>$fullName,
            'profile_picture'=>$profilePic,
            'action'=>$result['action'],
            'status'=>$result['status'],
            'time'=>$result['time'],
            'time_in_status'=>$result['time_in_status'] ?? '',
            'recorded_at'=>date('c')
        ];
        file_put_contents(__DIR__.'/last_scan.json', json_encode($last));
    } catch (Exception $e) { }
    return $result;
}

// End of file
