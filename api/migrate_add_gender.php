<?php
/**
 * Migration: Add gender column to users table
 * This migration adds a gender field (M/F) to support gender-based leave filtering
 */
require_once __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json');
require_once __DIR__ . '/../auth_guard.php';
require_api_auth(['hr', 'super_admin']);
require_once __DIR__ . '/../db.php';

$results = [];
$errors = [];

try {
    // Check if gender column already exists
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) as cnt 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'gender'
    ");
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && intval($result['cnt']) === 0) {
        // Add gender column after contact_no
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN gender ENUM('M','F') DEFAULT NULL 
            COMMENT 'Gender: M=Male, F=Female' 
            AFTER contact_no
        ");
        $results[] = "Successfully added 'gender' column to users table";
    } else {
        $results[] = "Gender column already exists, no action needed";
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Migration completed successfully',
        'results' => $results,
        'errors' => $errors
    ]);
    
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Migration failed',
        'results' => $results,
        'errors' => $errors
    ]);
}