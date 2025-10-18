<?php
require '../connecting_fIle/config.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = trim($_POST['employee_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$employee_id || !$email || !$new_password || !$confirm_password) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE employee_id = ? AND email = ?");
        $stmt->bind_param("ss", $employee_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $hashed, $user['id']);
            if ($update->execute()) {
                header("Location: login.php?success=Password updated successfully");
                exit;
            } else {
                $error = "Error updating password. Try again.";
            }
        } else {
            $error = "Invalid Employee ID or Email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .bg-main {
      background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    }
    .card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
    }
  </style>
</head>
<body class="bg-main min-h-screen flex items-center justify-center px-4">
  <div class="card max-w-md w-full p-8 rounded-2xl shadow-xl">
    <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Reset Your Password</h1>

    <?php if ($error): ?>
      <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-700 rounded-lg">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <input type="text" name="employee_id" placeholder="Employee ID" required
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"/>

      <input type="email" name="email" placeholder="Email Address" required
        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"/>

    <!-- New Password Field with Eye Toggle -->
<div class="relative group">
  <input type="password" id="new_password" name="new_password" placeholder="New Password" required
    class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition"/>
  <button type="button" onclick="togglePassword('new_password', 'eyeNew')" 
    class="absolute right-3 top-3 text-gray-400 hover:text-gray-700 transition duration-200">
    <svg id="eyeNew" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
      viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 
        7-9.542 7s-8.268-2.943-9.542-7z"/>
    </svg>
  </button>
</div>

<!-- Confirm Password Field with Eye Toggle -->
<div class="relative group">
  <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required
    class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition"/>
  <button type="button" onclick="togglePassword('confirm_password', 'eyeConfirm')" 
    class="absolute right-3 top-3 text-gray-400 hover:text-gray-700 transition duration-200">
    <svg id="eyeConfirm" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
      viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 
        7-9.542 7s-8.268-2.943-9.542-7z"/>
    </svg>
  </button>
</div>


      <button type="submit"
        class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">
        Update Password
      </button>
    </form>

    <div class="mt-6 text-center">
      <a href="login.php" class="text-blue-700 hover:underline text-sm">← Back to Login</a>
    </div>
  </div>

<script>
function togglePassword(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  const isPassword = input.type === 'password';

  input.type = isPassword ? 'text' : 'password';

  // Animate icon change
  icon.innerHTML = isPassword
    ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.056 
         10.056 0 012.224-3.592m3.292-2.455A9.957 9.957 0 0112 5c4.478 0 8.268 2.943 
         9.542 7a9.976 9.976 0 01-4.132 5.225M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M3 3l18 18"/>`
    : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
         d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.064 
         7-9.542 7s-8.268-2.943-9.542-7z"/>`;
}
</script>

</body>
</html>
