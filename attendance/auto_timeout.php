<?php
/**
 * Auto Timeout Script
 * If an employee has a time-in but no time-out by 9:00 PM,
 * mark their status as 'Forgotten' (no auto time_out value).
 *
 * Run daily at or after 9:00 PM Asia/Manila.
 */

require_once __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Manila');

$today = date('Y-m-d');
$nowTime = strtotime(date('H:i:s'));
$runAfter = strtotime('21:00:00'); // 9:00 PM

header('Content-Type: application/json');

if ($nowTime < $runAfter) {
    echo json_encode([
        'success' => false,
        'date' => $today,
        'updated' => 0,
        'message' => 'It is not yet 9:00 PM Manila time. No action taken.'
    ]);
    exit;
}

try {
    // Find today's records with time_in present and missing time_out
    $stmt = $pdo->prepare('SELECT id FROM attendance WHERE date = ? AND time_in IS NOT NULL AND time_out IS NULL');
    $stmt->execute([$today]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;

    if (!empty($rows)) {
        $upd = $pdo->prepare('UPDATE attendance SET time_out_status = ? WHERE id = ?');
        foreach ($rows as $r) {
            $upd->execute(['Forgotten', $r['id']]);
            $updated++;
        }
    }

    echo json_encode([
        'success' => true,
        'date' => $today,
        'updated' => $updated,
        'message' => "Marked {$updated} records as 'Forgotten' (no time-out recorded)."
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'date' => $today,
        'updated' => 0,
        'error' => $e->getMessage()
    ]);
}

?>
