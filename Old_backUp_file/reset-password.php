<?php
session_start();
require_once 'config.php';

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Messages
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .container-bg {
      background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
    }
    .card {
      backdrop-filter: blur(10px);
      background: rgba(255, 255, 255, 0.95);
    }
  </style>
</head>
<body class="container-bg min-h-screen flex items-center justify-center p-4">
  <div class="card w-full max-w-md p-8 rounded-2xl shadow-2xl">
    <div class="text-center mb-8">
      <h1 class="text-2xl font-bold text-gray-800 mb-1">Reset Your Password</h1>
      <p class="text-gray-600 text-sm">You must reset your password to continue</p>
    </div>

    <?php if ($error): ?>
      <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <form action="reset-password-handler.php" method="POST" class="space-y-6">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <!-- New Password -->
      <div>
        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
        <input type="password" name="new_password" id="new_password" required
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
      </div>

      <!-- Confirm Password -->
      <div>
        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" required
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
      </div>

      <button type="submit"
              class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition duration-200">
        Update Password
      </button>
    </form>
  </div>
</body>
</html>
