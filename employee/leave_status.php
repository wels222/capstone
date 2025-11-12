<?php
session_start();
require_once '../db.php';
$user_id = $_SESSION['user_id'] ?? null;
$employeeEmail = '';
if ($user_id) {
    $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $employeeEmail = $row['email'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Status</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
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
            </a>
            <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 transition-colors">
                <i class="fas fa-home text-lg"></i>
            </a>
            <img id="profileIcon" src="https://placehold.co/40x40/FF5733/FFFFFF?text=P" alt="Profile" class="w-10 h-10 rounded-full cursor-pointer">
            <!-- Profile Modal -->
            <div id="profileModal" class="fixed inset-0 hidden items-center justify-center modal-bg z-50">
                <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-xs mx-4 flex flex-col items-center">
                    <img src="https://placehold.co/80x80/FFD700/000000?text=W+P" alt="Profile" class="w-20 h-20 rounded-full mb-4">
                    <button id="logoutBtn" class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 mb-2">Log out</button>
                    <button id="closeProfileModal" class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancel</button>
                </div>
            </div>
        </div>
    </header>

    <main class="mt-8 lg:mt-12">
        <div class="bg-white rounded-xl shadow-md p-6 lg:p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">My Leave Status</h2>
                <div class="relative">
                    <input type="text" id="search-leave" placeholder="Search leave..." class="px-4 py-2 border rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody id="leave-table-body" class="bg-white divide-y divide-gray-200">
                        <!-- Leave requests will be populated here by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Leave Details Modal -->
    <div id="leave-details-modal" class="fixed inset-0 modal-bg flex items-center justify-center hidden">
        <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-lg mx-auto w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-800">Leave Details</h3>
                <button id="close-leave-details-modal" class="text-gray-500 hover:text-gray-700 text-lg">&times;</button>
            </div>
            <div id="leave-details-content" class="space-y-4">
                <!-- Details will be populated here by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Apply for Leave Modal -->
    <div id="apply-leave-modal" class="fixed inset-0 modal-bg flex items-center justify-center hidden">
        <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-lg mx-auto w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-800">Apply for Leave</h3>
                <button id="close-apply-leave-modal" class="text-gray-500 hover:text-gray-700 text-lg">&times;</button>
            </div>
            <form id="apply-leave-form" class="space-y-4">
                <div>
                    <label for="leave-type" class="block text-sm font-medium text-gray-700">Leave Type</label>
                    <select id="leave-type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="sick">Sick Leave</option>
                        <option value="vacation">Vacation Leave</option>
                        <option value="paternity">Paternity Leave</option>
                        <option value="maternity">Maternity Leave</option>
                    </select>
                </div>
                <div>
                    <label for="start-date" class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="date" id="start-date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="end-date" class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="date" id="end-date" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700">Reason</label>
                    <textarea id="reason" rows="3" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" id="cancel-apply-leave" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast-notification" class="fixed bottom-5 right-5 bg-green-500 text-white px-6 py-3 rounded-lg shadow-xl hidden transition-opacity duration-300 ease-in-out">
        Leave request submitted successfully!
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- MODAL ELEMENTS ---
            const applyLeaveBtn = document.getElementById('apply-leave-btn');
            const applyLeaveModal = document.getElementById('apply-leave-modal');
            const closeApplyLeaveModalBtn = document.getElementById('close-apply-leave-modal');
            const cancelApplyLeaveBtn = document.getElementById('cancel-apply-leave');
            const applyLeaveForm = document.getElementById('apply-leave-form');
            const leaveDetailsModal = document.getElementById('leave-details-modal');
            const closeLeaveDetailsModalBtn = document.getElementById('close-leave-details-modal');
            const leaveTableBody = document.getElementById('leave-table-body');
            const searchInput = document.getElementById('search-leave');
            const toastNotification = document.getElementById('toast-notification');

            // --- FETCH REAL LEAVE REQUESTS ---
            let leaveRequests = [];
            function fetchLeaveRequests() {
                fetch('../api/get_leave_requests.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && Array.isArray(data.data)) {
                            // Get current employee email from session (PHP inject)
                            const employeeEmail = window.employeeEmail || '';
                            leaveRequests = data.data.filter(req => req.employee_email === employeeEmail);
                            renderLeaveRequests(leaveRequests);
                        } else {
                            leaveTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">No leave requests found.</td></tr>';
                        }
                    })
                    .catch(() => {
                        leaveTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-red-500">Failed to fetch leave requests.</td></tr>';
                    });
            }

            // --- FUNCTIONALITY ---
            function renderLeaveRequests(requests) {
                leaveTableBody.innerHTML = '';
                requests.forEach(req => {
                    let statusLabel = 'Pending';
                    let statusColor = 'bg-yellow-100 text-yellow-800';
                    
                    // New logic: 3-tier approval system
                    // 1. Pending dept head
                    // 2. Approved by dept head, pending HR (status=approved, approved_by_hr=0)
                    // 3. Approved by HR, pending municipal (status=approved, approved_by_hr=1, approved_by_municipal=0)
                    // 4. Final approved by municipal (status=approved, approved_by_hr=1, approved_by_municipal=1)
                    // 5. Declined at any stage
                    
                    if (req.status === 'declined') {
                        statusLabel = 'Declined';
                        statusColor = 'bg-red-100 text-red-800';
                    } else if (req.status === 'approved') {
                        if (req.approved_by_municipal == 1) {
                            // Final approval - credits deducted
                            statusLabel = 'Approved';
                            statusColor = 'bg-green-100 text-green-800';
                        } else if (req.approved_by_hr == 1) {
                            // HR approved, waiting for municipal
                            statusLabel = 'Pending Municipal Admin';
                            statusColor = 'bg-blue-100 text-blue-800';
                        } else {
                            // Dept head approved, waiting for HR
                            statusLabel = 'Pending HR';
                            statusColor = 'bg-purple-100 text-purple-800';
                        }
                    } else {
                        // Initial pending (waiting for dept head)
                        statusLabel = 'Pending Department Head';
                        statusColor = 'bg-yellow-100 text-yellow-800';
                    }
                    
                    // Get start and end date from req.dates
                    let startDate = '';
                    let endDate = '';
                    if (req.dates) {
                        const matches = req.dates.match(/\d{4}-\d{2}-\d{2}/g);
                        if (matches && matches.length > 0) {
                            startDate = matches[0];
                            endDate = matches[1] ? matches[1] : matches[0];
                        }
                    }
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${req.leave_type}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${startDate}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${endDate}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusColor}">${statusLabel}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button data-id="${req.id}" class="view-details-btn text-blue-600 hover:text-blue-900">View Details</button>
                        </td>
                    `;
                    leaveTableBody.appendChild(row);
                });
                if (requests.length === 0) {
                    leaveTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">No leave requests found.</td></tr>';
                }
            }

            // Function to show the leave details modal
            function showLeaveDetailsModal(request) {
                const content = document.getElementById('leave-details-content');
                content.innerHTML = `
                    <div>
                        <span class="font-semibold text-gray-700">Leave Type:</span>
                        <span class="text-gray-600">${request.type}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Dates:</span>
                        <span class="text-gray-600">${request.startDate} to ${request.endDate}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Status:</span>
                        <span class="text-gray-600">${request.status}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Reason:</span>
                        <p class="text-gray-600 mt-1">${request.reason}</p>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Manager's Notes:</span>
                        <p class="text-gray-600 mt-1">${request.managerNotes}</p>
                    </div>
                `;
                leaveDetailsModal.classList.remove('hidden');
            }
            
            // Function to show a toast notification
            function showToast(message) {
                toastNotification.textContent = message;
                toastNotification.classList.remove('hidden');
                setTimeout(() => {
                    toastNotification.classList.add('hidden');
                }, 3000); // Hide after 3 seconds
            }

            // --- EVENT LISTENERS ---

            // Event listener for the "Apply for Leave" button
            if (applyLeaveBtn && applyLeaveModal) {
                applyLeaveBtn.addEventListener('click', () => {
                    applyLeaveModal.classList.remove('hidden');
                });
            }

            // Event listener to close the Apply for Leave modal
            if (closeApplyLeaveModalBtn && cancelApplyLeaveBtn) {
                closeApplyLeaveModalBtn.addEventListener('click', () => {
                    applyLeaveModal.classList.add('hidden');
                });
                cancelApplyLeaveBtn.addEventListener('click', () => {
                    applyLeaveModal.classList.add('hidden');
                });
            }

            // Event listener for the Apply for Leave form submission
            if (applyLeaveForm) {
                applyLeaveForm.addEventListener('submit', (e) => {
                    e.preventDefault(); // Prevent default form submission

                    const newRequest = {
                        id: leaveRequests.length + 1,
                        type: document.getElementById('leave-type').value,
                        startDate: document.getElementById('start-date').value,
                        endDate: document.getElementById('end-date').value,
                        reason: document.getElementById('reason').value,
                        status: "Pending", // New requests start as pending
                        managerNotes: "Pending review."
                    };

                    leaveRequests.push(newRequest);
                    renderLeaveRequests(leaveRequests);
                    applyLeaveModal.classList.add('hidden');
                    applyLeaveForm.reset();
                    showToast('Leave request submitted successfully!');
                });
            }

            // Event listener for clicking "View Details" buttons
            if (leaveTableBody) {
                leaveTableBody.addEventListener('click', (e) => {
                    if (e.target.classList.contains('view-details-btn')) {
                        const requestId = e.target.getAttribute('data-id');
                        fetch(`../api/get_leave_requests.php?id=${requestId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.data && data.data.length > 0) {
                                    // Find the exact request by id
                                    const req = data.data.find(r => r.id == requestId);
                                    if (!req) return;
                                    const content = document.getElementById('leave-details-content');
                                    
                                    // Determine status label with 3-tier approval
                                    let statusLabel = 'Pending';
                                    if (req.status === 'declined') {
                                        let declinedBy = 'Department Head';
                                        if (req.approved_by_hr == 1) {
                                            declinedBy = 'Municipal Admin';
                                        } else if (req.approved_by_hr == 0 && req.status === 'declined') {
                                            // Could be HR or dept head
                                            declinedBy = 'HR or Department Head';
                                        }
                                        statusLabel = `Declined by ${declinedBy}`;
                                    } else if (req.status === 'approved') {
                                        if (req.approved_by_municipal == 1) {
                                            statusLabel = 'Approved by Municipal Admin (Final)';
                                        } else if (req.approved_by_hr == 1) {
                                            statusLabel = 'Approved by HR - Pending Municipal Admin';
                                        } else {
                                            statusLabel = 'Approved by Department Head - Pending HR';
                                        }
                                    } else {
                                        statusLabel = 'Pending Department Head Approval';
                                    }
                                    
                                    // Show all fields from database for the selected request only
                                    let noteText = '';
                                    if (req.status === 'declined' && req.decline_reason) {
                                        noteText = req.decline_reason;
                                    } else if (req.status === 'pending') {
                                        noteText = 'Your leave request is being reviewed.';
                                    } else if (req.status === 'approved' && req.approved_by_municipal == 1) {
                                        noteText = 'Congratulations! Your leave has been fully approved.';
                                    } else if (req.status === 'approved' && req.approved_by_hr == 1) {
                                        noteText = 'Your leave has been approved by HR. Awaiting final approval from Municipal Admin.';
                                    } else if (req.status === 'approved') {
                                        noteText = 'Your leave has been approved by your Department Head. Awaiting HR approval.';
                                    } else {
                                        noteText = '';
                                    }
                                    content.innerHTML = `
                                        <div><span class='font-semibold text-gray-700'>Leave Type:</span> <span class='text-gray-600'>${req.leave_type}</span></div>
                                        <div><span class='font-semibold text-gray-700'>Dates:</span> <span class='text-gray-600'>${req.dates}</span></div>
                                        <div><span class='font-semibold text-gray-700'>Status:</span> <span class='text-gray-600'>${statusLabel}</span></div>
                                        <div><span class='font-semibold text-gray-700'>Reason:</span> <span class='text-gray-600'>${req.reason}</span></div>
                                        <div><span class='font-semibold text-gray-700'>Department Head:</span> <span class='text-gray-600' id='dept-head-name'></span></div>
                                        <div class='mt-4'><span class='font-semibold text-blue-700'>Note:</span> <span class='text-gray-600'>${noteText}</span></div>
                                    `;
                                    // Fetch department head name in real time
                                    fetch(`../api/dept_heads.php`)
                                        .then(response => response.json())
                                        .then(heads => {
                                            if (Array.isArray(heads)) {
                                                const head = heads.find(h => h.email === req.dept_head_email);
                                                if (head) {
                                                    document.getElementById('dept-head-name').textContent = head.name;
                                                } else {
                                                    document.getElementById('dept-head-name').textContent = req.dept_head_email;
                                                }
                                            }
                                        });
                                    leaveDetailsModal.classList.remove('hidden');
                                }
                            });
                    }
                });
            }

            // Event listener to close the Leave Details modal
            if (closeLeaveDetailsModalBtn) {
                closeLeaveDetailsModalBtn.addEventListener('click', () => {
                    leaveDetailsModal.classList.add('hidden');
                });
            }

            // Event listener for search input
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value.toLowerCase();
                    const filteredRequests = leaveRequests.filter(req => 
                        req.type.toLowerCase().includes(searchTerm) ||
                        req.startDate.includes(searchTerm) ||
                        req.endDate.includes(searchTerm) ||
                        req.status.toLowerCase().includes(searchTerm)
                    );
                    renderLeaveRequests(filteredRequests);
                });
            }

            // Initial fetch of leave requests
            fetchLeaveRequests();
            // Optionally, refresh every minute for real-time updates
            setInterval(fetchLeaveRequests, 60000);
        });
    </script>
    <script>
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
    </script>
    <script>
        // Inject employee email from PHP session/database
        window.employeeEmail = <?php echo json_encode($employeeEmail); ?>;
    </script>
</body>
</html>
