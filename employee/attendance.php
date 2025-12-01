<?php
require_once __DIR__ . '/../auth_guard.php';
// Allow access for Employee, HR, and Department Head roles
require_role(['employee','hr','department_head']);
require_once __DIR__ . '/../db.php';

$userId = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare('SELECT id, firstname, lastname, position, employee_id, email, profile_picture FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo "User not found.";
    exit();
}

if ((isset($_GET['qr']) && $_GET['qr']) || (!empty($_SESSION['qr_pending']))) {
    require_once __DIR__ . '/../attendance/qr_utils.php';
    $pending = $_GET['qr'] ?? $_SESSION['qr_pending'];
    if ($pending && qr_verify_token($pending, 0)) {
        // Record attendance for the current logged-in user
        $res = qr_record_attendance_for_user($pdo, $_SESSION['user_id']);
        // Clear any pending token stored in session
        unset($_SESSION['qr_pending']);

        // Determine base redirect target based on role/position (same logic as index.php)
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === 'superadmin') {
            $redirect = '../super_admin.php';
        } else {
            $sessRole = strtolower($_SESSION['role'] ?? $_SESSION['position'] ?? '');
            if ($sessRole === 'hr' || $sessRole === 'human resources') {
                $redirect = '../hr/dashboard.php';
            } elseif ($sessRole === 'department_head' || $sessRole === 'dept head' || $sessRole === 'dept_head') {
                $redirect = '../dept_head/dashboard.php';
            } elseif ($sessRole === 'employee') {
                $redirect = '../employee/dashboard.php';
            } else {
                $redirect = '../dashboard.php';
            }
        }

        // If attendance result exists, map it to query params similar to index.php
        if (!empty($res) && is_array($res)) {
            if (!empty($res['success'])) {
                $msg = ($res['action'] === 'time_in') ? 'timein_ok' : 'timeout_ok';
                $timeParam = isset($res['time']) ? '&att_time=' . urlencode($res['time']) : '';
                $statusParam = isset($res['status']) ? '&att_status=' . urlencode($res['status']) : '';
                header('Location: ' . $redirect . '?att=' . $msg . $timeParam . $statusParam);
                exit();
            } else {
                $lowerMsg = strtolower($res['message'] ?? '');
                if (strpos($lowerMsg, 'time out already') !== false || strpos($lowerMsg, 'time out already recorded') !== false) {
                    $timeParam = isset($res['time']) ? '&att_time=' . urlencode($res['time']) : '';
                    $statusParam = isset($res['status']) ? '&att_status=' . urlencode($res['status']) : '';
                    header('Location: ' . $redirect . '?att=already_timedout' . $timeParam . $statusParam);
                    exit();
                }
                header('Location: ' . $redirect . '?att=failed');
                exit();
            }
        }

        // Fallback: redirect to target dashboard without params
        header('Location: ' . $redirect);
        exit();
    } else {
        // Invalid/expired token - set a flag so UI can show feedback (consistent with index.php)
        $_SESSION['qr_pending_invalid'] = true;
        unset($_SESSION['qr_pending']);
        // Redirect back to login page to show invalid QR feedback
        header('Location: ../index.php');
        exit();
    }
}

// Determine identifier used in attendance.employee_id
$employeeId = $user['employee_id'] ?? null;

// If employee_id is missing, there's no attendance records tied; leave as null
$attendanceRows = [];
if ($employeeId) {
    $stmt = $pdo->prepare('SELECT id, employee_id, date, time_in, time_out, time_in_status, time_out_status, status, created_at FROM attendance WHERE employee_id = ? ORDER BY date ASC');
    $stmt->execute([$employeeId]);
    $attendanceRows = $stmt->fetchAll();
}

// Helper to format time and compute tardy/undertime
function fmtTime($dt) {
    if (!$dt) return null;
    try {
        $d = new DateTime($dt);
        return $d->format('h:i A');
    } catch (Exception $e) {
        return null;
    }
}

$records = [];
foreach ($attendanceRows as $r) {
    $timeInRaw = $r['time_in'];
    $timeOutRaw = $r['time_out'];
    $timeInFmt = fmtTime($timeInRaw);
    $timeOutFmt = fmtTime($timeOutRaw);

    // Get status from database
    $timeInStatus = $r['time_in_status'] ?? null;
    $timeOutStatus = $r['time_out_status'] ?? null;
    
    // determine unified display status prioritizing time-out status when available
    if ($timeInStatus === 'Absent' || !$timeInRaw) {
        $status = 'Absent';
    } elseif ($timeOutStatus === 'Undertime') {
        $status = 'Undertime';
    } elseif ($timeOutStatus === 'Overtime') {
        $status = 'Overtime';
    } elseif ($timeOutStatus === 'On-time' || $timeOutStatus === 'Out') {
        $status = 'Present';
    } elseif ($timeInStatus === 'Late') {
        $status = 'Late';
    } else {
        $status = 'Present';
    }

    // Set flags based on database status values
    $tardy = ($timeInStatus === 'Late');
    $undertime = ($timeOutStatus === 'Undertime');
    $overtime = ($timeOutStatus === 'Overtime');

    $records[] = [
        'id' => (int)$r['id'],
        'date' => $r['date'],
        'timeIn' => $timeInFmt,
        'timeOut' => $timeOutFmt,
        'timeInStatus' => $timeInStatus,
        'timeOutStatus' => $timeOutStatus,
        'status' => $status,
        'tardy' => $tardy,
        'undertime' => $undertime,
        'overtime' => $overtime,
    ];
}

// compute summary
$daysPresent = 0; $daysLate = 0; $daysAbsent = 0; $totalTardy = 0; $totalUndertime = 0; $totalOvertime = 0;
foreach ($records as $rec) {
    // Separate counting: Present = only "Present" status, Late = only "Late" status
    if ($rec['timeInStatus'] === 'Present') {
        $daysPresent++;
    } elseif ($rec['timeInStatus'] === 'Late') {
        $daysLate++;
    }
    // Absent = didn't time in (or timed in after 12:01 PM which is marked as Absent)
    if ($rec['timeInStatus'] === 'Absent' || !$rec['timeIn']) {
        $daysAbsent++;
    }
    
    if ($rec['tardy']) $totalTardy++;
    if ($rec['undertime']) $totalUndertime++;
    if ($rec['overtime']) $totalOvertime++;
}
$total = count($records);
$daysActive = $daysPresent + $daysLate; // Active = Present + Late (timed in before 12:01 PM)
$attendanceRate = $total ? round(($daysActive / $total) * 100) : 0;

// prepare payload for client
$payload = [
    'user' => [
        'name' => trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')),
        'position' => $user['position'] ?? '',
        'employee_id' => $employeeId,
        'email' => $user['email'] ?? ''
    ],
    'attendance' => $records,
    'summary' => [
        'daysPresent' => $daysPresent,
        'daysLate' => $daysLate,
        'daysActive' => $daysActive,
        'daysAbsent' => $daysAbsent,
        'totalTardy' => $totalTardy,
        'totalUndertime' => $totalUndertime,
        'totalOvertime' => $totalOvertime,
        'attendanceRate' => $attendanceRate
    ]
    ];

$profilePicture = $user['profile_picture'] ?? '';
?>
<?php
// Determine the correct home dashboard for the logged-in user based on role/position
$home_link = 'dashboard.php'; // default employee dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === 'superadmin') {
    $home_link = '../super_admin.html';
} else {
    $sessRole = strtolower($_SESSION['role'] ?? $_SESSION['position'] ?? '');
    if ($sessRole === 'hr' || $sessRole === 'human resources') {
        $home_link = '../hr/dashboard.php';
    } elseif ($sessRole === 'department_head' || $sessRole === 'dept head' || $sessRole === 'dept_head') {
        $home_link = '../dept_head/dashboard.php';
    } else {
        $home_link = 'dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>My Attendance</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    body { font-family: 'Inter', sans-serif; background:#f9fafb; margin:0; padding:0; }
    .modal-bg { background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); }
    .chart-container { position: relative; height: 220px; }
    .chart-container-small { position: relative; height: 180px; }
    .card-hover { transition: all 0.2s ease; }
    .card-hover:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
</style>
</head>
<body class="min-h-screen flex flex-col bg-gray-100 p-4 lg:p-10" data-user-id="<?= htmlspecialchars($userId, ENT_QUOTES) ?>">

<!-- Header: centered rounded card, not edge-to-edge -->
<header class="sticky top-0 z-50">
	<div class="max-w-7xl mx-auto">
		<div class="bg-white rounded-xl shadow-md px-4 py-3 flex items-center justify-between">
			<div class="flex items-center space-x-4">
				<div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center overflow-hidden">
					<img src="../assets/logo.png" alt="Logo" class="rounded-full w-full h-full object-cover">
				</div>
				<h1 class="text-xl font-bold text-gray-800">Dashboard</h1>
			</div>
			<div class="flex items-center space-x-4">

                <!-- Home button -->
                <a id="home-button" href="<?= htmlspecialchars($home_link, ENT_QUOTES) ?>" class="text-gray-600 hover:text-blue-600 transition-colors" aria-label="Home" title="Home">
					<i class="fas fa-home text-lg"></i>
				</a>

				<!-- Profile avatar + modal trigger -->
				<img id="profileIcon" src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/40x40/FF5733/FFFFFF?text=P'; ?>" alt="Profile" class="w-10 h-10 rounded-full cursor-pointer">
			</div>
		</div>
	</div>
</header>

<!-- Profile Modal -->
<div id="profileModal" class="fixed inset-0 hidden items-center justify-center modal-bg z-50">
	<div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-xs mx-4 flex flex-col items-center">
		<img id="profileModalPhoto" src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/80x80/FFD700/000000?text=W+P'; ?>" alt="Profile" class="w-20 h-20 rounded-full mb-4">
		<a href="dashboard.php" class="w-full px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100 mb-2 text-center">Go to Dashboard</a>
		<a href="logout.php" class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 mb-2 text-center">Log out</a>
		<button id="closeProfileModal" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
	</div>
</div>

<!-- Main content -->
<main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 space-y-5">
    <!-- Page Title Section -->
    <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Attendance Overview</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Employee ID: <span class="font-semibold text-gray-900"><?= htmlspecialchars($payload['user']['employee_id'] ?? '—') ?></span>
                    <span class="mx-2 text-gray-400">•</span>
                    <span class="text-gray-500"><?= date('F j, Y') ?></span>
                </p>
            </div>
            <a href="<?= htmlspecialchars($home_link, ENT_QUOTES) ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-all shadow-sm hover:shadow">
                <i class="fas fa-arrow-left text-xs"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </section>

    <!-- Summary Cards - 5 Cards: Green, Red, Yellow, Orange, Blue -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4" aria-label="Attendance summary">
        <!-- Days Present - GREEN -->
        <div class="card-hover bg-white rounded-xl p-5 border border-green-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-xl text-green-600"></i>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2.5 py-1 rounded-full">Active</span>
            </div>
            <p class="text-xs font-semibold text-green-700 uppercase tracking-wide">Present</p>
            <p class="text-3xl font-bold text-green-900 mt-1"><?= $payload['summary']['daysPresent'] ?></p>
        </div>
        
        <!-- Days Absent - RED -->
        <div class="card-hover bg-white rounded-xl p-5 border border-red-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-times-circle text-xl text-red-600"></i>
                </div>
                <span class="text-xs font-semibold text-red-600 bg-red-50 px-2.5 py-1 rounded-full">Tracked</span>
            </div>
            <p class="text-xs font-semibold text-red-700 uppercase tracking-wide">Absent</p>
            <p class="text-3xl font-bold text-red-900 mt-1"><?= $payload['summary']['daysAbsent'] ?></p>
        </div>
        
        <!-- Late/Tardy - YELLOW -->
        <div class="card-hover bg-white rounded-xl p-5 border border-yellow-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-xl text-yellow-600"></i>
                </div>
                <span class="text-xs font-semibold text-yellow-600 bg-yellow-50 px-2.5 py-1 rounded-full">Late</span>
            </div>
            <p class="text-xs font-semibold text-yellow-700 uppercase tracking-wide">Late</p>
            <p class="text-3xl font-bold text-yellow-900 mt-1"><?= $payload['summary']['daysLate'] ?></p>
        </div>
        
        <!-- Undertime - ORANGE -->
        <div class="card-hover bg-white rounded-xl p-5 border border-orange-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-clock text-xl text-orange-600"></i>
                </div>
                <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2.5 py-1 rounded-full">Early</span>
            </div>
            <p class="text-xs font-semibold text-orange-700 uppercase tracking-wide">Undertime</p>
            <p class="text-3xl font-bold text-orange-900 mt-1"><?= $payload['summary']['totalUndertime'] ?></p>
        </div>

        <!-- Overtime - BLUE -->
        <div class="card-hover bg-white rounded-xl p-5 border border-blue-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-business-time text-xl text-blue-600"></i>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full">Extra</span>
            </div>
            <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Overtime</p>
            <p class="text-3xl font-bold text-blue-900 mt-1"><?= $payload['summary']['totalOvertime'] ?></p>
        </div>
    </section>

    <!-- Comprehensive Analytics Section -->
    <section class="space-y-4" aria-label="Attendance analytics">
        <!-- Row 1: All Categories Doughnut + Trend Line Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Overall Attendance Percentage -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900">Overall Attendance Rate</h3>
                    <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-percentage text-purple-600 text-sm"></i>
                    </div>
                </div>
                <div class="flex flex-col items-center justify-center" style="height: 220px;">
                    <div class="text-center">
                        <p class="text-7xl font-bold text-purple-600" id="overallRate"><?= $payload['summary']['attendanceRate'] ?>%</p>
                        <p class="text-sm text-gray-500 mt-2">Attendance Rate</p>
                        <div class="mt-4 space-y-1">
                            <p class="text-xs text-gray-600">
                                <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                                Present: <span class="font-semibold"><?= $payload['summary']['daysPresent'] ?></span>
                            </p>
                            <p class="text-xs text-gray-600">
                                <span class="inline-block w-2 h-2 rounded-full bg-yellow-500 mr-1"></span>
                                Late: <span class="font-semibold"><?= $payload['summary']['daysLate'] ?></span>
                            </p>
                            <p class="text-xs text-gray-600">
                                <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-1"></span>
                                Absent: <span class="font-semibold"><?= $payload['summary']['daysAbsent'] ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Trend Line Chart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900">Attendance Trend</h3>
                    <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-indigo-600 text-sm"></i>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="trendChart" aria-label="Monthly attendance trend" role="img"></canvas>
                </div>
            </div>
        </div>

        <!-- Row 3: Statistics Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-blue-700 uppercase">Total Days</span>
                    <i class="fas fa-calendar text-blue-600"></i>
                </div>
                <p class="text-2xl font-bold text-blue-900"><?= $total ?></p>
                <p class="text-xs text-blue-600 mt-1">Recorded attendance</p>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-green-700 uppercase">On-Time Days</span>
                    <i class="fas fa-check-double text-green-600"></i>
                </div>
                <p class="text-2xl font-bold text-green-900"><?= max(0, $payload['summary']['daysPresent'] - $payload['summary']['totalTardy']) ?></p>
                <p class="text-xs text-green-600 mt-1">Perfect attendance</p>
            </div>

            <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-4 border border-amber-200">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-amber-700 uppercase">Punctuality Rate</span>
                    <i class="fas fa-star text-amber-600"></i>
                </div>
                <p class="text-2xl font-bold text-amber-900">
                    <?= $payload['summary']['daysPresent'] > 0 ? round((($payload['summary']['daysPresent'] - $payload['summary']['totalTardy']) / $payload['summary']['daysPresent']) * 100) : 0 ?>%
                </p>
                <p class="text-xs text-amber-600 mt-1">On-time arrivals</p>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-purple-700 uppercase">Avg. Work Hours</span>
                    <i class="fas fa-clock text-purple-600"></i>
                </div>
                <p class="text-2xl font-bold text-purple-900">8.0h</p>
                <p class="text-xs text-purple-600 mt-1">Daily average</p>
            </div>
        </div>
    </section>

    <!-- Daily Records Table -->
    <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden card-hover" aria-label="Daily attendance records">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Daily Records</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time In</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time In Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time Out</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Time Out Status</th>
                        <th scope="col" class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody id="attendance-table-body" class="bg-white divide-y divide-gray-200">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- Details modal -->
<div id="attendance-details-modal" class="fixed inset-0 hidden items-center justify-center modal-bg z-50" role="dialog" aria-labelledby="modal-title" aria-modal="true">
    <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h4 id="modal-title" class="text-lg font-semibold text-gray-800">Attendance Details</h4>
            <button id="close-details" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" aria-label="Close modal">&times;</button>
        </div>
        <div id="attendance-details-content" class="space-y-3"></div>
    </div>
</div>

<script>
    window.SERVER_ATTENDANCE = <?= json_encode($payload, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Notifications
    const bell = document.getElementById('notification-bell');
    const badge = document.getElementById('notification-badge');
    const dropdown = document.getElementById('notification-dropdown');

    function renderNotifications(notifs) {
        const nl = document.getElementById('notification-list');
        nl.innerHTML = '';
        if (!notifs || notifs.length === 0) {
            nl.innerHTML = '<div class="text-gray-500 p-2">No notifications.</div>';
            if (badge) badge.style.display = 'none';
            return;
        }
        const hasUnread = notifs.some(n => !n.read);
        if (badge) badge.style.display = hasUnread ? 'block' : 'none';

        notifs.forEach(n => {
            const card = document.createElement('div');
            card.className = 'bg-yellow-50 border border-yellow-200 rounded p-3 shadow-sm';
            const readClass = n.read ? 'opacity-70' : 'opacity-100';
            card.innerHTML = `
                <div class="flex justify-between ${readClass}">
                    <div>
                        <div class="font-semibold text-sm text-yellow-800">Notification:</div>
                        <div class="text-sm text-gray-700">${n.message}</div>
                    </div>
                    <div class="text-xs text-gray-500 ml-4">${n.created_at}</div>
                </div>
            `;
            nl.appendChild(card);
        });
    }

    function loadNotifications() {
        fetch('notifications.php')
           .then(r => r.json())
           .then(data => renderNotifications((data.success && Array.isArray(data.data)) ? data.data : []))
           .catch(() => {
               const nl = document.getElementById('notification-list');
               nl.innerHTML = '<div class="text-gray-500 p-2">Failed to load notifications.</div>';
           });
    }

    bell?.addEventListener('click', () => {
        dropdown.classList.toggle('hidden');
        if (!dropdown.classList.contains('hidden')) loadNotifications();
    });

    document.addEventListener('click', (e) => {
        if (!bell?.contains(e.target) && !dropdown?.contains(e.target)) {
            dropdown?.classList.add('hidden');
        }
    });

    const markAllBtn = document.getElementById('markAllReadBtn');
    const clearBtn = document.getElementById('clearNotifBtn');
    markAllBtn?.addEventListener('click', () => {
        fetch('notifications.php?action=mark_all_read', { method: 'POST' }).then(loadNotifications);
    });
    clearBtn?.addEventListener('click', () => {
        if (!confirm('Clear all notifications?')) return;
        fetch('notifications.php?action=clear_all', { method: 'POST' }).then(loadNotifications);
    });

    // Profile Modal
    const profileIcon = document.getElementById('profileIcon');
    const profileModal = document.getElementById('profileModal');
    const closeProfileModal = document.getElementById('closeProfileModal');

    profileIcon?.addEventListener('click', () => {
        profileModal.classList.remove('hidden');
        profileModal.classList.add('flex');
    });
    closeProfileModal?.addEventListener('click', () => {
        profileModal.classList.add('hidden');
        profileModal.classList.remove('flex');
    });
    profileModal?.addEventListener('click', (e) => {
        if (e.target === profileModal) {
            profileModal.classList.add('hidden');
            profileModal.classList.remove('flex');
        }
    });

    const data = window.SERVER_ATTENDANCE || { user: {}, attendance: [], summary: {} };
    const attendanceRecords = data.attendance || [];
    const summary = data.summary || {};

    // Render daily records table
    function renderDailyRecords() {
        const tbody = document.getElementById('attendance-table-body');
        tbody.innerHTML = '';
        if (attendanceRecords.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No attendance records found.</td></tr>';
            return;
        }
        attendanceRecords.slice().reverse().forEach(rec => {
            // Time In Status Badge
            let timeInStatusBadge = '';
            if (rec.timeIn) {
                const timeInStatus = rec.timeInStatus || (rec.tardy ? 'Late' : 'Present');
                if (timeInStatus === 'Late') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 px-2 py-0.5"><i class="fas fa-clock mr-1"></i>Late</span>';
                } else if (timeInStatus === 'Present') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-green-100 text-green-800 px-2 py-0.5"><i class="fas fa-check mr-1"></i>Present</span>';
                } else if (timeInStatus === 'Undertime') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-amber-100 text-amber-800 px-2 py-0.5"><i class="fas fa-hourglass-half mr-1"></i>Undertime</span>';
                } else if (timeInStatus === 'Absent') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-red-100 text-red-800 px-2 py-0.5"><i class="fas fa-times mr-1"></i>Absent</span>';
                } else {
                    timeInStatusBadge = '<span class="text-gray-400">—</span>';
                }
            } else {
                timeInStatusBadge = '<span class="text-gray-400">—</span>';
            }

            // Time Out Status Badge
            let timeOutStatusBadge = '';
            if (rec.timeOut) {
                const timeOutStatus = rec.timeOutStatus || (rec.undertime ? 'Undertime' : (rec.overtime ? 'Overtime' : 'Out'));
                if (timeOutStatus === 'Undertime') {
                    timeOutStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-orange-100 text-orange-800 px-2 py-0.5"><i class="fas fa-user-clock mr-1"></i>Undertime</span>';
                } else if (timeOutStatus === 'Overtime') {
                    timeOutStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-blue-100 text-blue-800 px-2 py-0.5"><i class="fas fa-business-time mr-1"></i>Overtime</span>';
                } else if (timeOutStatus === 'On-time' || timeOutStatus === 'Out') {
                    timeOutStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-green-100 text-green-800 px-2 py-0.5"><i class="fas fa-check mr-1"></i>Out</span>';
                } else {
                    timeOutStatusBadge = '<span class="text-gray-400">—</span>';
                }
            } else {
                timeOutStatusBadge = '<span class="text-gray-400">—</span>';
            }

            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 transition';
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${rec.date}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${rec.timeIn || '<span class="text-gray-400">—</span>'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${timeInStatusBadge}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${rec.timeOut || '<span class="text-gray-400">—</span>'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">${timeOutStatusBadge}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                    <button data-id="${rec.id}" class="view-details-btn text-blue-600 hover:text-blue-800 font-medium">Details</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    // Render all charts
    let trendChart = null;
    
    function renderCharts() {
        // Attendance Trend Line Chart (Last 30 days or available data)
        const ctx4 = document.getElementById('trendChart').getContext('2d');
        
        // Prepare trend data (last 30 days)
        const trendData = [];
        const trendLabels = [];
        const sortedRecords = [...attendanceRecords].sort((a, b) => new Date(a.date) - new Date(b.date));
        const last30 = sortedRecords.slice(-30);
        
        let cumulativeActive = 0; // Active = Present + Late
        last30.forEach((rec, idx) => {
            // Count as active if time_in_status is Present or Late
            if (rec.timeInStatus === 'Present' || rec.timeInStatus === 'Late') {
                cumulativeActive++;
            }
            trendLabels.push(new Date(rec.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            trendData.push(Math.round((cumulativeActive / (idx + 1)) * 100));
        });

        if (trendChart) trendChart.destroy();
        trendChart = new Chart(ctx4, {
            type: 'line',
            data: {
                labels: trendLabels.length > 0 ? trendLabels : ['No Data'],
                datasets: [{ 
                    label: 'Attendance Rate (%)',
                    data: trendData.length > 0 ? trendData : [0],
                    borderColor: '#6366F1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        max: 100,
                        ticks: { 
                            callback: (value) => value + '%',
                            font: { size: 10 }
                        } 
                    },
                    x: { 
                        ticks: { font: { size: 9 }, maxRotation: 45, minRotation: 45 },
                        grid: { display: false }
                    }
                }, 
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => `Rate: ${ctx.parsed.y}%`
                        }
                    }
                } 
            }
        });
    }

    // Details modal
    const detailsModal = document.getElementById('attendance-details-modal');
    const detailsContent = document.getElementById('attendance-details-content');
    document.body.addEventListener('click', (e) => {
        if (e.target.classList.contains('view-details-btn')) {
            const id = parseInt(e.target.dataset.id, 10);
            const rec = attendanceRecords.find(r => r.id === id);
            if (!rec) return;
            // Build status badges (same mapping as table rows)
            let timeInStatusBadge = '<span class="text-gray-400">—</span>';
            if (rec.timeIn) {
                const timeInStatus = rec.timeInStatus || (rec.tardy ? 'Late' : 'Present');
                if (timeInStatus === 'Late') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 px-2 py-0.5"><i class="fas fa-clock mr-1"></i>Late</span>';
                } else if (timeInStatus === 'Present') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-green-100 text-green-800 px-2 py-0.5"><i class="fas fa-check mr-1"></i>Present</span>';
                } else if (timeInStatus === 'Undertime') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-amber-100 text-amber-800 px-2 py-0.5"><i class="fas fa-hourglass-half mr-1"></i>Undertime</span>';
                } else if (timeInStatus === 'Absent') {
                    timeInStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-red-100 text-red-800 px-2 py-0.5"><i class="fas fa-times mr-1"></i>Absent</span>';
                }
            }

            let timeOutStatusBadge = '<span class="text-gray-400">—</span>';
            if (rec.timeOut) {
                const timeOutStatus = rec.timeOutStatus || (rec.undertime ? 'Undertime' : (rec.overtime ? 'Overtime' : 'Out'));
                if (timeOutStatus === 'Undertime') {
                    timeOutStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-orange-100 text-orange-800 px-2 py-0.5"><i class="fas fa-user-clock mr-1"></i>Undertime</span>';
                } else if (timeOutStatus === 'Overtime') {
                    timeOutStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-blue-100 text-blue-800 px-2 py-0.5"><i class="fas fa-business-time mr-1"></i>Overtime</span>';
                } else if (timeOutStatus === 'On-time' || timeOutStatus === 'Out') {
                    timeOutStatusBadge = '<span class="inline-flex items-center text-xs font-medium rounded-full bg-green-100 text-green-800 px-2 py-0.5"><i class="fas fa-check mr-1"></i>Out</span>';
                }
            }
            detailsContent.innerHTML = `
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-gray-500">Date</p>
                        <p class="text-sm font-medium text-gray-900">${rec.date}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Time In</p>
                        <p class="text-sm font-medium text-gray-900">${rec.timeIn || '—'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Time Out</p>
                        <p class="text-sm font-medium text-gray-900">${rec.timeOut || '—'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Time In Status</p>
                        <p class="text-sm font-medium text-gray-900">${timeInStatusBadge}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Time Out Status</p>
                        <p class="text-sm font-medium text-gray-900">${timeOutStatusBadge}</p>
                    </div>
                </div>
                ${rec.tardy || rec.undertime || rec.overtime ? `<div class="mt-3 pt-3 border-t border-gray-200">
                    <p class="text-xs text-gray-500 mb-2">Flags</p>
                    <div class="flex gap-2 flex-wrap">
                        ${rec.tardy ? '<span class="inline-flex items-center text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 px-2 py-0.5"><i class="fas fa-clock mr-1"></i>Late</span>' : ''}
                        ${rec.undertime ? '<span class="inline-flex items-center text-xs font-medium rounded-full bg-orange-100 text-orange-800 px-2 py-0.5"><i class="fas fa-user-clock mr-1"></i>Undertime</span>' : ''}
                        ${rec.overtime ? '<span class="inline-flex items-center text-xs font-medium rounded-full bg-blue-100 text-blue-800 px-2 py-0.5"><i class="fas fa-business-time mr-1"></i>Overtime</span>' : ''}
                    </div>
                </div>` : ''}
            `;
            detailsModal.classList.remove('hidden');
        }
        if (e.target.id === 'close-details') {
            detailsModal.classList.add('hidden');
        }
    });

    // Close modal on outside click
    detailsModal.addEventListener('click', (e) => {
        if (e.target === detailsModal) detailsModal.classList.add('hidden');
    });

    // Initial render
    renderDailyRecords();
    renderCharts();
});
</script>

</body>
</html>
