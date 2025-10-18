<?php
// 1. Show errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Include DB config
require_once 'config.php'; // ✅ Make sure this file connects to $conn

// 3. Define test user ID and deduction
$user_id = 5; // Change as needed
$deduction = 1.0;

// 4. Fetch current balance
$stmt = $conn->prepare("SELECT leave_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$current_balance = $result['leave_balance'] ?? 0;
$stmt->close();

echo "<p>✅ Current leave balance: $current_balance</p>";

// 5. Calculate new balance
$new_balance = max(0, $current_balance - $deduction);

// 6. Update new balance
$stmt = $conn->prepare("UPDATE users SET leave_balance = ? WHERE id = ?");
$stmt->bind_param("di", $new_balance, $user_id);

if ($stmt->execute()) {
    echo "<p>✅ Leave balance updated to: $new_balance</p>";
} else {
    echo "<p>❌ Update failed: " . $stmt->error . "</p>";
}
$stmt->close();
$conn->close();
?>
