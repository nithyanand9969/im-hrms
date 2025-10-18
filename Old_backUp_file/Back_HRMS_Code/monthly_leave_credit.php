<?php
require_once 'config.php';

$currentDate = date('Y-m-d');
$isJanFirst = date('m-d') === '01-01';

$sql = "SELECT id, date_of_joining, leave_balance, credited_15_days, last_leave_credited_at FROM users WHERE is_active = 1";
$result = $conn->query($sql);

if ($result) {
    while ($user = $result->fetch_assoc()) {
        $userId = $user['id'];
        $joinDate = $user['date_of_joining'];
        $balance = $user['leave_balance'];
        $credited15 = (bool)$user['credited_15_days'];
        $lastCredited = $user['last_leave_credited_at'];

        $joinTimestamp = strtotime($joinDate);
        $daysSinceJoin = floor((strtotime($currentDate) - $joinTimestamp) / (60 * 60 * 24));
        $newBalance = $balance;

        // Rule 1: Jan 1 reset leave if more than 10
        if ($isJanFirst && $balance > 10) {
            $newBalance = 10;
        }

        // Rule 2: Credit +1 after 15 days (once only)
        if (!$credited15 && $daysSinceJoin >= 15) {
            $newBalance += 1;
            $stmt = $conn->prepare("UPDATE users SET leave_balance = ?, credited_15_days = 1 WHERE id = ?");
            $stmt->bind_param("di", $newBalance, $userId);
            $stmt->execute();
            $stmt->close();
            continue;
        }

        // Rule 3: After 30 days, add 2 leave per month
        if ($daysSinceJoin >= 30) {
            $monthStart = date('Y-m-01');
            if (!$lastCredited || $lastCredited < $monthStart) {
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
