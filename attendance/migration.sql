-- ============================================
-- Attendance System Migration Script
-- Run this in phpMyAdmin after importing database.sql
-- ============================================

-- Step 1: Add employee_id and qr_code columns to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS employee_id VARCHAR(100) DEFAULT NULL UNIQUE AFTER profile_picture,
ADD COLUMN IF NOT EXISTS qr_code VARCHAR(255) DEFAULT NULL AFTER employee_id;

-- Step 2: Create attendance table (uses employee_id from users table)
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time_in DATETIME DEFAULT NULL,
    time_out DATETIME DEFAULT NULL,
    status VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_id (employee_id),
    INDEX idx_date (date),
    UNIQUE KEY unique_attendance (employee_id, date)
);

-- Step 3: Drop old employees table if it exists (not needed - we use users table)
DROP TABLE IF EXISTS employees;

-- Step 4: (Optional) Generate employee IDs for existing approved users
-- Uncomment the lines below if you want to auto-assign IDs to existing users

/*
SET @counter = 0;
SET @year = YEAR(CURDATE());

UPDATE users 
SET 
    employee_id = CONCAT('EMP', @year, '-', LPAD((@counter := @counter + 1), 4, '0')),
    qr_code = CONCAT('EMP', @year, '-', LPAD(@counter, 4, '0'))
WHERE 
    status = 'approved' 
    AND employee_id IS NULL;
*/

-- Verify the setup
SELECT 'Setup complete! Check results:' AS message;
SELECT COUNT(*) AS total_users, 
       SUM(CASE WHEN employee_id IS NOT NULL THEN 1 ELSE 0 END) AS users_with_id,
       SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_users
FROM users;

SELECT COUNT(*) AS attendance_records FROM attendance;
