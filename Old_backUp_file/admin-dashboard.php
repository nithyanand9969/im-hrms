<?php
session_start();
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Check if user is logged in
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header("Location: login.php?error=" . urlencode("Session expired. Please log in again."));
    exit;
}

// ✅ Safe access to session variables
$user = $_SESSION['user'];
$user_id = $user['id'] ?? null;
$user_email = strtolower($user['email'] ?? '');
$user_name = $user['name'] ?? '';
$role = strtolower($user['role'] ?? '');

if ($role !== 'admin') {
    echo "Access Denied.";
    exit;
}

$isRachna = (strtolower($user_email) === 'rachna@integritymatters.in');
require_once('config.php');


if (isset($_POST['action']) && $_POST['action'] === 'get_managers') {
    header('Content-Type: application/json');
    try {
        $position = $_POST['position'] ?? '';
        $managers = [];
        if ($position === 'manager') {
            $stmt = $conn->prepare("SELECT id, name FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY name");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $managers[] = $row;
            }
        } elseif ($position === 'user') {
            $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE (role = 'admin' OR role = 'manager') AND is_active = 1 ORDER BY role DESC, name");
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $managers[] = $row;
            }
        }
        echo json_encode(['success' => true, 'managers' => $managers]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching managers: ' . $e->getMessage()]);
        exit();
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'fetch_users') {
    header('Content-Type: application/json');
    try {
        $stmt = $conn->prepare("SELECT users.id, users.name, users.email, users.role, users.is_active, users.has_left, teams.name AS team_name
                               FROM users
                               LEFT JOIN teams ON users.team_id = teams.id
                               ORDER BY users.name");
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        echo json_encode(['success' => true, 'users' => $users]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

if (isset($_POST['action']) && $_POST['action'] === 'update_left_status') {
    header('Content-Type: application/json');
    try {
        $userId = intval($_POST['user_id']);
        $hasLeft = intval($_POST['has_left']);

        $stmt = $conn->prepare("UPDATE users SET has_left = ? WHERE id = ?");
        $stmt->bind_param('ii', $hasLeft, $userId);
        $stmt->execute();

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// ðŸŸ© Get user by ID
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $conn->prepare("SELECT id, name, email, phone, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
        echo json_encode(['success' => true, 'user' => $res->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    exit;
}

// ðŸŸ© Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_user') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, role=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $phone, $role, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    try {
        $user_id = $_POST['user_id'] ?? null;
        $status = $_POST['status'] ?? null;

        if ($user_id === null || !in_array($status, [0, 1])) {
            throw new Exception("Invalid data provided");
        }

        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $status, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
        } else {
            throw new Exception("Error updating user status: " . $conn->error);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_employee'])) {
    header('Content-Type: application/json');
    try {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
        $dob = $_POST['dob'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $employee_id = trim($_POST['employee_id'] ?? '');
        $joining_date = $_POST['joining_date'] ?? '';
        $leave_balance = intval($_POST['leave_balance'] ?? 0);
        $position = $_POST['position'] ?? '';
        $manager_id = !empty($_POST['manager']) ? intval($_POST['manager']) : null;
        $is_old_employee = isset($_POST['is_old_employee']) && $_POST['is_old_employee'] === 'on';

        // Validate required fields
        if (empty($full_name) || empty($email) || empty($phone) || empty($dob) || empty($gender) || empty($employee_id) || empty($joining_date) || empty($position)) {
            throw new Exception("All required fields must be filled");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        if (strlen($phone) < 10 || strlen($phone) > 15) {
            throw new Exception("Phone number must be between 10-15 digits");
        }

        $joining_timestamp = strtotime($joining_date);
        $today = time();

        if ($is_old_employee) {
            $min_allowed = strtotime('2013-01-01');
            if ($joining_timestamp < $min_allowed) {
                throw new Exception("Old employee joining date must be from 2013 onwards");
            }
        } else {
            $max = strtotime('+60 days');
            if ($joining_timestamp > $max) {
                throw new Exception("Joining date for new employee must be within 60 days from today");
            }
        }

        if (!in_array($position, ['admin', 'manager', 'user'])) {
            throw new Exception("Invalid position selected");
        }

        // Check if email or employee ID already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email already exists");
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE employee_id = ?");
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Employee ID already exists");
        }

        // Default password
        $default_password = password_hash('Welcome@123', PASSWORD_DEFAULT);

        // Insert new employee
        $stmt = $conn->prepare("INSERT INTO users 
            (name, email, phone, date_of_birth, gender, employee_id, date_of_joining, password, role, manager_id, leave_balance, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
        $stmt->bind_param("sssssssssii", $full_name, $email, $phone, $dob, $gender, $employee_id, $joining_date, $default_password, $position, $manager_id, $leave_balance);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Employee added successfully! Default password is: Welcome@123'
            ]);
        } else {
            throw new Exception("Error adding employee: " . $conn->error);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}
if (isset($_GET['action']) && $_GET['action'] === 'fetch_leave_data') {
    header('Content-Type: application/json');

    try {
        $pendingStmt = $conn->prepare("SELECT COUNT(*) AS status FROM leave_requests WHERE status = 'Pending'");
        $pendingStmt->execute();
        $pendingResult = $pendingStmt->get_result();
        $pendingCount = $pendingResult->fetch_assoc()['status'];

        $totalEmployeesStmt = $conn->prepare("SELECT COUNT(*) AS total_count FROM users WHERE role != 'admin'");
        $totalEmployeesStmt->execute();
        $totalEmployeesResult = $totalEmployeesStmt->get_result();
        $totalEmployeesCount = $totalEmployeesResult->fetch_assoc()['total_count'];

        echo json_encode([
            'success' => true,
            'pending_leave_requests' => $pendingCount,
            'total_employees' => $totalEmployeesCount
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// SQL query to fetch both Pending and Approved leave requests, joining with the users table
$query = "
    SELECT 
        u.id AS employee_id, 
        u.name AS employee_name, 
        u.email AS employee_email, 
        lr.leave_type, 
        lr.from_date, 
        lr.to_date, 
        lr.status
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    WHERE lr.status IN ('pending', 'approved')"; // Fetch both Pending and Approved statuses

$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$leaveRequests = [];
$pendingCount = 0;
$approvedCount = 0;
while ($row = $result->fetch_assoc()) {
    $leaveRequests[] = $row;
    if ($row['status'] == 'pending') {
        $pendingCount++;
    } elseif ($row['status'] == 'approved') {
        $approvedCount++;
    }
}

$leaveRequestsJson = json_encode($leaveRequests);



$sql_used = "
    SELECT SUM(
        CASE 
            WHEN leave_duration = 'Half Day' THEN 0.5 
            ELSE DATEDIFF(to_date, from_date) + 1  -- Full day leave
        END
    ) AS used_days
    FROM leave_requests
    WHERE user_id = ?  -- Ensure that user_id is properly passed in the query
    AND status = 'approved'
    AND YEAR(from_date) = ?
";

$stmt_used = $conn->prepare($sql_used);
$current_year = date('Y');  // Get the current year

// Ensure the user_id is passed properly
$stmt_used->bind_param("ii", $user_id, $current_year);  // Bind the user_id and current_year
$stmt_used->execute();
$result_used = $stmt_used->get_result();
$used_data = $result_used->fetch_assoc();
$used_days = $used_data['used_days'] ?? 0;  // Default to 0 if no data found
$stmt_used->close();

// Process Leave Request Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['leave_type'])) {
    $leave_type = $_POST['leave_type'];
    $leave_duration = $_POST['leave_duration'];
    $half_day_type = $_POST['half_day_type'] ?? null;
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'] ?? $from_date;
    $reason = $_POST['reason'];
    $status = 'pending';  // Default status
    $created_at = date("Y-m-d H:i:s");  // Current timestamp for creation

    // Insert the leave request into the database
    $sql = "INSERT INTO leave_requests 
            (user_id, leave_type, leave_duration, half_day_type, from_date, to_date, reason, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssss", $user_id, $leave_type, $leave_duration, $half_day_type, $from_date, $to_date, $reason, $status, $created_at);

    // Execute and handle success/error
    if ($stmt->execute()) {
        echo "<script>alert('Leave request submitted successfully!'); window.location.href = window.location.pathname;</script>";
        exit();
    } else {
        echo "<script>alert('Error submitting leave request: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// Fetch Leave History for the logged-in user
$sql_history = "SELECT leave_type, from_date, to_date, status FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->bind_param("i", $user_id);
$stmt_history->execute();
$result_history = $stmt_history->get_result();
$leaves = $result_history->fetch_all(MYSQLI_ASSOC);
$stmt_history->close();

// Fetch leave counts: pending, approved, and rejected
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


$attendance_days_present = 18;
$total_working_days = 22;

// Fetch leave requests that are pending approval or rejection for the logged-in user
$sql_pending_leaves = "
    SELECT u.name AS employee_name, lr.leave_type, lr.from_date, lr.to_date, lr.status, lr.reason 
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    WHERE lr.status IN ('pending', 'rejected') AND lr.user_id = ?
    ORDER BY lr.created_at DESC
";

$stmt_pending_leaves = $conn->prepare($sql_pending_leaves);
$stmt_pending_leaves->bind_param("i", $user_id);  // Get only the logged-in user's leave requests
$stmt_pending_leaves->execute();
$result_pending_leaves = $stmt_pending_leaves->get_result();

// Fetch all the leave data
$leave_requests = [];
while ($row = $result_pending_leaves->fetch_assoc()) {
    $leave_requests[] = $row;
}

$stmt_pending_leaves->close();

// Fetch leave request data once and reuse it for both desktop and mobile views
$sql = "SELECT lr.id, u.name AS employee_name, leave_type, from_date, to_date, leave_duration, reason, approval_level, approval_required
        FROM leave_requests lr
        JOIN users u ON lr.user_id = u.id
        WHERE lr.status = 'pending'";
$result = $conn->query($sql);

$leave_rows = [];
while ($row = $result->fetch_assoc()) {
    $leave_rows[] = $row;
}

// Handle approval/rejection form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_id'], $_POST['action'])) {
    $leave_id = (int) $_POST['leave_id'];
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';

    // Update leave status only if eligible to approve
    $stmt = $conn->prepare("SELECT approval_level, approval_required FROM leave_requests WHERE id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $approval_level = $res['approval_level'];
    $approval_required = $res['approval_required'];

    $can_approve = false;
    if ($user_role === 'manager' && $approval_level == 1) {
        $can_approve = true;
    } elseif ($user_role === 'admin' && $approval_level >= 2 && $approval_level < $approval_required) {
        $can_approve = true;
    }

    if ($can_approve) {
        $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, approval_level = approval_level + 1 WHERE id = ?");
        $stmt->bind_param("si", $action, $leave_id);
        $stmt->execute();
        $stmt->close();
    }

    // Refresh to prevent resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}



$sql = "SELECT lr.id, u.name AS employee_name, leave_type, from_date, to_date, leave_duration, reason, approval_level, approval_required
        FROM leave_requests lr
        JOIN users u ON lr.user_id = u.id
        WHERE lr.status = 'pending'";
$result = $conn->query($sql);

$leave_rows = [];
while ($row = $result->fetch_assoc()) {
    $leave_rows[] = $row;
}

// Handle approval/rejection form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_id'], $_POST['action'])) {
    $leave_id = (int) $_POST['leave_id'];
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';

    // Update leave status only if eligible to approve
    $stmt = $conn->prepare("SELECT approval_level, approval_required FROM leave_requests WHERE id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $approval_level = $res['approval_level'];
    $approval_required = $res['approval_required'];

    $can_approve = false;
    if ($user_role === 'manager' && $approval_level == 1) {
        $can_approve = true;
    } elseif ($user_role === 'admin' && $approval_level >= 2 && $approval_level < $approval_required) {
        $can_approve = true;
    }

    if ($can_approve) {
        $stmt = $conn->prepare("UPDATE leave_requests SET status = ?, approval_level = approval_level + 1 WHERE id = ?");
        $stmt->bind_param("si", $action, $leave_id);
        $stmt->execute();
        $stmt->close();
    }

    // Refresh to prevent resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}
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

<body class="bg-gray-100 font-sans antialiased">
  <div class="flex h-screen">
    <!-- Sidebar -->
      <aside class="hidden md:block sd:block lg:block md:flex w-64 bg-blue-900 text-white flex-col flex-shrink-0">
      <div class="text-2xl font-bold p-6 border-b border-blue-700">IM Management</div>
      <nav class="flex-1 p-4 space-y-2">
        <button class="nav-item block w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition" data-page="dashboard">
          <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
        </button>
        <button class="nav-item block w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition" data-page="manage-users">
          <i class="fas fa-users mr-2"></i> Manage Users
        </button>
        <button class="nav-item block w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition" data-page="leaves">
          <i class="fas fa-calendar-minus mr-3"></i> Leaves
        </button>
     <button onclick="openHolidayModal()" class="nav-item block w-full text-left px-4 py-3 rounded hover:bg-blue-800 transition">
  <i class="fas fa-calendar-alt mr-2"></i>
  <span class="text-m">Holidays</span>
</button>
      </nav>
      <div class="p-4 border-t border-blue-700">
        <a href="logout.php" class="w-full block bg-red-600 hover:bg-red-700 py-2 rounded text-center text-white flex items-center justify-center">
          <i class="fas fa-sign-out-alt mr-2"></i> Sign Out
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
    <span class="text-xs">Leaves</span>
  </button>

  <!-- ✅ Holiday List Button -->
  <button onclick="openHolidayModal()" class="nav-item flex flex-col items-center text-sm text-gray-700">
    <i class="fas fa-calendar-day text-lg"></i>
    <span class="text-xs">Holidays</span>
  </button>

  <!-- ✅ Logout Button as Link -->
  <a href="logout.php" class="nav-item flex flex-col items-center text-sm text-red-600">
    <i class="fas fa-sign-out-alt text-lg"></i>
    <span class="text-xs">Logout</span>
  </a>
</nav>

    
    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto" id="main-content">
      <!-- Dashboard Section -->
   <section id="dashboard" class="p-6">
<h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome, <?= htmlspecialchars($user['name']) ?></h2>
<p class="text-gray-600 mb-6">Login Time: <?= date('d-m-Y') ?></p>
<div class="flex justify-end mb-4">
    <form method="POST" action="export_leave_report.php" target="_blank">
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 flex items-center gap-2">
            <i class="fas fa-file-excel"></i> Export Report
        </button>
    </form>
</div>


<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Pending Leave Requests Card -->
    <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 cursor-pointer" id="pending-leave-card">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                <i class="fas fa-calendar-alt text-lg"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-700">Pending Leave Requests</h3>
                <p class="text-3xl text-blue-600 font-bold pending-leave-count">0</p> <!-- Placeholder -->
            </div>
        </div>
    </div>

    <!-- Approved Leave Requests Card -->
    <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 cursor-pointer" id="approved-leave-card">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                <i class="fas fa-check-circle text-lg"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-700">Approved Leave Requests</h3>
                <p class="text-3xl text-green-600 font-bold approved-leave-count">0</p> <!-- Placeholder -->
            </div>
        </div>
    </div>
</div>

<!-- Leave Balance Section -->
<div class="mt-6">
    <!-- Placeholder -->
</div>

<!-- Modal to show Pending Leave details -->
<div id="pending-leave-modal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-8 rounded-lg shadow-lg w-1/2 max-w-4xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-700">Pending Leave Requests Details</h3>
            <button id="close-pending-modal" class="text-gray-500 hover:text-red-600 text-xl font-bold">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <table class="min-w-full table-auto">
            <thead>
                <tr class="bg-gray-200">
                    <th class="px-4 py-2 text-left">Employee Name</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">Leave Type</th>
                    <th class="px-4 py-2 text-left">From Date</th>
                    <th class="px-4 py-2 text-left">To Date</th>
                    <th class="px-4 py-2 text-left">Status</th>
                </tr>
            </thead>
            <tbody id="pending-leave-details">
                <!-- Pending leave details will be dynamically inserted here -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal to show Approved Leave details -->
<div id="approved-leave-modal" class="fixed inset-0 z-50 bg-black bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white p-8 rounded-lg shadow-lg w-1/2 max-w-4xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-700">Approved Leave Requests Details</h3>
            <button id="close-approved-modal" class="text-gray-500 hover:text-red-600 text-xl font-bold">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <table class="min-w-full table-auto">
            <thead>
                <tr class="bg-gray-200">
                  
                    <th class="px-4 py-2 text-left">Employee Name</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">Leave Type</th>
                    <th class="px-4 py-2 text-left">From Date</th>
                    <th class="px-4 py-2 text-left">To Date</th>
                    <th class="px-4 py-2 text-left">Status</th>
                </tr>
            </thead>
            <tbody id="approved-leave-details">
                <!-- Approved leave details will be dynamically inserted here -->
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // The leave request data is embedded in the PHP as a JSON object
        const leaveRequests = <?php echo $leaveRequestsJson; ?>;

        // Dynamically populate the values for the "Pending Leave Requests" card
        document.querySelector('.pending-leave-count').textContent = leaveRequests.filter(leave => leave.status === 'pending').length || 0;

        // Dynamically populate the values for the "Approved Leave Requests" card
        document.querySelector('.approved-leave-count').textContent = leaveRequests.filter(leave => leave.status === 'approved').length || 0;

        // Handle the "Pending Leave Requests" card click event
        document.getElementById("pending-leave-card").addEventListener("click", function () {
            openLeaveModal(leaveRequests, 'pending'); // Open the modal and pass leave data for pending
        });

        // Handle the "Approved Leave Requests" card click event
        document.getElementById("approved-leave-card").addEventListener("click", function () {
            openLeaveModal(leaveRequests, 'approved'); // Open the modal and pass leave data for approved
        });

        // Function to open the modal and show leave details based on status
        function openLeaveModal(leaveRequests, status) {
            const leaveDetailsContainer = status === 'pending' ? document.getElementById("pending-leave-details") : document.getElementById("approved-leave-details");
            leaveDetailsContainer.innerHTML = ""; // Clear any previous content

            // Filter leave requests based on status
            const filteredRequests = leaveRequests.filter(leave => leave.status === status);

            // Loop through the filtered leave requests and display them in a table row
            filteredRequests.forEach(leave => {
                const leaveRow = `
                    <tr class="border-b">
                        <td class="px-4 py-2">${leave.employee_name}</td>
                        <td class="px-4 py-2">${leave.employee_email}</td>
                        <td class="px-4 py-2">${leave.leave_type}</td>
                        <td class="px-4 py-2">${leave.from_date}</td>
                        <td class="px-4 py-2">${leave.to_date}</td>
                        <td class="px-4 py-2">${leave.status}</td>
                    </tr>
                `;
                leaveDetailsContainer.innerHTML += leaveRow;
            });

            // Show the modal for the respective status
            if (status === 'pending') {
                document.getElementById("pending-leave-modal").classList.remove("hidden");
            } else if (status === 'approved') {
                document.getElementById("approved-leave-modal").classList.remove("hidden");
            }
        }

        // Close the pending leave modal when the close button is clicked
        document.getElementById("close-pending-modal").addEventListener("click", function () {
            document.getElementById("pending-leave-modal").classList.add("hidden");
        });

        // Close the approved leave modal when the close button is clicked
        document.getElementById("close-approved-modal").addEventListener("click", function () {
            document.getElementById("approved-leave-modal").classList.add("hidden");
        });
    });
</script>




<?php

$holidays = [];
$holidayQuery = $conn->query("SELECT holiday_date FROM holiday");
while ($row = $holidayQuery->fetch_assoc()) {
    $holidays[] = $row['holiday_date'];
}

// ✅ Get email to determine if it's Rachna
$is_rachna = (strtolower($user_email) === 'rachna@integritymatters.in');

// ✅ Build leave query
$query = "
  SELECT lr.*, u.name, u.email
  FROM leave_requests lr
  JOIN users u ON lr.user_id = u.id
  WHERE lr.status = 'pending'
";

// ✅ Apply conditional filters
if ($is_rachna) {
    // Rachna: should not see her own leave
    $query .= " AND u.email != 'rachna@integritymatters.in' AND lr.approval_level_2 = 0";
} else {
    // Other admins: should not see already-approved requests (approval_level = 1)
    $query .= " AND lr.approval_level = 0";
}

$query .= " ORDER BY lr.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

?>

<!-- ✅ Table View -->
<div class="bg-white rounded shadow p-4 mb-10">
  <h2 class="text-xl font-semibold text-indigo-700 mb-4">Pending Leave Requests</h2>
  <table class="min-w-full table-auto bg-white shadow rounded overflow-hidden text-left">
    <thead class="bg-indigo-50 text-indigo-700">
      <tr>
        <th class="px-4 py-2">Name</th>
        <th class="px-4 py-2">Email</th>
        <th class="px-4 py-2">Type</th>
        <th class="px-4 py-2">From</th>
        <th class="px-4 py-2">To</th>
        <th class="px-6 py-3 font-medium">Status</th>
               <th class="px-6 py-4 font-medium text-right whitespace-nowrap">Actions</th>
          
      </tr>
    </thead>
    <tbody class="divide-y">
      <?php
      if ($result->num_rows === 0): ?>
        <tr><td colspan="7" class="px-4 py-3 text-gray-500 text-center">No pending leave requests.</td></tr>
      <?php else:
        while ($row = $result->fetch_assoc()):
          $from = strtotime($row['from_date']);
          $to = strtotime($row['to_date']);
          $prev = date('Y-m-d', strtotime("-1 day", $from));
          $next = date('Y-m-d', strtotime("+1 day", $to));

          $is_prev_holiday_or_weekend = in_array($prev, $holidays) || in_array(date('w', strtotime($prev)), [0, 6]);
          $is_next_holiday_or_weekend = in_array($next, $holidays) || in_array(date('w', strtotime($next)), [0, 6]);
          $is_sandwich = $is_prev_holiday_or_weekend && $is_next_holiday_or_weekend;
      ?>
      <tr class="<?= $is_sandwich ? 'bg-red-50' : '' ?>">
        <td class="px-4 py-2"><?= htmlspecialchars($row['name']) ?></td>
        <td class="px-4 py-2"><?= htmlspecialchars($row['email']) ?></td>
        <td class="px-4 py-2"><?= $row['leave_type'] ?></td>
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
</div>

<!-- ✅ Mobile Card View -->
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
  ?>
  <div class="bg-white shadow-lg rounded-xl overflow-hidden border <?= $is_sandwich ? 'border-red-300' : 'border-gray-100' ?>">
    <div class="p-5 hover:bg-blue-50 transition-colors duration-150">
      <div class="flex items-center mb-4">
        <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['employee_name']) ?>&background=random" class="w-10 h-10 rounded-full mr-3 shadow" alt="User">
        <div>
          <p class="font-semibold text-gray-800"><?= htmlspecialchars($row['employee_name']) ?></p>
          <p class="text-sm text-blue-600 font-medium"><?= htmlspecialchars($row['leave_type']) ?></p>
        </div>
      </div>
      <div class="text-sm text-gray-700 space-y-1 mb-3">
        <div><strong>From:</strong> <?= $row['from_date'] ?></div>
        <div><strong>To:</strong> <?= $row['to_date'] ?></div>
        <div><strong>Duration:</strong> <?= htmlspecialchars($row['leave_duration']) ?></div>
        <div><strong>Reason:</strong> <?= htmlspecialchars($row['reason']) ?></div>
      </div>
      <div class="flex justify-between items-center">
        <span class="text-xs px-2 py-1 rounded-full font-medium <?= $is_sandwich ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' ?>">
          <?= $is_sandwich ? 'Sandwich' : 'Pending' ?>
        </span>
        <form method="POST" action="leave_update.php" class="flex gap-2">
          <input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
          <button name="action" value="approve" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Approve</button>
          <button name="action" value="reject" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Reject</button>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>




        

        <!-- Recent Activity and Calendar -->
        <div class="grid grid-cols-1 pt-5 lg:grid-cols-1 gap-6">
          <!-- Recent Activity -->
        
          
          <!-- Calendar -->
       <div class="bg-white p-4 rounded-lg shadow-md mt-6">
<iframe src="calendar.php" class="w-full h-[300px] border rounded mt-6" frameborder="0"></iframe>

</div>
        </div>
      </section>
      
<!-- Holiday Calendar Modal -->
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



      <!-- Manage Users Section -->
<section id="manage-users" class="p-6 hidden">
  <!-- Header Section -->
  <div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Manage Users</h2>
    <div class="relative">
      <input type="text" 
             placeholder="Search users..." 
             class="pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-64">
      <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
    </div>
  </div>

  <!-- Add Employee Button -->
  <button onclick="openEmployeeModal()" 
          class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition duration-200">
    <i class="fas fa-user mr-2"></i>Add Employee
  </button>
    <button id="exportActiveBtn"
    class="bg-blue-400 text-white px-4 py-2 rounded hover:bg-blue-700 transition mb-4">
    <i class="fas fa-download mr-2"></i>Export Active Employees
</button>

  <!-- User Table -->
 <div class="bg-white rounded-lg shadow-md overflow-hidden mt-6">
    <div class="overflow-x-auto">
      <table class="w-full table-auto">
        <thead class="bg-blue-50">
          <tr class="text-left text-sm text-blue-800">
            <th class="px-6 py-3 font-medium">Name</th>
            <th class="px-6 py-3 font-medium">Email</th>
            <th class="px-6 py-3 font-medium">Team</th>
            <th class="px-6 py-3 font-medium">Role</th>
            <th class="px-6 py-3 font-medium">Status</th>
               <th class="px-6 py-4 font-medium text-right whitespace-nowrap">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm" id="userTableBody">
          
        </tbody>
      </table>
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

  <!-- Employee Modal -->
      <!-- Employee Modal -->
    <div id="employeeModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white max-w-4xl w-full rounded-xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col animate-fadeIn">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold text-white">Add New Employee</h3>
                        <p class="text-blue-100">Fill in the employee details below</p>
                    </div>
                    <button onclick="closeModal()" class="text-white hover:text-blue-200 transition-transform hover:scale-110">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="overflow-y-auto flex-1 p-6">
                <form id="employeeForm" class="space-y-6">
                    <!-- Personal Information Section -->
                    <div class="bg-blue-50 p-5 rounded-lg border border-blue-200">
                        <div class="flex items-center mb-4">
                            <div class="bg-blue-100 p-2 rounded-full mr-3">
                                <svg class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-gray-800">Personal Information</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name*</label>
                                <input type="text" name="full_name" required class="form-input">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email*</label>
                                <input type="email" name="email" required class="form-input">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone*</label>
                                <input type="tel" name="phone" required pattern="[0-9]{10,15}" class="form-input">
                                <p class="text-xs text-gray-500 mt-1">10-15 digits only</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth*</label>
                                <input type="date" name="dob" required max="<?= date('Y-m-d', strtotime('-18 years')) ?>" class="form-input">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Gender*</label>
                                <select name="gender" required class="form-input">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Employment Information Section -->
                    <div class="bg-blue-50 p-5 rounded-lg border border-blue-200">
                        <div class="flex items-center mb-4">
                            <div class="bg-blue-100 p-2 rounded-full mr-3">
                                <svg class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                                    <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                                </svg>
                            </div>
                           
                            <h4 class="text-lg font-semibold text-gray-800">Employment Information</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID*</label>
                                <input type="text" name="employee_id" required class="form-input">
                            </div>
                           <div class="mb-4">
    <label class="inline-flex items-center mb-2">
        <input type="checkbox" id="isOldEmployee" class="mr-2">
        Old Employee (Joined before 2023)
    </label>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Joining Date*</label>
    <input type="date" name="joining_date" required 
           id="joiningDate"
           class="form-input">
    <p class="text-xs text-gray-500 mt-1" id="dateHelpText">
        Date must be within 30 days from today
    </p>
</div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Leave Balance</label>
                                <input type="number" name="leave_balance" min="0" value="0" class="form-input">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Position*</label>
                                <select name="position" required id="positionSelect" class="form-input">
                                    <option value="">Select Position</option>
                                    <option value="admin">Admin</option>
                                    <option value="manager">Manager</option>
                                    <option value="user">User</option>
                                </select>
                            </div>
                            <div id="reportingManagerDiv">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Reporting Manager</label>
                                <select name="manager" id="managerSelect" class="form-input">
                                    <option value="">Select Manager</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="submit_employee" value="1">
                </form>
            </div>
            
            <!-- Modal Footer -->
            <div class="bg-white border-t border-gray-200 p-4">
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                            class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-all duration-200 font-medium">
                        Cancel
                    </button>
                    <button type="button" onclick="submitEmployeeForm()" id="submitBtn"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 font-medium shadow-md hover:shadow-lg">
                        Save Employee
                    </button>
                </div>
            </div>
        </div>
        
    </div>
    
<div id="editUserModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
    <h2 class="text-xl font-semibold mb-4">Edit User</h2>
    <form id="editUserForm">
      <input type="hidden" name="action" value="update_user">
      <input type="hidden" name="id" id="editUserId">

      <div class="mb-3">
        <label>Name</label>
        <input type="text" id="editUserName" name="name" class="w-full border rounded px-3 py-2" required>
      </div>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" id="editUserEmail" name="email" class="w-full border rounded px-3 py-2" required>
      </div>
      <div class="mb-3">
        <label>Phone</label>
        <input type="text" id="editUserPhone" name="phone" class="w-full border rounded px-3 py-2">
      </div>
      <div class="mb-4">
        <label>Role</label>
        <select id="editUserRole" name="role" class="w-full border rounded px-3 py-2">
          <option value="user">User</option>
          <option value="manager">Manager</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeEditModal()" class="bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
      </div>
    </form>
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
    
    
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.2.0/fullcalendar.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>



<script>
function openHolidayModal() {
  document.getElementById('holidayModal').classList.remove('hidden');
}

function closeHolidayModal() {
  document.getElementById('holidayModal').classList.add('hidden');
}
function closeEditModal() {
  document.getElementById('editUserModal').classList.add('hidden');
}

function attachEditListeners() {
  document.querySelectorAll('.edit-user-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const userId = btn.dataset.userId;
      fetch(`admin-dashboard.php?id=${userId}`)
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const u = data.user;
            document.getElementById('editUserId').value = u.id;
            document.getElementById('editUserName').value = u.name;
            document.getElementById('editUserEmail').value = u.email;
            document.getElementById('editUserPhone').value = u.phone || '';
            document.getElementById('editUserRole').value = u.role;
            document.getElementById('editUserModal').classList.remove('hidden');
          } else {
            alert("User not found.");
          }
        });
    });
  });
}

document.getElementById('editUserForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('admin-dashboard.php', {
    method: 'POST',
    body: formData
  }).then(res => res.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        closeEditModal();
        setTimeout(() => location.reload(), 500);
      } else {
        alert(data.message || "Update failed.");
      }
    });
});

document.getElementById('exportActiveBtn').addEventListener('click', () => {
    fetch('export_active_users.php')
        .then(res => {
            if (!res.ok) throw new Error('Download failed');
            return res.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'active_employees.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        })
        .catch(err => {
            showNotification('error', err.message || 'Failed to export');
        });
});


document.querySelectorAll('.edit-user-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const userId = this.getAttribute('data-user-id');
        fetch(`admin-dashboard.php?id=${userId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const u = data.user;
                    document.getElementById('editUserId').value = u.id;
                    document.getElementById('editUserName').value = u.name;
                    document.getElementById('editUserEmail').value = u.email;
                    document.getElementById('editUserPhone').value = u.phone;
                    document.getElementById('editUserRole').value = u.role;
                    document.getElementById('editUserModal').classList.remove('hidden');
                } else {
                    alert(data.message);
                }
            })
            .catch(() => alert("Error loading user data"));
    });
});

document.getElementById('editUserForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('admin-dashboard.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                document.getElementById('editUserModal').classList.add('hidden');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert(data.message);
            }
        })
        .catch(() => alert("Error updating user"));
});



$(function () {

    const leaveRequests = <?php echo $leaveRequestsJson; ?>;


    const approvedLeaves = leaveRequests.filter(leave => leave.status === 'approved');

  
    const events = approvedLeaves.map(leave => ({
        title: `${leave.employee_name} - ${leave.leave_type}`, // Show employee 
        start: leave.from_date, // Event start date
        end: leave.to_date, // Event end date
        description: `Employee: ${leave.employee_name}\nLeave Type: ${leave.leave_type}`, // Description to show on click
        backgroundColor: '#4CAF50', // Green color for approved leaves
        borderColor: '#4CAF50', // Green border
        textColor: 'white' // Text color inside the event
    }));

    // Calendar setup
    const today = moment();
    const endDate = today.clone().add(60, 'days');
    $('#calendar').fullCalendar({
        height: 500,
        header: { left: 'prev,next today', center: 'title', right: 'month' },
        defaultDate: today.format('YYYY-MM-DD'),
        validRange: { start: today.format('YYYY-MM-DD'), end: endDate.format('YYYY-MM-DD') },
        events: events, // Pass the leave events to FullCalendar
        eventClick: function (event) {
            // Show leave details when an event is clicked
            alert(`Employee: ${event.title}\nLeave Type: ${event.description}\nFrom: ${event.start.format('YYYY-MM-DD')}\nTo: ${event.end.format('YYYY-MM-DD')}`);
        }
    });

 $('.nav-item').click(function () {
    // Remove active class from all nav items
    $('.nav-item').removeClass('active');
    
    // Add active class to clicked item
    $(this).addClass('active');
    
    // Get the page to show
    const page = $(this).data('page');
    
    // Hide all sections
    $('#main-content > section').addClass('hidden');
    
    // Show the selected section
    $(`#${page}`).removeClass('hidden');
    
    // Load users if needed
    if (page === 'manage-users') {
        loadUsers();
    }
});
   
   
   window.openEmployeeModal = function () {
    $('#employeeModal').removeClass('hidden');
    $('body').css('overflow', 'hidden');
};

window.closeModal = function () {
    $('#employeeModal').addClass('hidden');
    $('body').css('overflow', 'auto');
    $('#employeeForm')[0].reset();
    $('#reportingManagerDiv').show();
    $('#managerSelect').html('<option value="">Select Manager</option>');
    updateDateLimits(); // reset date limits
};

$('#employeeModal').on('click', function (e) {
    if (e.target === this) closeModal();
});

$(document).on('keydown', function (e) {
    if (e.key === 'Escape' && !$('#employeeModal').hasClass('hidden')) closeModal();
});

// Max DOB
const dobInput = $('input[name="dob"]')[0];
if (dobInput) {
    const d = new Date();
    d.setFullYear(d.getFullYear() - 18);
    dobInput.max = d.toISOString().split('T')[0];
}

// Position logic
$('#positionSelect').on('change', function () {
    const pos = this.value;
    const managerSelect = $('#managerSelect');
    if (pos === 'admin') {
        $('#reportingManagerDiv').hide();
        managerSelect.val('');
        managerSelect.removeAttr('required');
    } else {
        $('#reportingManagerDiv').show();
        managerSelect.attr('required', 'required');
        loadManagers(pos);
    }
});

function loadManagers(position) {
    const managerSelect = $('#managerSelect');
    managerSelect.html('<option value="">Loading...</option>');
    const formData = new FormData();
    formData.append('action', 'get_managers');
    formData.append('position', position);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            managerSelect.html('<option value="">Select Manager</option>');
            if (data.success && data.managers.length) {
                data.managers.forEach(manager => {
                    managerSelect.append(
                        $('<option>', {
                            value: manager.id,
                            text: manager.name + (manager.role ? ` (${manager.role})` : '')
                        })
                    );
                });
            } else {
                managerSelect.html('<option value="">No managers available</option>');
            }
        })
        .catch(() => managerSelect.html('<option value="">Error loading managers</option>'));
}

// âœ… Joining Date validation for Old Employees (2013 onwards)
function updateDateLimits() {
    const isOld = document.getElementById('isOldEmployee')?.checked;
    const input = document.getElementById('joiningDate');
    const helpText = document.getElementById('dateHelpText');

    if (!input) return;

    if (isOld) {
        input.min = "2013-01-01";
        input.max = "";
        if (helpText) helpText.textContent = "Old employees can have joining dates from 2013 onwards";
    } else {
        const today = new Date();
        const min = new Date(today.getTime() - 30 * 86400000);
        const max = new Date(today.getTime() + 30 * 86400000);
        input.min = min.toISOString().split('T')[0];
        input.max = max.toISOString().split('T')[0];
        if (helpText) helpText.textContent = "Date must be within 30 days from today";
    }
}

$('#isOldEmployee').on('change', updateDateLimits);
updateDateLimits();

$('#joiningDate').on('change', function () {
    const isOld = document.getElementById('isOldEmployee')?.checked;
    const selected = new Date(this.value);
    const today = new Date();

    if (isOld) {
        const minOld = new Date("2013-01-01");
        this.setCustomValidity(selected < minOld ? 'Date must be from 2013 onwards' : '');
    } else {
        const min = new Date(today.getTime() - 30 * 86400000);
        const max = new Date(today.getTime() + 30 * 86400000);
        this.setCustomValidity(selected < min || selected > max ? 'Joining date must be within 30 days from today' : '');
    }
});

// Border validation
$('.form-input').on('blur', function () {
    this.style.borderColor = this.checkValidity() ? '#10b981' : '#ef4444';
}).on('input', function () {
    this.style.borderColor = '#d1d5db';
});

// âœ… Submit form
$('#submitBtn').on('click', function () {
    const form = $('#employeeForm')[0];
    if (!form.checkValidity()) return form.reportValidity();
    if (!validateJoiningDate()) return showNotification('error', 'Invalid joining date');

    const submitBtn = $(this);
    submitBtn.prop('disabled', true).html('<span class="animate-pulse">Processing...</span>');

    const formData = new FormData(form);
    formData.append('submit_employee', '1');

    fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            showNotification(data.success ? 'success' : 'error', data.message);
            if (data.success) {
                closeModal();
                setTimeout(() => window.location.reload(), 1500);
            }
            submitBtn.prop('disabled', false).html('Save Employee');
        })
        .catch(() => {
            showNotification('error', 'An error occurred.');
            submitBtn.prop('disabled', false).html('Save Employee');
        });
});

// âœ… Custom join date validator with old employee support
function validateJoiningDate() {
    const input = document.getElementById('joiningDate');
    const isOld = document.getElementById('isOldEmployee')?.checked;
    const selected = new Date(input.value);
    if (isOld) {
        const minOld = new Date('2013-01-01');
        return selected >= minOld;
    } else {
        const today = new Date();
        const min = new Date(today.getTime() - 30 * 86400000);
        const max = new Date(today.getTime() + 30 * 86400000);
        return selected >= min && selected <= max;
    }
}

// Notification wrapper
window.showNotification = function (type, message) {
    if (window.toastr) {
        toastr[type](message);
    } else {
        alert(message);
    }
};

   
   


    // Load users immediately if already on Manage Users
    if ($('[data-page="manage-users"]').hasClass('active')) {
        loadUsers();
    }

    // Load leave data
    fetch(window.location.href + '?action=fetch_leave_data')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the UI with the fetched data
                document.querySelector('.pending-leave-count').textContent = data.pending_leave_requests;
                document.querySelector('.total-employees-count').textContent = data.total_employees;
            } else {
                console.error(data.message);
            }
        })
        .catch(error => console.error("Error fetching leave data:", error));
});

function loadUsers() {
    const formData = new FormData();
    formData.append('action', 'fetch_users');

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const tbody = document.getElementById('userTableBody');
        tbody.innerHTML = '';

        if (data.success && data.users.length > 0) {
            data.users.forEach(user => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=random" 
                                 class="w-8 h-8 rounded-full mr-3" alt="User">
                            <span>${user.name}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">${user.email}</td>
                    <td class="px-6 py-4">${user.team_name || '-'}</td>
                    <td class="px-6 py-4">${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</td>
                    <td class="px-6 py-4">${user.has_left == 1 ? '<span class="text-red-600 font-semibold">Inactive</span>' : '<span class="text-green-600">Active</span>'}</td>
                    <td class="px-6 py-4 text-right">
                        <button class="edit-user-btn text-blue-600 hover:text-blue-800 mr-3" 
                                data-user-id="${user.id}">
                            <i class="fas fa-edit"></i>
                        </button>

                        <button class="toggle-left-btn text-${user.has_left ? 'green' : 'red'}-600 hover:text-${user.has_left ? 'green' : 'red'}-800" 
                                data-user-id="${user.id}" 
                                data-has-left="${user.has_left}">
                            <i class="fas fa-${user.has_left ? 'play-circle' : 'pause-circle'}"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            // Toggle Left button event listeners
            document.querySelectorAll('.toggle-left-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const userId = this.getAttribute('data-user-id');
                    const currentStatus = parseInt(this.getAttribute('data-has-left'));
                    toggleUserLeftStatus(userId, currentStatus);
                });
            });

            // âœ… Add event listeners to edit buttons
            attachEditListeners();

        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-500 py-4">No users found.</td></tr>`;
        }
    })
    .catch(() => alert("Failed to load user data."));
}
function attachEditListeners() {
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const userId = btn.dataset.userId;

            fetch(`admin-dashboard.php?id=${userId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const u = data.user;
                        document.getElementById('editUserId').value = u.id;
                        document.getElementById('editUserName').value = u.name;
                        document.getElementById('editUserEmail').value = u.email;
                        document.getElementById('editUserPhone').value = u.phone || '';
                        document.getElementById('editUserRole').value = u.role;

                        document.getElementById('editUserModal').classList.remove('hidden');
                    } else {
                        alert("User not found.");
                    }
                });
        });
    });
}


function toggleUserLeftStatus(userId, currentStatus) {
    const newStatus = currentStatus === 1 ? 0 : 1;
    const formData = new FormData();
    formData.append('action', 'update_left_status');
    formData.append('user_id', userId);
    formData.append('has_left', newStatus);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadUsers();
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => alert("Failed to update left status: " + error));
}


function toggleUserStatus(userId, currentStatus) {
    const newStatus = currentStatus === 1 ? 0 : 1;
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('user_id', userId);
    formData.append('status', newStatus);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const button = document.querySelector(`.toggle-status-btn[data-user-id="${userId}"]`);
            const icon = button.querySelector('i');
            if (newStatus === 1) {
                icon.className = 'fas fa-pause-circle';
                button.classList.remove('text-green-600', 'hover:text-green-800');
                button.classList.add('text-red-600', 'hover:text-red-800');
            } else {
                icon.className = 'fas fa-play-circle';
                button.classList.remove('text-red-600', 'hover:text-red-800');
                button.classList.add('text-green-600', 'hover:text-green-800');
            }
            button.setAttribute('data-status', newStatus);
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => alert("Failed to update status: " + error));
}
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

