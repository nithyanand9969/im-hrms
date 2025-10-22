
<?php
session_start();
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../connecting_fIle/config.php';

?>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Welcome</title>
  <!-- CSS Dependencies -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.2.0/fullcalendar.min.css" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
/>
  <style>

          @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        .form-input {
            width: 100%;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-input:invalid {
            border-color: #ef4444;
        }
        
        .form-input:valid {
            border-color: #10b981;
        }
        
        #reportingManagerDiv {
            transition: all 0.3s ease-in-out;
        }
        
       
  </style>
</head>

<body>
    <body class="bg-gray-100 font-sans antialiased">
  <div class="flex h-screen">
    <!-- Sidebar -->
 <aside class="hidden md:flex w-64 bg-blue-900 text-white flex-col flex-shrink-0">
  <div class="text-2xl font-bold p-6 border-b border-blue-700">IM Management</div>
  <nav class="flex-1 p-4 space-y-2">
     <button class="nav-item flex items-center w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition" data-page="leaves">
      <i class="fas fa-calendar-minus w-6 text-center"></i>
      <span class="ml-2">Leaves</span>
    </button>
     <button class="nav-item flex items-center w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition" data-page="leaves">
      <i class="fas fa-download w-6 text-center"></i>
      <span class="ml-2">Export Report</span>
    </button>

    <button class="nav-item flex items-center w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition" data-page="holidaySection">
      <i class="fas fa-calendar-alt w-6 text-center"></i>
      <span class="ml-2">Holidays</span>
    </button>
  </nav>
  <div class="p-4 border-t border-blue-700">
    <a href="logout.php" class="w-full flex items-center justify-center bg-red-600 hover:bg-red-700 py-2 rounded text-white">
      <i class="fas fa-sign-out-alt w-6 text-center"></i>
      <span class="ml-2">Sign Out</span>
    </a>
  </div>
</aside>

<nav class="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow md:hidden flex justify-around py-2">
  <button class="nav-item flex flex-col items-center text-sm text-gray-700" data-page="dashboard">
    <i class="fas fa-tachometer-alt text-lg"></i>
    <span class="text-xs">Dashboard</span>
  </button>

  <button class="nav-item flex flex-col items-center text-sm text-gray-700" data-page="manage-users">
    <i class="fas fa-users text-lg"></i>
    <span class="text-xs">Users</span>
  </button>

  <button class="nav-item flex flex-col items-center text-sm text-gray-700" data-page="leaves">
    <i class="fas fa-calendar-minus text-lg"></i>
    <span class="text-xs">Your Leaves</span>
  </button>

  <!-- âœ… Holiday List Button -->
  <button onclick="openHolidayModal()" class="nav-item flex flex-col items-center text-sm text-gray-700">
    <i class="fas fa-calendar-day text-lg"></i>
    <span class="text-xs">Holidays</span>
  </button>

  <!-- âœ… Logout Button as Link -->
  <a href="logout.php" class="nav-item flex flex-col items-center text-sm text-red-600">
    <i class="fas fa-sign-out-alt text-lg"></i>
    <span class="text-xs">Logout</span>
  </a>
</nav>

    
    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto" id="main-content">
      <!-- Dashboard Section -->
   <section id="dashboard" class="p-6">


<div class="flex justify-end mb-4">
    <form method="POST" action="export_leave_report.php" target="_blank">
       
    </form>
    
    
    <div id="holidayModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
  <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold text-indigo-800">Holiday List</h2>
      <button onclick="closeHolidayModal()" class="text-gray-500 hover:text-gray-700">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="overflow-y-auto max-h-96">
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-gray-100">
            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700 border-b">Date</th>
            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700 border-b">Day</th>
          </tr>
        </thead>
        <tbody>
          <?php
          foreach ($holidays_from_db ?? [] as $holiday) {
            $date = new DateTime($holiday);
            $day = $date->format('l'); // Full day name (e.g., Monday)
            $formattedDate = $date->format('d-m-Y'); // Format date as DD-MM-YYYY
            
            echo "<tr class='hover:bg-gray-50'>
                    <td class='px-4 py-2 border-b text-sm'>$formattedDate</td>
                    <td class='px-4 py-2 border-b text-sm'>$day</td>
                  </tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
    
    <div class="mt-4 text-right">
      <button onclick="closeHolidayModal()" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
        Close
      </button>
    </div>
  </div>
</div>
<script>
function openHolidayModal() {
  document.getElementById('holidayModal').classList.remove('hidden');
}

function closeHolidayModal() {
  document.getElementById('holidayModal').classList.add('hidden');
}
</script>
</div>

</body>