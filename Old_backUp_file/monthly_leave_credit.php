<?php
require_once 'config.php';

$currentDate = date('Y-m-d');
$currentMonth = date('Y-m');

// ✅ FORCE TEST: Write log to confirm trigger
file_put_contents('credit_log.txt', "Leave credit script triggered at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// ✅ CREDIT LEAVE TO ALL ACTIVE USERS
$sql = "SELECT id, date_of_joining, leave_balance, credited_15_days, last_leave_credited_at FROM users WHERE is_active = 1";
$result = $conn->query($sql);

if ($result) {
    while ($user = $result->fetch_assoc()) {
        $userId       = $user['id'];
        $joinDate     = $user['date_of_joining'];
        $balance      = $user['leave_balance'];
        $credited15   = (bool)$user['credited_15_days'];
        $lastCredited = $user['last_leave_credited_at'];

        $daysSinceJoin = floor((strtotime($currentDate) - strtotime($joinDate)) / (60 * 60 * 24));
        $newBalance = $balance;

        // Rule 1: Jan 1st cap
        if (date('m-d') === '01-01' && $balance > 10) {
            $newBalance = 10;
            $stmt = $conn->prepare("UPDATE users SET leave_balance = ? WHERE id = ?");
            $stmt->bind_param("di", $newBalance, $userId);
            $stmt->execute();
            $stmt->close();
            continue;
        }

        // Rule 2: +1 leave after 15 days
        if (!$credited15 && $daysSinceJoin >= 15) {
            $newBalance += 1;
            $stmt = $conn->prepare("UPDATE users SET leave_balance = ?, credited_15_days = 1 WHERE id = ?");
            $stmt->bind_param("di", $newBalance, $userId);
            $stmt->execute();
            $stmt->close();
            continue;
        }

        // Rule 3: +2 every month after 30 days
        if ($daysSinceJoin >= 30) {
            $lastCreditedMonth = $lastCredited ? date('Y-m', strtotime($lastCredited)) : null;

            if ($lastCreditedMonth !== $currentMonth) {
                $newBalance += 2;
                $stmt = $conn->prepare("UPDATE users SET leave_balance = ?, last_leave_credited_at = ? WHERE id = ?");
                $stmt->bind_param("dsi", $newBalance, $currentDate, $userId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}
?>
