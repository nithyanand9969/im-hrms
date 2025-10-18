<?php
ob_start();
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/login_debug.log');

require_once('config.php');

// Debug helper
function debugLog($msg) {
    $entry = "[DEBUG] " . date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL;
    file_put_contents(__DIR__ . '/login_debug.log', $entry, FILE_APPEND | LOCK_EX);
}

debugLog("=== LOGIN STARTED ===");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debugLog("Invalid request method");
    header("Location: login.php?error=" . urlencode("Invalid request"));
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    debugLog("Email or password empty");
    header("Location: login.php?error=" . urlencode("Please fill in all fields"));
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

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        debugLog("User found: ID {$user['id']}, Role: {$user['role']}, Active: {$user['is_active']}");

        if ((int)$user['is_active'] !== 1) {
            debugLog("Account is not active");
            header("Location: login.php?error=" . urlencode("Account is not active"));
            exit();
        }

        // ✅ Secure password verification
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'first_login' => (int)$user['first_login']
            ];

            session_regenerate_id(true);
            $stmt->close();
            ob_end_clean();

            // First login password change
            if ((int)$user['first_login'] === 1) {
                header("Location: change_password.php");
                exit();
            }

            // ✅ Role-based redirect
            switch (strtolower($user['role'])) {
                case 'admin':
                    header("Location: admin-dashboard.php");
                    break;
                case 'manager':
                    header("Location: manager-dashboard.php");
                    break;
                case 'user':
                    header("Location: user-dashboard.php");
                    break;
                default:
                    header("Location: dashboard.php");
            }
            exit();
        } else {
            debugLog("Invalid password");
        }
    } else {
        debugLog("User not found for email: $email");
    }

    $stmt->close();
} catch (Exception $e) {
    debugLog("Exception: " . $e->getMessage());
    header("Location: login.php?error=" . urlencode("Server error, try again later"));
    exit();
}

debugLog("Login failed");
header("Location: login.php?error=" . urlencode("Invalid email or password"));
exit();
?>

