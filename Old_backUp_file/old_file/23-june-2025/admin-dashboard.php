<?php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect if user is not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['user'];
$user_id = $current_user['id'];
$user_role = strtolower($current_user['role']);

// Restrict access to admin role
if ($user_role !== 'admin') {
    echo "Access denied - Admin role required";
    header("Location: login.php");
    exit();
}

require_once('config.php');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'], $_POST['leave_id'])) {
    $status = ($_POST['action'] === 'approve') ? 'approved' : 'rejected';
    $leave_id = (int) $_POST['leave_id'];

    $stmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $leave_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Leave has been $status successfully.";
    } else {
        $_SESSION['error'] = "Failed to update leave status.";
    }
    $stmt->close();

    header("Location: super-admin.php");
    exit();
}

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
        $stmt = $conn->prepare("SELECT users.id, users.name, users.email, users.role, users.is_active, teams.name AS team_name
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
        $thirty_days_ago = strtotime('-30 days');
        $thirty_days_from_now = strtotime('+30 days');
        if ($joining_timestamp < $thirty_days_ago || $joining_timestamp > $thirty_days_from_now) {
            throw new Exception("Joining date must be within 30 days from today");
        }
        if (!in_array($position, ['admin', 'manager', 'user'])) {
            throw new Exception("Invalid position selected");
        }

        // Check if email or employee ID already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception("Email already exists");
        }
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE employee_id = ?");
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception("Employee ID already exists");
        }

        // Generate default password
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


$total_allowed = 24;  // Example: 24 days of leave per year

// Fetch the total used leave days for the logged-in user (including half-day leave)
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

$leave_balance = $total_allowed - $used_days;
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

// Placeholder for attendance (example: these values can come from the attendance system)
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
          <i class="fas fa-calendar-minus mr-2"></i> Leaves
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
  </nav>
    
    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto" id="main-content">
      <!-- Dashboard Section -->
   <section id="dashboard" class="p-6">
  <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome, Super Admin</h2>
  <p class="text-gray-600 mb-6">Login Time: <?php echo date('d-m-Y'); ?></p>

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




<!-- Desktop Table View -->
<div class="hidden md:block">
  <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-t-xl shadow-sm">
    <h3 class="text-lg font-semibold text-gray-800">Leave Requests</h3>
    <p class="text-sm text-gray-600">Manage employee leave applications</p>
  </div>

  <div class="overflow-hidden rounded-b-xl shadow-lg border border-gray-100">
    <table class="w-full table-auto divide-y divide-gray-200">
      <thead class="bg-gradient-to-r from-blue-100 to-indigo-100">
        <tr class="text-left text-sm text-gray-700">
          <th class="px-6 py-4 font-medium whitespace-nowrap">Employee</th>
          <th class="px-6 py-4 font-medium whitespace-nowrap">Leave Type</th>
          <th class="px-6 py-4 font-medium whitespace-nowrap">Dates</th>
          <th class="px-6 py-4 font-medium whitespace-nowrap">Duration</th>
          <th class="px-6 py-4 font-medium whitespace-nowrap">Reason</th>
          <th class="px-6 py-4 font-medium whitespace-nowrap">Status</th>
          <th class="px-6 py-4 font-medium text-right whitespace-nowrap">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 bg-white text-sm">
        <?php foreach ($leave_rows as $row): ?>
        <tr class="hover:bg-blue-50 transition-colors duration-150">
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center">
              
              <span class="font-medium text-gray-800"><?= htmlspecialchars($row['employee_name']) ?></span>
            </div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?= htmlspecialchars($row['leave_type']) ?></td>
          <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?= $row['from_date'] ?> - <?= $row['to_date'] ?></td>
          <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?= htmlspecialchars($row['leave_duration']) ?></td>
          <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($row['reason']) ?></td>
          <td class="px-6 py-4 whitespace-nowrap">
            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium flex items-center justify-center w-fit">
              <span class="w-2 h-2 rounded-full bg-yellow-500 mr-2"></span> Pending
            </span>
          </td>
          <td class="px-6 py-4 text-right whitespace-nowrap">
            <form method="POST" class="flex gap-2">
              <input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
              <button name="action" value="approve" class="bg-green-600 text-white px-3 py-1 rounded shadow hover:bg-green-700">Approve</button>
              <button name="action" value="reject" class="bg-red-600 text-white px-3 py-1 rounded shadow hover:bg-red-700">Reject</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Mobile Card View -->
<div class="block md:hidden space-y-4 mt-6">
<?php foreach ($leave_rows as $row): ?>
  <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-100">
    <div class="p-5 hover:bg-blue-50 transition-colors duration-150">
      <div class="flex items-center mb-4">
        <img src="https://randomuser.me/api/portraits/women/1.jpg" class="w-12 h-12 rounded-full mr-4 border-2 border-white shadow" alt="User">
        <div>
          <p class="font-semibold text-gray-800"><?= htmlspecialchars($row['employee_name']) ?></p>
          <p class="text-sm text-blue-600 font-medium"><?= htmlspecialchars($row['leave_type']) ?></p>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3 mb-4">
        <div>
          <p class="text-xs text-gray-500 font-medium">Dates</p>
          <p class="text-sm text-gray-700"><?= $row['from_date'] ?> - <?= $row['to_date'] ?></p>
        </div>
        <div>
          <p class="text-xs text-gray-500 font-medium">Duration</p>
          <p class="text-sm text-gray-700"><?= htmlspecialchars($row['leave_duration']) ?></p>
        </div>
        <div class="col-span-2">
          <p class="text-xs text-gray-500 font-medium">Reason</p>
          <p class="text-sm text-gray-700"><?= htmlspecialchars($row['reason']) ?></p>
        </div>
      </div>

      <div class="flex items-center justify-between">
        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium flex items-center">
          <span class="w-2 h-2 rounded-full bg-yellow-500 mr-2"></span> Pending
        </span>
        <form method="POST" class="flex gap-2">
          <input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
          <button name="action" value="approve" class="bg-green-600 text-white px-3 py-1 rounded shadow hover:bg-green-700">Approve</button>
          <button name="action" value="reject" class="bg-red-600 text-white px-3 py-1 rounded shadow hover:bg-red-700">Reject</button>
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
               <th class="px-6 py-4 font-medium text-right whitespace-nowrap">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm" id="userTableBody">
          <!-- User data will be inserted dynamically here -->
        </tbody>
      </table>
    </div>
  </div>
</section>


    <!-- Leaves Section -->
      <section id="leaves" class="p-6 hidden">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold text-gray-800">Leave Management</h2>
          <div class="flex space-x-3">
          
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
            <h3 class="text-sm font-medium text-gray-500">Your Total Leaves</h3>
             <p class="text-2xl font-bold text-indigo-700"><?= number_format($leave_balance) ?> Days</p>
          </div>
          <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <h3 class="text-sm font-medium text-gray-500">Pending Approval</h3>
            <p class="text-2xl font-bold text-yellow-600"><?= $pending_leaves_count ?></p>
          </div>
          <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500">
            <h3 class="text-sm font-medium text-gray-500">Approved</h3>
             <p class="text-2xl font-bold text-green-600"><?= $approved_leaves_count ?></p>
          </div>
          <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-red-500">
            <h3 class="text-sm font-medium text-gray-500">Rejected</h3>
             <p class="text-2xl font-bold text-red-600"><?= $rejected_leaves_count ?></p>
          </div>
        </div>
        

        
            
              <!-- Leave Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 pt-10">
            <!-- Leave Request Form -->
            <div class="bg-white p-6 shadow rounded-lg">
                <h2 class="text-xl font-semibold text-indigo-800 mb-6 pb-2 border-b">Request Leave</h2>
                <form id="leaveForm" method="POST" action="" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type</label>
                        <select name="leave_type" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                            <option value="">-- Select Leave Type --</option>
                     <option value="Personal Leave">Personal Leave</option>
<option value="Emergency Leave">Emergency Leave</option>
                          
                         
                           
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
                        <textarea name="reason" rows="4" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Briefly explain the reason for your leave..." required></textarea>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition duration-300 flex items-center justify-center">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Leave Request
                    </button>
                </form>
            </div>

            <!-- Leave History Trigger -->
            <div class="bg-white p-6 shadow rounded-lg flex flex-col">
                <div class="flex-1 flex items-center justify-center">
                    <button onclick="toggleModal()" class="text-indigo-600 font-medium text-lg p-4 rounded-lg border-2 border-dashed border-indigo-300 hover:border-solid hover:bg-indigo-50 transition-all">
                        <i class="fas fa-history mr-2"></i> View Leave History
                    </button>
                </div>
                
                <!-- Attendance Summary -->
             
            </div>
        </div>
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
                        <?php
                        $leavesPerPage = 10;
                        $totalLeaves = count($leaves);
                        $totalPages = ceil($totalLeaves / $leavesPerPage);
                        $currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
                        $currentPage = max(1, min($currentPage, $totalPages));
                        $startIndex = ($currentPage - 1) * $leavesPerPage;
                        $leavesToShow = array_slice($leaves, $startIndex, $leavesPerPage);

                        if (empty($leavesToShow)): ?>
                            <tr>
                                <td class="px-4 py-4 text-center text-gray-500" colspan="4">No leave history found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($leavesToShow as $leave): ?>
                                <?php
                                $statusClass = '';
                                $statusIcon = '';
                                if ($leave['status'] === 'approved') {
                                    $statusClass = 'status-approved';
                                    $statusIcon = 'fas fa-check-circle';
                                } elseif ($leave['status'] === 'rejected') {
                                    $statusClass = 'status-rejected';
                                    $statusIcon = 'fas fa-times-circle';
                                } else {
                                    $statusClass = 'status-pending';
                                    $statusIcon = 'fas fa-clock';
                                }
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= htmlspecialchars($leave['leave_type']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($leave['from_date']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($leave['to_date']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
                                            <i class="<?= $statusIcon ?> mr-1"></i> <?= htmlspecialchars(ucfirst($leave['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 pt-4 border-t">
                <div class="flex justify-center space-x-2">
                    <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                        <a href="?page=<?= $page ?>&modal=1" class="px-4 py-2 border rounded-md text-sm font-medium <?= $page === $currentPage ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-700 border-gray-300 hover:bg-gray-50' ?>">
                            <?= $page ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleLeaveType(isHalfDay) {
            document.getElementById('half-day-type').classList.toggle('hidden', !isHalfDay);
            document.getElementById('to-date-container').classList.toggle('hidden', isHalfDay);
            
            // Set default half day type if showing
            if (isHalfDay) {
                document.querySelector('select[name="half_day_type"]').value = "First Half";
            }
        }

        function toggleModal() {
            const modal = document.getElementById('leaveModal');
            modal.classList.toggle('hidden');
        }

        // Set today as default date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('from_date').value = today;
            document.getElementById('to_date').value = today;
            
            // If modal=1 is in URL, show modal on load
            const params = new URLSearchParams(window.location.search);
            if (params.get('modal') === '1') {
                toggleModal();
            }
        });
    </script>
      </section>
      
 
    </main>

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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Joining Date*</label>
                                <input type="date" name="joining_date" required 
                                       id="joiningDate"
                                       min="<?= date('Y-m-d', strtotime('-1500 days')) ?>" 
                                       max="<?= date('Y-m-d', strtotime('+30 days')) ?>"
                                       class="form-input">
                                <p class="text-xs text-gray-500 mt-1">Date must be within 30 days from today</p>
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
    
    
    
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.2.0/fullcalendar.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>



<script>
$(function () {
    // The leave request data is embedded in the PHP as a JSON object
    const leaveRequests = <?php echo $leaveRequestsJson; ?>;

    // Filter only approved leave requests
    const approvedLeaves = leaveRequests.filter(leave => leave.status === 'approved');

    // Format the leave data for FullCalendar
    const events = approvedLeaves.map(leave => ({
        title: `${leave.employee_name} - ${leave.leave_type}`, // Show employee name and leave type
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
        height: 300,
        header: { left: 'prev,next today', center: 'title', right: 'month' },
        defaultDate: today.format('YYYY-MM-DD'),
        validRange: { start: today.format('YYYY-MM-DD'), end: endDate.format('YYYY-MM-DD') },
        events: events, // Pass the leave events to FullCalendar
        eventClick: function (event) {
            // Show leave details when an event is clicked
            alert(`Employee: ${event.title}\nLeave Type: ${event.description}\nFrom: ${event.start.format('YYYY-MM-DD')}\nTo: ${event.end.format('YYYY-MM-DD')}`);
        }
    });

    // Sidebar navigation
    $('.nav-item').click(function () {
        $('.nav-item').removeClass('active');
        $(this).addClass('active');
        const page = $(this).data('page');
        $('#main-content > section').addClass('hidden');
        $(`#${page}`).removeClass('hidden');
        if (page === 'manage-users') loadUsers();
    });

    // Modal logic
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
    };
    $('#employeeModal').on('click', function (e) { if (e.target === this) closeModal(); });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && !$('#employeeModal').hasClass('hidden')) closeModal();
    });

    // Set max DOB
    const dobInput = $('input[name="dob"]')[0];
    if (dobInput) {
        const d = new Date();
        d.setFullYear(d.getFullYear() - 18);
        dobInput.max = d.toISOString().split('T')[0];
    }

    // Handle position selection
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

    $('#joiningDate').on('change', function () {
        const selected = new Date(this.value);
        const today = new Date();
        const min = new Date(today.getTime() - 30 * 86400000);
        const max = new Date(today.getTime() + 30 * 86400000);
        this.setCustomValidity(selected < min || selected > max ? 'Joining date must be within 30 days from today' : '');
    });

    $('.form-input').on('blur', function () {
        this.style.borderColor = this.checkValidity() ? '#10b981' : '#ef4444';
    }).on('input', function () {
        this.style.borderColor = '#d1d5db';
    });

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

    function validateJoiningDate() {
        const input = $('#joiningDate')[0];
        const d = new Date(input.value);
        const now = new Date();
           const past = new Date(now.getTime() - 7 * 365 * 24 * 60 * 60 * 1000);
        const future = new Date(now.getTime() + 30 * 86400000);
        return d >= past && d <= future;
    }

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
});

// Load users from server
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
                        
                       
                       <td class="px-6 py-4 text-right">
  <button class="text-blue-600 hover:text-blue-800 mr-3">
    <i class="fas fa-edit"></i>
  </button>
  
  <!-- Play/Pause Button -->
  <button class="toggle-status-btn text-green-600 hover:text-green-800" data-user-id="<?= $user['id']; ?>" data-status="<?= $user['is_active']; ?>">
    <i class="fas fa-play-circle"></i> <!-- Default Play icon -->
  </button>
</td>
`;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-gray-500 py-4">No users found.</td></tr>`;
            }
        })
        .catch(() => alert("Failed to load user data."));
}

function toggleUserStatus(userId, currentStatus) {
    // Toggle the status: if current is 1 (active), set it to 0 (inactive), and vice versa
    const newStatus = currentStatus === 1 ? 0 : 1;

    // Prepare FormData for the POST request
    const formData = new FormData();
    formData.append('action', 'update_status'); // Action to update the status
    formData.append('user_id', userId); // Pass the user ID
    formData.append('status', newStatus); // Pass the new status (1 or 0)

    // Send the request to the server
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // If the update is successful, change the button's appearance
            const button = document.getElementById('statusButton-' + userId);
            if (newStatus === 1) {
                button.innerHTML = '<i class="fas fa-pause"></i> Pause';
                button.classList.remove('bg-green-600');
                button.classList.add('bg-red-600');
            } else {
                button.innerHTML = '<i class="fas fa-play"></i> Play';
                button.classList.remove('bg-red-600');
                button.classList.add('bg-green-600');
            }
        } else {
            alert("Error: " + data.message); // Show error if any
        }
    })
    .catch(error => alert("Failed to update status: " + error));
}

fetch(window.location.href + '?action=fetch_leave_data')  // Using the current page's URL
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
    
    // Add to your PHP code:
$holidays = [
    '2025-01-01' => "New Year's Day",
    '2025-01-26' => "Republic Day",
    // Add all other holidays from the list
];

// Pass to JavaScript:
const holidays = <?php echo json_encode($holidays); ?>;

// In FullCalendar initialization:
events: [
    ...leaveEvents, 
    ...Object.entries(holidays).map(([date, title]) => ({
        title,
        start: date,
        allDay: true,
        backgroundColor: '#FF9800',
        borderColor: '#FF9800'
    }))
]
    .catch(error => console.error("Error fetching leave data:", error));
    function isSandwichLeave($from_date, $to_date, $holidays) {
    $from = new DateTime($from_date);
    $to = new DateTime($to_date);
    
    // Check Friday to Monday pattern
    if ($from->format('N') == 5 && $to->format('N') == 1 
        && $from->diff($to)->days == 3) {
        return true;
    }
    
    // Check if any dates between are holidays
    $period = new DatePeriod($from, new DateInterval('P1D'), $to);
    foreach ($period as $date) {
        if (isset($holidays[$date->format('Y-m-d')])) {
            return true;
        }
    }
    
    return false;
}

</script>

</body>
</html>

