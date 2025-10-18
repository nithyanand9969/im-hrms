<?php
require_once 'config.php';

// Define holiday dates
$holidays = [
    '2025-01-01', '2025-01-14', '2025-01-26', '2025-02-26',
    '2025-03-14', '2025-04-18', '2025-05-01', '2025-08-15',
    '2025-08-27', '2025-10-02', '2025-10-21', '2025-10-22', '2025-12-25'
];

// Assume $from_date, $to_date, and $applicant_id are already defined
$from = new DateTime($from_date);
$to = new DateTime($to_date);
$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($from, $interval, $to->modify('+1 day'));

// Generate list of leave dates
$dates = [];
foreach ($period as $dt) {
    $dates[] = $dt->format("Y-m-d");
}

// Initialize weekend and holiday checkers
function isWeekend($date) {
    $day = date('w', strtotime($date));
    return $day == 0 || $day == 6;
}

function isHoliday($date, $holidays) {
    return in_array($date, $holidays);
}

// Sandwich logic (only if both Friday and Monday are selected)
$all_dates = $dates;
$hasFriday = false;
$hasMonday = false;

foreach ($dates as $date) {
    $dayOfWeek = date('N', strtotime($date)); // 5 = Friday, 1 = Monday
    if ($dayOfWeek == 5) $hasFriday = true;
    if ($dayOfWeek == 1) $hasMonday = true;
}

if ($hasFriday && $hasMonday) {
    foreach ($dates as $date) {
        $dayOfWeek = date('N', strtotime($date));
        if ($dayOfWeek == 5) {
            $saturday = date('Y-m-d', strtotime($date . ' +1 day'));
            $sunday = date('Y-m-d', strtotime($date . ' +2 days'));
            $all_dates[] = $saturday;
            $all_dates[] = $sunday;
        }
    }
}

// Remove duplicates and sort dates
$all_dates = array_unique($all_dates);
sort($all_dates);

// Count total leave days
$total_days = count($all_dates);

// Fetch user's current leave balance
$stmt = $conn->prepare("SELECT leave_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $applicant_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

$balance = $user_data['leave_balance'] ?? 0;
$new_balance = max(0, $balance - $total_days);

// Update leave balance
$stmt = $conn->prepare("UPDATE users SET leave_balance = ? WHERE id = ?");
$stmt->bind_param("ii", $new_balance, $applicant_id);
$stmt->execute();
$stmt->close();
?>
