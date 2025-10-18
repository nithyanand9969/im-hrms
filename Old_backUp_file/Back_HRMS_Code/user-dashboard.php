<?php
session_start();
include 'config.php';

// ✅ Enforce user login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$user_name = $user['name'];
$current_year = date('Y');

// ✅ Toast messages
if (isset($_SESSION['toast'])) {
    echo "<script>
        toastr." . $_SESSION['toast']['type'] . "('" . addslashes($_SESSION['toast']['message']) . "');
    </script>";
    unset($_SESSION['toast']);
}

// ✅ Fetch leave_balance from users table
$sql_balance = "SELECT leave_balance FROM users WHERE id = ?";
$stmt_balance = $conn->prepare($sql_balance);
$stmt_balance->bind_param("i", $user_id);
$stmt_balance->execute();
$result_balance = $stmt_balance->get_result();
$row_balance = $result_balance->fetch_assoc();
$leave_balance = $row_balance['leave_balance'] ?? 0;
$stmt_balance->close();

$sql_used = "SELECT 
                SUM(
                    CASE 
                        WHEN leave_duration = 'Half Day' THEN 0.5 
                        ELSE DATEDIFF(to_date, from_date) + 1 
                    END
              ) as used_days
            FROM leave_requests 
            WHERE user_id = ? 
            AND status = 'approved' 
            AND YEAR(from_date) = ?";

$stmt_used = $conn->prepare($sql_used);
$stmt_used->bind_param("ii", $user_id, $current_year);
$stmt_used->execute();
$result_used = $stmt_used->get_result();
$used_data = $result_used->fetch_assoc();
$used_days = $used_data['used_days'] ?? 0;
$stmt_used->close();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['leave_type'])) {
    $leave_type = $_POST['leave_type'];
    $leave_duration = $_POST['leave_duration'];
    $half_day_type = $_POST['half_day_type'] ?? null;
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'] ?? $from_date;
    $reason = $_POST['reason'];
    $status = 'pending';
    $created_at = date("Y-m-d H:i:s");

    $approval_level = 0;
    $approval_required = 2;

    $sql = "INSERT INTO leave_requests 
            (user_id, leave_type, leave_duration, half_day_type, from_date, to_date, reason, status, approval_level, approval_required, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isssssssiis", 
        $user_id, $leave_type, $leave_duration, $half_day_type, 
        $from_date, $to_date, $reason, $status, 
        $approval_level, $approval_required, $created_at
    );
if ($stmt->execute()) {
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave request submitted successfully!'];
    $_SESSION['show_modal'] = true; // ✅ Flag to show modal
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

    $stmt->close();
}



$sql_history = "SELECT leave_type, leave_duration, from_date, to_date, status 
                FROM leave_requests 
                WHERE user_id = ? 
                ORDER BY created_at DESC";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->bind_param("i", $user_id);
$stmt_history->execute();
$result_history = $stmt_history->get_result();
$leave_history = $result_history->fetch_all(MYSQLI_ASSOC);
$stmt_history->close();


$sql_leave_counts = "SELECT 
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_leaves,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_leaves,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_leaves
    FROM leave_requests 
    WHERE user_id = ?";
$stmt_leave_counts = $conn->prepare($sql_leave_counts);
$stmt_leave_counts->bind_param("i", $user_id);
$stmt_leave_counts->execute();
$result_leave_counts = $stmt_leave_counts->get_result();
$leave_counts = $result_leave_counts->fetch_assoc();
$pending_leaves_count = $leave_counts['pending_leaves'] ?? 0;
$approved_leaves_count = $leave_counts['approved_leaves'] ?? 0;
$rejected_leaves_count = $leave_counts['rejected_leaves'] ?? 0;
$stmt_leave_counts->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Leave Management</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }

        .animate-fadeIn { animation: fadeIn 0.3s ease-in-out; }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }

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

        .status-approved { background-color: #dcfce7; color: #166534; }
        .status-rejected { background-color: #fef2f2; color: #dc2626; }
        .status-pending { background-color: #fef3c7; color: #d97706; }

        .card-hover { transition: transform 0.2s; }
        .card-hover:hover { transform: translateY(-2px); }

        /* Modal Animation */
        .modal-enter {
            opacity: 0;
            transform: scale(0.95);
        }
        .modal-enter-active {
            opacity: 1;
            transform: scale(1);
            transition: all 300ms;
        }
        .modal-exit {
            opacity: 1;
        }
        .modal-exit-active {
            opacity: 0;
            transform: scale(0.95);
            transition: all 300ms;
        }

        /* Mobile bottom navigation */
        .mobile-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .mobile-nav-item {
            flex: 1;
            text-align: center;
            padding: 12px 0;
            color: #4b5563;
            font-size: 0.75rem;
        }

        .mobile-nav-item.active { color: #4f46e5; }
        .mobile-nav-item i { display: block; margin: 0 auto 4px; font-size: 1.25rem; }

        @media (max-width: 768px) {
            .mobile-nav { display: flex; }
            main { padding-bottom: 70px; }
            .sidebar { display: none; }
        }
        @keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}
    </style>
</head>

<body class="bg-gray-100 font-sans antialiased">
    <div class="flex h-screen">
        <!-- Desktop Sidebar -->
        <aside class="sidebar w-64 bg-blue-900 text-white flex-col flex-shrink-0">
            <div class="text-xl font-bold p-5 border-b border-blue-700">IM Leave Management</div>
            <nav class="flex-1 p-4 space-y-2">
                <button class="nav-item block w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition" data-page="dashboard">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </button>
           <button onclick="openHolidayModal()" class="nav-item block w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition flex items-center">
  <i class="fas fa-calendar-day text-lg mr-2"></i>
  <span class="text-sm">Holidays List</span>
</button>
            </nav>
            <div class="p-4 border-t border-blue-700">
                <a href="logout.php" class="w-full block bg-red-600 hover:bg-red-700 py-2 rounded text-center text-white flex items-center justify-center">
                    <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            <div class="container mx-auto px-4 py-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-xl font-bold text-indigo-800">
                           <span class="text-gray-600">Welcome, <?= htmlspecialchars($user_name) ?></span>
                        <p id="currentTime" class="text-green-600">Current Time: --:--:--</p>
                    </h1>
                </div>

                <!-- Dashboard Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 shadow rounded-lg card-hover">
                        <div class="flex items-center">
                            <div class="bg-indigo-100 p-3 rounded-full mr-4">
                                <i class="fas fa-umbrella-beach text-indigo-600 text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-gray-500 text-sm font-medium">Leave Balance</h2>
                                <p class="text-2xl font-bold text-indigo-700"><?= number_format($leave_balance, 1) ?> Days</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 shadow rounded-lg card-hover">
                        <div class="flex items-center">
                            <div class="bg-yellow-100 p-3 rounded-full mr-4">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-gray-500 text-sm font-medium">Pending Leaves</h2>
                                <p class="text-2xl font-bold text-yellow-600"><?= $pending_leaves_count ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 shadow rounded-lg card-hover">
                        <div class="flex items-center">
                            <div class="bg-green-100 p-3 rounded-full mr-4">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-gray-500 text-sm font-medium">Approved Leaves</h2>
                                <p class="text-2xl font-bold text-green-600"><?= $approved_leaves_count ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-6 shadow rounded-lg card-hover">
                        <div class="flex items-center">
                            <div class="bg-red-100 p-3 rounded-full mr-4">
                                <i class="fas fa-times-circle text-red-600 text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-gray-500 text-sm font-medium">Rejected Leaves</h2>
                                <p class="text-2xl font-bold text-red-600"><?= $rejected_leaves_count ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leave Section -->
         <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

    <!-- Leave Request Form -->
    <div class="bg-white p-6 shadow rounded-lg">
        <h2 class="text-xl font-semibold text-indigo-800 mb-6 pb-2 border-b">Request Leave</h2>
        <form id="leaveForm" method="POST" action="" class="space-y-6">

            <!-- Leave Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type</label>
                <select name="leave_type" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                    <option value="">-- Select Leave Type --</option>
                    <option value="Emergency Leave">Emergency Leave</option>
                    <option value="Personal Leave">Personal Leave</option>
                </select>
            </div>

         <!-- Leave Duration -->
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Leave Duration</label>
    <div class="flex space-x-6">
        <label class="inline-flex items-center">
            <input type="radio" name="leave_duration" value="Full Day" class="form-radio h-5 w-5 text-indigo-600" required onclick="toggleLeaveType(false)">
            <span class="ml-2 text-gray-700">Full Day</span>
        </label>
        <label class="inline-flex items-center">
            <input type="radio" name="leave_duration" value="Half Day" class="form-radio h-5 w-5 text-indigo-600" required onclick="toggleLeaveType(true)">
            <span class="ml-2 text-gray-700">Half Day</span>
        </label>
    </div>
</div>
<div id="holidayModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl p-6 relative animate-fadeIn" onclick="event.stopPropagation()">
    <button onclick="closeHolidayModal()" class="absolute top-2 right-2 text-gray-500 hover:text-red-500 text-xl">&times;</button>
    <h2 class="text-xl font-bold mb-4 text-center">Holiday Calendar</h2>
    <iframe src="holiday-calendar.php" class="w-full h-[600px] rounded border" frameborder="0"></iframe>
  </div>
  
<script>
  const modal = document.getElementById('holidayModal');

  // Auto open only if URL contains ?showHoliday=1
  document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('showHoliday') === '1') {
      openHolidayModal();
    }
  });

  function openHolidayModal() {
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    document.addEventListener('keydown', escCloseHandler);
  }

  function closeHolidayModal() {
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    document.removeEventListener('keydown', escCloseHandler);

    // Redirect to dashboard without showHoliday param
    const base = '<?= strtolower($_SESSION["user"]["role"]) ?>-dashboard.php';
    window.location.href = base;
  }

  function escCloseHandler(e) {
    if (e.key === "Escape") closeHolidayModal();
  }

  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeHolidayModal();
  });
</script>
</div>
<!-- Half Day Type -->
<div id="half-day-type" class="hidden">
    <label class="block text-sm font-medium text-gray-700 mb-2">Half Day Type</label>
    <select name="half_day_type" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        <option value="">-- Select Half --</option>
        <option value="First Half">First Half</option>
        <option value="Second Half">Second Half</option>
    </select>
</div>

<!-- Date Inputs -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
        <input type="date" name="from_date" id="from_date" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
    </div>
    <div id="to-date-container">
        <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
        <input type="date" name="to_date" id="to_date" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
    </div>
</div>


            <!-- Reason -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                <textarea name="reason" rows="4" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Briefly explain the reason for your leave..." required></textarea>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition duration-300 flex items-center justify-center">
                <i class="fas fa-paper-plane mr-2"></i> Submit Leave Request
            </button>
        </form>
    </div>

    <!-- Leave History -->
    <div class="bg-white p-6 shadow rounded-lg">
        <h2 class="text-xl font-semibold text-indigo-800 mb-4 border-b pb-2">
            <i class="fas fa-history mr-2"></i> Leave History
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full table-auto text-sm text-gray-700">
                <thead class="bg-blue-50 text-blue-800">
                    <tr>
                        <th class="px-4 py-3 font-medium">Leave Type</th>
                        <th class="px-4 py-3 font-medium">Duration</th>
                        <th class="px-4 py-3 font-medium">From</th>
                        <th class="px-4 py-3 font-medium">To</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($leave_history as $leave): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2"><?= htmlspecialchars($leave['leave_type']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($leave['leave_duration']) ?></td>
                            <td class="px-4 py-2"><?= date('d-m-Y', strtotime($leave['from_date'])) ?></td>
                            <td class="px-4 py-2"><?= date('d-m-Y', strtotime($leave['to_date'])) ?></td>
                            <td class="px-4 py-2">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded 
                                    <?= $leave['status'] === 'approved' ? 'bg-green-100 text-green-700' : 
                                       ($leave['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                                       'bg-red-100 text-red-700') ?>">
                                    <?= ucfirst($leave['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

            </div>
        </main>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="modal-overlay absolute inset-0 bg-black opacity-50"></div>
        <div class="modal-container bg-white w-11/12 md:max-w-md mx-auto rounded shadow-lg z-50 overflow-y-auto animate-fadeIn">
            <div class="modal-content py-4 text-left px-6">
                <div class="flex justify-between items-center pb-3">
                    <p class="text-2xl font-bold text-green-600">Success!</p>
                    <button onclick="closeModal()" class="modal-close cursor-pointer z-50">
                        <svg class="fill-current text-black" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                            <path d="M14.53 4.53l-1.06-1.06L9 7.94 4.53 3.47 3.47 4.53 7.94 9l-4.47 4.47 1.06 1.06L9 10.06l4.47 4.47 1.06-1.06L10.06 9z"></path>
                        </svg>
                    </button>
                </div>
                <div class="mb-4">
                    <div class="flex items-center justify-center mb-4">
                        <svg class="w-16 h-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <p class="text-gray-700 text-center text-lg">Your leave request has been submitted successfully!</p>
                </div>
                <div class="flex justify-center pt-2">
                    <button onclick="closeModal()" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition duration-300">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Header -->
    <div class="md:hidden px-4 py-3 bg-white border-b shadow-sm flex justify-between items-center">
        <div class="flex items-center space-x-2">
            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                <i class="fas fa-user text-indigo-600"></i>
            </div>
            <span class="text-sm text-gray-700 font-medium"><?= htmlspecialchars($user_name) ?></span>
        </div>
        <a href="logout.php" class="text-red-500 text-xs font-medium flex items-center space-x-1">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <!-- Mobile Bottom Navigation -->
<nav class="mobile-nav flex justify-around bg-white border-t border-gray-200 shadow md:hidden py-2">

  <!-- Dashboard -->
  <a href="#" class="mobile-nav-item flex flex-col items-center text-sm text-gray-700 active" data-page="dashboard">
    <i class="fas fa-tachometer-alt text-lg"></i>
    <span class="text-xs">Dashboard</span>
  </a>

  <!-- History -->
  <a href="#" class="mobile-nav-item flex flex-col items-center text-sm text-gray-700" data-page="history">
    <i class="fas fa-history text-lg"></i>
    <span class="text-xs">History</span>
  </a>

  <!-- Holidays List -->
  <a href="#" onclick="openHolidayModal()" class="mobile-nav-item flex flex-col items-center text-sm text-gray-700">
    <i class="fas fa-calendar-day text-lg mr-1"></i>
    <span class="text-xs">Holidays</span>
  </a>

  <!-- Logout -->
  <a href="logout.php" class="mobile-nav-item flex flex-col items-center text-sm text-red-600">
    <i class="fas fa-sign-out-alt text-lg"></i>
    <span class="text-xs">Logout</span>
  </a>

</nav>

    
<!-- ✅ Modal Container -->
<div id="customModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
  <div class="modal-box bg-white rounded-lg shadow-lg p-6 max-w-md w-full relative animate-fadeIn">
    <button onclick="closeCustomModal()" class="absolute top-2 right-2 text-gray-600 hover:text-red-600">
      <i class="fas fa-times"></i>
    </button>
    <div class="text-center">
      <h2 class="text-xl font-semibold text-green-600 mb-3">🎉 Action Successful</h2>
      <p class="text-gray-700">Your action was completed successfully!</p>
      <div class="mt-4">
        <button onclick="closeCustomModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
          OK
        </button>
      </div>
    </div>
  </div>
</div>

<!-- JS Dependencies -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
 

    // ✅ Toggle Leave Type Visibility
    function toggleLeaveType(isHalfDay) {
        const halfDayField = document.getElementById("half-day-type");
        const toDateField = document.getElementById("to-date-container");
        const toDateInput = document.getElementById("to_date");

        if (isHalfDay) {
            halfDayField.classList.remove("hidden");
            toDateField.classList.add("hidden");
            toDateInput.removeAttribute("required");
            toDateInput.value = fromInput?.value || '';  // ✅ Set to_date = from_date if Half Day
        } else {
            halfDayField.classList.add("hidden");
            toDateField.classList.remove("hidden");
            toDateInput.setAttribute("required", "required");
        }
    }

    // ✅ DOM Ready Logic
    document.addEventListener("DOMContentLoaded", function () {
        fromInput = document.getElementById("from_date");
        toInput = document.getElementById("to_date");

        const today = new Date().toISOString().split("T")[0];
        fromInput.setAttribute("min", today);
        toInput.setAttribute("min", today);

        fromInput.addEventListener("change", function () {
            toInput.setAttribute("min", this.value);

            const selectedDuration = document.querySelector("input[name='leave_duration']:checked");
            if (selectedDuration && selectedDuration.value === "Half Day") {
                toInput.value = this.value; // Sync to_date for half-day
            }
        });

        <?php if (isset($_SESSION['toast']) && $_SESSION['toast']['type'] === 'success'): ?>
            showModal();
        <?php endif; ?>
    });

function showModal() {
    document.getElementById('successModal')?.classList.remove('hidden');
    
    // ✅ Reset the form
    document.getElementById("leaveForm").reset();
    toggleLeaveType(false); // Hide half-day field and show to_date again

    setTimeout(closeModal, 5000);
}

    function closeModal() {
        document.getElementById('successModal')?.classList.add('hidden');
    }

    // ✅ Form Validation
    document.getElementById("leaveForm").addEventListener("submit", function (e) {
        const leaveType = document.querySelector("select[name='leave_type']").value;
        const leaveDuration = document.querySelector("input[name='leave_duration']:checked");
        const fromDate = fromInput.value;
        const reason = document.querySelector("textarea[name='reason']").value.trim();

        if (!leaveType || !leaveDuration || !fromDate || !reason) {
            e.preventDefault();
            toastr.error("Please fill in all required fields.");
            return;
        }

        if (leaveDuration.value === "Half Day") {
            const halfDayType = document.querySelector("select[name='half_day_type']").value;
            if (!halfDayType) {
                e.preventDefault();
                toastr.error("Please select half day type.");
                return;
            }
        }

        if (leaveDuration.value === "Full Day") {
            const toDate = toInput.value;
            if (toDate && new Date(fromDate) > new Date(toDate)) {
                e.preventDefault();
                toastr.error("From date cannot be later than To date.");
                return;
            }
        }
    });

    // ✅ Mobile Nav Active Toggle
    document.querySelectorAll(".mobile-nav-item").forEach(item => {
        item.addEventListener("click", function () {
            document.querySelectorAll(".mobile-nav-item").forEach(nav => nav.classList.remove("active"));
            this.classList.add("active");
        });
    });

    // ✅ Clock Display
    function updateTime() {
        const now = new Date();
        document.getElementById("currentTime").textContent = "🕒 Current Time: " + now.toLocaleTimeString();
    }

    setInterval(updateTime, 1000);
    updateTime(); 
    


</script>

<script>
// Show modal
function openCustomModal() {
    document.getElementById("customModal").classList.remove("hidden");
}

// Hide modal
function closeCustomModal() {
    document.getElementById("customModal").classList.add("hidden");
}

// Close modal on outside click
document.addEventListener("click", function (e) {
    const modal = document.getElementById("customModal");
    const box = document.querySelector(".modal-box");
    if (!modal.classList.contains("hidden") && !box.contains(e.target)) {
        closeCustomModal();
    }
});

// Close modal on ESC key
document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
        closeCustomModal();
    }
});
</script>





</body>
</html>

