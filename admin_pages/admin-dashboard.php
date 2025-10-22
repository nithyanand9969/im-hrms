<?php
session_start();
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header("Location: ../auth/login.php?error=" . urlencode("Session expired. Please log in again."));
    exit();
}

require_once '../connecting_fIle/config.php';

$user = $_SESSION['user'];
$user_id = $user['id'] ?? null;
$user_email = strtolower($user['email'] ?? '');
$user_name = $user['name'] ?? '';
$user_role = strtolower($user['role'] ?? '');

if ($user_role !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// --- Gather holidays ---
$holidays = [];
$holidayQuery = $conn->query("SELECT holiday_date FROM holiday");
while ($row = $holidayQuery->fetch_assoc()) {
    $holidays[] = $row['holiday_date'];
}

// --- Get leave rows for mobile cards (full list for count/mobile view) ---
$is_rachna = ($user_email === 'rachna@integritymatters.in');
$query = "
  SELECT lr.*, u.name, u.email, u.role
  FROM leave_requests lr
  JOIN users u ON lr.user_id = u.id
  WHERE lr.status = 'pending'
";
if ($is_rachna) {
    $query .= " AND u.email != 'rachna@integritymatters.in' AND lr.approval_level_2 = 0";
} else {
    $query .= " AND lr.approval_level = 0";
}
$query .= " ORDER BY lr.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$leave_rows = [];
while ($row = $result->fetch_assoc()) {
    $leave_rows[] = $row;
}
$stmt->close();

// --- Pagination for desktop table ---
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$totalQuery = $conn->query("SELECT COUNT(*) as total FROM leave_requests WHERE status='pending'");
$totalRows = $totalQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$sql = "SELECT lr.*, u.name, u.email FROM leave_requests lr JOIN users u ON lr.user_id = u.id WHERE lr.status='pending' ORDER BY lr.from_date DESC LIMIT $limit OFFSET $offset";
$tableResult = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Welcome</title>
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.2.0/fullcalendar.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95);} to { opacity: 1; transform: scale(1);} }
        @keyframes pulse { 0%,100% {opacity: 1;} 50% {opacity: .5;} }
        .animate-fadeIn {animation: fadeIn 0.3s ease-in-out;}
        .animate-pulse {animation: pulse 2s cubic-bezier(0.4,0,0.6,1) infinite;}
        .form-input { width:100%; padding:10px 16px; border-radius:8px; border:1px solid #d1d5db; transition:all 0.2s;}
        .form-input:focus {outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.1);}
        .form-input:invalid {border-color:#ef4444;}
        .form-input:valid { border-color:#10b981;}
        #reportingManagerDiv {transition: all 0.3s ease-in-out;}
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="hidden md:block lg:block w-64 bg-blue-900 text-white flex-col flex-shrink-0">
            <div class="text-2xl font-bold p-6 border-b border-blue-700">IM</div>
            <nav class="flex-1 p-4 space-y-2">
                <button class="nav-item block w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition" data-page="dashboard">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </button>
                <button class="nav-item block w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition" data-page="manage-users">
                    <i class="fas fa-users mr-2"></i>Manage Users
                </button>
                <button class="nav-item block w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition" data-page="leaves">
                    <i class="fas fa-calendar-minus mr-2"></i>Leaves
                </button>
                <button class="nav-item block w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition" data-page="holidays">
                    <i class="fas fa-calendar-minus mr-2"></i>Holidays
                </button>
                <button class="nav-item block w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition" data-page="user-activity">
                    <i class="fas fa-calendar-minus mr-2"></i>User Activity
                </button>
            </nav>
            <div class="p-4 border-t border-blue-700">
                <a href="logout.php" class="w-full block bg-red-600 hover:bg-red-700 py-2 rounded text-center text-white flex items-center justify-center">
                    <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
                </a>
            </div>
        </aside>

        <!-- Bottom Nav: Mobile only -->
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
                <span class="text-xs">Leaves</span>
            </button>
            <button class="nav-item flex flex-col items-center text-sm text-gray-700" data-page="holidays">
                <i class="fas fa-calendar-day text-lg"></i>
                <span class="text-xs">Holidays</span>
            </button>
            <button class="nav-item flex flex-col items-center text-sm text-gray-700" data-page="user-activity">
                <i class="fas fa-calendar-day text-lg"></i>
                <span class="text-xs">User Activity</span>
            </button>
            <a href="logout.php" class="nav-item flex flex-col items-center text-sm text-red-600">
                <i class="fas fa-sign-out-alt text-lg"></i>
                <span class="text-xs">Logout</span>
            </a>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto" id="main-content">
            <!-- Yellow Ribbon Desktop -->
            <div class="hidden md:flex justify-between items-center mb-6 px-4 py-3 rounded" style="background-color: rgb(255, 192, 0); color: #222;">
                <h2 class="text-2xl font-bold">Welcome, <?= htmlspecialchars($user_name) ?></h2>
                <form method="POST" action="export_leave_report.php" target="_blank">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 flex items-center gap-2">
                        <i class="fas fa-file-excel"></i> Export Report
                    </button>
                </form>
            </div>
            <!-- Mobile Welcome -->
            <div class="block md:hidden mb-4 px-4 py-3 rounded" style="background-color: rgb(255, 192, 0); color: #222;">
                <h2 class="text-xl font-bold">Welcome, <?= htmlspecialchars($user_name) ?></h2>
            </div>
            <!-- Card Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 px-4">
                <!-- Pending Leave Requests Card -->
                <div class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 cursor-pointer" id="pending-leave-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-calendar-alt text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Pending Leave Requests</h3>
                            <p class="text-3xl text-blue-600 font-bold"><?= count($leave_rows) ?></p>
                        </div>
                    </div>
                </div>
                <!-- Approved Leave Requests Card -->
                <div class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 cursor-pointer" id="approved-leave-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                            <i class="fas fa-check-circle text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Approved Leave Requests</h3>
                            <p class="text-3xl text-green-600 font-bold">0</p>
                        </div>
                    </div>
                </div>
                <!-- Rejected/Cancel Leaves Card -->
                <div class="bg-white p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 cursor-pointer" id="rejected-leave-card">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-calendar-alt text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Rejected / Cancel Leaves</h3>
                            <p class="text-3xl text-blue-600 font-bold">0</p>
                        </div>
                    </div>
                </div>
            </div>

<div class="bg-white rounded shadow p-4 mb-10 hidden md:block">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-xl font-semibold text-indigo-700">Pending Leave Requests</h2>
    <form method="GET" class="flex items-center gap-2">
      <label for="limit" class="text-sm text-gray-600">Rows per page:</label>
      <select name="limit" id="limit" onchange="this.form.submit()" class="border rounded px-2 py-1">
        <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5</option>
        <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
        <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15</option>
      </select>
      <input type="hidden" name="page" value="<?= $page ?>">
    </form>
  </div>
  <table class="min-w-full table-auto bg-white shadow rounded overflow-hidden text-left">
    <thead class="bg-indigo-50 text-indigo-700">
      <tr>
        <th class="px-4 py-2">Name</th>
        <th class="px-4 py-2">Type</th>
        <th class="px-4 py-2">From</th>
        <th class="px-4 py-2">To</th>
        <th class="px-4 py-2">Status</th>
        <th class="px-4 py-2 text-right">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php if ($tableResult->num_rows === 0): ?>
        <tr><td colspan="6" class="px-4 py-3 text-gray-500 text-center">No pending leave requests.</td></tr>
      <?php else:
        while ($row = $tableResult->fetch_assoc()):
          $from = strtotime($row['from_date']);
          $to = strtotime($row['to_date']);
          $prev = date('Y-m-d', strtotime("-1 day", $from));
          $next = date('Y-m-d', strtotime("+1 day", $to));

          $is_prev_holiday_or_weekend = in_array($prev, $holidays) || in_array(date('w', strtotime($prev)), [0, 6]);
          $is_next_holiday_or_weekend = in_array($next, $holidays) || in_array(date('w', strtotime($next)), [0, 6]);
          $is_sandwich = $is_prev_holiday_or_weekend && $is_next_holiday_or_weekend;

          $display_name = $row['name'];
      ?>
      <tr class="<?= $is_sandwich ? 'bg-red-50' : '' ?>">
        <td class="px-4 py-2"><?= htmlspecialchars($display_name) ?></td>
        <td class="px-4 py-2"><?= htmlspecialchars($row['leave_duration']) ?></td>
        <td class="px-4 py-2"><?= date('d-m-Y', strtotime($row['from_date'])) ?></td>
        <td class="px-4 py-2"><?= date('d-m-Y', strtotime($row['to_date'])) ?></td>
        <td class="px-4 py-2">
          <?php if ($is_sandwich): ?>
            <span class="text-red-600 font-semibold">Sandwich</span>
          <?php else: ?>
            <span class="text-yellow-600">Pending</span>
          <?php endif; ?>
        </td>
        <td class="px-4 py-2 text-right">
          <form action="leave_update.php" method="POST" class="flex gap-2 justify-end">
            <input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
            <button type="submit" name="action" value="approve" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Approve</button>
            <button type="submit" name="action" value="reject" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Reject</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
  <!-- Pagination Links -->
  <div class="flex justify-center mt-4 gap-2">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?= $i ?>&limit=<?= $limit ?>"
         class="px-3 py-1 rounded border <?= $i == $page ? 'bg-indigo-600 text-white' : 'bg-white text-indigo-600 hover:bg-indigo-100' ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
  </div>
</div>

<div class="block md:hidden space-y-4 mt-6">
<?php foreach ($leave_rows as $row): ?>
  <?php
    $from = strtotime($row['from_date']);
    $to = strtotime($row['to_date']);
    $prev = date('Y-m-d', strtotime("-1 day", $from));
    $next = date('Y-m-d', strtotime("+1 day", $to));
    $is_prev = in_array($prev, $holidays) || in_array(date('w', strtotime($prev)), [0, 6]);
    $is_next = in_array($next, $holidays) || in_array(date('w', strtotime($next)), [0, 6]);
    $is_sandwich = $is_prev && $is_next;

    $display_name = $row['name'];
  ?>
  <div class="bg-white shadow-lg rounded-xl overflow-hidden border <?= $is_sandwich ? 'border-red-300' : 'border-gray-100' ?>">
    <div class="p-5 hover:bg-blue-50 transition-colors duration-150">
      <div class="flex items-center mb-4">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($display_name) ?>&background=random" class="w-10 h-10 rounded-full mr-3 shadow" alt="User">
        <div>
           <p class="font-semibold text-gray-800"><?= htmlspecialchars($display_name) ?></p>
          <p class="text-sm text-blue-600 font-medium"><?= htmlspecialchars($row['leave_duration']) ?></p>
        </div>
      </div>
      <div class="text-sm text-gray-700 space-y-1 mb-3">
        <div><strong>From:</strong> <?= $row['from_date'] ?></div>
        <div><strong>To:</strong> <?= $row['to_date'] ?></div>
        <div><strong>Reason:</strong> <?= htmlspecialchars($row['reason']) ?></div>
      </div>
      <div class="flex justify-between items-center">
        <span class="text-xs px-2 py-1 rounded-full font-medium <?= $is_sandwich ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' ?>">
          <?= $is_sandwich ? 'Sandwich' : 'Pending' ?>
        </span>
        <form method="POST" action="../leave_pages/leave_update.php" class="flex gap-2">
          <input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
          <button name="action" value="approve" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Approve</button>
          <button name="action" value="reject" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Reject</button>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

        </main>
    </div>
</body>
</html>
