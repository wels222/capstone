<?php
// dashboard.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
require_once '../db.php';
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT firstname, lastname, mi, position, profile_picture FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    $fullName = $user['firstname'] . ' ' . ($user['mi'] ? $user['mi'] . '. ' : '') . $user['lastname'];
    $position = $user['position'];
    $profilePicture = $user['profile_picture'] ?? '';
} else {
    $fullName = 'Employee';
    $position = '';
    $profilePicture = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .container {
            display: block;
        }
        .modal-bg {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }
        /* To-dos analytics sticky header and layout tweaks */
        #todos-analytics { position: sticky; top: 1.25rem; align-self: start; }
        #todos-analytics .overview-header { position: sticky; top: 0.75rem; z-index: 20; background: transparent; padding-top: 0.25rem; }
        #todos-list { max-height: 36rem; overflow-y: auto; padding-right: 0.5rem; }

        /* Keep analytics panel in normal flow; on larger screens only adjust list height.
           Avoid position:fixed to preserve original layout. */
        #todos-last-updated { display: inline-block; width: 7.5rem; text-align: right; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, 'Roboto Mono', 'Courier New', monospace; color: #9CA3AF; }
        @media (min-width: 768px) {
            /* Slightly increase list height on larger viewports but keep the analytics panel in-flow */
            #todos-list { max-height: calc(100vh - 14rem); }
        }
    </style>
</head>
<body class="bg-gray-100 p-6 lg:p-10">

    <header class="bg-white rounded-xl shadow-md p-4 flex items-center justify-between z-50 sticky top-0">
        <div class="flex items-center space-x-4">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <img src="../assets/logo.png" alt="Logo" class="rounded-full">
            </div>
            <h1 id="header-title" class="text-xl font-bold text-gray-800">Dashboard</h1>
        </div>
        <div class="flex items-center space-x-4">
            <div id="notification-bell-container" class="relative">
                <button id="notification-bell" class="text-gray-600 hover:text-blue-600 transition-colors relative">
                    <i class="fas fa-bell text-lg"></i>
                    <span id="notification-badge" style="display:none;" class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full px-2 py-0.5 text-xs font-bold">!</span>
                </button>
                <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-96 bg-white border border-gray-200 rounded shadow-lg z-50">
                    <div class="flex items-center justify-between px-4 py-2 border-b">
                        <strong class="text-sm">Notifications</strong>
                        <div class="flex items-center gap-2">
                            <button id="markAllReadBtn" class="text-xs text-blue-600 hover:underline">Mark all read</button>
                            <button id="clearNotifBtn" class="text-xs text-red-600 hover:underline">Clear</button>
                        </div>
                    </div>
                    <div id="notification-list" class="p-3 space-y-3 max-h-80 overflow-y-auto"></div>
                    <div class="px-4 py-2 border-t text-center text-xs text-gray-500">Showing latest notifications</div>
                </div>
            </div>
            <img id="profileIcon" src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/40x40/FF5733/FFFFFF?text=P'; ?>" alt="Profile" class="w-10 h-10 rounded-full cursor-pointer">
            <!-- Profile Modal -->
            <div id="profileModal" class="fixed inset-0 hidden items-center justify-center modal-bg z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-xs mx-4 flex flex-col items-center">
                    <img id="profileModalPhoto" src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/80x80/FFD700/000000?text=W+P'; ?>" alt="Profile" class="w-20 h-20 rounded-full mb-4">
                    <button id="logoutBtn" class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 mb-2">Log out</button>
                    <button id="closeProfileModal" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow p-4 overflow-y-auto mt-6">
        <div id="dashboard-page" class="container">
            <div class="bg-blue-600 w-full rounded-xl shadow-lg p-6 flex flex-col items-start text-white relative overflow-hidden mb-6">
                <div class="absolute inset-0 bg-blue-700 bg-opacity-20 backdrop-blur-sm z-10"></div>
                <div class="relative z-20 flex items-center space-x-6">
                    <img id="profilePicture" src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/80x80/FFD700/000000?text=W+P'; ?>" alt="Profile" class="w-20 h-20 rounded-full border-4 border-white">
                    <div class="flex flex-col text-left">
                        <h2 id="profileName" class="text-3xl font-extrabold tracking-tight"><?php echo htmlspecialchars($fullName); ?></h2>
                        <p id="profilePosition" class="text-sm opacity-80 mt-1"><?php echo htmlspecialchars($position); ?></p>
                    </div>
                    <button id="editProfileBtn" class="bg-white text-blue-600 text-sm px-3 py-1 rounded-full hover:bg-gray-100 transition-colors">
                        Edit Profile
                    </button>
                </div>
            </div>

            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="flex flex-wrap justify-around gap-4 text-center">
                        <a href="apply_leave.html" class="flex flex-col items-center space-y-2 cursor-pointer">
                            <div class="bg-blue-100 p-4 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M17 17h.01" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600">Apply for Leave</span>
                        </a>
                        <a href="leave_status.php" class="flex flex-col items-center space-y-2 cursor-pointer">
                            <div class="bg-purple-100 p-4 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600">Leave Status</span>
                        </a>
                        <a href="task.html" class="flex flex-col items-center space-y-2 cursor-pointer">
                            <div class="bg-green-100 p-4 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600">Task</span>
                        </a>
                        <a href="../attendance/my_qr.php" class="flex flex-col items-center space-y-2 cursor-pointer">
                            <div class="bg-indigo-100 p-4 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600">My QR Code</span>
                        </a>
                        <a href="view_attendance.html" class="flex flex-col items-center space-y-2 cursor-pointer">
                            <div class="bg-yellow-100 p-4 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600">View Attendance</span>
                        </a>
                        <a href="events.html" class="flex flex-col items-center space-y-2 cursor-pointer">
                            <div class="bg-teal-100 p-4 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h.01M7 11h.01M16 11h.01m-2-2h.01m-6-4h.01M8 21h8a2 2 0 002-2V7a2 2 0 00-2-2H8a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600">Events</span>
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Leave Credits</h3>
                    <!-- Scrollable grid: show roughly 3 columns x 2 rows on md+ and allow vertical scroll -->
                    <div class="relative">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-h-[28rem] md:max-h-[20rem] overflow-y-auto pr-2">
                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">Vacation Leave</span>
                                    <span class="text-sm text-gray-500">15 days</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 15 days</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>

                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">Mandatory / Forced Leave</span>
                                    <span class="text-sm text-gray-500">5 days</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 5 days</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>

                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">Sick Leave</span>
                                    <span class="text-sm text-gray-500">15 days</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 15 days</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>

                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">Maternity Leave</span>
                                    <span class="text-sm text-gray-500">105 days</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 105 days</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>

                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">Paternity Leave</span>
                                    <span class="text-sm text-gray-500">7 days</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 7 days</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>

                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">Special Privilege Leave</span>
                                    <span class="text-sm text-gray-500">3 days</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 3 days</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>

                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">Solo Parent Leave</span>
                                    <span class="text-sm text-gray-500">7 days</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 7 days</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>

                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">Study Leave</span>
                                    <span class="text-sm text-gray-500">6 months</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 6 months</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>

                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">10-Day VAWC Leave</span>
                                    <span class="text-sm text-gray-500">10 days</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 10 days</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>

                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">Rehabilitation Leave</span>
                                    <span class="text-sm text-gray-500">6 months</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 6 months</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>

                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">Special Leave Benefits for Women</span>
                                    <span class="text-sm text-gray-500">60 days</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 60 days</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>

                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">Special Emergency (Calamity) Leave</span>
                                    <span class="text-sm text-gray-500">5 days</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 5 days</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>

                            <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center justify-between w-full">
                                    <span class="font-medium text-gray-700">Adoption Leave</span>
                                    <span class="text-sm text-gray-500">60 days</span>
                                </div>
                                <p class="text-sm text-gray-500">Available - 60 days</p>
                                <p class="text-sm text-gray-500">Used - 00</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-2">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">To-dos</h3>
                        <div class="flex space-x-2 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 100-2 1 1 0 000 2zm0 7a1 1 0 100-2 1 1 0 000 2zm0 7a1 1 0 100-2 1 1 0 000 2z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    <div id="todos-grid" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <div id="todos-list" class="space-y-3">
                                <!-- Real-time tasks will be injected here as professional cards -->
                            </div>
                        </div>
                        <div id="todos-analytics" class="md:col-span-1 bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center justify-between mb-3 overview-header">
                                <h4 class="text-sm font-semibold text-gray-700">Tasks Overview</h4>
                                <span id="todos-last-updated" class="text-xs text-gray-400">--</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <div class="p-2 bg-white rounded shadow-sm text-center">
                                    <div class="text-xs text-gray-500">Pending</div>
                                    <div id="count-pending" class="text-xl font-bold text-yellow-600">0</div>
                                </div>
                                <div class="p-2 bg-white rounded shadow-sm text-center">
                                    <div class="text-xs text-gray-500">In Progress</div>
                                    <div id="count-inprogress" class="text-xl font-bold text-blue-600">0</div>
                                </div>
                                <div class="p-2 bg-white rounded shadow-sm text-center">
                                    <div class="text-xs text-gray-500">Completed</div>
                                    <div id="count-completed" class="text-xl font-bold text-green-600">0</div>
                                </div>
                                <div class="p-2 bg-white rounded shadow-sm text-center">
                                    <div class="text-xs text-gray-500">Missed</div>
                                    <div id="count-missed" class="text-xl font-bold text-red-600">0</div>
                                </div>
                            </div>
                            <div class="bg-white p-3 rounded shadow-sm">
                                <canvas id="todosChart" width="200" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Performance</h3>
                        <div class="text-sm text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 100-2 1 1 0 000 2zm0 7a1 1 0 100-2 1 1 0 000 2zm0 7a1 1 0 100-2 1 1 0 000 2z" />
                            </svg>
                        </div>
                    </div>
                    <canvas id="performanceChart"></canvas>
                    <div class="flex justify-between text-sm mt-4">
                        <span>Low performance in April</span>
                        <span class="text-blue-600">Details</span>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-2">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Attendance Comparison Chart</h3>
                        <div class="flex items-center space-x-4 text-sm text-gray-600">
                            <label class="flex items-center space-x-1">
                                <input type="radio" name="chart-period" value="daily" checked>
                                <span>Daily</span>
                            </label>
                            <label class="flex items-center space-x-1">
                                <input type="radio" name="chart-period" value="weekly">
                                <span>Week</span>
                            </label>
                            <label class="flex items-center space-x-1">
                                <input type="radio" name="chart-period" value="monthly">
                                <span>Month</span>
                            </label>
                        </div>
                    </div>
                    <canvas id="attendanceChart"></canvas>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-2">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Announcement(s)</h3>
                        <div class="flex space-x-2 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 100-2 1 1 0 000 2zm0 7a1 1 0 100-2 1 1 0 000 2zm0 7a1 1 0 100-2 1 1 0 000 2z" />
                            </svg>
                        </div>
                    </div>
                        <ul id="events-list">
                            <!-- Events will be loaded here -->
                        </ul>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 flex flex-col items-center justify-center">
                    <div class="w-24 h-24 rounded-full bg-green-100 flex items-center justify-center font-bold text-green-600 text-lg">
                        <span>90%</span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mt-4">Excellent</h3>
                    <p class="text-sm text-gray-500 mt-1 text-center">Score better than last month</p>
                    <div class="bg-blue-600 rounded-full px-4 py-1 text-white text-xs mt-4">Details</div>
                </div>
            </div>
        </div>

        <div id="editProfileModal" class="fixed inset-0 hidden items-center justify-center modal-bg">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm mx-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Edit Profile</h3>
                    <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>
                <form id="editProfileForm">
                    <div class="mb-4 text-center">
                        <img id="profilePhotoPreview" src="<?php echo $profilePicture ? htmlspecialchars($profilePicture) : 'https://placehold.co/80x80/FFD700/000000?text=W+P'; ?>" alt="Profile" class="w-24 h-24 rounded-full mx-auto mb-2 border-4 border-gray-200">
                        <label class="cursor-pointer bg-gray-200 px-3 py-1 text-sm rounded-full hover:bg-gray-300">
                            Change Photo
                            <input type="file" id="photoInput" class="hidden" accept="image/*">
                        </label>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancelBtn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </main>
        <script>
        // Realtime To-dos: professional UI + analytics (counts + donut) polling tasks assigned to the logged-in user
        (function(){
            const listEl = document.getElementById('todos-list');
            const lastUpdatedEl = document.getElementById('todos-last-updated');
            const countPendingEl = document.getElementById('count-pending');
            const countInprogressEl = document.getElementById('count-inprogress');
            const countCompletedEl = document.getElementById('count-completed');
            const countMissedEl = document.getElementById('count-missed');
            const chartEl = document.getElementById('todosChart');
            if (!listEl || !chartEl) return;

            let todosChart = null;
            function initChart(){
                try{
                    const ctx = chartEl.getContext('2d');
                    todosChart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Pending','In Progress','Completed','Missed'],
                            datasets: [{
                                data: [0,0,0,0],
                                backgroundColor: ['#f59e0b','#3b82f6','#10b981','#ef4444']
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth:12 } } } }
                    });
                }catch(e){ console.warn('Chart init failed', e); }
            }

            function formatDue(d){ if(!d) return ''; try{ const dt = new Date(d.replace(' ', 'T')); return dt.toLocaleString(); }catch(e){ return d; } }

            function renderList(tasks){
                const filtered = tasks.filter(t => t.status === 'pending' || t.status === 'in_progress');
                listEl.innerHTML = '';
                if (filtered.length === 0) {
                    listEl.innerHTML = '<div class="py-6 text-center text-sm text-gray-500">No pending tasks</div>';
                    return;
                }

                filtered.forEach(t => {
                    const card = document.createElement('div');
                    card.className = 'bg-white p-3 rounded-lg shadow-sm flex items-start justify-between gap-4 hover:shadow-md transition';

                    const left = document.createElement('div');
                    left.className = 'flex-1 min-w-0';
                    const title = document.createElement('div');
                    title.className = 'font-semibold text-gray-800 truncate';
                    title.textContent = t.title || '(untitled)';
                    const meta = document.createElement('div');
                    meta.className = 'text-xs text-gray-500 mt-1';
                    meta.textContent = (t.assigned_by_email?('Assigned by '+t.assigned_by_email):'') + (t.due_date?(' • due '+ formatDue(t.due_date)): '');
                    left.appendChild(title);
                    left.appendChild(meta);

                    const right = document.createElement('div');
                    right.className = 'flex flex-col items-end gap-2';
                    const badge = document.createElement('div');
                    badge.className = 'text-xs px-2 py-1 rounded-full font-semibold ' + (t.status==='pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800');
                    badge.textContent = t.status === 'pending' ? 'Pending' : 'In Progress';
                    const view = document.createElement('a');
                    view.className = 'text-xs text-gray-400 hover:text-blue-600';
                    view.href = '/capstone/employee/task.html';
                    view.textContent = 'View';
                    right.appendChild(badge);
                    right.appendChild(view);

                    card.appendChild(left);
                    card.appendChild(right);
                    listEl.appendChild(card);
                });
            }

            function updateAnalytics(stats){
                countPendingEl.textContent = stats.pending;
                countInprogressEl.textContent = stats.in_progress;
                countCompletedEl.textContent = stats.completed;
                countMissedEl.textContent = stats.missed;
                if(todosChart && todosChart.data && Array.isArray(todosChart.data.datasets)){
                    todosChart.data.datasets[0].data = [stats.pending, stats.in_progress, stats.completed, stats.missed];
                    todosChart.update();
                }
                const now = new Date();
                if(lastUpdatedEl) lastUpdatedEl.textContent = now.toLocaleTimeString();
            }

            async function fetchTasks(){
                try{
                    const r = await fetch('/capstone/api/tasks_list_employee.php');
                    if (!r.ok) throw new Error('network');
                    const js = await r.json();
                    if (!js || !js.success || !Array.isArray(js.tasks)) {
                        listEl.innerHTML = '<div class="py-2 text-sm text-red-500">Failed to load tasks</div>';
                        return;
                    }

                    // compute missed flag and stats
                    const now = new Date();
                    let stats = { pending:0, in_progress:0, completed:0, missed:0 };
                    js.tasks.forEach(t => {
                        const status = (t.status||'').toLowerCase();
                        let isMissed = false;
                        if(t.due_date){ try{ const due = new Date((t.due_date||'').replace(' ', 'T')); if(due && !isNaN(due.getTime()) && due < now && status !== 'completed') isMissed = true; }catch(e){} }
                        if(isMissed) stats.missed++;
                        if(status === 'pending') stats.pending++;
                        else if(status === 'in_progress') stats.in_progress++;
                        else if(status === 'completed') stats.completed++;
                    });

                    renderList(js.tasks);
                    updateAnalytics(stats);
                }catch(e){
                    console.error('Failed to fetch tasks', e);
                    listEl.innerHTML = '<div class="py-2 text-sm text-red-500">Failed to load tasks</div>';
                }
            }

            // initialize
            initChart();
            fetchTasks();
            const POLL_MS = 1000; // poll every 1 second for realtime
            let pollId = setInterval(fetchTasks, POLL_MS);
            // Update the 'last updated' clock every second
            let clockInterval = setInterval(()=>{ if(lastUpdatedEl) lastUpdatedEl.textContent = new Date().toLocaleTimeString(); }, 1000);
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    clearInterval(pollId); pollId = null;
                    clearInterval(clockInterval); clockInterval = null;
                } else {
                    if (!pollId) { pollId = setInterval(fetchTasks, POLL_MS); fetchTasks(); }
                    if (!clockInterval) { clockInterval = setInterval(()=>{ if(lastUpdatedEl) lastUpdatedEl.textContent = new Date().toLocaleTimeString(); }, 1000); }
                }
            });
        })();
        </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
           // Notification logic
           let notifications = [];
           const bell = document.getElementById('notification-bell');
           const badge = document.getElementById('notification-badge');
           const dropdown = document.getElementById('notification-dropdown');
           const list = document.getElementById('notification-list');
           // Fetch notifications for employee and render as scrollable cards
           function renderNotifications(notifs) {
               const nl = document.getElementById('notification-list');
               nl.innerHTML = '';
               if (!notifs || notifs.length === 0) {
                   nl.innerHTML = '<div class="text-gray-500 p-2">No notifications.</div>';
                   badge.style.display = 'none';
                   return;
               }

               // show badge if there's any unread (simple heuristic)
               const hasUnread = notifs.some(n => !n.read);
               badge.style.display = hasUnread ? 'block' : 'none';

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
                   .then(response => response.json())
                   .then(data => {
                       notifications = (data.success && Array.isArray(data.data)) ? data.data : [];
                       renderNotifications(notifications);
                   })
                   .catch(() => {
                       document.getElementById('notification-list').innerHTML = '<div class="text-gray-500 p-2">Failed to load notifications.</div>';
                   });
           }

           loadNotifications();
           // Toggle dropdown on bell click
           bell.addEventListener('click', () => {
               dropdown.classList.toggle('hidden');
               if (!dropdown.classList.contains('hidden')) {
                   // refresh when opening
                   loadNotifications();
               }
           });
           // Hide dropdown when clicking outside
           document.addEventListener('click', (e) => {
               if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
                   dropdown.classList.add('hidden');
               }
           });
            // Profile Modal logic
               // Notification actions: Mark all read & Clear
               const markAllBtn = document.getElementById('markAllReadBtn');
               const clearBtn = document.getElementById('clearNotifBtn');
               if (markAllBtn) {
                   markAllBtn.addEventListener('click', () => {
                       fetch('notifications.php?action=mark_all_read', { method: 'POST' })
                           .then(() => loadNotifications());
                   });
               }
               if (clearBtn) {
                   clearBtn.addEventListener('click', () => {
                       if (!confirm('Clear all notifications?')) return;
                       fetch('notifications.php?action=clear_all', { method: 'POST' })
                           .then(() => loadNotifications());
                   });
               }
           
               // end notification actions
           
                // Profile Modal logic
            const profileIcon = document.getElementById('profileIcon');
            const profileModal = document.getElementById('profileModal');
            const logoutBtn = document.getElementById('logoutBtn');
            const closeProfileModal = document.getElementById('closeProfileModal');
            profileIcon.addEventListener('click', () => {
                profileModal.classList.remove('hidden');
                profileModal.classList.add('flex');
            });
            closeProfileModal.addEventListener('click', () => {
                profileModal.classList.add('hidden');
                profileModal.classList.remove('flex');
            });
            logoutBtn.addEventListener('click', () => {
                window.location.href = 'logout.php';
            });
            profileModal.addEventListener('click', (e) => {
                if (e.target === profileModal) {
                    profileModal.classList.add('hidden');
                    profileModal.classList.remove('flex');
                }
            });
            // Performance Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            new Chart(performanceCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Performance',
                        data: [65, 59, 80, 81, 56, 55],
                        borderColor: '#6366f1',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Attendance Chart
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(attendanceCtx, {
                type: 'line',
                data: {
                    labels: ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
                    datasets: [{
                        label: 'Attendance',
                        data: [80, 90, 85, 95, 88, 92, 90, 93, 89, 91, 94, 95],
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Edit Profile Modal functionality
            const editProfileModal = document.getElementById('editProfileModal');
            const editProfileBtn = document.getElementById('editProfileBtn');
            const closeModalBtn = document.getElementById('closeModalBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const editProfileForm = document.getElementById('editProfileForm');
            const photoInput = document.getElementById('photoInput');
            const profilePhotoPreview = document.getElementById('profilePhotoPreview');
            const profilePicture = document.getElementById('profilePicture');
            let newPhotoDataUrl = null;

            editProfileBtn.addEventListener('click', () => {
                profilePhotoPreview.src = profilePicture.src;
                newPhotoDataUrl = null;
                editProfileModal.classList.remove('hidden');
                editProfileModal.classList.add('flex');
            });

            function hideModal() {
                editProfileModal.classList.add('hidden');
                editProfileModal.classList.remove('flex');
            }

            closeModalBtn.addEventListener('click', hideModal);
            cancelBtn.addEventListener('click', hideModal);
            editProfileModal.addEventListener('click', (e) => {
                if (e.target === editProfileModal) {
                    hideModal();
                }
            });

            photoInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePhotoPreview.src = e.target.result;
                        newPhotoDataUrl = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            editProfileForm.addEventListener('submit', (e) => {
                e.preventDefault();
                if (newPhotoDataUrl) {
                    // Save to DB via API
                    fetch('../api/employee_info.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: <?php echo json_encode($user_id); ?>, profile_picture: newPhotoDataUrl })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            profilePicture.src = newPhotoDataUrl;
                            document.getElementById('profileIcon').src = newPhotoDataUrl;
                            document.getElementById('profileModalPhoto').src = newPhotoDataUrl;
                            document.getElementById('profilePhotoPreview').src = newPhotoDataUrl;
                        }
                        hideModal();
                    });
                } else {
                    hideModal();
                }
            });
            // Fetch and display events from database
            fetch('../api/get_events.php')
                .then(response => response.json())
                .then(data => {
                    const eventsList = document.getElementById('events-list');
                    eventsList.innerHTML = '';
                    if (data && data.length > 0) {
                        data.forEach(event => {
                            const li = document.createElement('li');
                            li.className = 'py-2 border-b last:border-0 flex justify-between items-center';
                            li.innerHTML = `
                                <span><strong>${event.title}</strong> - ${event.description || ''}</span>
                                <span class="text-xs text-gray-500 ml-2">${event.date} ${event.time ? ('- ' + event.time) : ''}</span>
                            `;
                            eventsList.appendChild(li);
                        });
                    } else {
                        eventsList.innerHTML = '<li class="py-2 text-gray-500">No events found.</li>';
                    }
                })
                .catch(err => {
                    document.getElementById('events-list').innerHTML = '<li class="py-2 text-red-500">Failed to load events.</li>';
                });
    });
    </script>
</body>
</html>