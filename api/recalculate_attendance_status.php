<?php
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
        
        // Recalculate Time In Status
        if ($timeIn) {
            $timeInTimestamp = strtotime($timeIn);
            $present_start = strtotime($date . ' 05:30:00'); // 5:30 AM
            $present_end = strtotime($date . ' 07:00:00');   // 7:00 AM
            $late_end = strtotime($date . ' 12:00:00');      // 12:00 PM
            
            if ($timeInTimestamp >= $present_start && $timeInTimestamp <= $present_end) {
                $timeInStatus = 'Present'; // 5:30 AM - 7:00 AM
            } elseif ($timeInTimestamp > $present_end && $timeInTimestamp <= $late_end) {
                $timeInStatus = 'Late'; // 7:01 AM - 12:00 PM
            } else {
                $timeInStatus = 'Absent'; // 12:01 PM onwards
            }
        }
        
        // Recalculate Time Out Status
        if ($timeOut) {
            $timeOutTimestamp = strtotime($timeOut);
            $undertime_start = strtotime($date . ' 07:30:00'); // 7:30 AM
            $undertime_end = strtotime($date . ' 16:59:59');   // 4:59:59 PM
            $ontime_start = strtotime($date . ' 17:00:00');    // 5:00 PM
            $ontime_end = strtotime($date . ' 17:05:00');      // 5:05 PM
            $overtime_start = strtotime($date . ' 17:06:00');  // 5:06 PM
            
            if ($timeOutTimestamp >= $undertime_start && $timeOutTimestamp <= $undertime_end) {
                $timeOutStatus = 'Undertime'; // 7:30 AM - 4:59 PM
            } elseif ($timeOutTimestamp >= $ontime_start && $timeOutTimestamp <= $ontime_end) {
                $timeOutStatus = 'On-time'; // 5:00 PM - 5:05 PM
            } elseif ($timeOutTimestamp >= $overtime_start) {
                $timeOutStatus = 'Overtime'; // 5:06 PM onwards
            } else {
                $timeOutStatus = 'On-time'; // Default fallback
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
