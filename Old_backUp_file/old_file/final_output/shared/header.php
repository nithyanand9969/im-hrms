<?php
session_start();
require_once 'config.php';
date_default_timezone_set("Asia/Kolkata");
$lastLogin = date("F j, Y - g:i A");
$pageTitle = "Leave Management System";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.2.0/fullcalendar.min.js"></script>
  <style>
    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }
    .status-pending { background-color: #fffbeb; color: #f59e0b; }
    .status-approved { background-color: #f0fdf4; color: #10b981; }
    .status-rejected { background-color: #fef2f2; color: #ef4444; }
    .active-nav { background-color: #3b82f6; color: white; }
    .form-input:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
  </style>
</head>
<body class="bg-gray-100 min-h-screen flex">
  <!-- Sidebar -->
  <aside class="w-64 bg-blue-800 text-white flex-shrink-0 hidden md:block">
    <div class="p-6 text-xl font-bold border-b border-blue-700 flex items-center">
      <i class="fas fa-calendar-alt mr-2"></i>Leave System
    </div>
    <nav class="mt-6 space-y-1 px-4">
      <?php if ($_SESSION['user']['role'] === 'admin'): ?>
        <a href="admin-dashboard.php" class="nav-item block py-3 px-4 rounded hover:bg-blue-700 transition">
          <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
        </a>
        <a href="manage-users.php" class="nav-item block py-3 px-4 rounded hover:bg-blue-700 transition">
          <i class="fas fa-users mr-3"></i> Manage Users
        </a>
        <a href="admin-leaves.php" class="nav-item block py-3 px-4 rounded hover:bg-blue-700 transition">
          <i class="fas fa-calendar-minus mr-3"></i> Leaves
        </a>
        <a href="attendance.php" class="nav-item block py-3 px-4 rounded hover:bg-blue-700 transition">
          <i class="fas fa-clipboard-check mr-3"></i> Attendance
        </a>
      <?php elseif ($_SESSION['user']['role'] === 'manager'): ?>
        <a href="manager-dashboard.php" class="nav-item block py-3 px-4 rounded hover:bg-blue-700 transition">
          <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
        </a>
        <a href="team-leaves.php" class="nav-item block py-3 px-4 rounded hover:bg-blue-700 transition">
          <i class="fas fa-users mr-3"></i> Team Leaves
        </a>
        <a href="reports.php" class="nav-item block py-3 px-4 rounded hover:bg-blue-700 transition">
          <i class="fas fa-chart-bar mr-3"></i> Reports
        </a>
      <?php else: ?>
        <a href="user-dashboard.php" class="nav-item block py-3 px-4 rounded hover:bg-blue-700 transition">
          <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
        </a>
      <?php endif; ?>
    </nav>
    <div class="mt-auto p-4 border-t border-blue-700 text-sm">
      <div class="flex items-center justify-between">
        <div>
          <div class="font-medium"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
          <div class="text-blue-200 text-xs"><?= ucfirst($_SESSION['user']['role']) ?></div>
        </div>
        <a href="logout.php" class="text-red-300 hover:text-white">
          <i class="fas fa-sign-out-alt text-lg"></i>
        </a>
      </div>
    </div>
  </aside>

  <!-- Main Content -->
  <div class="flex-1 flex flex-col">
    <!-- Top Bar -->
    <header class="bg-white shadow p-4">
      <div class="flex justify-between items-center">
        <div>
          <h1 class="text-xl font-semibold text-gray-800"><?= $pageTitle ?></h1>
          <p class="text-sm text-gray-500">Last login: <?= $lastLogin ?></p>
        </div>
        <div class="flex items-center space-x-4">
          <div class="relative">
            <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm w-64">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
          </div>
          <div class="relative">
            <button class="text-gray-600 hover:text-gray-900 relative">
              <i class="fas fa-bell text-xl"></i>
              <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>
          </div>
          <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold">
              <?= substr($_SESSION['user']['name'], 0, 1) ?>
            </div>
          </div>
        </div>
      </div>
    </header>