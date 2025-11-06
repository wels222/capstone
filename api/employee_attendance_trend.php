<?php
session_start();
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    // get employee id for logged-in user
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare('SELECT employee_id FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $employeeId = $row['employee_id'] ?? null;
    if (!$employeeId) {
        echo json_encode(['success' => false, 'error' => 'No employee id for user']);
        exit();
    }

    $range = $_GET['range'] ?? 'daily';
    $trend = [];

    if ($range === 'daily') {
        // last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $d = new DateTime();
            $d->modify("-{$i} days");
            $date = $d->format('Y-m-d');
            $stmt = $pdo->prepare('SELECT time_in_status, time_out_status FROM attendance WHERE employee_id = ? AND date = ? LIMIT 1');
            $stmt->execute([$employeeId, $date]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $present = ($r && $r['time_in_status'] === 'Present') ? 1 : 0;
            $late = ($r && $r['time_in_status'] === 'Late') ? 1 : 0;
            $undertime = ($r && $r['time_out_status'] === 'Undertime') ? 1 : 0;
            $overtime = ($r && $r['time_out_status'] === 'Overtime') ? 1 : 0;
            $trend[] = ['label' => $date, 'present' => $present, 'late' => $late, 'undertime' => $undertime, 'overtime' => $overtime];
        }
    } elseif ($range === 'weekly') {
        // last 12 weeks
        for ($w = 11; $w >= 0; $w--) {
            $start = new DateTime('monday this week');
            $start->modify("-{$w} weeks");
            $end = clone $start;
            $end->modify('+6 days');
            $stmt = $pdo->prepare('SELECT date, time_in_status, time_out_status FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ?');
            $stmt->execute([$employeeId, $start->format('Y-m-d'), $end->format('Y-m-d')]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $counts = ['present' => 0, 'late' => 0, 'undertime' => 0, 'overtime' => 0];
            foreach ($rows as $r) {
                if ($r['time_in_status'] === 'Present') $counts['present']++;
                if ($r['time_in_status'] === 'Late') $counts['late']++;
                if ($r['time_out_status'] === 'Undertime') $counts['undertime']++;
                if ($r['time_out_status'] === 'Overtime') $counts['overtime']++;
            }
            $trend[] = ['label' => $start->format('Y-m-d'), 'present' => $counts['present'], 'late' => $counts['late'], 'undertime' => $counts['undertime'], 'overtime' => $counts['overtime']];
        }
    } else {
        // monthly - last 12 months
        for ($m = 11; $m >= 0; $m--) {
            $start = new DateTime('first day of this month');
            $start->modify("-{$m} months");
            $end = clone $start;
            $end->modify('last day of this month');
            $stmt = $pdo->prepare('SELECT date, time_in_status, time_out_status FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ?');
            $stmt->execute([$employeeId, $start->format('Y-m-d'), $end->format('Y-m-d')]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $counts = ['present' => 0, 'late' => 0, 'undertime' => 0, 'overtime' => 0];
            foreach ($rows as $r) {
                if ($r['time_in_status'] === 'Present') $counts['present']++;
                if ($r['time_in_status'] === 'Late') $counts['late']++;
                if ($r['time_out_status'] === 'Undertime') $counts['undertime']++;
                if ($r['time_out_status'] === 'Overtime') $counts['overtime']++;
            }
            $trend[] = ['label' => $start->format('Y-m'), 'present' => $counts['present'], 'late' => $counts['late'], 'undertime' => $counts['undertime'], 'overtime' => $counts['overtime']];
        }
    }

    echo json_encode(['success' => true, 'range' => $range, 'trend' => $trend]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

