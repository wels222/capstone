-- users table: includes role, contact number, and normalized status values
CREATE TABLE IF NOT EXISTS users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	lastname VARCHAR(100) NOT NULL,
	firstname VARCHAR(100) NOT NULL,
	mi CHAR(1),
	department VARCHAR(100) NOT NULL,
	position ENUM('Permanent','Casual','JO','OJT') NOT NULL DEFAULT 'Permanent',
	role ENUM('employee','department_head','hr') NOT NULL DEFAULT 'employee',
	status ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending',
	contact_no VARCHAR(50) DEFAULT NULL,
	email VARCHAR(100) NOT NULL UNIQUE,
	password VARCHAR(255) NOT NULL,
	profile_picture MEDIUMTEXT,
	employee_id VARCHAR(100) DEFAULT NULL UNIQUE,
	qr_code VARCHAR(255) DEFAULT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    time VARCHAR(50),
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
);

-- Persist leave requests
CREATE TABLE IF NOT EXISTS leave_requests (
	id INT AUTO_INCREMENT PRIMARY KEY,
	employee_email VARCHAR(100) NOT NULL,
	dept_head_email VARCHAR(100) NOT NULL,
	leave_type VARCHAR(255) NOT NULL,
	dates VARCHAR(255) NOT NULL,
	reason TEXT,
	signature_path VARCHAR(255) DEFAULT NULL,
	details LONGTEXT DEFAULT NULL,
	request_token VARCHAR(100) NULL,
	status ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending',
	decline_reason TEXT DEFAULT NULL,
	approved_by_hr TINYINT(1) NOT NULL DEFAULT 0,
	applied_at DATETIME NOT NULL,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY uq_request_token (request_token)
);

-- Persist employee signature for reuse
CREATE TABLE IF NOT EXISTS employee_signatures (
	id INT AUTO_INCREMENT PRIMARY KEY,
	employee_email VARCHAR(100) NOT NULL UNIQUE,
	file_path VARCHAR(255) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'recall',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tasks assigned by department heads to employees
CREATE TABLE IF NOT EXISTS tasks (
	id INT AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(255) NOT NULL,
	description TEXT,
	due_date DATETIME DEFAULT NULL,
	status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
	assigned_to_email VARCHAR(100) NOT NULL,
	assigned_by_email VARCHAR(100) NOT NULL,
	attachment_path VARCHAR(255) DEFAULT NULL,
	submission_file_path VARCHAR(255) DEFAULT NULL,
	submission_note TEXT DEFAULT NULL,
	completed_at DATETIME DEFAULT NULL,
	ack_note TEXT DEFAULT NULL,
	ack_at DATETIME DEFAULT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

-- Attendance records table for QR-based attendance
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
