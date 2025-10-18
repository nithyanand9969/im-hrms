<?php
require_once '../connecting_fIle/config.php';

$currentDate = date('Y-m-d');
$currentMonth = date('Y-m');

// Log script trigger
file_put_contents('credit_log.txt', "Leave credit script triggered at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Check database connection
if (!$conn) {
    file_put_contents('credit_log.txt', "Error: Database connection failed: " . mysqli_connect_error() . " at $currentDate\n", FILE_APPEND);
    exit;
}

// Get logged-in user ID from session
$loggedInUserId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
if (!$loggedInUserId) {
    file_put_contents('credit_log.txt', "Error: No user ID found in session at $currentDate\n", FILE_APPEND);
    exit;
}

// Check if today is the 12th
if (date('d') !== '12') {
    file_put_contents('credit_log.txt', "Info: Not the 12th of the month, no leave credit applied at $currentDate\n", FILE_APPEND);
    exit;
}

// Fetch user data
$sql = "SELECT id, leave_balance, last_leave_credited_at FROM users WHERE id = ? AND is_active = 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    file_put_contents('credit_log.txt', "Error: SQL prepare failed: " . $conn->error . " at $currentDate\n", FILE_APPEND);
    exit;
}
$stmt->bind_param("i", $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $userId = $user['id'];
    $balance = $user['leave_balance'];
    $lastCredited = $user['last_leave_credited_at'] ?? null;

    file_put_contents('credit_log.txt', "Processing user ID: $userId, Balance: $balance, Last Credited: " . ($lastCredited ?? 'null') . " at $currentDate\n", FILE_APPEND);

    // Check if leave was already credited this month
    $lastCreditedMonth = $lastCredited ? date('Y-m', strtotime($lastCredited)) : null;
    if ($lastCreditedMonth !== $currentMonth) {
        $newBalance = $balance + 2;
        $stmt2 = $conn->prepare("UPDATE users SET leave_balance = ?, last_leave_credited_at = ? WHERE id = ?");
        if (!$stmt2) {
            file_put_contents('credit_log.txt', "Error: SQL prepare failed for update: " . $conn->error . " at $currentDate\n", FILE_APPEND);
            $stmt->close();
            exit;
        }
        $stmt2->bind_param("dsi", $newBalance, $currentDate, $userId);
        if ($stmt2->execute()) {
            file_put_contents('credit_log.txt', "Success: User $userId credited 2 leaves (new balance: $newBalance) at $currentDate\n", FILE_APPEND);
        } else {
            file_put_contents('credit_log.txt', "Error: Failed to credit 2 leaves for user $userId: " . $stmt2->error . " at $currentDate\n", FILE_APPEND);
        }
        $stmt2->close();
    } else {
        file_put_contents('credit_log.txt', "Info: User $userId already credited this month at $currentDate\n", FILE_APPEND);
    }
} else {
    file_put_contents('credit_log.txt', "Error: No active user found with ID $loggedInUserId at $currentDate\n", FILE_APPEND);
}
$stmt->close();
?>