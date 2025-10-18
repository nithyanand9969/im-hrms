<?php
function applySandwichLeavePolicy($conn, $from_date, $to_date, $user_id, $leave_duration) {
    // Define fixed holidays
    $holidays = [
        '2025-01-01', '2025-01-14', '2025-01-26', '2025-02-26',
        '2025-03-14', '2025-04-18', '2025-05-01', '2025-08-15',
        '2025-08-27', '2025-10-02', '2025-10-21', '2025-10-22', '2025-12-25'
    ];

    $deduct_days = 0;
    $leave_duration = strtolower(trim($leave_duration));

    if ($leave_duration === 'half day') {
        $deduct_days = 0.5;
    } else {
        // Full Day or Multiple Days
        $from = new DateTime($from_date);
        $to = new DateTime($to_date);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($from, $interval, $to->modify('+1 day'));

        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            $weekday = $date->format('N'); // 1 = Monday, 7 = Sunday

            if (!in_array($day, $holidays) && $weekday < 6) {
                $deduct_days++;
            }
        }

        // Sandwich leave before/after logic
        $day_before = (new DateTime($from_date))->modify('-1 day')->format('Y-m-d');
        $day_after  = (new DateTime($to_date))->modify('+1 day')->format('Y-m-d');

        if (in_array($day_before, $holidays) || in_array(date('w', strtotime($day_before)), [0, 6])) {
            $deduct_days++;
        }
        if (in_array($day_after, $holidays) || in_array(date('w', strtotime($day_after)), [0, 6])) {
            $deduct_days++;
        }
    }

    // ✅ Fetch current leave balance
    $stmt = $conn->prepare("SELECT leave_balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $current_balance = (float)($result['leave_balance'] ?? 0);
    $new_balance = max(0, $current_balance - $deduct_days);

    // ✅ Update the leave balance
    $stmt = $conn->prepare("UPDATE users SET leave_balance = ? WHERE id = ?");
    $stmt->bind_param("di", $new_balance, $user_id);
    if ($stmt->execute()) {
        error_log("Leave balance updated: Deducted $deduct_days for user $user_id");
    } else {
        error_log("Failed to update leave balance for user $user_id");
    }
    $stmt->close();
}
?>
