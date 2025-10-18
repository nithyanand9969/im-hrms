<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '../leave_pages/monthly_leave_credit.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/login_debug.log');

require_once('../connecting_fIle/config.php');

// Log debug messages to file
function debugLog($msg) {
    $entry = "[DEBUG] " . date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL;
    file_put_contents(__DIR__ . '/login_debug.log', $entry, FILE_APPEND | LOCK_EX);
}

// Insert login event to database
function logEvent($conn, $userId, $email, $action, $details = null) {
    $stmt = $conn->prepare("INSERT INTO event_log (user_id, user_email, action, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $email, $action, $details);
    $stmt->execute();
    $stmt->close();
}

debugLog("=== LOGIN STARTED ===");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debugLog("Invalid request method");
    header("Location: ../auth/login.php?error=" . urlencode("Invalid request"));
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    debugLog("Email or password empty");
    header("Location: ../auth/login.php?error=" . urlencode("Please fill in all fields"));
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id, name, email, password, role, is_active, first_login FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        debugLog("User not found for email: $email");
        logEvent($conn, 0, $email, 'Failed Login', 'No account with this email');
        header("Location: ../auth/login.php?error=" . urlencode("Invalid email or password"));
        exit();
    }

    $user = $result->fetch_assoc();
    debugLog("User found: ID {$user['id']}, Role: {$user['role']}, Active: {$user['is_active']}");

    if ((int)$user['is_active'] !== 1) {
        debugLog("Account is not active");
        header("Location: ../auth/login.php?error=" . urlencode("Account is not active"));
        exit();
    }

    if (!password_verify($password, $user['password'])) {
        debugLog("Invalid password");
        logEvent($conn, $user['id'], $user['email'], 'Failed Login', 'Invalid password attempt');
        header("Location: login.php?error=" . urlencode("Invalid email or password"));
        exit();
    }

    // Valid login
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'first_login' => (int)$user['first_login']
    ];
    session_regenerate_id(true);
    logEvent($conn, $user['id'], $user['email'], 'Login', 'User logged in successfully');

    $stmt->close();
    ob_end_clean();

    if ((int)$user['first_login'] === 1) {
        header("Location: ../auth/change_password.php");
        exit();
    }

    debugLog("Redirecting user with role: " . strtolower($user['role']));

    switch (strtolower($user['role'])) {
        case 'admin':
            header("Location: ../admin_pages/admin-dashboard.php");
            break;
        case 'sr_manager':
            header("Location: ../senior_manager_pages/srmanager-dashboard.php");
            break;
        case 'manager':
            header("Location: ../manager_pages/manager-dashboard.php");
            break;
        default:
            header("Location: ../user_pages/user-dashboard.php");
            break;
    }
    exit();

} catch (Exception $e) {
    debugLog("Exception: " . $e->getMessage());
    header("Location: login.php?error=" . urlencode("Server error, try again later"));
    exit();
}
