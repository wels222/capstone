-- Seed: 3 users per department + varied attendance
-- Generated on 2025-11-19

USE `capstone`;

-- All accounts share password: 123456 (bcrypt)
SET @pwd := '$2y$10$RAaicR8c3Un8fXrTbr.M1OZH/PBdcJOrRPa.gUc.hzIJU8nLk9XhO';

-- NOTE: This script assumes a clean database. If duplicates occur (email/employee_id), adjust IDs or delete existing rows.

-- === Users ===
-- Columns: lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id

-- Office of the Municipal Mayor
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('Mayor', 'EmpOne', NULL, 'Office of the Municipal Mayor', 'Permanent', 'employee', 'approved', '09170000001', 'mayor.emp1@example.com', @pwd, 'EMP2025-1001'),
('Mayor', 'EmpTwo', NULL, 'Office of the Municipal Mayor', 'Casual', 'employee', 'approved', '09170000002', 'mayor.emp2@example.com', @pwd, 'EMP2025-1002'),
('Mayor', 'EmpThree', NULL, 'Office of the Municipal Mayor', 'JO', 'employee', 'approved', '09170000003', 'mayor.emp3@example.com', @pwd, 'EMP2025-1003');

-- Office of the Municipal Vice Mayor
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('ViceMayor', 'EmpOne', NULL, 'Office of the Municipal Vice Mayor', 'Permanent', 'employee', 'approved', '09170000011', 'vicemayor.emp1@example.com', @pwd, 'EMP2025-1004'),
('ViceMayor', 'EmpTwo', NULL, 'Office of the Municipal Vice Mayor', 'Casual', 'employee', 'approved', '09170000012', 'vicemayor.emp2@example.com', @pwd, 'EMP2025-1005'),
('ViceMayor', 'EmpThree', NULL, 'Office of the Municipal Vice Mayor', 'JO', 'employee', 'approved', '09170000013', 'vicemayor.emp3@example.com', @pwd, 'EMP2025-1006');

-- Office of the Sangguiniang Bayan
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('SB', 'EmpOne', NULL, 'Office of the Sangguiniang Bayan', 'Permanent', 'employee', 'approved', '09170000021', 'sb.emp1@example.com', @pwd, 'EMP2025-1007'),
('SB', 'EmpTwo', NULL, 'Office of the Sangguiniang Bayan', 'Casual', 'employee', 'approved', '09170000022', 'sb.emp2@example.com', @pwd, 'EMP2025-1008'),
('SB', 'EmpThree', NULL, 'Office of the Sangguiniang Bayan', 'JO', 'employee', 'approved', '09170000023', 'sb.emp3@example.com', @pwd, 'EMP2025-1009');

-- Office of the Municipal Administrator
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('Admin', 'EmpOne', NULL, 'Office of the Municipal Administrator', 'Permanent', 'employee', 'approved', '09170000031', 'admin.emp1@example.com', @pwd, 'EMP2025-1010'),
('Admin', 'EmpTwo', NULL, 'Office of the Municipal Administrator', 'Casual', 'employee', 'approved', '09170000032', 'admin.emp2@example.com', @pwd, 'EMP2025-1011'),
('Admin', 'EmpThree', NULL, 'Office of the Municipal Administrator', 'JO', 'employee', 'approved', '09170000033', 'admin.emp3@example.com', @pwd, 'EMP2025-1012');

-- Office of the Municipal Engineer
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('Engineer', 'EmpOne', NULL, 'Office of the Municipal Engineer', 'Permanent', 'employee', 'approved', '09170000041', 'engineer.emp1@example.com', @pwd, 'EMP2025-1013'),
('Engineer', 'EmpTwo', NULL, 'Office of the Municipal Engineer', 'Casual', 'employee', 'approved', '09170000042', 'engineer.emp2@example.com', @pwd, 'EMP2025-1014'),
('Engineer', 'EmpThree', NULL, 'Office of the Municipal Engineer', 'JO', 'employee', 'approved', '09170000043', 'engineer.emp3@example.com', @pwd, 'EMP2025-1015');

-- Office of the MPDC
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('MPDC', 'EmpOne', NULL, 'Office of the MPDC', 'Permanent', 'employee', 'approved', '09170000051', 'mpdc.emp1@example.com', @pwd, 'EMP2025-1016'),
('MPDC', 'EmpTwo', NULL, 'Office of the MPDC', 'Casual', 'employee', 'approved', '09170000052', 'mpdc.emp2@example.com', @pwd, 'EMP2025-1017'),
('MPDC', 'EmpThree', NULL, 'Office of the MPDC', 'JO', 'employee', 'approved', '09170000053', 'mpdc.emp3@example.com', @pwd, 'EMP2025-1018');

-- Office of the Municipal Budget Officer
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('Budget', 'EmpOne', NULL, 'Office of the Municipal Budget Officer', 'Permanent', 'employee', 'approved', '09170000061', 'budget.emp1@example.com', @pwd, 'EMP2025-1019'),
('Budget', 'EmpTwo', NULL, 'Office of the Municipal Budget Officer', 'Casual', 'employee', 'approved', '09170000062', 'budget.emp2@example.com', @pwd, 'EMP2025-1020'),
('Budget', 'EmpThree', NULL, 'Office of the Municipal Budget Officer', 'JO', 'employee', 'approved', '09170000063', 'budget.emp3@example.com', @pwd, 'EMP2025-1021');

-- Office of the Municipal Assessor
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('Assessor', 'EmpOne', NULL, 'Office of the Municipal Assessor', 'Permanent', 'employee', 'approved', '09170000071', 'assessor.emp1@example.com', @pwd, 'EMP2025-1022'),
('Assessor', 'EmpTwo', NULL, 'Office of the Municipal Assessor', 'Casual', 'employee', 'approved', '09170000072', 'assessor.emp2@example.com', @pwd, 'EMP2025-1023'),
('Assessor', 'EmpThree', NULL, 'Office of the Municipal Assessor', 'JO', 'employee', 'approved', '09170000073', 'assessor.emp3@example.com', @pwd, 'EMP2025-1024');

-- Office of the Municipal Accountant
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('Accountant', 'EmpOne', NULL, 'Office of the Municipal Accountant', 'Permanent', 'employee', 'approved', '09170000081', 'accountant.emp1@example.com', @pwd, 'EMP2025-1025'),
('Accountant', 'EmpTwo', NULL, 'Office of the Municipal Accountant', 'Casual', 'employee', 'approved', '09170000082', 'accountant.emp2@example.com', @pwd, 'EMP2025-1026'),
('Accountant', 'EmpThree', NULL, 'Office of the Municipal Accountant', 'JO', 'employee', 'approved', '09170000083', 'accountant.emp3@example.com', @pwd, 'EMP2025-1027');

-- Office of the Municipal Civil Registrar
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('CivilRegistrar', 'EmpOne', NULL, 'Office of the Municipal Civil Registrar', 'Permanent', 'employee', 'approved', '09170000091', 'civilreg.emp1@example.com', @pwd, 'EMP2025-1028'),
('CivilRegistrar', 'EmpTwo', NULL, 'Office of the Municipal Civil Registrar', 'Casual', 'employee', 'approved', '09170000092', 'civilreg.emp2@example.com', @pwd, 'EMP2025-1029'),
('CivilRegistrar', 'EmpThree', NULL, 'Office of the Municipal Civil Registrar', 'JO', 'employee', 'approved', '09170000093', 'civilreg.emp3@example.com', @pwd, 'EMP2025-1030');

-- Office of the Municipal Treasurer
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('Treasurer', 'EmpOne', NULL, 'Office of the Municipal Treasurer', 'Permanent', 'employee', 'approved', '09170000101', 'treasurer.emp1@example.com', @pwd, 'EMP2025-1031'),
('Treasurer', 'EmpTwo', NULL, 'Office of the Municipal Treasurer', 'Casual', 'employee', 'approved', '09170000102', 'treasurer.emp2@example.com', @pwd, 'EMP2025-1032'),
('Treasurer', 'EmpThree', NULL, 'Office of the Municipal Treasurer', 'JO', 'employee', 'approved', '09170000103', 'treasurer.emp3@example.com', @pwd, 'EMP2025-1033');

-- Office of the Municipal Social Welfare and Development Officer
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('MSWDO', 'EmpOne', NULL, 'Office of the Municipal Social Welfare and Development Officer', 'Permanent', 'employee', 'approved', '09170000111', 'mswdo.emp1@example.com', @pwd, 'EMP2025-1034'),
('MSWDO', 'EmpTwo', NULL, 'Office of the Municipal Social Welfare and Development Officer', 'Casual', 'employee', 'approved', '09170000112', 'mswdo.emp2@example.com', @pwd, 'EMP2025-1035'),
('MSWDO', 'EmpThree', NULL, 'Office of the Municipal Social Welfare and Development Officer', 'JO', 'employee', 'approved', '09170000113', 'mswdo.emp3@example.com', @pwd, 'EMP2025-1036');

-- Office of the Municipal Health Officer
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('Health', 'EmpOne', NULL, 'Office of the Municipal Health Officer', 'Permanent', 'employee', 'approved', '09170000121', 'health.emp1@example.com', @pwd, 'EMP2025-1037'),
('Health', 'EmpTwo', NULL, 'Office of the Municipal Health Officer', 'Casual', 'employee', 'approved', '09170000122', 'health.emp2@example.com', @pwd, 'EMP2025-1038'),
('Health', 'EmpThree', NULL, 'Office of the Municipal Health Officer', 'JO', 'employee', 'approved', '09170000123', 'health.emp3@example.com', @pwd, 'EMP2025-1039');

-- Office of the Municipal Agriculturist
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('Agriculturist', 'EmpOne', NULL, 'Office of the Municipal Agriculturist', 'Permanent', 'employee', 'approved', '09170000131', 'agri.emp1@example.com', @pwd, 'EMP2025-1040'),
('Agriculturist', 'EmpTwo', NULL, 'Office of the Municipal Agriculturist', 'Casual', 'employee', 'approved', '09170000132', 'agri.emp2@example.com', @pwd, 'EMP2025-1041'),
('Agriculturist', 'EmpThree', NULL, 'Office of the Municipal Agriculturist', 'JO', 'employee', 'approved', '09170000133', 'agri.emp3@example.com', @pwd, 'EMP2025-1042');

-- Office of the MDRRMO
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('MDRRMO', 'EmpOne', NULL, 'Office of the MDRRMO', 'Permanent', 'employee', 'approved', '09170000141', 'mdrrmo.emp1@example.com', @pwd, 'EMP2025-1043'),
('MDRRMO', 'EmpTwo', NULL, 'Office of the MDRRMO', 'Casual', 'employee', 'approved', '09170000142', 'mdrrmo.emp2@example.com', @pwd, 'EMP2025-1044'),
('MDRRMO', 'EmpThree', NULL, 'Office of the MDRRMO', 'JO', 'employee', 'approved', '09170000143', 'mdrrmo.emp3@example.com', @pwd, 'EMP2025-1045');

-- Office of the Municipal Legal Officer
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('Legal', 'EmpOne', NULL, 'Office of the Municipal Legal Officer', 'Permanent', 'employee', 'approved', '09170000151', 'legal.emp1@example.com', @pwd, 'EMP2025-1046'),
('Legal', 'EmpTwo', NULL, 'Office of the Municipal Legal Officer', 'Casual', 'employee', 'approved', '09170000152', 'legal.emp2@example.com', @pwd, 'EMP2025-1047'),
('Legal', 'EmpThree', NULL, 'Office of the Municipal Legal Officer', 'JO', 'employee', 'approved', '09170000153', 'legal.emp3@example.com', @pwd, 'EMP2025-1048');

-- Office of the Municipal General Services Officer
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('GSO', 'EmpOne', NULL, 'Office of the Municipal General Services Officer', 'Permanent', 'employee', 'approved', '09170000161', 'gso.emp1@example.com', @pwd, 'EMP2025-1049'),
('GSO', 'EmpTwo', NULL, 'Office of the Municipal General Services Officer', 'Casual', 'employee', 'approved', '09170000162', 'gso.emp2@example.com', @pwd, 'EMP2025-1050'),
('GSO', 'EmpThree', NULL, 'Office of the Municipal General Services Officer', 'JO', 'employee', 'approved', '09170000163', 'gso.emp3@example.com', @pwd, 'EMP2025-1051');

-- Ensure password variable exists for standalone runs of sections below
SET @pwd := COALESCE(@pwd, '$2y$10$RAaicR8c3Un8fXrTbr.M1OZH/PBdcJOrRPa.gUc.hzIJU8nLk9XhO');

-- === Department Heads ===
-- One department_head per department; password is 123456 (@pwd)
INSERT INTO users (lastname, firstname, mi, department, position, role, status, contact_no, email, password, employee_id)
VALUES
('Mayor', 'Head', NULL, 'Office of the Municipal Mayor', 'Permanent', 'department_head', 'approved', '09179990001', 'mayor.head@example.com', @pwd, 'EMP2025-2001'),
('ViceMayor', 'Head', NULL, 'Office of the Municipal Vice Mayor', 'Permanent', 'department_head', 'approved', '09179990002', 'vicemayor.head@example.com', @pwd, 'EMP2025-2002'),
('SB', 'Head', NULL, 'Office of the Sangguiniang Bayan', 'Permanent', 'department_head', 'approved', '09179990003', 'sb.head@example.com', @pwd, 'EMP2025-2003'),
('Admin', 'Head', NULL, 'Office of the Municipal Administrator', 'Permanent', 'department_head', 'approved', '09179990004', 'admin.head@example.com', @pwd, 'EMP2025-2004'),
('Engineer', 'Head', NULL, 'Office of the Municipal Engineer', 'Permanent', 'department_head', 'approved', '09179990005', 'engineer.head@example.com', @pwd, 'EMP2025-2005'),
('MPDC', 'Head', NULL, 'Office of the MPDC', 'Permanent', 'department_head', 'approved', '09179990006', 'mpdc.head@example.com', @pwd, 'EMP2025-2006'),
('Budget', 'Head', NULL, 'Office of the Municipal Budget Officer', 'Permanent', 'department_head', 'approved', '09179990007', 'budget.head@example.com', @pwd, 'EMP2025-2007'),
('Assessor', 'Head', NULL, 'Office of the Municipal Assessor', 'Permanent', 'department_head', 'approved', '09179990008', 'assessor.head@example.com', @pwd, 'EMP2025-2008'),
('Accountant', 'Head', NULL, 'Office of the Municipal Accountant', 'Permanent', 'department_head', 'approved', '09179990009', 'accountant.head@example.com', @pwd, 'EMP2025-2009'),
('CivilRegistrar', 'Head', NULL, 'Office of the Municipal Civil Registrar', 'Permanent', 'department_head', 'approved', '09179990010', 'civilreg.head@example.com', @pwd, 'EMP2025-2010'),
('Treasurer', 'Head', NULL, 'Office of the Municipal Treasurer', 'Permanent', 'department_head', 'approved', '09179990011', 'treasurer.head@example.com', @pwd, 'EMP2025-2011'),
('MSWDO', 'Head', NULL, 'Office of the Municipal Social Welfare and Development Officer', 'Permanent', 'department_head', 'approved', '09179990012', 'mswdo.head@example.com', @pwd, 'EMP2025-2012'),
('Health', 'Head', NULL, 'Office of the Municipal Health Officer', 'Permanent', 'department_head', 'approved', '09179990013', 'health.head@example.com', @pwd, 'EMP2025-2013'),
('Agriculturist', 'Head', NULL, 'Office of the Municipal Agriculturist', 'Permanent', 'department_head', 'approved', '09179990014', 'agri.head@example.com', @pwd, 'EMP2025-2014'),
('MDRRMO', 'Head', NULL, 'Office of the MDRRMO', 'Permanent', 'department_head', 'approved', '09179990015', 'mdrrmo.head@example.com', @pwd, 'EMP2025-2015'),
('Legal', 'Head', NULL, 'Office of the Municipal Legal Officer', 'Permanent', 'department_head', 'approved', '09179990016', 'legal.head@example.com', @pwd, 'EMP2025-2016'),
('GSO', 'Head', NULL, 'Office of the Municipal General Services Officer', 'Permanent', 'department_head', 'approved', '09179990017', 'gso.head@example.com', @pwd, 'EMP2025-2017')
ON DUPLICATE KEY UPDATE
	lastname=VALUES(lastname), firstname=VALUES(firstname), department=VALUES(department), role=VALUES(role), status=VALUES(status), password=VALUES(password);

-- Link department_heads table (idempotent)
INSERT INTO department_heads (email, department)
VALUES
('mayor.head@example.com','Office of the Municipal Mayor'),
('vicemayor.head@example.com','Office of the Municipal Vice Mayor'),
('sb.head@example.com','Office of the Sangguiniang Bayan'),
('admin.head@example.com','Office of the Municipal Administrator'),
('engineer.head@example.com','Office of the Municipal Engineer'),
('mpdc.head@example.com','Office of the MPDC'),
('budget.head@example.com','Office of the Municipal Budget Officer'),
('assessor.head@example.com','Office of the Municipal Assessor'),
('accountant.head@example.com','Office of the Municipal Accountant'),
('civilreg.head@example.com','Office of the Municipal Civil Registrar'),
('treasurer.head@example.com','Office of the Municipal Treasurer'),
('mswdo.head@example.com','Office of the Municipal Social Welfare and Development Officer'),
('health.head@example.com','Office of the Municipal Health Officer'),
('agri.head@example.com','Office of the Municipal Agriculturist'),
('mdrrmo.head@example.com','Office of the MDRRMO'),
('legal.head@example.com','Office of the Municipal Legal Officer'),
('gso.head@example.com','Office of the Municipal General Services Officer')
ON DUPLICATE KEY UPDATE department=VALUES(department);

-- === Attendance: unique patterns per employee across full range ===
-- Date range: 2025-10-21 .. 2025-11-19 (inclusive)
-- Uses all statuses for time_in_status (Present, Late, Undertime, Absent)
-- and time_out_status (On-time, Undertime, Overtime, Out)

-- Insert or update attendance for seeded users
-- Employees: EMP2025-1001..EMP2025-1051
-- Dept Heads: EMP2025-2001..EMP2025-2017
INSERT INTO attendance (employee_id, date, time_in, time_out, time_in_status, time_out_status, status, notes)
SELECT
	s.employee_id,
	s.date,
	CASE
		WHEN s.in_status = 'Absent' THEN NULL
		WHEN s.in_status = 'Late' THEN CONCAT(s.date, ' 08:20:00')
		WHEN s.in_status = 'Undertime' THEN CONCAT(s.date, ' 09:30:00')
		ELSE CONCAT(s.date, ' 07:45:00')
	END AS time_in,
	CASE
		WHEN s.in_status = 'Absent' THEN NULL
		WHEN s.out_status = 'Undertime' THEN CONCAT(s.date, ' 16:00:00')
		WHEN s.out_status = 'On-time' THEN CONCAT(s.date, ' 17:00:00')
		WHEN s.out_status = 'Overtime' THEN CONCAT(s.date, ' 18:30:00')
		ELSE CONCAT(s.date, ' 17:10:00')
	END AS time_out,
	s.in_status AS time_in_status,
	CASE WHEN s.in_status = 'Absent' THEN 'Out' ELSE s.out_status END AS time_out_status,
	CASE WHEN s.in_status = 'Absent' THEN 'Absent' ELSE 'Present' END AS status,
	CASE WHEN s.in_status = 'Absent' THEN 'Absent' ELSE NULL END AS notes
FROM (
	SELECT
		u.employee_id,
		t.d AS date,
		-- derive a robust numeric seed from employee_id (handles formats like 'EMP-2025-000003')
		CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) AS seed_num,
		MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) AS in_offset,
		MOD(MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) * 3, 8) AS out_offset,
		(1 + MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 7)) AS in_step,
		(1 + MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 7), 7)) AS out_step,
		MOD(((t.day_no - 1) * (1 + MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 7))
			+ MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8)), 8) AS c_in,
		MOD(((t.day_no - 1) * (1 + MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 7), 7))
			+ MOD(MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) * 3, 8)), 8) AS c_out,
		-- compute final in/out statuses with per-employee variations
		CASE
			WHEN MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 8), 3) = 1 THEN
				CASE MOD(((t.day_no - 1) * (1 + MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 7))
					+ MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8)), 8)
					-- variant 1: swap Late and Undertime
					WHEN 0 THEN 'Present'
					WHEN 1 THEN 'Undertime'
					WHEN 2 THEN 'Present'
					WHEN 3 THEN 'Late'
					WHEN 4 THEN 'Present'
					WHEN 5 THEN 'Undertime'
					WHEN 6 THEN 'Present'
					WHEN 7 THEN 'Absent'
				END
			WHEN MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 8), 3) = 2 THEN
				CASE MOD(((t.day_no - 1) * (1 + MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 7))
					+ MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8)), 8)
					-- variant 2: swap Present and Late
					WHEN 0 THEN 'Late'
					WHEN 1 THEN 'Present'
					WHEN 2 THEN 'Late'
					WHEN 3 THEN 'Undertime'
					WHEN 4 THEN 'Late'
					WHEN 5 THEN 'Present'
					WHEN 6 THEN 'Late'
					WHEN 7 THEN 'Absent'
				END
			ELSE
				CASE MOD(((t.day_no - 1) * (1 + MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 7))
					+ MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8)), 8)
					-- base: P, L, P, U, P, L, P, A
					WHEN 0 THEN 'Present'
					WHEN 1 THEN 'Late'
					WHEN 2 THEN 'Present'
					WHEN 3 THEN 'Undertime'
					WHEN 4 THEN 'Present'
					WHEN 5 THEN 'Late'
					WHEN 6 THEN 'Present'
					WHEN 7 THEN 'Absent'
				END
		END AS in_status,
		CASE
			WHEN MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 8), 4) = 1 THEN
				CASE MOD(((t.day_no - 1) * (1 + MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 7), 7))
					+ MOD(MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) * 3, 8)), 8)
					-- variant 1: swap On-time and Out
					WHEN 0 THEN 'Out'
					WHEN 1 THEN 'Undertime'
					WHEN 2 THEN 'Overtime'
					WHEN 3 THEN 'Out'
					WHEN 4 THEN 'On-time'
					WHEN 5 THEN 'Overtime'
					WHEN 6 THEN 'Out'
					WHEN 7 THEN 'Undertime'
				END
			WHEN MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 8), 4) = 2 THEN
				CASE MOD(((t.day_no - 1) * (1 + MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 7), 7))
					+ MOD(MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) * 3, 8)), 8)
					-- variant 2: rotate On-time->Undertime->Overtime->Out->On-time
					WHEN 0 THEN 'Undertime'
					WHEN 1 THEN 'Overtime'
					WHEN 2 THEN 'Out'
					WHEN 3 THEN 'Undertime'
					WHEN 4 THEN 'On-time'
					WHEN 5 THEN 'Out'
					WHEN 6 THEN 'Undertime'
					WHEN 7 THEN 'Overtime'
				END
			WHEN MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 8), 4) = 3 THEN
				CASE MOD(((t.day_no - 1) * (1 + MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 7), 7))
					+ MOD(MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) * 3, 8)), 8)
					-- variant 3: swap Undertime and Overtime
					WHEN 0 THEN 'On-time'
					WHEN 1 THEN 'Overtime'
					WHEN 2 THEN 'Undertime'
					WHEN 3 THEN 'On-time'
					WHEN 4 THEN 'Out'
					WHEN 5 THEN 'Undertime'
					WHEN 6 THEN 'On-time'
					WHEN 7 THEN 'Overtime'
				END
			ELSE
				CASE MOD(((t.day_no - 1) * (1 + MOD(FLOOR(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED) / 7), 7))
					+ MOD(MOD(CAST(RIGHT(REPLACE(u.employee_id,'-',''), 6) AS UNSIGNED), 8) * 3, 8)), 8)
					-- base: OT, UT, OTm, OT, Out, OTm, OT, UT
					WHEN 0 THEN 'On-time'
					WHEN 1 THEN 'Undertime'
					WHEN 2 THEN 'Overtime'
					WHEN 3 THEN 'On-time'
					WHEN 4 THEN 'Out'
					WHEN 5 THEN 'Overtime'
					WHEN 6 THEN 'On-time'
					WHEN 7 THEN 'Undertime'
				END
		END AS out_status
	FROM users u
	JOIN (
		SELECT 1 AS day_no, CAST('2025-10-21' AS DATE) AS d
		UNION ALL SELECT 2, CAST('2025-10-22' AS DATE)
		UNION ALL SELECT 3, CAST('2025-10-23' AS DATE)
		UNION ALL SELECT 4, CAST('2025-10-24' AS DATE)
		UNION ALL SELECT 5, CAST('2025-10-25' AS DATE)
		UNION ALL SELECT 6, CAST('2025-10-26' AS DATE)
		UNION ALL SELECT 7, CAST('2025-10-27' AS DATE)
		UNION ALL SELECT 8, CAST('2025-10-28' AS DATE)
		UNION ALL SELECT 9, CAST('2025-10-29' AS DATE)
		UNION ALL SELECT 10, CAST('2025-10-30' AS DATE)
		UNION ALL SELECT 11, CAST('2025-10-31' AS DATE)
		UNION ALL SELECT 12, CAST('2025-11-01' AS DATE)
		UNION ALL SELECT 13, CAST('2025-11-02' AS DATE)
		UNION ALL SELECT 14, CAST('2025-11-03' AS DATE)
		UNION ALL SELECT 15, CAST('2025-11-04' AS DATE)
		UNION ALL SELECT 16, CAST('2025-11-05' AS DATE)
		UNION ALL SELECT 17, CAST('2025-11-06' AS DATE)
		UNION ALL SELECT 18, CAST('2025-11-07' AS DATE)
		UNION ALL SELECT 19, CAST('2025-11-08' AS DATE)
		UNION ALL SELECT 20, CAST('2025-11-09' AS DATE)
		UNION ALL SELECT 21, CAST('2025-11-10' AS DATE)
		UNION ALL SELECT 22, CAST('2025-11-11' AS DATE)
		UNION ALL SELECT 23, CAST('2025-11-12' AS DATE)
		UNION ALL SELECT 24, CAST('2025-11-13' AS DATE)
		UNION ALL SELECT 25, CAST('2025-11-14' AS DATE)
		UNION ALL SELECT 26, CAST('2025-11-15' AS DATE)
		UNION ALL SELECT 27, CAST('2025-11-16' AS DATE)
		UNION ALL SELECT 28, CAST('2025-11-17' AS DATE)
		UNION ALL SELECT 29, CAST('2025-11-18' AS DATE)
		UNION ALL SELECT 30, CAST('2025-11-19' AS DATE)
	) AS t ON 1=1
	WHERE u.employee_id REGEXP '^EMP2025-(10(0[1-9]|[1-4][0-9]|5[0-1])|20(0[1-9]|1[0-7]))$'
 ) AS s
ON DUPLICATE KEY UPDATE
	time_in=VALUES(time_in),
	time_out=VALUES(time_out),
	time_in_status=VALUES(time_in_status),
	time_out_status=VALUES(time_out_status),
	status=VALUES(status),
	notes=VALUES(notes);
