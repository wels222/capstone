<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #f3f4f6; 
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 { font-size: 32px; margin-bottom: 8px; }
        .subtitle { font-size: 14px; opacity: 0.9; }
        .controls {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            flex-wrap: wrap;
        }
        label { font-weight: 600; color: #374151; }
        select {
            padding: 10px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        select:hover { border-color: #667eea; }
        select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }
        .card-title {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .card-value {
            font-size: 36px;
            font-weight: 700;
            color: #1f2937;
        }
        .total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
        .present { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; }
        .late { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; }
        .absent { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #fff; }
        .realtime {
            background: #fff;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .pulse {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            display: inline-block;
            margin-right: 6px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        .status-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .status-btn.active {
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-chart-line"></i> Attendance Dashboard</h1>
        <div class="subtitle">Real-time attendance monitoring system</div>
    </div>

    <div class="controls">
        <label for="dept-filter"><i class="fas fa-filter"></i> Filter by Department:</label>
        <select id="dept-filter">
            <option value="">All Departments</option>
            <option value="Office of the Municipal Mayor">Office of the Municipal Mayor</option>
            <option value="Office of the Municipal Vice Mayor">Office of the Municipal Vice Mayor</option>
            <option value="Office of the Sangguiniang Bayan">Office of the Sangguiniang Bayan</option>
            <option value="Office of the Municipal Administrator">Office of the Municipal Administrator</option>
            <option value="Office of the Municipal Engineer">Office of the Municipal Engineer</option>
            <option value="Office of the MPDC">Office of the MPDC</option>
            <option value="Office of the Municipal Budget Officer">Office of the Municipal Budget Officer</option>
            <option value="Office of the Municipal Assessor">Office of the Municipal Assessor</option>
            <option value="Office of the Municipal Accountant">Office of the Municipal Accountant</option>
            <option value="Office of the Municipal Civil Registrar">Office of the Municipal Civil Registrar</option>
            <option value="Office of the Municipal Treasurer">Office of the Municipal Treasurer</option>
            <option value="Office of the Municipal Social Welfare and Development Officer">Office of the Municipal Social Welfare and Development Officer</option>
            <option value="Office of the Municipal Health Officer">Office of the Municipal Health Officer</option>
            <option value="Office of the Municipal Agriculturist">Office of the Municipal Agriculturist</option>
            <option value="Office of the MDRRMO">Office of the MDRRMO</option>
            <option value="Office of the Municipal Legal Officer">Office of the Municipal Legal Officer</option>
            <option value="Office of the Municipal General Services Officer">Office of the Municipal General Services Officer</option>
        </select>
    </div>

    <div class="grid">
        <div class="card">
            <div class="card-icon total"><i class="fas fa-users"></i></div>
            <div class="card-title">Total Employees</div>
            <div class="card-value" id="total">-</div>
        </div>
        <div class="card">
            <div class="card-icon" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: #fff;"><i class="fas fa-user-check"></i></div>
            <div class="card-title">Active Today</div>
            <div class="card-value" id="active">-</div>
        </div>
        <div class="card">
            <div class="card-icon present"><i class="fas fa-check-circle"></i></div>
            <div class="card-title">Present</div>
            <div class="card-value" id="present">-</div>
        </div>
        <div class="card">
            <div class="card-icon late"><i class="fas fa-clock"></i></div>
            <div class="card-title">Late</div>
            <div class="card-value" id="late">-</div>
        </div>
        <div class="card">
            <div class="card-icon absent"><i class="fas fa-times-circle"></i></div>
            <div class="card-title">Absent Today</div>
            <div class="card-value" id="absent">-</div>
        </div>
    </div>

    <div class="realtime">
        <span class="pulse"></span>Auto-refreshing every 5 seconds | Last update: <strong id="last-update">-</strong>
    </div>

    <!-- Status Filter Buttons -->
    <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">
        <button class="status-btn active" data-status="all" style="padding: 12px 24px; background: #667eea; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);">
            <i class="fas fa-list"></i> All Records
        </button>
        <button class="status-btn" data-status="Present" style="padding: 12px 24px; background: #10b981; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s;">
            <i class="fas fa-check-circle"></i> Present Only
        </button>
        <button class="status-btn" data-status="Late" style="padding: 12px 24px; background: #f59e0b; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s;">
            <i class="fas fa-clock"></i> Late Only
        </button>
        <button class="status-btn" data-status="Absent" style="padding: 12px 24px; background: #ef4444; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s;">
            <i class="fas fa-times-circle"></i> Absent Only
        </button>
        <button class="status-btn" data-status="Out" style="padding: 12px 24px; background: #14b8a6; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s;">
            <i class="fas fa-user-check"></i> Out
        </button>
        <button class="status-btn" data-status="Undertime" style="padding: 12px 24px; background: #fb923c; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s;">
            <i class="fas fa-hourglass-half"></i> Undertime
        </button>
        <button class="status-btn" data-status="Overtime" style="padding: 12px 24px; background: #3b82f6; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 14px; transition: all 0.3s;">
            <i class="fas fa-clock"></i> Overtime
        </button>
    </div>

    <!-- Attendance Records Section -->
    <div style="margin-top: 30px;">
        <div class="controls" style="margin-bottom: 16px;">
            <input type="date" id="date-filter" value="<?= date('Y-m-d') ?>" style="padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px;">
            <input type="text" id="search" placeholder="Search by name or ID" style="padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px;">
            <button id="refresh-btn" style="padding: 10px 16px; background: #667eea; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif;">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button id="export-csv" style="padding: 10px 16px; background: #10b981; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif;">
                <i class="fas fa-file-csv"></i> Export CSV
            </button>
            <button id="export-excel" style="padding: 10px 16px; background: #10b981; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-family: 'Poppins', sans-serif;">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
        </div>

        <div style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 16px 24px;">
                <h2 style="font-size: 20px; margin: 0;"><i class="fas fa-table"></i> Attendance Records</h2>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Employee ID</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Name</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Department</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Date</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Time In</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Time In Status</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Time Out</th>
                            <th style="padding: 16px; text-align: left; font-weight: 700; color: #374151; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 2px solid #e5e7eb;">Time Out Status</th>
                        </tr>
                    </thead>
                    <tbody id="records-tbody">
                        <tr><td colspan="8" style="text-align: center; padding: 40px; color: #6b7280;"><i class="fas fa-spinner fa-spin"></i> Loading records...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let currentRecords = [];
        let statusFilter = 'all';

        async function fetchDashboard(){
            const dept = document.getElementById('dept-filter').value;
            const url = 'get_dashboard.php' + (dept ? '?department=' + encodeURIComponent(dept) : '');
            
            try {
                const res = await fetch(url);
                const data = await res.json();
                
                if(data.success){
                    document.getElementById('total').textContent = data.total_employees;
                    document.getElementById('active').textContent = data.active || (data.present + data.late);
                    document.getElementById('present').textContent = data.present;
                    document.getElementById('late').textContent = data.late;
                    document.getElementById('absent').textContent = data.absent;
                    document.getElementById('last-update').textContent = new Date().toLocaleTimeString();
                }
            } catch(err) {
                console.error('Dashboard fetch error:', err);
            }
        }

        async function loadRecords(){
            const date = document.getElementById('date-filter').value;
            const dept = document.getElementById('dept-filter').value;
            const search = document.getElementById('search').value;
            
            let url = 'get_attendance.php?date=' + encodeURIComponent(date);
            if(dept) url += '&department=' + encodeURIComponent(dept);
            if(search) url += '&search=' + encodeURIComponent(search);
            if(statusFilter !== 'all') url += '&status=' + encodeURIComponent(statusFilter);

            try {
                const res = await fetch(url);
                const data = await res.json();
                
                if(data.success){
                    currentRecords = data.records;
                    renderRecords(currentRecords);
                } else {
                    document.getElementById('records-tbody').innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Error: ' + (data.error || 'Failed to load') + '</td></tr>';
                }
            } catch(err) {
                console.error('Records fetch error:', err);
                document.getElementById('records-tbody').innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Connection error. Please check if the database is running.</td></tr>';
            }
        }

        function renderRecords(records){
            const tbody = document.getElementById('records-tbody');
            if(records.length === 0){
                let msg = 'No attendance records found';
                if(statusFilter !== 'all') msg += ' for status: ' + statusFilter;
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 40px; color: #6b7280;"><i class="fas fa-info-circle"></i> ' + msg + '</td></tr>';
                return;
            }
            
            tbody.innerHTML = '';
            records.forEach(rec=>{
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #e5e7eb';
                tr.style.transition = 'background 0.2s';
                tr.onmouseover = function(){ this.style.background = '#f9fafb'; };
                tr.onmouseout = function(){ this.style.background = ''; };
                
                const timeIn = rec.time_in ? new Date(rec.time_in).toLocaleTimeString() : '-';
                const timeOut = rec.time_out ? new Date(rec.time_out).toLocaleTimeString() : '-';
                
                // Time In Status
                const timeInStatus = rec.time_in_status || 'Absent';
                let timeInBg = '#fee2e2';
                let timeInColor = '#dc2626';
                if(timeInStatus === 'Present') { timeInBg = '#dcfce7'; timeInColor = '#16a34a'; }
                else if(timeInStatus === 'Late') { timeInBg = '#fef3c7'; timeInColor = '#d97706'; }
                else if(timeInStatus === 'Undertime') { timeInBg = '#fde68a'; timeInColor = '#b45309'; }
                
                // Time Out Status
                const timeOutStatus = rec.time_out_status || '-';
                let timeOutBg = '#f3f4f6';
                let timeOutColor = '#6b7280';
                if(timeOutStatus === 'On-time' || timeOutStatus === 'Out') { timeOutBg = '#d1fae5'; timeOutColor = '#059669'; }
                else if(timeOutStatus === 'Undertime') { timeOutBg = '#fed7aa'; timeOutColor = '#ea580c'; }
                else if(timeOutStatus === 'Overtime') { timeOutBg = '#dbeafe'; timeOutColor = '#2563eb'; }
                
                tr.innerHTML = `
                    <td style="padding: 16px;"><strong>${rec.employee_id || ''}</strong></td>
                    <td style="padding: 16px;">${rec.name || ''}</td>
                    <td style="padding: 16px;">${rec.department || ''}</td>
                    <td style="padding: 16px;">${rec.date || ''}</td>
                    <td style="padding: 16px;">${timeIn}</td>
                    <td style="padding: 16px;"><span style="padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; background: ${timeInBg}; color: ${timeInColor};">${timeInStatus}</span></td>
                    <td style="padding: 16px;">${timeOut}</td>
                    <td style="padding: 16px;"><span style="padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; background: ${timeOutBg}; color: ${timeOutColor};">${timeOutStatus}</span></td>
                `;
                tbody.appendChild(tr);
            });
        }

        function exportCSV(){
            if(currentRecords.length === 0){ alert('No data to export'); return; }
            let csv = 'Employee ID,Name,Department,Date,Time In,Time In Status,Time Out,Time Out Status\n';
            currentRecords.forEach(rec=>{
                csv += `"${rec.employee_id || ''}","${rec.name || ''}","${rec.department || ''}","${rec.date || ''}","${rec.time_in || ''}","${rec.time_in_status || 'Absent'}","${rec.time_out || ''}","${rec.time_out_status || ''}"\n`;
            });
            downloadFile(csv, 'attendance_' + document.getElementById('date-filter').value + '.csv', 'text/csv');
        }

        function exportExcel(){
            if(currentRecords.length === 0){ alert('No data to export'); return; }
            let html = '<html><head><meta charset="utf-8"></head><body><table border="1">';
            html += '<tr><th>Employee ID</th><th>Name</th><th>Department</th><th>Date</th><th>Time In</th><th>Time In Status</th><th>Time Out</th><th>Time Out Status</th></tr>';
            currentRecords.forEach(rec=>{
                html += `<tr><td>${rec.employee_id || ''}</td><td>${rec.name || ''}</td><td>${rec.department || ''}</td><td>${rec.date || ''}</td><td>${rec.time_in || ''}</td><td>${rec.time_in_status || 'Absent'}</td><td>${rec.time_out || ''}</td><td>${rec.time_out_status || ''}</td></tr>`;
            });
            html += '</table></body></html>';
            downloadFile(html, 'attendance_' + document.getElementById('date-filter').value + '.xls', 'application/vnd.ms-excel');
        }

        function downloadFile(content, filename, mimeType){
            const blob = new Blob([content], {type: mimeType});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
        }

        // Event listeners
        document.getElementById('dept-filter').addEventListener('change', function(){
            fetchDashboard();
            loadRecords();
        });
        document.getElementById('date-filter').addEventListener('change', loadRecords);
        document.getElementById('search').addEventListener('input', loadRecords);
        document.getElementById('refresh-btn').addEventListener('click', function(){
            fetchDashboard();
            loadRecords();
        });
        document.getElementById('export-csv').addEventListener('click', exportCSV);
        document.getElementById('export-excel').addEventListener('click', exportExcel);

        // Status filter buttons
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function(){
                // Remove active class from all buttons
                document.querySelectorAll('.status-btn').forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                // Set filter and reload
                statusFilter = this.getAttribute('data-status');
                loadRecords();
            });
        });

        // Auto-refresh dashboard every 5 seconds
        setInterval(fetchDashboard, 5000);
        
        // Auto-refresh records every 10 seconds
        setInterval(loadRecords, 10000);
        
        // Initial load
        fetchDashboard();
        loadRecords();
    </script>
</body>
</html>
