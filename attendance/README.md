# QR-Based Attendance Monitoring System

## 📁 Folder Structure
```
attendance/
├── index.php           - Main QR scanner page (auto-opens camera)
├── scan_qr.php         - API endpoint for recording attendance
├── dashboard.php       - Centralized dashboard with stats and records table
├── get_dashboard.php   - API for dashboard data
├── get_attendance.php  - API for attendance records
├── my_qr.php          - View and download personal QR code
├── menu.php           - Navigation menu
├── migration.sql      - Database setup script
└── README.md          - This file
```

## 🗄️ Database Architecture

### Single Source of Truth: `users` table
The system uses **ONLY the `users` table** - no separate employees table needed.

**users table** contains:
- Basic info (firstname, lastname, email, etc.)
- `employee_id` - Unique ID (e.g., EMP2025-0001)
- `qr_code` - QR code data (same as employee_id)
- `profile_picture` - User photo
- `department` - User's department
- `status` - pending/approved/declined

**attendance table** references:
- `employee_id` links to users.employee_id
- Stores time_in, time_out, status per day

## 🚀 Features

### 1. **QR Scanner (index.php)**
- Auto-opens camera on page load
- Mirrored camera view for selfie-style scanning
- Real-time clock display
- 3-second employee info popup after scan
- Automatic Time In/Time Out detection
- Status classification (Present/Late/Absent)

### 2. **Dashboard (dashboard.php)**
- Real-time statistics
- Department filtering (17 departments)
- Auto-refresh every 5 seconds
- Visual cards with icons

### 3. **Records (records.php)**
- Complete attendance table
- Date/Department/Search filters
- CSV and Excel export
- Auto-refresh every 10 seconds

### 4. **My QR Code (my_qr.php)**
- View personal QR code
- Download QR as image
- Print-ready format

## 🔧 Setup Instructions

### 1. Database Setup
Run this SQL in phpMyAdmin:
```sql
-- Add employee_id and qr_code to users table
ALTER TABLE users 
ADD COLUMN employee_id VARCHAR(100) DEFAULT NULL UNIQUE AFTER profile_picture,
ADD COLUMN qr_code VARCHAR(255) DEFAULT NULL AFTER employee_id;

-- Create attendance table
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
```

### 2. Registration
When users register via `register.php`, they automatically get:
- Unique Employee ID (format: EMP2025-0001, EMP2025-0002, etc.)
- QR code containing their Employee ID

### 3. Access URLs
- Scanner: `http://localhost/capstone/attendance/`
- Dashboard: `http://localhost/capstone/attendance/dashboard.php`
- Records: `http://localhost/capstone/attendance/records.php`
- My QR: `http://localhost/capstone/attendance/my_qr.php`

## 📋 How It Works

### Registration Flow
1. User fills registration form
2. System auto-generates Employee ID (EMP[YEAR]-[XXXX])
3. QR code is created from Employee ID
4. Super admin approves account
5. User can view/download their QR code

### Attendance Flow
1. Employee shows QR code to scanner
2. Camera reads Employee ID from QR
3. System checks if record exists for today:
   - **No record** → Record Time In + Calculate status (Present if ≤7:00 AM, Late if >7:00 AM)
   - **Has Time In, no Time Out** → Record Time Out
   - **Already completed** → Show error message
4. Display employee info for 3 seconds
5. Auto-resume scanning

### Status Rules
- **Present**: Time In at or before 7:00 AM
- **Late**: Time In after 7:00 AM
- **Absent**: No Time In record for the day

## 🏢 Department List (17 Total)
1. Mayor's Office
2. Vice Mayor's Office
3. SB Office
4. Accounting
5. Budget
6. Treasury
7. Assessor
8. Engineering
9. Planning & Development
10. HR
11. Civil Registrar
12. MSWDO
13. MHO
14. Agriculture
15. Tourism
16. Market
17. General Services

## 🎨 Design Features
- Modern gradient UI (Purple theme)
- Responsive layout
- Auto-refresh capabilities
- Smooth animations
- Professional card designs
- Icon integration (Font Awesome)

## 🔐 Security
- Only approved users can scan
- Session-based authentication for my_qr.php
- SQL injection protection (prepared statements)
- Unique attendance per day per employee

## 📱 Browser Compatibility
- ✅ Chrome (Recommended)
- ✅ Edge
- ✅ Firefox
- ⚠️ Safari (Camera permissions may vary)

## 🛠️ Troubleshooting

### Camera not opening automatically
- Check browser camera permissions
- Use `http://localhost` (not IP without HTTPS)
- Try different browser

### QR code not scanning
- Ensure QR is clear and well-lit
- Hold 15-30cm from camera
- Check if employee is approved in database

### Data not showing
- Check database connection in `db.php`
- Verify tables exist
- Check browser console for errors

## 📊 Export Formats
- **CSV**: Comma-separated values for Excel/Sheets
- **Excel**: .xls format (HTML table)

## 🔄 Auto-Refresh Intervals
- Dashboard: 5 seconds
- Records: 10 seconds
- Scanner: Continuous (real-time)

## 💡 Tips
- Print QR codes in high quality (300 DPI minimum)
- Laminate QR cards for durability
- Position scanner at eye level for easy access
- Use good lighting for scanner area

---

**Version**: 1.0  
**Date**: November 4, 2025  
**Developer**: Capstone Team  
**For**: Mabini Municipality
