<?php

session_start();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get messages from URL parameters
$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Leave Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .login-container {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>

<body class="login-container min-h-screen flex items-center justify-center p-4">
    <div class="login-card w-full max-w-md p-8 rounded-2xl shadow-2xl">
        <!-- Header -->
       <div class="text-center mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome Back</h1>
    <p class="text-gray-600">IM Leave Management System</p>
</div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="login_handler.php" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <!-- Email Field -->
       <!-- Email Input with Icon -->
<div class="relative mb-4">
       <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
    </svg>
    <input 
        type="email" 
        id="email" 
        name="email" 
        required 
        autocomplete="email"
        placeholder="Email Address"
        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
    >
</div>

<!-- Password Input with Toggle Icon -->
<div class="relative mb-4">
  <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.104 0 2-.896 2-2V7a2 2 0 10-4 0v2c0 1.104.896 2 2 2zm6 0v7H6v-7h12z" />
    </svg>
    <input 
        type="password" 
        id="password" 
        name="password" 
        required 
        autocomplete="current-password"
        placeholder="Password"
        class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
    >
    <button 
        type="button" 
        id="togglePassword" 
        tabindex="-1"
        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
    >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>
    </button>
</div>

<!-- Submit Button -->
<div>
    <button 
        type="submit" 
        class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
    >
        Sign In
    </button>
</div>
<div class="text-right mb-4">
  <a href="forgot_password.php" class="text-sm text-blue-600 hover:underline">Forgot Password?</a>
</div>

        </form>

       
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            if (toggleBtn && passwordInput) {
                toggleBtn.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                });
            }

            // Clear URL parameters after page load
            if (window.location.search) {
                const url = new URL(window.location);
                url.search = '';
                window.history.replaceState({}, document.title, url);
            }
        });
    </script>
</body>
</html>