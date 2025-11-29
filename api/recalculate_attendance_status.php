<?php
require_once __DIR__ . '/_bootstrap.php';
/**
 * Recalculate all attendance status based on new time ranges
 * This will update all existing records to match the new rules
 */
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

try {
    // Fetch all attendance records
    $stmt = $pdo->query('SELECT id, date, time_in, time_out FROM attendance WHERE time_in IS NOT NULL OR time_out IS NOT NULL');
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    
    foreach ($records as $rec) {
        $id = $rec['id'];
        $date = $rec['date'];
        $timeIn = $rec['time_in'];
        $timeOut = $rec['time_out'];
        
        $timeInStatus = null;
        $timeOutStatus = null;
        
        // Recalculate Time In Status (new rules)
        if ($timeIn) {
            $timeInTimestamp = strtotime($timeIn);
            $present_start = strtotime($date . ' 06:00:00'); // 6:00 AM
            $present_end = strtotime($date . ' 08:00:00');   // 8:00 AM
            $late_end = strtotime($date . ' 12:00:00');      // 12:00 PM
            $undertime_end = strtotime($date . ' 17:00:00'); // 5:00 PM

            if ($timeInTimestamp < $present_start) {
                $timeInStatus = 'Present';
            } elseif ($timeInTimestamp <= $present_end) {
                $timeInStatus = 'Present';
            } elseif ($timeInTimestamp <= $late_end) {
                $timeInStatus = 'Late';
            } elseif ($timeInTimestamp <= $undertime_end) {
                $timeInStatus = 'Undertime';
            } else {
                $timeInStatus = 'Absent';
            }
        }
        
        // Recalculate Time Out Status (new rules)
        if ($timeOut) {
            $timeOutTimestamp = strtotime($timeOut);
            $undertime_end = strtotime($date . ' 16:59:59');   // 4:59:59 PM
            $out_start = strtotime($date . ' 17:00:00');       // 5:00 PM
            $out_end = strtotime($date . ' 17:05:00');         // 5:05 PM
            $overtime_start = strtotime($date . ' 18:00:00');  // 6:00 PM

            if ($timeOutTimestamp <= $undertime_end) {
                $timeOutStatus = 'Undertime';
            } elseif ($timeOutTimestamp >= $out_start && $timeOutTimestamp <= $out_end) {
                $timeOutStatus = 'Out';
            } elseif ($timeOutTimestamp >= $overtime_start) {
                $timeOutStatus = 'Overtime';
            } else {
                $timeOutStatus = 'Out';
            }
        }
        
        // Update the record
        $updateSql = 'UPDATE attendance SET ';
        $params = [];
        $updates = [];
        
        if ($timeInStatus !== null) {
            $updates[] = 'time_in_status = ?';
            $params[] = $timeInStatus;
        }
        
        if ($timeOutStatus !== null) {
            $updates[] = 'time_out_status = ?';
            $params[] = $timeOutStatus;
        }
        
        if (!empty($updates)) {
            $updateSql .= implode(', ', $updates) . ' WHERE id = ?';
            $params[] = $id;
            
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute($params);
            $updated++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Recalculated attendance status for all records',
        'total_records' => count($records),
        'updated' => $updated
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
