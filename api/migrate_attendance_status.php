<?php
require_once __DIR__ . '/_bootstrap.php';
/**
 * Migration script to update attendance table ENUM values
 * Run this once to update the database schema
 */
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

try {
    // Update time_in_status ENUM to include 'Present', 'Late', 'Undertime', 'Absent'
    $sql1 = "ALTER TABLE attendance MODIFY COLUMN time_in_status ENUM('Present', 'Late', 'Undertime', 'Absent', 'on_time', 'late', 'absent') DEFAULT NULL";
    $pdo->exec($sql1);
    
    // Update time_out_status ENUM to include 'Out', 'On-time', 'Undertime', 'Overtime' (keep On-time for backward compat)
    $sql2 = "ALTER TABLE attendance MODIFY COLUMN time_out_status ENUM('Out', 'On-time', 'Undertime', 'Overtime', 'on_time', 'early', 'absent') DEFAULT NULL";
    $pdo->exec($sql2);
    
    // Migrate existing data
    // Convert old values to new values for time_in_status
    $pdo->exec("UPDATE attendance SET time_in_status = 'Present' WHERE time_in_status = 'on_time'");
    $pdo->exec("UPDATE attendance SET time_in_status = 'Late' WHERE time_in_status = 'late'");
    $pdo->exec("UPDATE attendance SET time_in_status = 'Absent' WHERE time_in_status = 'absent'");
    
    // Convert old values to new values for time_out_status
    $pdo->exec("UPDATE attendance SET time_out_status = 'On-time' WHERE time_out_status = 'on_time'");
    $pdo->exec("UPDATE attendance SET time_out_status = 'Undertime' WHERE time_out_status = 'early'");
    
    // Now remove old ENUM values (optional - keeps backward compatibility if commented out)
    $sql3 = "ALTER TABLE attendance MODIFY COLUMN time_in_status ENUM('Present', 'Late', 'Undertime', 'Absent') DEFAULT NULL";
    $pdo->exec($sql3);
    
    $sql4 = "ALTER TABLE attendance MODIFY COLUMN time_out_status ENUM('Out', 'On-time', 'Undertime', 'Overtime') DEFAULT NULL";
    $pdo->exec($sql4);

    // Optionally convert existing 'On-time' to 'Out'
    $pdo->exec("UPDATE attendance SET time_out_status = 'Out' WHERE time_out_status = 'On-time'");
    
    echo json_encode([
        'success' => true,
        'message' => 'Attendance status columns updated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
