<?php
session_start();
include_once '../connecting_fIle/config.php'; // make sure $conn is available

// ✅ Log the logout event if user session exists
if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id'] ?? 0;
    $userEmail = $_SESSION['user']['email'] ?? null;

    if ($userId || $userEmail) {
        $stmt = $conn->prepare("
            INSERT INTO event_log (user_id, user_email, action, details)
            VALUES (?, ?, 'Logout', 'User logged out')
        ");
        $stmt->bind_param("is", $userId, $userEmail);
        $stmt->execute();
        $stmt->close();
    }
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php");
http_response_code(200);
exit();
?>
