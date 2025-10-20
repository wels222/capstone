<?php
// dashboard.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bayan ng Mabini | Employee System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* All colors are shades of blue or neutral tones to match the request. */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8; /* A light blue-gray */
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px; /* Fixed width for desktop */
            background-color: #ffffff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.5rem 1rem;
            border-right: 4px solid #3b82f6; /* Blue border */
            flex-shrink: 0; /* Prevents sidebar from shrinking */
            position: fixed; /* Fix the sidebar */
            top: 60px; /* Adjust based on header height */
            left: 0;
            bottom: 0;
            overflow-y: auto; /* Enable scrolling for sidebar content if needed */
        }

        .logo-container {
            display: flex;
            align-items: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #d1d5db;
        }

        .logo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid #55a2ea;
            padding: 3px;
        }

        .logo-text {
            font-size: 1rem;
            font-weight: 600;
            margin-left: 0.75rem;
            color: #1e3a8a; /* Dark blue */
            line-height: 1.25;
        }

        .nav-menu ul {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: background-color 0.2s, color 0.2s, transform 0.2s;
        }

        .nav-item a:hover,
        .nav-item.active a {
            background-color: #dbeafe; /* Light blue */
            color: #1d4ed8;
            font-weight: 600;
            transform: translateY(-2px);
        }

        .nav-item a i {
            width: 20px;
            text-align: center;
            margin-right: 1rem;
        }

        .sign-out {
            margin-top: auto;
        }

        .sign-out a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #dc2626;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: background-color 0.2s, transform 0.2s;
        }

        .sign-out a:hover {
            background-color: #fee2e2;
            transform: translateY(-2px);
        }

        .main-content {
            flex-grow: 1;
            padding: 2.5rem;
            margin-left: 280px; /* Add margin to prevent content from going under the sidebar */
            margin-top: 60px; /* Add margin to prevent content from going under the header */
            overflow-y: auto;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .header-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Adjusted for responsiveness */
            gap: 1.5rem; /* Reduced gap for smaller screens */
            margin-bottom: 2rem;
        }

        .header-box {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-top: 4px solid;
            width: 100%; /* Ensures it fills its grid cell */
        }

        .header-box:nth-child(1) { border-color: #3b82f6; }
        .header-box:nth-child(2) { border-color: #93c5fd; }
        .header-box:nth-child(3) { border-color: #22d3ee; }
        .header-box:nth-child(4) { border-color: #60a5fa; }

        .header-box .category {
            font-size: 0.9rem;
            color: #4b5563;
            font-weight: 500;
            display: block;
            margin-bottom: 0.5rem;
        }

        .header-box .count {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
        }

        .header-box .active-count {
            font-size: 0.8rem;
            color: #9ca3af;
            font-weight: 500;
        }

        .projects-events-container {
            display: flex;
            flex-wrap: wrap; /* Allows wrapping on smaller screens */
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .active-projects-box, .events-box {
            background-color: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            flex: 1 1 45%; /* Flex-basis allows them to grow but wrap */
            min-width: 300px; /* Ensures they don't get too narrow */
        }

        .active-projects-box h3, .events-box h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th, td {
            text-align: left;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
        }

        .progress-bar {
            background-color: #e5e7eb;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            width: 100px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-badge.inprogress { background-color: #dbeafe; color: #1e40af; }
        .status-badge.pending { background-color: #fef2f2; color: #ef4444; }
        .status-badge.completed { background-color: #f0fdf4; color: #22c55e; }

        .event-item {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .event-item:last-child {
            border-bottom: none;
        }

        .event-date {
            background-color: #e5e7eb;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-align: center;
            line-height: 1;
        }

        .event-date .day {
            font-size: 1.5rem;
            font-weight: 700;
            display: block;
        }

        .event-date .month {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
            color: #6b7280;
        }

        .event-details {
            flex-grow: 1;
        }

        .event-details .event-title {
            font-weight: 600;
            color: #1f2937;
        }

        .event-details .event-location {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .event-time {
            font-size: 0.9rem;
            color: #9ca3af;
        }

        .all-projects-chart {
            background-color: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center; /* Center content when stacked */
            gap: 2rem;
            flex-wrap: wrap; /* Allows chart and legend to wrap */
        }

        .chart-container {
            width: 150px;
            height: 150px;
            position: relative;
        }

        .chart-legend h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.75rem;
        }

        .chart-legend ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .chart-legend li {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: #4b5563;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            margin-right: 0.75rem;
        }

        .legend-color.complete { background-color: #2563eb; }
        .legend-color.pending { background-color: #93c5fd; }
        .legend-color.not-start { background-color: #60a5fa; }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background-color: #ffffff;
            border-bottom: 1px solid #a7c4ff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            position: fixed; 
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header-left {
            display: flex;
            align-items: center;
        }

        .header-logo .logo-image {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }

        .header-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1e3a8a;
        }

        .header-profile {
            display: flex;
            align-items: center;
        }
        
        .header-profile .profile-image {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            margin-left: 10px;
        }

        .header-profile .notification-icon {
            font-size: 1.25rem;
            color: #6b7280;
            cursor: pointer;
            transition: color 0.2s;
        }

        .header-profile .notification-icon:hover {
            color: #3b82f6;
        }


        /* Employees Section Styles */
        .employee-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            overflow-x: auto;
        }

        .employee-tab-btn {
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s, transform 0.2s;
            border: 1px solid #d1d5db;
        }

        .employee-tab-btn.active {
            font-weight: 600;
            transform: translateY(-2px);
            color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-color: transparent;
        }

        /* Category-specific button colors matching dashboard boxes */
        .employee-tab-btn[data-category="Permanent"].active { background-color: #3b82f6; }
        .employee-tab-btn[data-category="Casual"].active { background-color: #93c5fd; }
        .employee-tab-btn[data-category="JO"].active { background-color: #22d3ee; }
        .employee-tab-btn[data-category="OJT"].active { background-color: #60a5fa; }

        .employee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .employee-card {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.1);
        }

        .employee-card .active-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 12px;
            height: 12px;
            background-color: #10b981; /* Green color for active status */
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .employee-card .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e7eb;
            margin-bottom: 1rem;
        }

        .employee-card .employee-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .employee-card .employee-category {
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        /* Category-specific card colors */
        .employee-card[data-category="Permanent"] .employee-category { color: #3b82f6; }
        .employee-card[data-category="Casual"] .employee-category { color: #93c5fd; }
        .employee-card[data-category="JO"] .employee-category { color: #22d3ee; }
        .employee-card[data-category="OJT"] .employee-category { color: #60a5fa; }


        /* Modal Popup Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            z-index: 1001;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            position: relative;
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            max-height: 90vh; /* Limit height to prevent full screen takeover */
            overflow-y: auto; /* Enable scrolling for modal content */
        }

        .modal-overlay.show .modal-content {
            transform: scale(1);
        }

        .modal-close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #9ca3af;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close-btn:hover {
            color: #ef4444;
        }
        
        .modal-profile-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .modal-profile-header img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #3b82f6;
            margin-bottom: 1rem;
        }
        
        .modal-profile-header h4 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .modal-profile-header .employee-details {
            font-size: 1rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        .modal-leave-credits {
            margin-top: 1rem;
            text-align: left;
        }
        
        .modal-leave-credits h5 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .modal-leave-credits ul {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .modal-leave-credits li {
            background-color: #f3f4f6;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            color: #374151;
        }
        
        .modal-leave-credits .credit-count {
            font-weight: 600;
            color: #1d4ed8;
        }
        
        @media (max-width: 1024px) {
            .header-container {
                grid-template-columns: 1fr 1fr;
            }

            .projects-events-container {
                flex-direction: column;
            }

            .all-projects-chart {
                flex-direction: column;
                text-align: center;
            }

            .chart-legend {
                margin-top: 2rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                height: auto;
            }

            .sidebar {
                width: 100%;
                height: auto;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 1rem;
                position: relative;
                border-right: none;
                border-bottom: 4px solid #3b82f6;
            }
            
            .nav-menu {
                display: none;
            }
            
            .main-content {
                padding: 1.5rem;
                margin-left: 0;
                margin-top: 60px;
            }
            
            .header-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .employee-tabs {
                flex-wrap: nowrap;
            }
        }
    </style>
</head>
<body>

    <header class="top-header">
        <div class="header-left">
            <div class="header-logo">
                <img src="../assets/logo.png" alt="Mabini Logo" class="logo-image">
            </div>
            <span class="header-text">Dept Head</span>
        </div>
        <div class="header-profile">
            <i class="fas fa-bell notification-icon"></i>
            <img src="../assets/logo.png" alt="Profile" class="profile-image">
        </div>
    </header>

    <div class="container">
        <aside class="sidebar">
            <nav class="nav-menu">
                <ul>
                    <li class="nav-item active">
                        <a href="#"><i class="fas fa-th-large"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="leave-status.html"><i class="fas fa-calendar-alt"></i> Leave Status</a>
                    </li>
                    <li class="nav-item">
                        <a href="task-status.html"><i class="fas fa-tasks"></i> Task Status</a>
                    </li>
                    <li class="nav-item">
                        <a href="leave-request.html"><i class="fas fa-calendar-plus"></i> Leave Request</a>
                    </li>
                </ul>
            </nav>
            <div class="sign-out">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="main-content-area">
                <section id="dashboard-content" class="content-section active">
                    <div class="header-container">
                        <div class="header-box">
                            <span class="category">Permanent</span>
                            <div class="count" id="count-permanent">0</div>
                            <span class="active-count" id="active-permanent">0 Active</span>
                        </div>
                        <div class="header-box">
                            <span class="category">Casual</span>
                            <div class="count" id="count-casual">0</div>
                            <span class="active-count" id="active-casual">0 Active</span>
                        </div>
                        <div class="header-box">
                            <span class="category">JO</span>
                            <div class="count" id="count-jo">0</div>
                            <span class="active-count" id="active-jo">0 Active</span>
                        </div>
                        <div class="header-box">
                            <span class="category">OJT</span>
                            <div class="count" id="count-ojt">0</div>
                            <span class="active-count" id="active-ojt">0 Active</span>
                        </div>
                    </div>
                    <div class="projects-events-container">
                        <div class="active-projects-box">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-bold text-gray-800">Active Projects</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr>
                                            <th class="py-3 px-4 font-semibold text-sm text-gray-500 uppercase tracking-wider">Project Name</th>
                                            <th class="py-3 px-4 font-semibold text-sm text-gray-500 uppercase tracking-wider">Project Lead</th>
                                            <th class="py-3 px-4 font-semibold text-sm text-gray-500 uppercase tracking-wider">Progress</th>
                                            <th class="py-3 px-4 font-semibold text-sm text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="py-3 px-4 whitespace-nowrap font-semibold text-sm text-gray-500 uppercase tracking-wider">Due Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="py-4 px-4 whitespace-nowrap">Bender project</td>
                                            <td class="py-4 px-4 whitespace-nowrap">Johnson</td>
                                            <td class="py-4 px-4">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: 63%; background-color: #55a2ea;"></div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 whitespace-nowrap"><span class="status-badge inprogress">Inprogress</span></td>
                                            <td class="py-4 px-4 whitespace-nowrap">06 Jan 2025</td>
                                        </tr>
                                        <tr>
                                            <td class="py-4 px-4 whitespace-nowrap">Batmon</td>
                                            <td class="py-4 px-4 whitespace-nowrap">William</td>
                                            <td class="py-4 px-4">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: 24%; background-color: #ef4444;"></div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 whitespace-nowrap"><span class="status-badge pending">Pending</span></td>
                                            <td class="py-4 px-4 whitespace-nowrap">06 Jan 2025</td>
                                        </tr>
                                        <tr>
                                            <td class="py-4 px-4 whitespace-nowrap">Candy</td>
                                            <td class="py-4 px-4 whitespace-nowrap">Paul</td>
                                            <td class="py-4 px-4">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: 86%; background-color: #22c55e;"></div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 whitespace-nowrap"><span class="status-badge completed">Completed</span></td>
                                            <td class="py-4 px-4 whitespace-nowrap">30 Jan 2025</td>
                                        </tr>
                                        <tr>
                                            <td class="py-4 px-4 whitespace-nowrap">Throwing</td>
                                            <td class="py-4 px-4 whitespace-nowrap">Elizabeth</td>
                                            <td class="py-4 px-4">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: 51%; background-color: #6b7280;"></div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 whitespace-nowrap"><span class="status-badge inprogress">Inprogress</span></td>
                                            <td class="py-4 px-4 whitespace-nowrap">11 Jan 2025</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="events-box">
                            <h3 class="text-xl font-bold text-gray-800">Events</h3>
                                <ul id="events-list">
                                    <!-- Events will be loaded here -->
                                </ul>
                        </div>
                    </div>
                    <div class="all-projects-chart">
                        <div class="chart-container">
                            <canvas id="projectChart"></canvas>
                        </div>
                        <div class="chart-legend">
                            <h4>All Projects</h4>
                            <ul>
                                <li><span class="legend-color complete"></span> Complete</li>
                                <li><span class="legend-color pending"></span> Pending</li>
                                <li><span class="legend-color not-start"></span> Not Start</li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chartData = {
                labels: ['Complete', 'Pending', 'Not Start'],
                datasets: [{
                    label: 'Project Status',
                    data: [3, 2, 4],
                    backgroundColor: [
                        '#2563eb', // Complete (Blue)
                        '#93c5fd', // Pending (Light Blue)
                        '#60a5fa'  // Not Start (Lighter Blue)
                    ],
                    hoverOffset: 4
                }]
            };

            const config = {
                type: 'doughnut',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            };

            const projectChart = document.getElementById('projectChart');
            if(projectChart) {
                new Chart(projectChart, config);
            }

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

        // Dept head counts: fetch current user, then employees, filter by department
        async function updateDeptCounts(){
            try{
                const uResp = await fetch('../api/current_user.php');
                const user = await uResp.json();
                if(!user || !user.logged_in){ console.warn('No logged in user'); return; }
                const dept = (user.department || '').toString();
                // fetch all employees and filter by department
                const empResp = await fetch('../api/get_employees.php');
                const empJson = await empResp.json();
                const employees = (empJson && empJson.employees) || [];
                // Filter employees to the same department, exclude pending accounts and exclude HR role
                const filtered = employees.filter(e => {
                    const empDept = (e.department || '').toString();
                    const role = (e.role || '').toString().toLowerCase();
                    const status = (e.status || '').toString().toLowerCase();
                    // must be same department
                    if (empDept !== dept) return false;
                    // exclude HR users from all counts
                    if (role === 'hr') return false;
                    // exclude accounts that are still pending
                    if (status === 'pending') return false;
                    return true;
                });

                // categories we track
                const categories = ['Permanent','Casual','JO','OJT'];
                const counts = { Permanent:0, Casual:0, JO:0, OJT:0 };
                const active = { Permanent:0, Casual:0, JO:0, OJT:0 };
                filtered.forEach(e=>{
                    const pos = (e.position||'').toString();
                    if(categories.includes(pos)){
                        counts[pos] = (counts[pos]||0) + 1;
                        if(((e.status||'').toString().toLowerCase()) === 'approved') active[pos] = (active[pos]||0) + 1;
                    }
                });

                // update DOM
                document.getElementById('count-permanent').textContent = counts['Permanent'] || 0;
                document.getElementById('active-permanent').textContent = `${active['Permanent'] || 0} Active`;
                document.getElementById('count-casual').textContent = counts['Casual'] || 0;
                document.getElementById('active-casual').textContent = `${active['Casual'] || 0} Active`;
                document.getElementById('count-jo').textContent = counts['JO'] || 0;
                document.getElementById('active-jo').textContent = `${active['JO'] || 0} Active`;
                document.getElementById('count-ojt').textContent = counts['OJT'] || 0;
                document.getElementById('active-ojt').textContent = `${active['OJT'] || 0} Active`;
            }catch(err){ console.error('updateDeptCounts error', err); }
        }

        // initialize and poll periodically
        updateDeptCounts();
        setInterval(updateDeptCounts, 12000); // refresh every 12s
    </script>
</body>
</html>

