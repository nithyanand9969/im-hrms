<?php
session_start();
require_once('config.php');

// Check if user is logged in and first login is required
if (!isset($_SESSION['user']) || $_SESSION['user']['first_login'] != 1) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $error = "Please fill in both fields.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $userId = $_SESSION['user']['id'];
        $role = strtolower($_SESSION['user']['role']);

        // Hash the password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ?, first_login = 0 WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $hashedPassword, $userId);
            if ($stmt->execute()) {
                // Update session values properly
                $_SESSION['user']['first_login'] = 0;
                $_SESSION['user']['role'] = $role; // Ensure role is set
                session_regenerate_id(true);

                // Redirect to appropriate dashboard
                switch ($role) {
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
                $error = "Error updating password.";
            }
            $stmt->close();
        } else {
            $error = "Server error. Please try again.";
        }
    }
}
?>
<!-- Tailwind HTML form (same as before) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>

    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-semibold mb-6 text-center">Change Your Password</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-4">
                <label class="block mb-1 font-medium">New Password</label>
                <input type="password" name="new_password" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300">
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-medium">Confirm Password</label>
                <input type="password" name="confirm_password" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                Save & Continue
            </button>
        </form>
    </div>
</body>
    <script src="https://cdn.tailwindcss.com"></script>
</html>
