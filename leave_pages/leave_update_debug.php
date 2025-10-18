
<?php
session_start();
require_once '../connecting_fIle/config.php';

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Unauthorized access.");
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$role = strtolower($user['role']);
$email = $user['email'];

$leave_id = intval($_POST['leave_id']);
$action = $_POST['action'] ?? '';

// Log the start
file_put_contents("debug_log.txt", "Processing leave ID: $leave_id\n", FILE_APPEND);

// Fetch leave request
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE id = ?");
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$leave = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$leave) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Leave request not found.'];
    header("Location: {$role}-dashboard.php");
    exit();
}

$applicant_id = (int)$leave['user_id'];
$current_level = (int)$leave['approval_level'];
$required_level = (int)$leave['approval_required'];
$from_date = $leave['from_date'];
$to_date = $leave['to_date'] ?: $from_date;
$leave_duration = $leave['leave_duration'];
$half_day_type = $leave['half_day_type'] ?? '';

// Reject logic
if ($action === 'reject') {
    $stmt = $conn->prepare("UPDATE leave_requests SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave rejected.'];
    header("Location: {$role}-dashboard.php");
    exit();
}

// Prevent duplicate approval
$stmt = $conn->prepare("SELECT id FROM leave_approvals WHERE leave_id = ? AND admin_id = ? AND role = ?");
$stmt->bind_param("iis", $leave_id, $user_id, $role);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['toast'] = ['type' => 'info', 'message' => 'You already approved this leave.'];
    header("Location: {$role}-dashboard.php");
    exit();
}
$stmt->close();

// Log approval
$stmt = $conn->prepare("INSERT INTO leave_approvals (leave_id, admin_id, role, approved_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $leave_id, $user_id, $role);
$stmt->execute();
$stmt->close();

$current_level++;

if ($current_level >= $required_level) {
    $stmt = $conn->prepare("UPDATE leave_requests SET status = 'approved', approval_level = ? WHERE id = ?");
    $stmt->bind_param("ii", $current_level, $leave_id);
    $stmt->execute();
    $stmt->close();

    // Apply deduction
    file_put_contents("debug_log.txt", "Final approval met. Deducting balance.\n", FILE_APPEND);
    applySandwichLeavePolicy($conn, $from_date, $to_date, $applicant_id, $leave_duration, $half_day_type, $leave_id);
} else {
    $stmt = $conn->prepare("UPDATE leave_requests SET approval_level = ? WHERE id = ?");
    $stmt->bind_param("ii", $current_level, $leave_id);
    $stmt->execute();
    $stmt->close();
}

$_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave approved successfully.'];
header("Location: {$role}-dashboard.php");
exit();

function applySandwichLeavePolicy($conn, $from_date, $to_date, $user_id, $leave_duration, $half_day_type, $leave_id) {
    try {
        $deduct_days = 0;
        $leave_duration = strtolower(trim($leave_duration));
        $half_day_type = strtolower(trim($half_day_type ?? ''));
        $from_day = date('N', strtotime($from_date));

        $holidays = [];
        $res = $conn->query("SELECT holiday_date FROM holiday");
        while ($row = $res->fetch_assoc()) {
            $holidays[] = $row['holiday_date'];
        }

        $is_half_day_combo = (
            $from_date === $to_date &&
            $leave_duration === 'half day' &&
            (
                ($from_day == 5 && $half_day_type === 'second half') ||
                ($from_day == 1 && $half_day_type === 'first half')
            )
        );

        if ($leave_duration === 'half day') {
            $deduct_days = $is_half_day_combo ? 2.5 : 0.5;
        } else {
            $from = new DateTime($from_date);
            $to = new DateTime($to_date);
            $interval = new DateInterval('P1D');
            $period = new DatePeriod($from, $interval, $to->modify('+1 day'));

            $days = [];
            $hasFriday = false;
            $hasMonday = false;

            foreach ($period as $date) {
                $dayStr = $date->format('Y-m-d');
                $days[] = $dayStr;
                $dow = $date->format('N');
                if ($dow == 5) $hasFriday = true;
                if ($dow == 1) $hasMonday = true;
            }

            if ($hasFriday && $hasMonday) {
                foreach ($days as $d) {
                    if (date('N', strtotime($d)) == 5) {
                        $days[] = date('Y-m-d', strtotime("$d +1 day"));
                        $days[] = date('Y-m-d', strtotime("$d +2 day"));
                    }
                }
            }

            $days = array_unique($days);
            sort($days);

            foreach ($days as $day) {
                $weekday = date('N', strtotime($day));
                if ($weekday <= 5 || in_array($day, $holidays)) {
                    $deduct_days++;
                }
            }
        }

        // Lock balance row
        $conn->begin_transaction();
        $stmt = $conn->prepare("SELECT leave_balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $current_balance = (float)($result['leave_balance'] ?? 0);
        $new_balance = $current_balance - $deduct_days;

        file_put_contents("debug_log.txt", "Deducting $deduct_days days. Old: $current_balance, New: $new_balance\n", FILE_APPEND);

        $stmt = $conn->prepare("UPDATE users SET leave_balance = ? WHERE id = ?");
        $stmt->bind_param("di", $new_balance, $user_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE leave_requests SET balance_updated = 1 WHERE id = ?");
        $stmt->bind_param("i", $leave_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        file_put_contents("debug_log.txt", "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
?>
