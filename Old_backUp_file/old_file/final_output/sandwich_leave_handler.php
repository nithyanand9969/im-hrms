<?php
require_once 'config.php';

function isWeekend($date) {
    $day = date('w', strtotime($date)); // Sunday=0, Saturday=6
    return $day == 0 || $day == 6;
}

function isHoliday($date, $holidays) {
    return in_array($date, $holidays);
}

function checkSandwichLeave($from_date, $to_date) {
    global $conn;

    // Fetch holidays from DB
    $holidays = [];
    $result = $conn->query("SELECT holiday_date FROM holiday");
    while ($row = $result->fetch_assoc()) {
        $holidays[] = $row['holiday_date'];
    }

    // Create date range
    $from = new DateTime($from_date);
    $to = new DateTime($to_date);
    $period = new DatePeriod($from, new DateInterval('P1D'), $to->modify('+1 day'));

    $dates = [];
    foreach ($period as $date) {
        $dates[] = $date->format("Y-m-d");
    }

    $sandwich_dates = [];

    // Detect gaps with weekend/holiday in between leave dates
    for ($i = 0; $i < count($dates) - 1; $i++) {
        $current = new DateTime($dates[$i]);
        $next = new DateTime($dates[$i + 1]);

        if ($current->diff($next)->days === 2) {
            $middle = $current->modify('+1 day')->format('Y-m-d');
            if (isWeekend($middle) || isHoliday($middle, $holidays)) {
                $sandwich_dates[] = $middle;
            }
        }
    }

    // Friday–Monday sandwich check
    $hasFriday = $hasMonday = false;
    foreach ($dates as $d) {
        $day = date('N', strtotime($d)); // Monday=1, ..., Sunday=7
        if ($day == 5) $hasFriday = true;
        if ($day == 1) $hasMonday = true;
    }

    if ($hasFriday && $hasMonday) {
        foreach ($dates as $d) {
            if (date('N', strtotime($d)) == 5) {
                $saturday = date('Y-m-d', strtotime($d . ' +1 day'));
                $sunday = date('Y-m-d', strtotime($d . ' +2 days'));
                if (isWeekend($saturday)) $sandwich_dates[] = $saturday;
                if (isWeekend($sunday)) $sandwich_dates[] = $sunday;
            }
        }
    }

    return [
        'is_sandwich' => count($sandwich_dates) > 0,
        'sandwich_dates' => array_unique($sandwich_dates),
        'all_dates' => $dates,
        'holidays' => $holidays
    ];
}

function applySandwichAndDeductBalance($from_date, $to_date, $applicant_id) {
    global $conn;

    $result = checkSandwichLeave($from_date, $to_date);
    $all_dates = $result['all_dates'];
    $sandwich_dates = $result['sandwich_dates'];
    $holidays = $result['holidays'];

    $combined_dates = array_unique(array_merge($all_dates, $sandwich_dates));
    sort($combined_dates);

    $deduct_days = 0;
    foreach ($combined_dates as $date) {
        if (!isWeekend($date) && !isHoliday($date, $holidays)) {
            $deduct_days++;
        } elseif (!in_array($date, $all_dates)) {
            $deduct_days++; // Count sandwich-only
        }
    }

    // Fetch current leave balance
    $stmt = $conn->prepare("SELECT leave_balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $applicant_id);
    $stmt->execute();
    $result_set = $stmt->get_result();
    $user_data = $result_set->fetch_assoc();
    $stmt->close();

    $balance = $user_data['leave_balance'] ?? 0;
    $new_balance = max(0, $balance - $deduct_days);

    // Update user balance
    $stmt = $conn->prepare("UPDATE users SET leave_balance = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_balance, $applicant_id);
    $stmt->execute();
    $stmt->close();

    return $result['is_sandwich'] ? 'Yes' : 'No';
}
?>
