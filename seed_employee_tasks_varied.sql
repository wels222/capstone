-- Seed: Employee Tasks with Varied Statuses (3-5 tasks per employee)
-- Generated on 2025-11-19
-- Each employee gets multiple tasks assigned by their department head with different statuses

USE `capstone`;

-- Employee task records with varied statuses
-- Statuses include: pending, in_progress, completed

-- Office of the Municipal Mayor employees (assigned by mayor.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Prepare Monthly Report', 'Compile and prepare the monthly department report for November', '2025-11-20 17:00:00', 'in_progress', 'mayor.emp1@example.com', 'mayor.head@example.com', NULL),
('Update Database Records', 'Review and update all employee database records', '2025-11-25 17:00:00', 'pending', 'mayor.emp1@example.com', 'mayor.head@example.com', NULL),
('Organize Team Meeting', 'Schedule and organize quarterly team meeting', '2025-11-15 17:00:00', 'completed', 'mayor.emp1@example.com', 'mayor.head@example.com', '2025-11-15 16:30:00'),
('File Documentation', 'Organize and file all pending documentation', '2025-11-22 17:00:00', 'in_progress', 'mayor.emp1@example.com', 'mayor.head@example.com', NULL),
('Review Budget Proposals', 'Review submitted budget proposals from all departments', '2025-11-28 17:00:00', 'pending', 'mayor.emp1@example.com', 'mayor.head@example.com', NULL),
('Create Presentation', 'Create presentation for upcoming council meeting', '2025-11-21 17:00:00', 'in_progress', 'mayor.emp2@example.com', 'mayor.head@example.com', NULL),
('Process Leave Requests', 'Review and process all pending leave requests', '2025-11-19 17:00:00', 'completed', 'mayor.emp2@example.com', 'mayor.head@example.com', '2025-11-19 15:00:00'),
('Update Website Content', 'Update department website with latest information', '2025-11-26 17:00:00', 'pending', 'mayor.emp2@example.com', 'mayor.head@example.com', NULL),
('Coordinate with HR', 'Coordinate employee training schedule with HR department', '2025-11-23 17:00:00', 'in_progress', 'mayor.emp2@example.com', 'mayor.head@example.com', NULL),
('Inventory Check', 'Conduct monthly inventory check of office supplies', '2025-11-18 17:00:00', 'completed', 'mayor.emp3@example.com', 'mayor.head@example.com', '2025-11-18 16:00:00'),
('Draft Memo', 'Draft memo regarding new office policies', '2025-11-24 17:00:00', 'pending', 'mayor.emp3@example.com', 'mayor.head@example.com', NULL),
('Schedule Maintenance', 'Schedule regular maintenance for office equipment', '2025-11-20 17:00:00', 'in_progress', 'mayor.emp3@example.com', 'mayor.head@example.com', NULL),
('Prepare Event', 'Prepare logistics for department event', '2025-11-27 17:00:00', 'pending', 'mayor.emp3@example.com', 'mayor.head@example.com', NULL),
('Archive Old Files', 'Archive and organize old department files', '2025-11-22 17:00:00', 'in_progress', 'mayor.emp3@example.com', 'mayor.head@example.com', NULL);

-- Office of the Municipal Vice Mayor employees (assigned by vicemayor.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Prepare Minutes', 'Prepare minutes from last council meeting', '2025-11-20 17:00:00', 'completed', 'vicemayor.emp1@example.com', 'vicemayor.head@example.com', '2025-11-20 14:30:00'),
('Review Proposals', 'Review project proposals from various departments', '2025-11-25 17:00:00', 'in_progress', 'vicemayor.emp1@example.com', 'vicemayor.head@example.com', NULL),
('Coordinate Meeting', 'Coordinate inter-department meeting schedule', '2025-11-22 17:00:00', 'pending', 'vicemayor.emp1@example.com', 'vicemayor.head@example.com', NULL),
('Update Records', 'Update office records and filing system', '2025-11-27 17:00:00', 'pending', 'vicemayor.emp1@example.com', 'vicemayor.head@example.com', NULL),
('Compile Reports', 'Compile weekly activity reports', '2025-11-21 17:00:00', 'in_progress', 'vicemayor.emp2@example.com', 'vicemayor.head@example.com', NULL),
('Process Documents', 'Process incoming official documents', '2025-11-19 17:00:00', 'completed', 'vicemayor.emp2@example.com', 'vicemayor.head@example.com', '2025-11-19 16:00:00'),
('Prepare Agenda', 'Prepare agenda for next council session', '2025-11-26 17:00:00', 'pending', 'vicemayor.emp2@example.com', 'vicemayor.head@example.com', NULL),
('Update Database', 'Update constituent database with recent information', '2025-11-23 17:00:00', 'in_progress', 'vicemayor.emp2@example.com', 'vicemayor.head@example.com', NULL),
('Organize Files', 'Organize digital filing system', '2025-11-28 17:00:00', 'pending', 'vicemayor.emp2@example.com', 'vicemayor.head@example.com', NULL),
('Setup Equipment', 'Setup audio-visual equipment for meeting', '2025-11-18 17:00:00', 'completed', 'vicemayor.emp3@example.com', 'vicemayor.head@example.com', '2025-11-18 15:30:00'),
('Maintenance Check', 'Conduct maintenance check of office facilities', '2025-11-24 17:00:00', 'pending', 'vicemayor.emp3@example.com', 'vicemayor.head@example.com', NULL),
('Prepare Materials', 'Prepare materials for upcoming workshop', '2025-11-22 17:00:00', 'in_progress', 'vicemayor.emp3@example.com', 'vicemayor.head@example.com', NULL);

-- Office of the Sangguiniang Bayan employees (assigned by sb.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Draft Resolution', 'Draft council resolution for infrastructure project', '2025-11-21 17:00:00', 'in_progress', 'sb.emp1@example.com', 'sb.head@example.com', NULL),
('Research Ordinances', 'Research similar ordinances from other municipalities', '2025-11-25 17:00:00', 'pending', 'sb.emp1@example.com', 'sb.head@example.com', NULL),
('Compile Session Minutes', 'Compile minutes from last week''s session', '2025-11-17 17:00:00', 'completed', 'sb.emp1@example.com', 'sb.head@example.com', '2025-11-17 16:00:00'),
('Prepare Committee Report', 'Prepare report for committee on appropriations', '2025-11-23 17:00:00', 'in_progress', 'sb.emp1@example.com', 'sb.head@example.com', NULL),
('Review Legislative Agenda', 'Review and organize items for next legislative agenda', '2025-11-28 17:00:00', 'pending', 'sb.emp1@example.com', 'sb.head@example.com', NULL),
('Update Records System', 'Update digital records management system', '2025-11-22 17:00:00', 'in_progress', 'sb.emp2@example.com', 'sb.head@example.com', NULL),
('Process Petitions', 'Process and review citizen petitions', '2025-11-20 17:00:00', 'completed', 'sb.emp2@example.com', 'sb.head@example.com', '2025-11-20 15:30:00'),
('Organize Public Hearing', 'Organize logistics for upcoming public hearing', '2025-11-26 17:00:00', 'pending', 'sb.emp2@example.com', 'sb.head@example.com', NULL),
('Archive Documents', 'Archive old legislative documents', '2025-11-24 17:00:00', 'in_progress', 'sb.emp2@example.com', 'sb.head@example.com', NULL),
('Setup Session Hall', 'Setup and prepare session hall for next meeting', '2025-11-21 17:00:00', 'completed', 'sb.emp3@example.com', 'sb.head@example.com', '2025-11-21 14:00:00'),
('Prepare Notice', 'Prepare public notice for upcoming ordinance hearing', '2025-11-27 17:00:00', 'pending', 'sb.emp3@example.com', 'sb.head@example.com', NULL),
('Coordinate Stakeholders', 'Coordinate with stakeholders for consultation meeting', '2025-11-23 17:00:00', 'in_progress', 'sb.emp3@example.com', 'sb.head@example.com', NULL),
('Update Website', 'Update council website with recent resolutions', '2025-11-25 17:00:00', 'pending', 'sb.emp3@example.com', 'sb.head@example.com', NULL),
('Inventory Supplies', 'Conduct inventory of office and session supplies', '2025-11-29 17:00:00', 'pending', 'sb.emp3@example.com', 'sb.head@example.com', NULL);

-- Office of the Municipal Administrator employees (assigned by admin.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Coordinate Departments', 'Coordinate activities across all municipal departments', '2025-11-22 17:00:00', 'in_progress', 'admin.emp1@example.com', 'admin.head@example.com', NULL),
('Review Policies', 'Review and update administrative policies', '2025-11-26 17:00:00', 'pending', 'admin.emp1@example.com', 'admin.head@example.com', NULL),
('Prepare Performance Report', 'Prepare quarterly performance report', '2025-11-19 17:00:00', 'completed', 'admin.emp1@example.com', 'admin.head@example.com', '2025-11-19 16:00:00'),
('Process Memorandums', 'Process and distribute office memorandums', '2025-11-21 17:00:00', 'in_progress', 'admin.emp1@example.com', 'admin.head@example.com', NULL),
('Monitor Attendance', 'Monitor and compile department attendance records', '2025-11-23 17:00:00', 'in_progress', 'admin.emp2@example.com', 'admin.head@example.com', NULL),
('Schedule Inspections', 'Schedule facility inspections for all offices', '2025-11-25 17:00:00', 'pending', 'admin.emp2@example.com', 'admin.head@example.com', NULL),
('Update Procedures Manual', 'Update standard operating procedures manual', '2025-11-28 17:00:00', 'pending', 'admin.emp2@example.com', 'admin.head@example.com', NULL),
('Consolidate Reports', 'Consolidate monthly reports from all departments', '2025-11-20 17:00:00', 'completed', 'admin.emp2@example.com', 'admin.head@example.com', '2025-11-20 15:00:00'),
('Organize Training', 'Organize administrative staff training session', '2025-11-27 17:00:00', 'in_progress', 'admin.emp2@example.com', 'admin.head@example.com', NULL),
('Maintain Equipment', 'Maintain and service office equipment', '2025-11-24 17:00:00', 'pending', 'admin.emp3@example.com', 'admin.head@example.com', NULL),
('Setup Meeting Room', 'Setup meeting room for administrative conference', '2025-11-18 17:00:00', 'completed', 'admin.emp3@example.com', 'admin.head@example.com', '2025-11-18 14:30:00'),
('Order Supplies', 'Order office supplies for next quarter', '2025-11-22 17:00:00', 'in_progress', 'admin.emp3@example.com', 'admin.head@example.com', NULL);

-- Office of the Municipal Engineer employees (assigned by engineer.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Inspect Road Project', 'Conduct site inspection for ongoing road construction', '2025-11-22 17:00:00', 'in_progress', 'engineer.emp1@example.com', 'engineer.head@example.com', NULL),
('Review Building Plans', 'Review and approve submitted building plans', '2025-11-25 17:00:00', 'pending', 'engineer.emp1@example.com', 'engineer.head@example.com', NULL),
('Prepare Engineering Report', 'Prepare monthly engineering activities report', '2025-11-18 17:00:00', 'completed', 'engineer.emp1@example.com', 'engineer.head@example.com', '2025-11-18 16:00:00'),
('Site Survey', 'Conduct site survey for proposed drainage system', '2025-11-24 17:00:00', 'in_progress', 'engineer.emp1@example.com', 'engineer.head@example.com', NULL),
('Update Project Timeline', 'Update timeline for all ongoing infrastructure projects', '2025-11-27 17:00:00', 'pending', 'engineer.emp1@example.com', 'engineer.head@example.com', NULL),
('Assess Bridge Condition', 'Assess structural condition of municipal bridge', '2025-11-23 17:00:00', 'in_progress', 'engineer.emp2@example.com', 'engineer.head@example.com', NULL),
('Process Permits', 'Process engineering permits for new constructions', '2025-11-20 17:00:00', 'completed', 'engineer.emp2@example.com', 'engineer.head@example.com', '2025-11-20 14:00:00'),
('Prepare Cost Estimates', 'Prepare cost estimates for proposed projects', '2025-11-26 17:00:00', 'pending', 'engineer.emp2@example.com', 'engineer.head@example.com', NULL),
('Monitor Construction', 'Monitor compliance of ongoing construction activities', '2025-11-21 17:00:00', 'in_progress', 'engineer.emp2@example.com', 'engineer.head@example.com', NULL),
('CAD Drawings', 'Create CAD drawings for municipal building renovation', '2025-11-24 17:00:00', 'in_progress', 'engineer.emp3@example.com', 'engineer.head@example.com', NULL),
('Materials Testing', 'Conduct materials testing for road project', '2025-11-27 17:00:00', 'pending', 'engineer.emp3@example.com', 'engineer.head@example.com', NULL),
('Compile As-Built Plans', 'Compile as-built plans for completed projects', '2025-11-19 17:00:00', 'completed', 'engineer.emp3@example.com', 'engineer.head@example.com', '2025-11-19 15:30:00'),
('Equipment Calibration', 'Calibrate engineering survey equipment', '2025-11-22 17:00:00', 'in_progress', 'engineer.emp3@example.com', 'engineer.head@example.com', NULL),
('Update GIS Database', 'Update municipal GIS database with new data', '2025-11-28 17:00:00', 'pending', 'engineer.emp3@example.com', 'engineer.head@example.com', NULL);

-- Office of the MPDC employees (assigned by mpdc.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Draft Development Plan', 'Draft comprehensive municipal development plan', '2025-11-25 17:00:00', 'in_progress', 'mpdc.emp1@example.com', 'mpdc.head@example.com', NULL),
('Conduct Community Consultation', 'Conduct community consultation for zoning plan', '2025-11-20 17:00:00', 'completed', 'mpdc.emp1@example.com', 'mpdc.head@example.com', '2025-11-20 16:00:00'),
('Research Best Practices', 'Research development best practices from other LGUs', '2025-11-27 17:00:00', 'pending', 'mpdc.emp1@example.com', 'mpdc.head@example.com', NULL),
('Update Land Use Map', 'Update municipal land use map', '2025-11-23 17:00:00', 'in_progress', 'mpdc.emp1@example.com', 'mpdc.head@example.com', NULL),
('Prepare Project Proposal', 'Prepare project proposal for infrastructure development', '2025-11-24 17:00:00', 'in_progress', 'mpdc.emp2@example.com', 'mpdc.head@example.com', NULL),
('Monitor Development Projects', 'Monitor progress of ongoing development projects', '2025-11-21 17:00:00', 'completed', 'mpdc.emp2@example.com', 'mpdc.head@example.com', '2025-11-21 15:00:00'),
('Statistical Analysis', 'Conduct statistical analysis for development indicators', '2025-11-28 17:00:00', 'pending', 'mpdc.emp2@example.com', 'mpdc.head@example.com', NULL),
('Coordinate with NGOs', 'Coordinate with NGOs for development programs', '2025-11-26 17:00:00', 'pending', 'mpdc.emp2@example.com', 'mpdc.head@example.com', NULL),
('Prepare Progress Report', 'Prepare quarterly progress report on development plans', '2025-11-22 17:00:00', 'in_progress', 'mpdc.emp2@example.com', 'mpdc.head@example.com', NULL),
('GIS Mapping', 'Create GIS maps for development planning', '2025-11-23 17:00:00', 'in_progress', 'mpdc.emp3@example.com', 'mpdc.head@example.com', NULL),
('Documentation', 'Document all planning activities for record keeping', '2025-11-19 17:00:00', 'completed', 'mpdc.emp3@example.com', 'mpdc.head@example.com', '2025-11-19 14:30:00'),
('Organize Workshop', 'Organize planning workshop for stakeholders', '2025-11-25 17:00:00', 'pending', 'mpdc.emp3@example.com', 'mpdc.head@example.com', NULL);

-- Office of the Municipal Budget Officer employees (assigned by budget.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Prepare Budget Proposal', 'Prepare annual budget proposal for next fiscal year', '2025-11-25 17:00:00', 'in_progress', 'budget.emp1@example.com', 'budget.head@example.com', NULL),
('Review Expenditures', 'Review and analyze quarterly expenditures', '2025-11-20 17:00:00', 'completed', 'budget.emp1@example.com', 'budget.head@example.com', '2025-11-20 15:30:00'),
('Process Budget Requests', 'Process budget requests from all departments', '2025-11-27 17:00:00', 'pending', 'budget.emp1@example.com', 'budget.head@example.com', NULL),
('Consolidate Financial Data', 'Consolidate financial data for budget planning', '2025-11-23 17:00:00', 'in_progress', 'budget.emp1@example.com', 'budget.head@example.com', NULL),
('Update Budget System', 'Update and maintain budget management system', '2025-11-28 17:00:00', 'pending', 'budget.emp1@example.com', 'budget.head@example.com', NULL),
('Analyze Revenue Trends', 'Analyze municipal revenue trends and projections', '2025-11-22 17:00:00', 'in_progress', 'budget.emp2@example.com', 'budget.head@example.com', NULL),
('Prepare Financial Report', 'Prepare monthly financial performance report', '2025-11-19 17:00:00', 'completed', 'budget.emp2@example.com', 'budget.head@example.com', '2025-11-19 16:00:00'),
('Coordinate Budget Hearings', 'Coordinate schedule for budget hearings', '2025-11-26 17:00:00', 'pending', 'budget.emp2@example.com', 'budget.head@example.com', NULL),
('Monitor Disbursements', 'Monitor budget disbursements and obligations', '2025-11-24 17:00:00', 'in_progress', 'budget.emp2@example.com', 'budget.head@example.com', NULL),
('Compile Budget Documents', 'Compile all budget-related documents and records', '2025-11-21 17:00:00', 'completed', 'budget.emp3@example.com', 'budget.head@example.com', '2025-11-21 14:30:00'),
('Verify Allocations', 'Verify budget allocations against approved budget', '2025-11-25 17:00:00', 'in_progress', 'budget.emp3@example.com', 'budget.head@example.com', NULL),
('Archive Financial Records', 'Archive previous year financial records', '2025-11-29 17:00:00', 'pending', 'budget.emp3@example.com', 'budget.head@example.com', NULL);

-- Office of the Municipal Assessor employees (assigned by assessor.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Conduct Property Assessment', 'Conduct assessment of newly constructed properties', '2025-11-23 17:00:00', 'in_progress', 'assessor.emp1@example.com', 'assessor.head@example.com', NULL),
('Update Tax Records', 'Update real property tax records and database', '2025-11-25 17:00:00', 'pending', 'assessor.emp1@example.com', 'assessor.head@example.com', NULL),
('Process Tax Declarations', 'Process and verify tax declarations', '2025-11-19 17:00:00', 'completed', 'assessor.emp1@example.com', 'assessor.head@example.com', '2025-11-19 15:00:00'),
('Field Inspection', 'Conduct field inspection of properties for reassessment', '2025-11-27 17:00:00', 'pending', 'assessor.emp1@example.com', 'assessor.head@example.com', NULL),
('Review Property Values', 'Review and update property market values', '2025-11-22 17:00:00', 'in_progress', 'assessor.emp2@example.com', 'assessor.head@example.com', NULL),
('Prepare Assessment Roll', 'Prepare quarterly assessment roll report', '2025-11-20 17:00:00', 'completed', 'assessor.emp2@example.com', 'assessor.head@example.com', '2025-11-20 16:30:00'),
('Issue Tax Certifications', 'Issue real property tax certifications', '2025-11-26 17:00:00', 'pending', 'assessor.emp2@example.com', 'assessor.head@example.com', NULL),
('Update Property Maps', 'Update cadastral and property boundary maps', '2025-11-24 17:00:00', 'in_progress', 'assessor.emp2@example.com', 'assessor.head@example.com', NULL),
('Verify Ownership Records', 'Verify property ownership records and documents', '2025-11-28 17:00:00', 'pending', 'assessor.emp2@example.com', 'assessor.head@example.com', NULL),
('Process Appeals', 'Process appeals on property assessments', '2025-11-21 17:00:00', 'in_progress', 'assessor.emp3@example.com', 'assessor.head@example.com', NULL),
('Archive Old Records', 'Archive old assessment records properly', '2025-11-29 17:00:00', 'pending', 'assessor.emp3@example.com', 'assessor.head@example.com', NULL),
('Generate Reports', 'Generate monthly assessment statistics report', '2025-11-18 17:00:00', 'completed', 'assessor.emp3@example.com', 'assessor.head@example.com', '2025-11-18 15:30:00');

-- Office of the Municipal Accountant employees (assigned by accountant.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Prepare Financial Statements', 'Prepare monthly financial statements', '2025-11-22 17:00:00', 'in_progress', 'accountant.emp1@example.com', 'accountant.head@example.com', NULL),
('Audit Transactions', 'Audit recent financial transactions for compliance', '2025-11-26 17:00:00', 'pending', 'accountant.emp1@example.com', 'accountant.head@example.com', NULL),
('Reconcile Bank Accounts', 'Reconcile all municipal bank accounts', '2025-11-19 17:00:00', 'completed', 'accountant.emp1@example.com', 'accountant.head@example.com', '2025-11-19 16:00:00'),
('Process Vouchers', 'Process and verify disbursement vouchers', '2025-11-24 17:00:00', 'in_progress', 'accountant.emp1@example.com', 'accountant.head@example.com', NULL),
('Review Payroll', 'Review and verify monthly payroll computations', '2025-11-28 17:00:00', 'pending', 'accountant.emp1@example.com', 'accountant.head@example.com', NULL),
('Maintain General Ledger', 'Maintain and update general ledger entries', '2025-11-21 17:00:00', 'in_progress', 'accountant.emp2@example.com', 'accountant.head@example.com', NULL),
('Prepare Tax Reports', 'Prepare quarterly tax remittance reports', '2025-11-20 17:00:00', 'completed', 'accountant.emp2@example.com', 'accountant.head@example.com', '2025-11-20 15:00:00'),
('Monitor Collections', 'Monitor revenue collections from all sources', '2025-11-25 17:00:00', 'pending', 'accountant.emp2@example.com', 'accountant.head@example.com', NULL),
('Verify Obligations', 'Verify budget obligations and availability', '2025-11-23 17:00:00', 'in_progress', 'accountant.emp2@example.com', 'accountant.head@example.com', NULL),
('File Financial Documents', 'File and organize financial documents', '2025-11-27 17:00:00', 'pending', 'accountant.emp3@example.com', 'accountant.head@example.com', NULL),
('Generate COA Reports', 'Generate reports for Commission on Audit submission', '2025-11-29 17:00:00', 'pending', 'accountant.emp3@example.com', 'accountant.head@example.com', NULL),
('Update Accounting System', 'Update accounting software and records', '2025-11-18 17:00:00', 'completed', 'accountant.emp3@example.com', 'accountant.head@example.com', '2025-11-18 14:30:00');

-- Office of the Municipal Civil Registrar employees (assigned by civilreg.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Process Birth Certificates', 'Process and register new birth certificates', '2025-11-22 17:00:00', 'in_progress', 'civilreg.emp1@example.com', 'civilreg.head@example.com', NULL),
('Issue Certifications', 'Issue civil registry certifications and documents', '2025-11-25 17:00:00', 'pending', 'civilreg.emp1@example.com', 'civilreg.head@example.com', NULL),
('Update Registry Books', 'Update and maintain civil registry books', '2025-11-20 17:00:00', 'completed', 'civilreg.emp1@example.com', 'civilreg.head@example.com', '2025-11-20 16:00:00'),
('Verify Documents', 'Verify authenticity of civil registry documents', '2025-11-27 17:00:00', 'pending', 'civilreg.emp1@example.com', 'civilreg.head@example.com', NULL),
('Process Marriage Licenses', 'Process applications for marriage licenses', '2025-11-21 17:00:00', 'in_progress', 'civilreg.emp2@example.com', 'civilreg.head@example.com', NULL),
('Conduct Marriage Ceremonies', 'Conduct civil marriage ceremonies', '2025-11-19 17:00:00', 'completed', 'civilreg.emp2@example.com', 'civilreg.head@example.com', '2025-11-19 15:30:00'),
('Update Death Records', 'Register and update death certificates', '2025-11-26 17:00:00', 'pending', 'civilreg.emp2@example.com', 'civilreg.head@example.com', NULL),
('Archive Old Records', 'Archive civil registry records from previous years', '2025-11-23 17:00:00', 'in_progress', 'civilreg.emp2@example.com', 'civilreg.head@example.com', NULL),
('Prepare Monthly Report', 'Prepare monthly civil registration statistics report', '2025-11-28 17:00:00', 'pending', 'civilreg.emp2@example.com', 'civilreg.head@example.com', NULL),
('Register Events', 'Register vital events and maintain records', '2025-11-24 17:00:00', 'in_progress', 'civilreg.emp3@example.com', 'civilreg.head@example.com', NULL),
('Coordinate with PSA', 'Coordinate with PSA for document transmission', '2025-11-29 17:00:00', 'pending', 'civilreg.emp3@example.com', 'civilreg.head@example.com', NULL),
('Digitize Records', 'Digitize old civil registry records', '2025-11-18 17:00:00', 'completed', 'civilreg.emp3@example.com', 'civilreg.head@example.com', '2025-11-18 14:00:00');

-- Office of the Municipal Treasurer employees (assigned by treasurer.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Process Tax Payments', 'Process real property tax payments', '2025-11-22 17:00:00', 'in_progress', 'treasurer.emp1@example.com', 'treasurer.head@example.com', NULL),
('Reconcile Collections', 'Reconcile daily collections with reports', '2025-11-25 17:00:00', 'pending', 'treasurer.emp1@example.com', 'treasurer.head@example.com', NULL),
('Deposit Collections', 'Deposit municipal collections to bank', '2025-11-19 17:00:00', 'completed', 'treasurer.emp1@example.com', 'treasurer.head@example.com', '2025-11-19 16:30:00'),
('Issue Official Receipts', 'Issue official receipts for various payments', '2025-11-27 17:00:00', 'pending', 'treasurer.emp1@example.com', 'treasurer.head@example.com', NULL),
('Monitor Cash Flow', 'Monitor municipal cash flow and liquidity', '2025-11-24 17:00:00', 'in_progress', 'treasurer.emp1@example.com', 'treasurer.head@example.com', NULL),
('Process Business Permits', 'Process business permit fees and payments', '2025-11-21 17:00:00', 'in_progress', 'treasurer.emp2@example.com', 'treasurer.head@example.com', NULL),
('Prepare Collection Report', 'Prepare monthly collection report', '2025-11-20 17:00:00', 'completed', 'treasurer.emp2@example.com', 'treasurer.head@example.com', '2025-11-20 15:00:00'),
('Update Treasury System', 'Update treasury management system', '2025-11-26 17:00:00', 'pending', 'treasurer.emp2@example.com', 'treasurer.head@example.com', NULL),
('Issue Tax Clearances', 'Issue tax clearances and certifications', '2025-11-23 17:00:00', 'in_progress', 'treasurer.emp2@example.com', 'treasurer.head@example.com', NULL),
('Count Cash Drawer', 'Count and verify cash drawer at end of day', '2025-11-28 17:00:00', 'pending', 'treasurer.emp3@example.com', 'treasurer.head@example.com', NULL),
('File Treasury Documents', 'File and organize treasury documents', '2025-11-18 17:00:00', 'completed', 'treasurer.emp3@example.com', 'treasurer.head@example.com', '2025-11-18 16:00:00'),
('Process Refunds', 'Process tax refunds and corrections', '2025-11-29 17:00:00', 'pending', 'treasurer.emp3@example.com', 'treasurer.head@example.com', NULL);

-- Office of the Municipal Social Welfare and Development Officer employees (assigned by mswdo.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Process 4Ps Beneficiaries', 'Process and verify 4Ps beneficiary list', '2025-11-22 17:00:00', 'in_progress', 'mswdo.emp1@example.com', 'mswdo.head@example.com', NULL),
('Conduct Home Visits', 'Conduct home visits for social case studies', '2025-11-25 17:00:00', 'pending', 'mswdo.emp1@example.com', 'mswdo.head@example.com', NULL),
('Organize Feeding Program', 'Organize community feeding program', '2025-11-19 17:00:00', 'completed', 'mswdo.emp1@example.com', 'mswdo.head@example.com', '2025-11-19 15:00:00'),
('Prepare Social Reports', 'Prepare monthly social welfare activity reports', '2025-11-27 17:00:00', 'pending', 'mswdo.emp1@example.com', 'mswdo.head@example.com', NULL),
('Distribute Assistance', 'Distribute financial assistance to qualified beneficiaries', '2025-11-21 17:00:00', 'in_progress', 'mswdo.emp2@example.com', 'mswdo.head@example.com', NULL),
('Interview Applicants', 'Interview and assess assistance applicants', '2025-11-20 17:00:00', 'completed', 'mswdo.emp2@example.com', 'mswdo.head@example.com', '2025-11-20 16:00:00'),
('Update DSWD Database', 'Update DSWD-related database and records', '2025-11-26 17:00:00', 'pending', 'mswdo.emp2@example.com', 'mswdo.head@example.com', NULL),
('Coordinate with Barangays', 'Coordinate with barangays for social programs', '2025-11-23 17:00:00', 'in_progress', 'mswdo.emp2@example.com', 'mswdo.head@example.com', NULL),
('Monitor PWD Programs', 'Monitor programs for persons with disabilities', '2025-11-28 17:00:00', 'pending', 'mswdo.emp2@example.com', 'mswdo.head@example.com', NULL),
('Process Senior Citizen IDs', 'Process and issue senior citizen IDs', '2025-11-24 17:00:00', 'in_progress', 'mswdo.emp3@example.com', 'mswdo.head@example.com', NULL),
('Organize Training', 'Organize livelihood training for indigent families', '2025-11-29 17:00:00', 'pending', 'mswdo.emp3@example.com', 'mswdo.head@example.com', NULL),
('Document Case Studies', 'Document and file social case studies', '2025-11-18 17:00:00', 'completed', 'mswdo.emp3@example.com', 'mswdo.head@example.com', '2025-11-18 15:30:00');

-- Office of the Municipal Health Officer employees (assigned by health.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Conduct Vaccination Drive', 'Organize and conduct community vaccination drive', '2025-11-22 17:00:00', 'in_progress', 'health.emp1@example.com', 'health.head@example.com', NULL),
('Monitor Disease Outbreaks', 'Monitor and report disease outbreak cases', '2025-11-25 17:00:00', 'pending', 'health.emp1@example.com', 'health.head@example.com', NULL),
('Prepare Health Report', 'Prepare monthly health statistics report', '2025-11-19 17:00:00', 'completed', 'health.emp1@example.com', 'health.head@example.com', '2025-11-19 16:00:00'),
('Inspect Food Establishments', 'Inspect food establishments for sanitation compliance', '2025-11-27 17:00:00', 'pending', 'health.emp1@example.com', 'health.head@example.com', NULL),
('Coordinate with DOH', 'Coordinate with DOH for health programs', '2025-11-24 17:00:00', 'in_progress', 'health.emp1@example.com', 'health.head@example.com', NULL),
('Distribute Medicines', 'Distribute medicines to barangay health stations', '2025-11-21 17:00:00', 'in_progress', 'health.emp2@example.com', 'health.head@example.com', NULL),
('Conduct Health Education', 'Conduct health education sessions in communities', '2025-11-20 17:00:00', 'completed', 'health.emp2@example.com', 'health.head@example.com', '2025-11-20 15:30:00'),
('Process Health Certificates', 'Process and issue health certificates', '2025-11-26 17:00:00', 'pending', 'health.emp2@example.com', 'health.head@example.com', NULL),
('Monitor RHU Operations', 'Monitor operations of rural health units', '2025-11-23 17:00:00', 'in_progress', 'health.emp2@example.com', 'health.head@example.com', NULL),
('Update Patient Records', 'Update and maintain patient medical records', '2025-11-28 17:00:00', 'pending', 'health.emp3@example.com', 'health.head@example.com', NULL),
('Inventory Medical Supplies', 'Conduct inventory of medical supplies and equipment', '2025-11-18 17:00:00', 'completed', 'health.emp3@example.com', 'health.head@example.com', '2025-11-18 14:30:00'),
('Prepare Dengue Report', 'Prepare dengue monitoring and surveillance report', '2025-11-29 17:00:00', 'pending', 'health.emp3@example.com', 'health.head@example.com', NULL);

-- Office of the Municipal Agriculturist employees (assigned by agri.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Conduct Farm Visit', 'Conduct farm visits for technical assistance', '2025-11-22 17:00:00', 'in_progress', 'agri.emp1@example.com', 'agri.head@example.com', NULL),
('Distribute Seeds', 'Distribute vegetable seeds to farmers', '2025-11-25 17:00:00', 'pending', 'agri.emp1@example.com', 'agri.head@example.com', NULL),
('Prepare Agricultural Report', 'Prepare monthly agricultural production report', '2025-11-19 17:00:00', 'completed', 'agri.emp1@example.com', 'agri.head@example.com', '2025-11-19 15:00:00'),
('Monitor Crop Production', 'Monitor crop production in various barangays', '2025-11-27 17:00:00', 'pending', 'agri.emp1@example.com', 'agri.head@example.com', NULL),
('Organize Farmers Training', 'Organize training for organic farming methods', '2025-11-21 17:00:00', 'in_progress', 'agri.emp2@example.com', 'agri.head@example.com', NULL),
('Process Subsidy Applications', 'Process farmers subsidy applications', '2025-11-20 17:00:00', 'completed', 'agri.emp2@example.com', 'agri.head@example.com', '2025-11-20 16:30:00'),
('Coordinate with DA', 'Coordinate with Department of Agriculture programs', '2025-11-26 17:00:00', 'pending', 'agri.emp2@example.com', 'agri.head@example.com', NULL),
('Inspect Irrigation Systems', 'Inspect and assess irrigation systems', '2025-11-23 17:00:00', 'in_progress', 'agri.emp2@example.com', 'agri.head@example.com', NULL),
('Update Farm Records', 'Update farmers database and farm records', '2025-11-28 17:00:00', 'pending', 'agri.emp2@example.com', 'agri.head@example.com', NULL),
('Test Soil Samples', 'Collect and test soil samples from farms', '2025-11-24 17:00:00', 'in_progress', 'agri.emp3@example.com', 'agri.head@example.com', NULL),
('Distribute Fertilizers', 'Distribute fertilizers to registered farmers', '2025-11-18 17:00:00', 'completed', 'agri.emp3@example.com', 'agri.head@example.com', '2025-11-18 15:30:00'),
('Monitor Pest Control', 'Monitor and assist in pest control activities', '2025-11-29 17:00:00', 'pending', 'agri.emp3@example.com', 'agri.head@example.com', NULL);

-- Office of the MDRRMO employees (assigned by mdrrmo.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Conduct Disaster Drill', 'Conduct earthquake drill in schools', '2025-11-22 17:00:00', 'in_progress', 'mdrrmo.emp1@example.com', 'mdrrmo.head@example.com', NULL),
('Update Contingency Plan', 'Update municipal disaster contingency plan', '2025-11-25 17:00:00', 'pending', 'mdrrmo.emp1@example.com', 'mdrrmo.head@example.com', NULL),
('Inspect Evacuation Centers', 'Inspect and assess evacuation centers', '2025-11-19 17:00:00', 'completed', 'mdrrmo.emp1@example.com', 'mdrrmo.head@example.com', '2025-11-19 16:00:00'),
('Prepare Risk Assessment', 'Prepare community risk assessment report', '2025-11-27 17:00:00', 'pending', 'mdrrmo.emp1@example.com', 'mdrrmo.head@example.com', NULL),
('Monitor Weather Updates', 'Monitor weather forecasts and issue advisories', '2025-11-24 17:00:00', 'in_progress', 'mdrrmo.emp1@example.com', 'mdrrmo.head@example.com', NULL),
('Maintain Emergency Equipment', 'Maintain rescue and emergency equipment', '2025-11-21 17:00:00', 'in_progress', 'mdrrmo.emp2@example.com', 'mdrrmo.head@example.com', NULL),
('Conduct Training', 'Conduct basic life support training for responders', '2025-11-20 17:00:00', 'completed', 'mdrrmo.emp2@example.com', 'mdrrmo.head@example.com', '2025-11-20 15:00:00'),
('Update Emergency Contacts', 'Update emergency contact directory', '2025-11-26 17:00:00', 'pending', 'mdrrmo.emp2@example.com', 'mdrrmo.head@example.com', NULL),
('Inspect Fire Hazards', 'Inspect establishments for fire hazards', '2025-11-23 17:00:00', 'in_progress', 'mdrrmo.emp2@example.com', 'mdrrmo.head@example.com', NULL),
('Prepare Disaster Supplies', 'Prepare and inventory disaster relief supplies', '2025-11-28 17:00:00', 'pending', 'mdrrmo.emp3@example.com', 'mdrrmo.head@example.com', NULL),
('Update Warning System', 'Update early warning system equipment', '2025-11-18 17:00:00', 'completed', 'mdrrmo.emp3@example.com', 'mdrrmo.head@example.com', '2025-11-18 14:30:00'),
('Coordinate with BFP', 'Coordinate with Bureau of Fire Protection', '2025-11-29 17:00:00', 'pending', 'mdrrmo.emp3@example.com', 'mdrrmo.head@example.com', NULL);

-- Office of the Municipal Legal Officer employees (assigned by legal.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Review Contracts', 'Review and draft municipal contracts', '2025-11-22 17:00:00', 'in_progress', 'legal.emp1@example.com', 'legal.head@example.com', NULL),
('Provide Legal Opinion', 'Provide legal opinion on municipal matters', '2025-11-25 17:00:00', 'pending', 'legal.emp1@example.com', 'legal.head@example.com', NULL),
('Attend Court Hearings', 'Attend court hearings for municipal cases', '2025-11-19 17:00:00', 'completed', 'legal.emp1@example.com', 'legal.head@example.com', '2025-11-19 16:30:00'),
('Draft Ordinances', 'Draft proposed municipal ordinances', '2025-11-27 17:00:00', 'pending', 'legal.emp1@example.com', 'legal.head@example.com', NULL),
('Process Legal Documents', 'Process and notarize legal documents', '2025-11-21 17:00:00', 'in_progress', 'legal.emp2@example.com', 'legal.head@example.com', NULL),
('Prepare Legal Memorandum', 'Prepare legal memorandum on property issues', '2025-11-20 17:00:00', 'completed', 'legal.emp2@example.com', 'legal.head@example.com', '2025-11-20 15:30:00'),
('Review Resolutions', 'Review legality of council resolutions', '2025-11-26 17:00:00', 'pending', 'legal.emp2@example.com', 'legal.head@example.com', NULL),
('Handle Complaints', 'Handle citizen complaints and legal inquiries', '2025-11-23 17:00:00', 'in_progress', 'legal.emp2@example.com', 'legal.head@example.com', NULL),
('Update Legal Database', 'Update municipal legal database and files', '2025-11-28 17:00:00', 'pending', 'legal.emp2@example.com', 'legal.head@example.com', NULL),
('Research Legal Precedents', 'Research legal precedents for ongoing cases', '2025-11-24 17:00:00', 'in_progress', 'legal.emp3@example.com', 'legal.head@example.com', NULL),
('Prepare Case Documents', 'Prepare documents for filing in court', '2025-11-18 17:00:00', 'completed', 'legal.emp3@example.com', 'legal.head@example.com', '2025-11-18 15:00:00'),
('Coordinate with Prosecutor', 'Coordinate with prosecutor''s office on cases', '2025-11-29 17:00:00', 'pending', 'legal.emp3@example.com', 'legal.head@example.com', NULL);

-- Office of the Municipal General Services Officer employees (assigned by gso.head@example.com)
INSERT INTO tasks (title, description, due_date, status, assigned_to_email, assigned_by_email, completed_at) VALUES
('Maintain Municipal Vehicles', 'Conduct maintenance of all municipal vehicles', '2025-11-22 17:00:00', 'in_progress', 'gso.emp1@example.com', 'gso.head@example.com', NULL),
('Manage Building Facilities', 'Manage and maintain municipal building facilities', '2025-11-25 17:00:00', 'pending', 'gso.emp1@example.com', 'gso.head@example.com', NULL),
('Process Supply Requests', 'Process office supply requests from departments', '2025-11-19 17:00:00', 'completed', 'gso.emp1@example.com', 'gso.head@example.com', '2025-11-19 16:00:00'),
('Conduct Property Inventory', 'Conduct inventory of municipal properties', '2025-11-27 17:00:00', 'pending', 'gso.emp1@example.com', 'gso.head@example.com', NULL),
('Coordinate Security Services', 'Coordinate security services for municipal buildings', '2025-11-24 17:00:00', 'in_progress', 'gso.emp1@example.com', 'gso.head@example.com', NULL),
('Arrange Vehicle Dispatch', 'Arrange vehicle dispatch for official use', '2025-11-21 17:00:00', 'in_progress', 'gso.emp2@example.com', 'gso.head@example.com', NULL),
('Supervise Janitorial Services', 'Supervise janitorial services in all offices', '2025-11-20 17:00:00', 'completed', 'gso.emp2@example.com', 'gso.head@example.com', '2025-11-20 15:00:00'),
('Process Procurement', 'Process procurement of office equipment', '2025-11-26 17:00:00', 'pending', 'gso.emp2@example.com', 'gso.head@example.com', NULL),
('Maintain Records', 'Maintain property and equipment records', '2025-11-23 17:00:00', 'in_progress', 'gso.emp2@example.com', 'gso.head@example.com', NULL),
('Monitor Utilities', 'Monitor utilities consumption and billing', '2025-11-28 17:00:00', 'pending', 'gso.emp2@example.com', 'gso.head@example.com', NULL),
('Repair Office Equipment', 'Repair and service office equipment', '2025-11-24 17:00:00', 'in_progress', 'gso.emp3@example.com', 'gso.head@example.com', NULL),
('Distribute Supplies', 'Distribute office supplies to all departments', '2025-11-18 17:00:00', 'completed', 'gso.emp3@example.com', 'gso.head@example.com', '2025-11-18 15:30:00'),
('Update Asset Registry', 'Update municipal asset registry system', '2025-11-29 17:00:00', 'pending', 'gso.emp3@example.com', 'gso.head@example.com', NULL);

-- Municipal Events
INSERT INTO events (title, date, time, location, description) VALUES
('Municipal Assembly Meeting', '2025-11-25', '09:00 AM', 'Municipal Hall Session Room', 'Regular monthly assembly meeting for all department heads and key personnel to discuss municipal affairs and ongoing projects.'),
('Disaster Preparedness Training', '2025-11-28', '08:00 AM', 'MDRRMO Training Center', 'Quarterly disaster preparedness and response training for all municipal employees. Topics include first aid, evacuation procedures, and emergency protocols.'),
('Budget Planning Workshop', '2025-12-02', '01:00 PM', 'Municipal Conference Room', 'Annual budget planning workshop for department heads to prepare and present budget proposals for the upcoming fiscal year.'),
('Health and Wellness Program', '2025-11-30', '08:30 AM', 'Municipal Gymnasium', 'Monthly health and wellness program for all municipal employees including free medical check-ups, health education, and fitness activities.'),
('IT System Training', '2025-12-05', '10:00 AM', 'Computer Laboratory', 'Training session on the new municipal information system and digital documentation procedures for all office staff.'),
('Tax Campaign Launch', '2025-12-01', '09:00 AM', 'Municipal Plaza', 'Launch of the annual real property tax collection campaign. Open to all citizens and coordinated by Treasury and Assessor offices.'),
('Infrastructure Project Review', '2025-11-29', '02:00 PM', 'Engineering Office', 'Quarterly review meeting for ongoing infrastructure projects. All project engineers and contractors required to attend.'),
('Civil Service Examination', '2025-12-10', '07:00 AM', 'Municipal High School', 'Semi-annual Civil Service Examination for aspiring government employees. Coordination with Civil Service Commission.'),
('Agricultural Fair', '2025-12-08', '08:00 AM', 'Municipal Sports Complex', 'Annual agricultural fair showcasing local farm products, modern farming techniques, and distribution of farming assistance.'),
('Year-End Performance Evaluation', '2025-12-15', '09:00 AM', 'HR Office', 'Scheduled performance evaluation sessions for all municipal employees. Department heads to coordinate with HR.'),
('Legal Clinic Outreach', '2025-12-03', '01:00 PM', 'Barangay Hall Complex', 'Free legal consultation and assistance for constituents. Organized by Municipal Legal Office with volunteer lawyers.'),
('Fire Safety Seminar', '2025-12-07', '08:30 AM', 'Fire Station', 'Fire safety and prevention seminar for all building administrators and facility managers. Conducted by BFP and MDRRMO.'),
('Senior Citizen Program', '2025-12-12', '09:00 AM', 'MSWDO Office', 'Monthly program for senior citizens including distribution of benefits, health check-ups, and recreational activities.'),
('Municipal Christmas Party', '2025-12-20', '05:00 PM', 'Municipal Hall Grounds', 'Annual Christmas celebration for all municipal employees and their families. Dinner, entertainment, and gift-giving program.'),
('New Year Planning Session', '2025-12-28', '08:00 AM', 'Mayor''s Conference Room', 'Strategic planning session for the new year. Discussion of priorities, projects, and initiatives for all departments.');

-- Completion message
SELECT 'Employee tasks and events seeded successfully!' AS message;
