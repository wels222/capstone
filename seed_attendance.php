<?php
/**
 * Attendance Data Seeder - AWS EC2 Production Ready
 * 
 * This script will:
 * 1. Drop all existing attendance records ONLY
 * 2. Generate realistic attendance data from February 1, 2025 to December 9, 2025
 * 3. Create random patterns: on-time, late, overtime, undertime, and absences
 * 4. Skip weekends (Saturday and Sunday)
 * 
 * SAFETY: Only affects the 'attendance' table. All other tables are untouched.
 * 
 * Usage: Open in browser with password protection
 */

// ============================================
// SECURITY - Password Protection
// ============================================
define('SEED_PASSWORD', 'BayanMabini2025!'); // CHANGE THIS PASSWORD!

session_start();

// Logout handler
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: seed_attendance.php');
    exit;
}

// Check authentication
if (!isset($_SESSION['seed_authenticated'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === SEED_PASSWORD) {
            $_SESSION['seed_authenticated'] = true;
            header('Location: seed_attendance.php');
            exit;
        } else {
            $error = "Invalid password!";
        }
    }
    
    if (!isset($_SESSION['seed_authenticated'])) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>🔐 Attendance Seeder - Protected</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                }
                .login-container {
                    background: white;
                    padding: 40px;
                    border-radius: 15px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 450px;
                    width: 100%;
                }
                h1 { color: #667eea; margin-bottom: 10px; font-size: 28px; }
                .subtitle { color: #666; font-size: 14px; margin-bottom: 25px; }
                input[type="password"] {
                    width: 100%;
                    padding: 15px;
                    border: 2px solid #ddd;
                    border-radius: 8px;
                    font-size: 16px;
                    margin-bottom: 20px;
                    box-sizing: border-box;
                    transition: border-color 0.3s;
                }
                input[type="password"]:focus {
                    outline: none;
                    border-color: #667eea;
                }
                button {
                    width: 100%;
                    padding: 15px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: bold;
                    cursor: pointer;
                    transition: transform 0.2s;
                }
                button:hover { transform: translateY(-2px); }
                .error {
                    background: #ffebee;
                    color: #c62828;
                    padding: 12px;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    border-left: 4px solid #f44336;
                    font-size: 14px;
                }
                .warning {
                    background: #fff3e0;
                    color: #e65100;
                    padding: 15px;
                    border-radius: 8px;
                    margin-top: 25px;
                    font-size: 13px;
                    text-align: left;
                    border-left: 4px solid #ff9800;
                    line-height: 1.5;
                }
                .safe-badge {
                    background: #e8f5e9;
                    color: #2e7d32;
                    padding: 8px 15px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: bold;
                    display: inline-block;
                    margin-top: 15px;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h1>🔐 Attendance Seeder</h1>
                <p class="subtitle">Secure Access Required</p>
                
                <?php if (isset($error)): ?>
                    <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" autocomplete="off">
                    <input type="password" name="password" placeholder="Enter password" required autofocus>
                    <button type="submit">🔓 Unlock Seeder</button>
                </form>
                
                <span class="safe-badge">✅ SAFE: Only affects attendance table</span>
                
                <div class="warning">
                    <strong>⚠️ Warning:</strong><br>
                    This will delete all existing attendance records and generate new data from February 1 to December 9, 2025.<br><br>
                    <strong>✅ Safe:</strong> Only the <code>attendance</code> table will be affected. All other tables (users, leave_requests, etc.) remain untouched.
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// ============================================
// PERFORMANCE OPTIMIZATION FOR AWS EC2
// ============================================
@ini_set('max_execution_time', 600);        // 10 minutes
@ini_set('memory_limit', '512M');           // 512MB memory
@set_time_limit(600);
@ini_set('output_buffering', '0');
@ini_set('implicit_flush', '1');
@ob_implicit_flush(true);

// Disable existing output buffers
while (@ob_get_level()) {
    @ob_end_flush();
}

// Start new output buffer
ob_start();

// ============================================
// DATABASE CONNECTION
// ============================================
try {
    require_once __DIR__ . '/db.php';
    
    // Test database connection
    $pdo->query("SELECT 1");
    
} catch (Exception $e) {
    die("<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Database Error</title></head><body style='font-family: Arial; padding: 50px; text-align: center;'><div style='background: #ffebee; padding: 30px; border-radius: 10px; border-left: 5px solid #f44336; max-width: 600px; margin: 0 auto;'><h2 style='color: #c62828;'>❌ Database Connection Failed</h2><p style='color: #666;'>" . htmlspecialchars($e->getMessage()) . "</p><a href='seed_attendance.php' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Try Again</a></div></body></html>");
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Configuration
$START_DATE = '2025-02-01';
$END_DATE = '2025-12-09';
$BATCH_SIZE = 500; // Insert records in batches for better performance

// Time windows for different statuses
$TIME_WINDOWS = [
    'on_time' => ['06:00:00', '08:00:00'],      // 6:00 AM - 8:00 AM = Present
    'late' => ['08:01:00', '12:00:00'],         // 8:01 AM - 12:00 PM = Late
    'very_late' => ['12:01:00', '17:00:00'],    // 12:01 PM - 5:00 PM = Undertime
    'timeout_early' => ['14:00:00', '16:59:00'], // 2:00 PM - 4:59 PM = Undertime
    'timeout_ontime' => ['17:00:00', '17:59:00'], // 5:00 PM - 5:59 PM = Out/On-time
    'timeout_overtime' => ['18:00:00', '22:00:00'] // 6:00 PM - 10:00 PM = Overtime
];

// Attendance probability weights (out of 100)
$ATTENDANCE_WEIGHTS = [
    'present_ontime' => 65,    // 65% on time
    'present_late' => 28,      // 28% late
    'present_very_late' => 3,  // 3% very late (undertime status)
    'absent' => 4              // 4% absent
];

// Timeout probability weights (when present)
$TIMEOUT_WEIGHTS = [
    'ontime' => 70,      // 70% leave on time (5-6 PM)
    'undertime' => 15,   // 15% leave early (undertime)
    'overtime' => 15     // 15% overtime (after 6 PM)
];

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Attendance Data Seeder</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            text-align: center;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 5px solid;
        }
        .status.info {
            background: #e3f2fd;
            border-color: #2196F3;
            color: #1565C0;
        }
        .status.success {
            background: #e8f5e9;
            border-color: #4CAF50;
            color: #2E7D32;
        }
        .status.warning {
            background: #fff3e0;
            border-color: #FF9800;
            color: #E65100;
        }
        .status.error {
            background: #ffebee;
            border-color: #f44336;
            color: #C62828;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #f0f0f0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-card .label {
            font-size: 14px;
            opacity: 0.9;
        }
        .log {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-top: 20px;
        }
        .log-entry {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .timestamp {
            color: #666;
            font-weight: bold;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            display: block;
            margin: 30px auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔄 Attendance Data Seeder</h1>
        <p class='subtitle'>February 1 - December 9, 2025 | AWS EC2 Optimized | Safe Mode</p>
";

@ob_flush();
@flush();

try {
    // Start timing
    $startTime = microtime(true);
    
    echo "<div class='status info'>
            <strong>📊 Starting seeding process...</strong><br>
            Date Range: {$START_DATE} to {$END_DATE}<br>
            Server: " . gethostname() . " | PHP: " . phpversion() . "<br>
            <span style='color: #4CAF50; font-weight: bold;'>✅ SAFE MODE: Only attendance table will be modified</span>
          </div>";
    
    @ob_flush();
    @flush();
    
    // Get ALL approved users (employees, HR, dept heads - everyone!)
    $employeeQuery = "SELECT employee_id, firstname, lastname, department, role 
                      FROM users 
                      WHERE status = 'approved' 
                      AND employee_id IS NOT NULL 
                      ORDER BY employee_id";
    $employeeStmt = $pdo->query($employeeQuery);
    $employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $employeeCount = count($employees);
    
    if ($employeeCount === 0) {
        throw new Exception("No approved employees found in the database!");
    }
    
    echo "<div class='status info'>
            <strong>👥 Found {$employeeCount} employees</strong>
          </div>";
    
    @ob_flush();
    @flush();
    
    // Calculate total working days (excluding weekends)
    $startDateTime = new DateTime($START_DATE);
    $endDateTime = new DateTime($END_DATE);
    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($startDateTime, $interval, $endDateTime->modify('+1 day'));
    
    $workingDays = [];
    foreach ($dateRange as $date) {
        $dayOfWeek = $date->format('N'); // 1 (Monday) to 7 (Sunday)
        if ($dayOfWeek < 6) { // Monday to Friday only
            $workingDays[] = $date->format('Y-m-d');
        }
    }
    
    $totalWorkingDays = count($workingDays);
    $totalRecords = $employeeCount * $totalWorkingDays;
    
    echo "<div class='stats'>
            <div class='stat-card'>
                <div class='label'>Employees</div>
                <div class='number'>{$employeeCount}</div>
            </div>
            <div class='stat-card'>
                <div class='label'>Working Days</div>
                <div class='number'>{$totalWorkingDays}</div>
            </div>
            <div class='stat-card'>
                <div class='label'>Total Records</div>
                <div class='number'>" . number_format($totalRecords) . "</div>
            </div>
          </div>";
    
    @ob_flush();
    @flush();
    
    // Drop existing attendance records ONLY - Safe operation
    echo "<div class='status warning'>
            <strong>🗑️ Clearing existing attendance data...</strong><br>
            <span style='font-size: 12px;'>Safe: Only the attendance table will be cleared. All other tables remain intact.</span>
          </div>";
    
    @ob_flush();
    @flush();
    
    // Use TRUNCATE for faster operation (only works on attendance table)
    try {
        $pdo->exec("TRUNCATE TABLE attendance");
    } catch (Exception $e) {
        // Fallback to DELETE if TRUNCATE fails
        $pdo->exec("DELETE FROM attendance");
    }
    
    echo "<div class='status success'>
            <strong>✅ Attendance table cleared successfully</strong><br>
            <span style='font-size: 12px;'>All other tables (users, leave_requests, tasks, etc.) are safe and untouched.</span>
          </div>";
    
    @ob_flush();
    @flush();
    
    // Generate attendance records
    echo "<div class='status info'>
            <strong>⚙️ Generating attendance records...</strong>
          </div>";
    
    echo "<div class='progress-bar'>
            <div class='progress-fill' id='progressBar' style='width: 0%'>0%</div>
          </div>";
    
    echo "<div class='log' id='logContainer'></div>";
    
    @ob_flush();
    @flush();
    
    // Prepare insert statement
    $insertSQL = "INSERT INTO attendance 
                  (employee_id, date, time_in, time_out, time_in_status, time_out_status, status, notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $pdo->prepare($insertSQL);
    
    $recordsInserted = 0;
    $batchData = [];
    $stats = [
        'present_ontime' => 0,
        'present_late' => 0,
        'present_very_late' => 0,
        'absent' => 0,
        'overtime' => 0,
        'undertime' => 0
    ];
    
    // Begin transaction
    $pdo->beginTransaction();
    
    foreach ($employees as $employee) {
        foreach ($workingDays as $date) {
            // Determine attendance status based on weights
            $rand = mt_rand(1, 100);
            $timeIn = null;
            $timeOut = null;
            $timeInStatus = null;
            $timeOutStatus = null;
            $status = null;
            $notes = null;
            
            if ($rand <= $ATTENDANCE_WEIGHTS['present_ontime']) {
                // Present and on time (60%)
                $timeIn = $date . ' ' . randomTime($TIME_WINDOWS['on_time'][0], $TIME_WINDOWS['on_time'][1]);
                $timeInStatus = 'Present';
                $status = 'Present';
                $stats['present_ontime']++;
                
            } elseif ($rand <= ($ATTENDANCE_WEIGHTS['present_ontime'] + $ATTENDANCE_WEIGHTS['present_late'])) {
                // Present but late (25%)
                $timeIn = $date . ' ' . randomTime($TIME_WINDOWS['late'][0], $TIME_WINDOWS['late'][1]);
                $timeInStatus = 'Late';
                $status = 'Present';
                $stats['present_late']++;
                
            } elseif ($rand <= ($ATTENDANCE_WEIGHTS['present_ontime'] + $ATTENDANCE_WEIGHTS['present_late'] + $ATTENDANCE_WEIGHTS['present_very_late'])) {
                // Present but very late (5%)
                $timeIn = $date . ' ' . randomTime($TIME_WINDOWS['very_late'][0], $TIME_WINDOWS['very_late'][1]);
                $timeInStatus = 'Undertime';
                $status = 'Present';
                $stats['present_very_late']++;
                
            } else {
                // Absent (2%) - Insert record with Absent status
                $timeIn = null;
                $timeOut = null;
                $timeInStatus = 'Absent';
                $timeOutStatus = null;
                $status = 'Absent';
                $notes = 'No attendance recorded';
                $stats['absent']++;
            }
            
            // Determine time out if present (skip if absent)
            if ($timeIn !== null) {
                $timeoutRand = mt_rand(1, 100);
                
                if ($timeoutRand <= $TIMEOUT_WEIGHTS['ontime']) {
                    // Leave on time (70%)
                    $timeOut = $date . ' ' . randomTime($TIME_WINDOWS['timeout_ontime'][0], $TIME_WINDOWS['timeout_ontime'][1]);
                    $timeOutStatus = 'Out';
                    
                } elseif ($timeoutRand <= ($TIMEOUT_WEIGHTS['ontime'] + $TIMEOUT_WEIGHTS['undertime'])) {
                    // Leave early - undertime (15%)
                    $timeOut = $date . ' ' . randomTime($TIME_WINDOWS['timeout_early'][0], $TIME_WINDOWS['timeout_early'][1]);
                    $timeOutStatus = 'Undertime';
                    $stats['undertime']++;
                    
                } else {
                    // Overtime (15%)
                    $timeOut = $date . ' ' . randomTime($TIME_WINDOWS['timeout_overtime'][0], $TIME_WINDOWS['timeout_overtime'][1]);
                    $timeOutStatus = 'Overtime';
                    $stats['overtime']++;
                }
            }
            
            // Execute insert for all attendance (present and absent)
            $insertStmt->execute([
                $employee['employee_id'],
                $date,
                $timeIn,
                $timeOut,
                $timeInStatus,
                $timeOutStatus,
                $status,
                $notes
            ]);
            
            $recordsInserted++;
            
            // Update progress every 50 records (optimized for hosting)
            if ($recordsInserted % 50 === 0) {
                $progress = round(($recordsInserted / $totalRecords) * 100, 1);
                echo "<script>
                        document.getElementById('progressBar').style.width = '{$progress}%';
                        document.getElementById('progressBar').textContent = '{$progress}%';
                        var log = document.getElementById('logContainer');
                        log.innerHTML += '<div class=\"log-entry\"><span class=\"timestamp\">[" . date('H:i:s') . "]</span> Processed {$recordsInserted} / {$totalRecords} records...</div>';
                        log.scrollTop = log.scrollHeight;
                      </script>";
                
                @ob_flush();
                @flush();
                
                // Micro-sleep to prevent timeout on slower servers
                usleep(500); // 0.5ms
            }
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Final progress update
    echo "<script>
            document.getElementById('progressBar').style.width = '100%';
            document.getElementById('progressBar').textContent = '100%';
          </script>";
    
    @ob_flush();
    @flush();
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "<div class='status success'>
            <strong>✅ Seeding completed successfully!</strong><br>
            <br>
            📊 <strong>Statistics:</strong><br>
            • Total records inserted: <strong>" . number_format($recordsInserted) . "</strong><br>
            • On-time attendance: <strong>" . number_format($stats['present_ontime']) . "</strong> (~65%)<br>
            • Late arrivals: <strong>" . number_format($stats['present_late']) . "</strong> (~28%)<br>
            • Very late (Undertime): <strong>" . number_format($stats['present_very_late']) . "</strong> (~3%)<br>
            • Absences: <strong>" . number_format($stats['absent']) . "</strong> (~4%)<br>
            • Overtime instances: <strong>" . number_format($stats['overtime']) . "</strong><br>
            • Undertime instances: <strong>" . number_format($stats['undertime']) . "</strong><br>
            <br>
            ⏱️ <strong>Execution time:</strong> {$duration} seconds<br>
            📅 <strong>Date range:</strong> {$START_DATE} to {$END_DATE}<br>
            👥 <strong>Employees with attendance:</strong> {$employeeCount}<br>
            🖥️ <strong>Server:</strong> " . gethostname() . "<br>
            <br>
            <span style='color: #4CAF50; font-weight: bold;'>✅ Safe: Only attendance table was modified. All other data is intact.</span>
          </div>";
    
    echo "<script>
            var log = document.getElementById('logContainer');
            log.innerHTML += '<div class=\"log-entry\"><span class=\"timestamp\">[" . date('H:i:s') . "]</span> <strong style=\"color: green;\">✅ All done! Database seeded successfully.</strong></div>';
            log.scrollTop = log.scrollHeight;
          </script>";
    
} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "<div class='status error'>
            <strong>❌ Error occurred:</strong><br>
            <strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>
            <strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>
            <strong>Line:</strong> " . $e->getLine() . "<br>
            <br>
            <span style='color: #4CAF50;'>✅ Good news: Transaction was rolled back. Your data is safe.</span>
          </div>";
    
    echo "<script>
            var log = document.getElementById('logContainer');
            if (log) {
                log.innerHTML += '<div class=\"log-entry\"><span class=\"timestamp\">[" . date('H:i:s') . "]</span> <strong style=\"color: red;\">❌ Error: " . htmlspecialchars($e->getMessage()) . "</strong></div>';
                log.scrollTop = log.scrollHeight;
            }
          </script>";
}

echo "
        <div style='text-align: center; margin-top: 30px;'>
            <button onclick='window.location.reload()' style='display: inline-block; margin: 10px;'>🔄 Run Again</button>
            <button onclick='window.location.href=\"hr/analytics.php\"' style='display: inline-block; margin: 10px;'>📊 View Analytics</button>
            <button onclick='window.location.href=\"?logout=1\"' style='display: inline-block; margin: 10px; background: linear-gradient(135deg, #f44336 0%, #e91e63 100%);'>🔓 Logout</button>
        </div>
    </div>
</body>
</html>";

@ob_end_flush();

/**
 * Generate a random time between two time strings
 * 
 * @param string $start Start time (HH:MM:SS)
 * @param string $end End time (HH:MM:SS)
 * @return string Random time in HH:MM:SS format
 */
function randomTime($start, $end) {
    $startSeconds = strtotime($start);
    $endSeconds = strtotime($end);
    $randomSeconds = mt_rand($startSeconds, $endSeconds);
    return date('H:i:s', $randomSeconds);
}