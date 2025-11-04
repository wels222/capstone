# 📊 Attendance Status System

## Overview
The attendance system uses a **single `status` column** that changes based on time in and time out actions.

---

## ⏰ Status Flow

### **Time In (Morning)**
When employee scans QR for the first time:
- ✅ **Present** - Time in at or before 7:00 AM (Green)
- 🟠 **Late** - Time in between 7:01 AM and 7:30 AM (Yellow/Orange)
- 🔴 **Absent** - Time in after 7:30 AM (Red) - Too late, marked as absent

### **Time Out (Afternoon/Evening)**
When employee scans QR for the second time, status updates to:
- ✅ **On-time** - Time out between 5:00 PM - 5:05 PM (Green)
- 🟡 **Undertime** - Time out before 5:00 PM (Yellow)
- 🔵 **Overtime** - Time out at 5:30 PM or later (Blue)
- Note: 5:06 PM - 5:29 PM also considered **On-time** (grace period)

### **No Time In**
If employee never scans QR by 7:30 AM:
- 🔴 **Absent** - No attendance record (Red)

---

## 🎨 Color Coding

| Status | Color | Hex Code |
|--------|-------|----------|
| Present | 🟢 Green | #10b981 |
| Late | 🟠 Orange/Yellow | #f59e0b |
| On-time | 🟢 Green | #10b981 |
| Undertime | 🟡 Yellow | #f59e0b |
| Overtime | 🔵 Blue | #3b82f6 |
| Absent | 🔴 Red | #ef4444 |

---

## 📁 Files Updated

### 1. **scan_qr.php** (Backend)
- Time In: Sets status to "Present" or "Late"
- Time Out: Updates status to "On-time", "Undertime", or "Overtime"

### 2. **scan.html** (Frontend)
- Displays appropriate color based on status
- Shows status badge with colored background

### 3. **get_dashboard.php** (Dashboard API)
- Calculates Present, Late, and Absent counts
- Absent = Total Employees - Present

### 4. **mark_absent.php** (Helper Script)
- Run at end of day to mark employees without time_in as "Absent"
- Can be automated via cron job

---

## 🔧 Database Schema

```sql
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time_in DATETIME DEFAULT NULL,
    time_out DATETIME DEFAULT NULL,
    status VARCHAR(20),  -- Changes from time_in status to time_out status
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 📝 Usage Example

**Employee EMP2025-0001 Timeline:**

| Time | Action | Status | Color |
|------|--------|--------|-------|
| 6:45 AM | Time In | Present | 🟢 Green |
| 5:15 PM | Time Out | On-time | 🟢 Green |

**Employee EMP2025-0002 Timeline:**

| Time | Action | Status | Color |
|------|--------|--------|-------|
| 8:30 AM | Time In | Late | 🟠 Orange |
| 6:00 PM | Time Out | Overtime | 🔵 Blue |

**Employee EMP2025-0003 Timeline:**

| Time | Action | Status | Color |
|------|--------|--------|-------|
| 7:15 AM | Time In | Late | 🟠 Orange |
| 4:30 PM | Time Out | Undertime | 🟡 Yellow |

**Employee EMP2025-0004:**

| Time | Action | Status | Color |
|------|--------|--------|-------|
| - | No Time In | Absent | 🔴 Red |

---

## 🚀 How to Mark Absent Employees

Run this command at the end of each day (e.g., 11:59 PM):

```bash
php c:\xampp\htdocs\capstone\attendance\mark_absent.php
```

Or set up Windows Task Scheduler to run it automatically daily.

---

## ✅ Complete!

The attendance system now properly tracks and displays:
- Time In status (Present/Late)
- Time Out status (On-time/Undertime/Overtime)
- Absent status (No attendance record)
- Color-coded visual feedback on scanner and dashboard
