# 🎯 QR Attendance System - Quick Start Guide

## ✅ What's Been Implemented

### 1. **Database Updates**
- ✅ Added `employee_id` and `qr_code` columns to `users` table
- ✅ Created `attendance` table for recording attendance
- ✅ Updated `database.sql` with all necessary tables

### 2. **Registration System** 
- ✅ Auto-generates unique Employee ID (EMP2025-0001, EMP2025-0002, etc.)
- ✅ Auto-creates QR code for each new registration
- ✅ Uses existing department list (17 departments)

### 3. **Attendance Folder** (`/attendance/`)
Created complete attendance monitoring system:

#### Files Created:
- **index.php** - QR Scanner (auto-opens camera, mirrored view)
- **scan_qr.php** - API for recording Time In/Out
- **dashboard.php** - Real-time dashboard with stats
- **get_dashboard.php** - Dashboard API
- **records.php** - Attendance records with CSV/Excel export
- **get_attendance.php** - Records API
 
- **migration.sql** - Easy database setup
- **README.md** - Complete documentation

## 🚀 Installation Steps

### Step 1: Database Setup
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select database: `capstone`
3. Click "Import" tab
4. Import `database.sql` (already updated with attendance table)
5. Done! (or run `attendance/migration.sql` if you have existing database)

### Step 2: Test the System

#### A. Register a New User
1. Go to: `http://localhost/capstone/register.php`
2. Fill in the form
3. Note your Employee ID shown on success
4. Wait for super admin approval

#### B. QR Scanner
1. Go to: `http://localhost/capstone/attendance/`
2. **Camera will auto-open** (allow camera access)
3. Show QR code to camera (mirrored view)
4. System records Time In/Out automatically

#### C. View Dashboard
1. Go to: `http://localhost/capstone/attendance/dashboard.php`
2. See real-time statistics
3. Filter by department
4. Auto-refreshes every 5 seconds

#### D. View Records
1. Go to: `http://localhost/capstone/attendance/records.php`
2. Search/filter attendance
3. Export to CSV or Excel

 #### E. View Your QR Code
 1. Login to your account
 2. Download or print your QR code via the administration panel or the rotating QR station (the personal QR page has been removed)

## 📱 Access Links

| Feature | URL |
|---------|-----|
| Registration | `http://localhost/capstone/register.php` |
| Login | `http://localhost/capstone/index.php` |
| **QR Scanner** | `http://localhost/capstone/attendance/` |
| **Dashboard** | `http://localhost/capstone/attendance/dashboard.php` |
| **Records** | `http://localhost/capstone/attendance/records.php` |
| **My QR Code** | (removed — personal QR page retired; use rotating station) |

## 🎯 Key Features

### ✅ Implemented Features:
1. ✅ **Auto-generate unique Employee ID** for each registration
2. ✅ **QR code auto-creation** (uses Employee ID as QR data)
3. ✅ **Camera auto-opens** on scanner page
4. ✅ **Mirrored camera view** (selfie-style for easy scanning)
5. ✅ **Auto Time In/Out detection** (first scan = in, second = out)
6. ✅ **Status classification** (Present if ≤7:00 AM, Late if >7:00 AM)
7. ✅ **3-second info popup** after scan
8. ✅ **Real-time dashboard** with department filter
9. ✅ **CSV/Excel export** from records
10. ✅ **17 departments** matching your system
11. ✅ **Professional UI/UX** with gradients and animations
12. ✅ **Clean folder structure** (all attendance files in `/attendance/`)

### 🏢 Departments (17 Total):
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

## 🔧 How It Works

### Registration Flow:
```
User Registers → System generates EMP2025-0001 → QR code created → Super Admin approves → User can view QR
```

### Attendance Flow:
```
Show QR to camera → Camera reads Employee ID → Check database:
  ├─ No record? → TIME IN (calculate status)
  ├─ Has Time In? → TIME OUT
  └─ Already complete? → Error message
→ Show employee info for 3 seconds → Resume scanning
```

### Status Logic:
- **Present**: Time In ≤ 7:00 AM
- **Late**: Time In > 7:00 AM
- **Absent**: No Time In for the day

## 🎨 UI/UX Features
- **Modern gradient design** (Purple theme)
- **Auto-opening camera** (no manual start needed)
- **Mirrored camera view** (easier to align QR)
- **Real-time clock** on scanner
- **Smooth animations** for info cards
- **Responsive layout** (works on different screen sizes)
- **Auto-refresh** on dashboard and records
- **Icon integration** (Font Awesome)
- **Professional cards** with shadows and hover effects

## 📊 Testing Checklist

- [ ] Database imported successfully
- [ ] Can register new user and see Employee ID
- [ ] Super admin can approve user
 - [ ] Can view QR code at my_qr.php (note: personal QR page removed)
- [ ] Scanner page auto-opens camera
- [ ] Camera is mirrored (selfie view)
- [ ] Can scan QR and record Time In
- [ ] Employee info shows for 3 seconds
- [ ] Can scan again and record Time Out
- [ ] Dashboard shows correct counts
- [ ] Department filter works
- [ ] Records page shows attendance
- [ ] Can export to CSV
- [ ] Can export to Excel

## 🐛 Troubleshooting

### Camera not opening?
- Check browser permissions (allow camera)
- Use Chrome (recommended)
- Must be on `http://localhost` (not IP without HTTPS)

### QR not scanning?
- Ensure good lighting
- Hold QR 15-30cm from camera
- Check if QR code is clear

### Employee not found?
- Verify user is approved in database
- Check employee_id column is populated
- Ensure qr_code matches employee_id

### No data showing?
- Check database connection in `db.php`
- Verify tables exist
- Check browser console (F12) for errors

## 📁 File Structure
```
capstone/
├── register.php (✅ Updated with Employee ID generation)
├── database.sql (✅ Updated with attendance table)
├── attendance/ (📁 NEW FOLDER)
│   ├── index.php (QR Scanner)
│   ├── scan_qr.php (API)
│   ├── dashboard.php
│   ├── get_dashboard.php (API)
│   ├── records.php
│   ├── get_attendance.php (API)
│   ├── my_qr.php
│   ├── migration.sql
│   └── README.md
└── QUICKSTART.md (This file)
```

## 🎓 For Developers

### API Endpoints:

**POST /attendance/scan_qr.php**
```json
Request: {"employee_code": "EMP2025-0001"}
Response: {
  "success": true,
  "action": "time_in",
  "employee": {...},
  "time": "2025-11-04 08:30:00",
  "status": "Late"
}
```

**GET /attendance/get_dashboard.php?department=HR**
```json
Response: {
  "success": true,
  "total_employees": 50,
  "present": 45,
  "late": 5,
  "absent": 0
}
```

**GET /attendance/get_attendance.php?date=2025-11-04&department=HR**
```json
Response: {
  "success": true,
  "records": [...]
}
```

## ✨ Summary

You now have a **complete, professional QR-based attendance monitoring system** that:

1. ✅ Auto-generates Employee IDs and QR codes during registration
2. ✅ Auto-opens camera for scanning (mirrored view)
3. ✅ Records Time In/Out automatically
4. ✅ Classifies status (Present/Late/Absent)
5. ✅ Shows real-time dashboard with department filtering
6. ✅ Exports attendance data to CSV/Excel
7. ✅ Uses your existing 17 departments
8. ✅ Has professional, modern UI/UX
9. ✅ All attendance files organized in `/attendance/` folder

**Everything is ready to use!** 🎉

---

**Need Help?** Check `attendance/README.md` for detailed documentation.
