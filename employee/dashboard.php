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
    </style>
</head>
<body class="bg-gray-100 p-6 lg:p-10">

    <header class="bg-white rounded-xl shadow-md p-4 flex items-center justify-between z-10 sticky top-0">
        <div class="flex items-center space-x-4">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <img src="../assets/logo.png" alt="Logo" class="rounded-full">
            </div>
            <h1 id="header-title" class="text-xl font-bold text-gray-800">Dashboard</h1>
        </div>
        <div class="flex items-center space-x-4">
            <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                <i class="fas fa-bell text-lg"></i>
            </a>
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
                <div class="absolute inset-0 bg-blue-700 bg-opacity-20 backdrop-blur-sm z-0"></div>
                <div class="relative z-10 flex items-center space-x-6">
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
                        <a href="leave_status.html" class="flex flex-col items-center space-y-2 cursor-pointer">
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

                <div class="bg-white rounded-xl shadow-md p-6 lg:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="col-span-1 md:col-span-3">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Leave</h3>
                    </div>
                    <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between w-full">
                            <span class="font-medium text-gray-700">Vacation Leave</span>
                            <span class="text-sm text-gray-500">05/07</span>
                        </div>
                        <p class="text-sm text-gray-500">Available - 05</p>
                        <p class="text-sm text-gray-500">Used - 02</p>
                    </div>
                    <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between w-full">
                            <span class="font-medium text-gray-700">Maternity Leave</span>
                            <span class="text-sm text-gray-500">105/105</span>
                        </div>
                        <p class="text-sm text-gray-500">Available - 105</p>
                        <p class="text-sm text-gray-500">Used - 00</p>
                    </div>
                    <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between w-full">
                            <span class="font-medium text-gray-700">Study Leave</span>
                            <span class="text-sm text-gray-500">00/00</span>
                        </div>
                        <p class="text-sm text-gray-500">Available - 00</p>
                        <p class="text-sm text-gray-500">Used - 00</p>
                    </div>
                    <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between w-full">
                            <span class="font-medium text-gray-700">Mandatory Force Leave</span>
                            <span class="text-sm text-gray-500">02/02</span>
                        </div>
                        <p class="text-sm text-gray-500">Available - 00</p>
                        <p class="text-sm text-gray-500">Used - 02</p>
                    </div>
                    <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between w-full">
                            <span class="font-medium text-gray-700">Social Leave Benefits</span>
                            <span class="text-sm text-gray-500">00/00</span>
                        </div>
                        <p class="text-sm text-gray-500">Available - 00</p>
                        <p class="text-sm text-gray-500">Used - 00</p>
                    </div>
                    <div class="flex flex-col items-start p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between w-full">
                            <span class="font-medium text-gray-700">Social Emergency</span>
                            <span class="text-sm text-gray-500">00/00</span>
                        </div>
                        <p class="text-sm text-gray-500">Available - 00</p>
                        <p class="text-sm text-gray-500">Used - 00</p>
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
                    <ul>
                        <li class="py-2 border-b last:border-0 flex justify-between items-center">
                            <span>Complete Onboarding Document Upload</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </li>
                        <li class="py-2 border-b last:border-0 flex justify-between items-center">
                            <span>Follow up on clients on documents</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </li>
                        <li class="py-2 border-b last:border-0 flex justify-between items-center">
                            <span>Design wireframes for LMS</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </li>
                        <li class="py-2 border-b last:border-0 flex justify-between items-center">
                            <span>Create case study for next IT project</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </li>
                    </ul>
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
        document.addEventListener('DOMContentLoaded', () => {
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