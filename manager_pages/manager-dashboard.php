<?php
session_start();
$_SESSION['success'] = "This is a test success message";

$user = $_SESSION['user'];
$user_id = $user['id'];
$user_email = $user['email'];
$user_name = $user['name'];
$role = strtolower($user['role']);
if ($role !== 'manager') {
    header("Location: login.php"); // redirect to login page
    exit();
}
require_once('../connecting_fIle/config.php');

$user_id = $_SESSION['user']['id'];
$holidayResult = $conn->query("SELECT holiday_date FROM holiday");
$holidays_from_db = [];
while ($row = $holidayResult->fetch_assoc()) {
    $holidays_from_db[] = $row['holiday_date'];
}

$holidayResult = $conn->query("SELECT holiday_date FROM holiday");
$holidays_from_db = [];
while ($row = $holidayResult->fetch_assoc()) {
    $holidays_from_db[] = $row['holiday_date'];
}


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
    <title>Manager Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.2.0/fullcalendar.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
// Toastr configuration
toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};
</script>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .animate-fadeIn { animation: fadeIn 0.3s ease-in-out; }
        
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
        
        #reportingManagerDiv { transition: all 0.3s ease-in-out; }
        
        .status-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
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
            color: #64748b;
        }

        .mobile-nav-item.active {
            color: #3b82f6;
        }

        .mobile-nav-item i {
            display: block;
            margin: 0 auto 4px;
            font-size: 20px;
        }

        .mobile-nav-item span {
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .mobile-nav {
                display: flex;
            }
            
            aside {
                display: none;
            }
            
            main {
                margin-bottom: 60px;
            }
        }
    </style>
</head>

<body class="bg-gray-100 font-sans antialiased">
    <div class="flex h-screen">
        <!-- Sidebar - Desktop -->
        <aside class="hidden md:block w-64 bg-blue-900 text-white flex flex-col flex-shrink-0">
            <div class="text-2xl font-bold p-6 border-b border-blue-700">Leave Management</div>
            <nav class="flex-1 p-4 space-y-2">
                <button class="nav-item w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition-colors duration-200 bg-blue-800" data-page="dashboard">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </button>
                <button class="nav-item w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition-colors duration-200" data-page="leaves">
                    <i class="fas fa-calendar-minus mr-2"></i> Leaves
                </button>
              <button onclick="openHolidayModal()" class="nav-item block w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition">
  <i class="fas fa-calendar-alt mr-2"></i>
  <span class="text-m">Holidays</span>
</button>
            </nav>
            <div class="p-4 border-t border-blue-700">
                <a href="logout.php" class="w-full block bg-red-600 hover:bg-red-700 py-2 rounded transition-colors duration-200 text-center text-white flex items-center justify-center">
                    <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto" id="main-content">
            <!-- Dashboard Section -->
            <section id="dashboard" class="p-4">
                <h2 class="text-xl font-bold text-gray-800 mb-1">
                    Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>
                </h2>
                <p class="text-gray-600 mb-3">
                    Login Time: <?php echo date('d-m-Y'); ?>
                </p>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                    <!-- Card 1: Pending Leaves -->
                    <div class="bg-white p-3 rounded shadow hover:shadow-md transition">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-blue-100 text-blue-600 mr-3">
                                <i class="fas fa-calendar-alt text-base"></i>
                            </div>
                            <div>
                                <?php
                                
$stmt = $conn->prepare("
    SELECT COUNT(*) AS pending 
    FROM leave_requests 
    WHERE status = 'pending' 
      AND user_id IN (SELECT id FROM users WHERE manager_id = ?)
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_approvals_count = $stmt->get_result()->fetch_assoc()['pending'] ?? 0;
$stmt->close();
?>
                                
                               
                                <h3 class="text-sm text-gray-700 font-medium">Pending Leaves</h3>
  <p class="text-lg font-bold text-blue-600">
    <?php echo $pending_approvals_count; ?>
  </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Approved Leaves -->
                    <div class="bg-white p-3 rounded shadow hover:shadow-md transition">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-green-100 text-green-600 mr-3">
                                <i class="fas fa-check-circle text-base"></i>
                            </div>
                            <div>
                               <?php
$stmt = $conn->prepare("
    SELECT COUNT(*) AS approved 
    FROM leave_requests 
    WHERE status = 'approved' 
      AND user_id IN (SELECT id FROM users WHERE manager_id = ?)
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$approved_leaves_count = $stmt->get_result()->fetch_assoc()['approved'] ?? 0;
$stmt->close();
?>

                                
                                  <h3 class="text-sm text-gray-700 font-medium">Approved Leaves</h3>
    <p class="text-lg font-bold text-green-600">
        <?php echo $approved_leaves_count; ?>
    </p>
                            </div>
                        </div>
                    </div>

                
                    <div class="bg-white p-3 rounded shadow hover:shadow-md transition">
  <div class="flex items-center justify-between">
    <div class="flex items-center">
      <div class="p-2 rounded-full bg-purple-100 text-purple-600 mr-3">
        <i class="fas fa-users text-base"></i>
      </div>
      <div>
        <h3 class="text-sm text-gray-700 font-medium">Total Team Members</h3>
        <p class="text-lg font-bold text-purple-600">
          <?php
            $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE manager_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $team_members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $total_employees_count = count($team_members);
            $stmt->close();
            echo $total_employees_count;
          ?>
        </p>
      </div>
    </div>
    <button onclick="toggleTeamModal()" class="text-sm text-blue-600 hover:underline">View Team</button>
  </div>
</div>

                </div>
<div class="bg-white rounded-lg mb-1 shadow-md overflow-hidden mt-6">
    <div class="bg-white rounded-lg mb-1 shadow-md overflow-hidden mt-6">
        <div class="overflow-x-auto">
            <table class="w-full table-auto">
                <thead class="bg-blue-50">
                <tr class="text-left text-sm text-blue-800">
                    <th class="px-6 py-3 font-medium">Name</th>
                    <th class="px-6 py-3 font-medium">Email</th>
                    <th class="px-6 py-3 font-medium">Team</th>
                    <th class="px-6 py-3 font-medium">Role</th>
                    <th class="px-6 py-3 font-medium text-right whitespace-nowrap">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm" id="userTableBody">
<?php
$stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.role FROM users u WHERE u.manager_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$users_result = $stmt->get_result();

while ($user = $users_result->fetch_assoc()):
    $leave_stmt = $conn->prepare("
        SELECT * FROM leave_requests 
        WHERE user_id = ? AND status = 'pending' AND approval_level = 0
        ORDER BY created_at DESC
    ");
    $leave_stmt->bind_param("i", $user['id']);
    $leave_stmt->execute();
    $leaves = $leave_stmt->get_result();

    if ($leaves->num_rows > 0):
?>
    <tr class="bg-white">
        <td class="px-6 py-4 font-medium text-gray-800"><?= htmlspecialchars($user['name']) ?></td>
        <td class="px-6 py-4"><?= htmlspecialchars($user['email']) ?></td>
        <td class="px-6 py-4">Team <?= htmlspecialchars($user['id']) ?></td>
        <td class="px-6 py-4 capitalize"><?= htmlspecialchars($user['role']) ?></td>
        <td class="px-6 py-4 text-right">Pending Leaves: <?= $leaves->num_rows ?></td>
    </tr>
    <?php while ($leave = $leaves->fetch_assoc()): 
        $from_date = $leave['from_date'];
        $to_date = $leave['to_date'];
        $leave_duration = strtolower(trim($leave['leave_duration']));
        $half_day_type = strtolower(trim($leave['half_day_type'] ?? ''));
        $holidays = [
            '2025-01-01', '2025-01-14', '2025-01-26', '2025-02-26',
            '2025-03-14', '2025-04-18', '2025-05-01', '2025-08-15',
            '2025-08-27', '2025-10-02', '2025-10-21', '2025-10-22', '2025-12-25'
        ];

        $is_sandwich = false;
        $from_day = date('N', strtotime($from_date)); // 1=Mon, 5=Fri
        $to_day = date('N', strtotime($to_date));
        $days_diff = (strtotime($to_date) - strtotime($from_date)) / 86400;

        // Sandwich for Friday 2nd half to Monday 1st half (single day, half-day)
        $is_sandwich_half_day = (
            $from_date === $to_date &&
            $leave_duration === 'half day' &&
            $from_day == 5 && $half_day_type === 'second half'
        ) || (
            $from_date === $to_date &&
            $leave_duration === 'half day' &&
            $to_day == 1 && $half_day_type === 'first half'
        );

        // Sandwich for continuous Friday to Monday (full days)
        $is_sandwich_full = (
            $from_day == 5 && $to_day == 1 &&
            $days_diff == 3 // Must be continuous (Fri, Sat, Sun, Mon)
        );

        if ($is_sandwich_half_day || $is_sandwich_full) {
            $is_sandwich = true;
        }
    ?>
    <tr class="bg-gray-50 <?= $is_sandwich ? 'border-l-4 border-red-600' : '' ?>">
        <td colspan="5" class="px-6 pb-4 pt-1">
            <div class="border p-4 rounded-md shadow-sm <?= $is_sandwich ? 'bg-red-50' : '' ?>">
                <div class="flex justify-between items-center">
                    <div>
                        <p><strong>Type:</strong> <?= htmlspecialchars($leave['leave_type']) ?></p>
                        <p><strong>Duration:</strong> <?= htmlspecialchars($leave['leave_duration']) ?></p>
                        <p><strong>From:</strong> <?= date('d-m-Y', strtotime($leave['from_date'])) ?> 
                           <strong>To:</strong> <?= date('d-m-Y', strtotime($leave['to_date'])) ?></p>
                        <p><strong>Reason:</strong> <?= htmlspecialchars($leave['reason']) ?></p>
                        <?php if ($is_sandwich): ?>
                        <p class="mt-2 text-sm font-semibold text-red-600">
                           Sandwich Leave Detected (continuous Fri-Mon or Fri 2nd half to Mon 1st half)
                        </p>
                        <?php endif; ?>
                    </div>
                   <form action="leave_update.php" method="post" class="mt-3 flex gap-3">
        <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
        <button type="submit" name="action" value="approve" class="bg-green-600 text-white px-4 py-1 rounded">Approve</button>
        <button type="submit" name="action" value="reject" class="bg-red-600 text-white px-4 py-1 rounded">Reject</button>
      </form>
                </div>
            </div>
        </td>
    </tr>
    <?php endwhile; ?>
<?php 
    endif;
    $leave_stmt->close();
endwhile;
$stmt->close();
?>
                </tbody>
            </table>
        </div>
    </div>
</div>





                <!-- Calendar Section -->
             <div class="grid grid-cols-1 gap-3">
    <div class="bg-white p-3 rounded shadow w-full">
        <h3 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
            <i class="fas fa-calendar-alt mr-2 text-blue-600"></i> Team Leave Calendar
        </h3>

        <?php
  $calendarEvents = [];

$stmt = $conn->prepare("
    SELECT u.name, lr.from_date, lr.to_date, lr.status 
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    WHERE u.manager_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Set color based on status
    $color = '#22c55e'; // green for approved
    if ($row['status'] === 'rejected') {
        $color = '#ef4444'; // red for rejected
    } elseif ($row['status'] === 'pending') {
        $color = '#facc15'; // yellow for pending
    }

    $calendarEvents[] = [
        'title' => $row['name'] . ' (' . ucfirst($row['status']) . ')',
        'start' => $row['from_date'],
        'end'   => date('Y-m-d', strtotime($row['to_date'] . ' +1 day')),
        'color' => $color,
        'allDay' => true
    ];
}
$stmt->close();

        ?>

        <div id="calendar" class="text-xs w-full min-h-[300px]"></div>

    <script>
$(function () {
    const calendarEvents = <?= json_encode($calendarEvents) ?>;

    $('#calendar').fullCalendar({
        height: 500,
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month'
        },
        defaultDate: moment().format('YYYY-MM-DD'),
        events: calendarEvents,
        displayEventTime: false
    });
});
</script>
    </div>
</div>

            </section>

            <!-- Leaves Section -->
    <section id="leaves" class="p-6 hidden">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Your Dashboard</h2>
    </div>
<?php
$user_id = $_SESSION['user']['id'];

// Get leave balance
$stmt = $conn->prepare("SELECT leave_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$leave_balance = $result->fetch_assoc()['leave_balance'] ?? 0;
$stmt->close();

// Get counts by status
$statuses = ['pending', 'approved', 'rejected'];
$counts = [];

foreach ($statuses as $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM leave_requests WHERE status = ? AND user_id = ?");
    $stmt->bind_param("si", $status, $user_id);
    $stmt->execute();
    $counts[$status] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $stmt->close();
}
?>

  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8">
  <!-- Leave Balance -->
  <div class="bg-white rounded-lg shadow p-4 flex items-center space-x-4">
    <div class="bg-indigo-100 p-3 rounded-full">
      <i class="fas fa-umbrella-beach text-indigo-600 text-xl"></i>
    </div>
    <div>
      <h4 class="text-sm text-gray-500 font-medium">Leave Balance</h4>
      <p class="text-2xl font-bold text-indigo-700"><?php echo number_format($leave_balance, 1); ?> Days</p>
    </div>
  </div>

  <!-- Pending Leaves -->
  <div class="bg-white rounded-lg shadow p-4 flex items-center space-x-4">
    <div class="bg-yellow-100 p-3 rounded-full">
      <i class="fas fa-clock text-yellow-600 text-xl"></i>
    </div>
    <div>
      <h4 class="text-sm text-gray-500 font-medium">Pending Leaves</h4>
      <p class="text-2xl font-bold text-yellow-600"><?php echo $counts['pending']; ?></p>
    </div>
  </div>

  <!-- Approved Leaves -->
  <div class="bg-white rounded-lg shadow p-4 flex items-center space-x-4">
    <div class="bg-green-100 p-3 rounded-full">
      <i class="fas fa-check-circle text-green-600 text-xl"></i>
    </div>
    <div>
      <h4 class="text-sm text-gray-500 font-medium">Approved Leaves</h4>
      <p class="text-2xl font-bold text-green-600"><?php echo $counts['approved']; ?></p>
    </div>
  </div>

  <!-- Rejected Leaves -->
  <div class="bg-white rounded-lg shadow p-4 flex items-center space-x-4">
    <div class="bg-red-100 p-3 rounded-full">
      <i class="fas fa-times-circle text-red-600 text-xl"></i>
    </div>
    <div>
      <h4 class="text-sm text-gray-500 font-medium">Rejected Leaves</h4>
      <p class="text-2xl font-bold text-red-600"><?php echo $counts['rejected']; ?></p>
    </div>
  </div>
</div>


    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white p-6 shadow rounded-lg">
            <h2 class="text-xl font-semibold text-indigo-800 mb-6 pb-2 border-b">Request Leave</h2>
            <form id="leaveForm" method="POST" action="leave_update.php" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type</label>
                    <select name="leave_type" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                        <option value="">-- Select Leave Type --</option>
                        <option value="Emergency Leave">Emergency Leave</option>
                        <option value="Personal Leave">Personal Leave</option>
                    </select>
                </div>
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
                <div id="half-day-type" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Half Day Type</label>
                    <select name="half_day_type" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Select Half --</option>
                        <option value="First Half">First Half</option>
                        <option value="Second Half">Second Half</option>
                    </select>
                </div>
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                    <textarea name="reason" rows="4" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required></textarea>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition duration-300 flex items-center justify-center">
                    <i class="fas fa-paper-plane mr-2"></i> Submit Leave Request
                </button>
            </form>
        </div>

        <div class="bg-white p-6 shadow rounded-lg">
    <h2 class="text-xl font-semibold text-indigo-800 mb-4 border-b pb-2">
        <i class="fas fa-history mr-2"></i> Leave History
    </h2>

    <?php
    $stmt = $conn->prepare("SELECT leave_type, leave_duration, from_date, to_date, half_day_type, status FROM leave_requests WHERE user_id = ? ORDER BY from_date DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $leave_history = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    ?>

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
                        <td class="px-4 py-2">
                            <?= !empty($leave['from_date']) && $leave['from_date'] !== '0000-00-00'
                                ? date('d-m-Y', strtotime($leave['from_date']))
                                : '—' ?>
                        </td>
                        <td class="px-4 py-2">
                            <?php
                            $isHalfDay = strtolower($leave['leave_duration']) === 'half day';
                            $toDate = $leave['to_date'] ?? '';
                            $validToDate = (!empty($toDate) && $toDate !== '0000-00-00' && strtotime($toDate));

                            if ($isHalfDay || !$validToDate) {
                                echo htmlspecialchars($leave['half_day_type'] ?? '—');
                            } else {
                                echo date('d-m-Y', strtotime($toDate));
                            }
                            ?>
                        </td>
                        <td class="px-4 py-2">
                            <?php
                            $status = strtolower($leave['status']);
                            $badgeColor = match ($status) {
                                'approved' => 'bg-green-100 text-green-700',
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-700'
                            };
                            ?>
                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded <?= $badgeColor ?>">
                                <?= ucfirst($status) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

    </div>
</section>

        </main>
    </div>
    <!-- Success Modal -->



    <!-- Mobile Bottom Navigation -->
    <div class="mobile-nav">
        <button class="mobile-nav-item active" data-page="dashboard">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </button>
        <button class="mobile-nav-item" data-page="leaves">
            <i class="fas fa-calendar-minus"></i>
            <span>Leaves</span>
        </button>
        <a href="logout.php" class="mobile-nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

    <!-- Leave History Modal -->
<div id="leaveModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
    <div class="flex justify-between items-center mb-4 pb-2 border-b">
      <h2 class="text-xl font-semibold text-indigo-800">Leave History</h2>
      <button onclick="toggleModal()" class="text-gray-500 hover:text-red-600 text-xl font-bold">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="overflow-x-auto flex-grow">
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-indigo-100">
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Type</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">From</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">To</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if (empty($leaves)): ?>
            <tr>
              <td class="px-4 py-4 text-center text-gray-500" colspan="4">No leave history found.</td>
            </tr>
          <?php else: ?>
            <?php
              // Get the last leave entry (assuming leaves are sorted by most recent first)
              $lastLeaveId = end($leaves)['id'] ?? 0;
            ?>
            <?php foreach ($leaves as $leave): ?>
              <?php
                $status = strtolower($leave['status'] ?? '');
                $statusClass = match ($status) {
                  'approved' => 'bg-green-100 text-green-700',
                  'rejected' => 'bg-red-100 text-red-700',
                  default => 'bg-yellow-100 text-yellow-700',
                };
                $statusIcon = match ($status) {
                  'approved' => 'fas fa-check-circle',
                  'rejected' => 'fas fa-times-circle',
                  default => 'fas fa-clock',
                };

                // Show cancel button only for the last leave and if it's pending
                $canCancel = ($status === 'pending' && ($leave['id'] ?? 0) === $lastLeaveId);
              ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= htmlspecialchars($leave['leave_type'] ?? '') ?></td>
                <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($leave['from_date'] ?? '') ?></td>
                <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($leave['to_date'] ?? '') ?></td>
                <td class="px-4 py-3">
                  <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
                      <i class="<?= $statusIcon ?> mr-1"></i> <?= ucfirst($status) ?>
                    </span>
                    <?php if ($canCancel): ?>
                      <form method="POST" action="cancel_leave.php" onsubmit="return confirm('Are you sure you want to cancel this leave?');">
                        <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                        <button type="submit" class="px-2 py-0.5 bg-red-500 hover:bg-red-600 text-white text-xs rounded-md">Cancel</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


<div id="teamModal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center hidden">
  <div class="bg-white w-full max-w-md rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold text-indigo-700">Team Members</h3>
      <button onclick="toggleTeamModal()" class="text-gray-600 hover:text-red-600 text-lg font-bold">&times;</button>
    </div>
    <ul class="divide-y divide-gray-200 text-sm">
      <?php if (!empty($team_members)): ?>
        <?php foreach ($team_members as $member): ?>
          <li class="py-2">
            <div class="font-medium text-gray-800"><?= htmlspecialchars($member['name']) ?></div>
            <div class="text-gray-500"><?= htmlspecialchars($member['email']) ?></div>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li class="py-2 text-gray-500">No team members found.</li>
      <?php endif; ?>
    </ul>
  </div>
</div>


<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
  <div class="bg-white px-6 py-6 rounded-lg shadow-xl w-full max-w-md text-center">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold text-green-600">Success!</h3>
      <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
    </div>
    <div class="flex flex-col items-center space-y-4">
      <svg class="w-16 h-16 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
      </svg>
      <p class="text-gray-700 text-base">
        Your leave request has been submitted successfully!<br>
        It will be reviewed shortly.
      </p>
      <button onclick="closeModal()" class="mt-4 px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
        OK
      </button>
    </div>
  </div>
  

</div>

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
$today = new DateTime(); // Current date

foreach ($holidays_from_db ?? [] as $holiday) {
    $date = new DateTime($holiday);
    $day = $date->format('l'); // Full day name (e.g., Monday)
    $formattedDate = $date->format('d-m-Y'); // Format date as DD-MM-YYYY

    // Determine if the date is past or upcoming
    if ($date < $today) {
        $rowClass = 'bg-red-100 text-red-700'; // Past holiday: red
    } else {
        $rowClass = 'bg-green-100 text-green-700'; // Future holiday: green
    }

    echo "<tr class='hover:bg-gray-50 $rowClass'>
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
  // Hide the modal
  document.getElementById('holidayModal').classList.add('hidden');
  
  // Redirect to the "dashboard" tab after closing
  const dashboardBtn = document.querySelector('button[data-page="dashboard"]');
  if (dashboardBtn) {
    dashboardBtn.click(); // simulate a click on the dashboard tab button
  }
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.2.0/fullcalendar.min.js"></script>

<script>
$(function () {
    // ✅ Initialize Calendar
    $('#calendar').fullCalendar({
        height: 300,
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month'
        },
        defaultDate: moment().format('YYYY-MM-DD'),
        events: <?php echo json_encode($calendarEvents); ?>
    });

    // ✅ Page Toggle (Desktop)
    $('.nav-item').on('click', function () {
        $('.nav-item').removeClass('bg-blue-800');
        $(this).addClass('bg-blue-800');
        const page = $(this).data('page');
        $('#main-content > section').addClass('hidden');
        $(`#${page}`).removeClass('hidden');
    });

    // ✅ Page Toggle (Mobile)
    $('.mobile-nav-item').on('click', function (e) {
        if ($(this).attr('href')) return;
        $('.mobile-nav-item').removeClass('active');
        $(this).addClass('active');
        const page = $(this).data('page');
        $('#main-content > section').addClass('hidden');
        $(`#${page}`).removeClass('hidden');
    });

    // ✅ Toastr Settings
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": true,
        "timeOut": "5000"
    };

  
function toggleTeamModal() {
  const modal = document.getElementById('teamModal');
  if (modal) modal.classList.toggle('hidden');
}


function closeModal() {
  const modal = document.getElementById('successModal');
  if (modal) modal.classList.add('hidden');
}

  
    <?php if (isset($_SESSION['success'])): ?>
        toastr.success("<?= addslashes($_SESSION['success']) ?>");
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        toastr.error("<?= addslashes($_SESSION['error']) ?>");
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['toast']) && $_SESSION['toast']['type'] === 'success'): ?>
        showModal();
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    // ✅ Auto Date Fill
    const today = new Date().toISOString().split('T')[0];
    $('#from_date, #to_date').val(today).attr('min', today);
    $('#from_date').on('change', function () {
        $('#to_date').attr('min', this.value);
    });

    // ✅ Default Section on Mobile
    if (window.innerWidth <= 768) {
        $('#dashboard').removeClass('hidden');
        $('#leaves').addClass('hidden');
    }

    // ✅ Leave Form Validation
    $('#leaveForm').on('submit', function (e) {
        const leaveType = $('select[name="leave_type"]').val();
        const leaveDuration = $('input[name="leave_duration"]:checked').val();
        const fromDate = $('#from_date').val();
        const reason = $('textarea[name="reason"]').val().trim();

        if (!leaveType || !leaveDuration || !fromDate || !reason) {
            e.preventDefault();
            toastr.error('Please fill in all required fields.');
            return false;
        }

        if (leaveDuration === 'Half Day') {
            const halfDayType = $('select[name="half_day_type"]').val();
            if (!halfDayType) {
                e.preventDefault();
                toastr.error('Please select half day type.');
                return false;
            }
        }

        if (leaveDuration === 'Full Day') {
            const toDate = $('#to_date').val();
            if (toDate && new Date(fromDate) > new Date(toDate)) {
                e.preventDefault();
                toastr.error('From date cannot be later than to date.');
                return false;
            }
        }
    });

    // ✅ Time Display
    function updateTime() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString();
        $('#currentTime').text('⏰ Current Time: ' + timeStr);
    }
    setInterval(updateTime, 1000);
    updateTime();
});

function toggleModal() {
    document.getElementById("leaveModal").classList.toggle("hidden");
}

function toggleTeamModal() {
    document.getElementById("teamModal").classList.toggle("hidden");
}

function showModal() {
    const modal = document.getElementById('successModal');
    modal.classList.remove('hidden');
    setTimeout(closeModal, 5000); // Auto-close after 5 seconds
}

function closeModal() {
    const modal = document.getElementById('successModal');
    modal.classList.add('hidden');
}

// ✅ Toggle Half-Day Type
function toggleLeaveType(isHalfDay) {
    $('#half-day-type').toggleClass('hidden', !isHalfDay);
    $('#to-date-container').toggleClass('hidden', isHalfDay);
    const toDateInput = document.getElementById('to_date');

    if (isHalfDay) {
        toDateInput.removeAttribute('required');
        toDateInput.value = '';
        document.querySelector('select[name="half_day_type"]').value = "First Half";
    } else {
        toDateInput.setAttribute('required', 'required');
    }
}
</script>

</body>
</html>