<?php
session_start();
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Unauthorized access.";
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$role = strtolower($user['role']);
$email = strtolower($user['email']);

// ✅ If no leave_id, treat as apply request
if (!isset($_POST['leave_id'])) {
    $leave_type     = $_POST['leave_type'];
    $leave_duration = $_POST['leave_duration'];
    $half_day_type  = $_POST['half_day_type'] ?? '';
    $from_date      = $_POST['from_date'];
    $to_date        = $_POST['to_date'] ?? $from_date;
    $reason         = $_POST['reason'];

    if (!$leave_type || !$leave_duration || !$from_date || !$reason) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'All required fields must be filled.'];
        header("Location: {$role}-dashboard.php");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO leave_requests 
        (user_id, leave_type, leave_duration, half_day_type, from_date, to_date, reason, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("issssss", $user_id, $leave_type, $leave_duration, $half_day_type, $from_date, $to_date, $reason);
    
    if ($stmt->execute()) {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave request submitted.'];
    } else {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Failed to submit leave request.'];
    }
    $stmt->close();
    header("Location: {$role}-dashboard.php");
    exit;
}

// ✅ If leave_id is provided — handle approval/rejection
$leave_id = intval($_POST['leave_id']);
$action = $_POST['action'] ?? '';

if (!in_array($action, ['approve', 'reject'])) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid action.'];
    header("Location: {$role}-dashboard.php");
    exit;
}

// ✅ Fetch leave request with applicant email
$stmt = $conn->prepare("SELECT lr.*, u.email as applicant_email FROM leave_requests lr JOIN users u ON lr.user_id = u.id WHERE lr.id = ?");
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$leave = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$leave) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Leave not found.'];
    header("Location: {$role}-dashboard.php");
    exit;
}

$applicant_email = strtolower($leave['applicant_email']);
$is_rachna = ($email === 'rachna@integritymatters.in');
$is_applicant_rachna = ($applicant_email === 'rachna@integritymatters.in');

// ✅ Prevent same user from approving their own leave
if ($email === $applicant_email) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'You cannot approve your own leave.'];
    header("Location: {$role}-dashboard.php");
    exit;
}

// ✅ Prevent duplicate action
$stmt = $conn->prepare("SELECT 1 FROM leave_approvals WHERE leave_id = ? AND approved_by = ?");
$stmt->bind_param("is", $leave_id, $email);
$stmt->execute();
$alreadyActioned = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($alreadyActioned) {
    $_SESSION['toast'] = ['type' => 'warning', 'message' => 'You have already taken action on this leave.'];
    header("Location: {$role}-dashboard.php");
    exit;
}

// ✅ If rejected, mark final
if ($action === 'reject') {
    $stmt = $conn->prepare("UPDATE leave_requests SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO leave_approvals (leave_id, approved_by, decision) VALUES (?, ?, 'rejected')");
    $stmt->bind_param("is", $leave_id, $email);
    $stmt->execute();
    $stmt->close();

    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave rejected.'];
    header("Location: {$role}-dashboard.php");
    exit;
}

// ✅ Log approval
$stmt = $conn->prepare("INSERT INTO leave_approvals (leave_id, approved_by, decision) VALUES (?, ?, 'approved')");
$stmt->bind_param("is", $leave_id, $email);
$stmt->execute();
$stmt->close();

// ✅ Update approval levels
if ($is_rachna) {
    $stmt = $conn->prepare("UPDATE leave_requests SET approval_level_2 = 1 WHERE id = ?");
} else {
    $stmt = $conn->prepare("UPDATE leave_requests SET approval_level = 1 WHERE id = ?");
}
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$stmt->close();

// ✅ Check final approval
$stmt = $conn->prepare("SELECT approval_level, approval_level_2 FROM leave_requests WHERE id = ?");
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$levels = $stmt->get_result()->fetch_assoc();
$stmt->close();

$level1 = (int)$levels['approval_level'];
$level2 = (int)$levels['approval_level_2'];
$is_final = false;

if ($is_applicant_rachna) {
    $is_final = ($level1 === 1); // Rachna's leave needs one admin
} elseif ($role === 'admin' && !$is_rachna) {
    $is_final = ($level2 === 1); // Other admin leave needs Rachna
} else {
    $is_final = ($level1 === 1 && $level2 === 1); // All others
}

$new_status = $is_final ? 'approved' : 'pending';
$stmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
$stmt->bind_param("si", $new_status, $leave_id);
$stmt->execute();
$stmt->close();

if ($is_final) {
    applySandwichLeavePolicy($conn, $leave['from_date'], $leave['to_date'], $leave['user_id'], $leave['leave_duration']);
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave fully approved and balance deducted.'];
} else {
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave approved. Awaiting final approval.'];
}

header("Location: {$role}-dashboard.php");
exit;

// ✅ Sandwich Leave Policy
function applySandwichLeavePolicy($conn, $from_date, $to_date, $user_id, $leave_duration) {
    $holidays = [];
    $res = $conn->query("SELECT holiday_date FROM holiday");
    while ($row = $res->fetch_assoc()) {
        $holidays[] = $row['holiday_date'];
    }

    $deduct_days = 0;
    $leave_duration = strtolower(trim($leave_duration));

    if ($leave_duration === 'half day') {
        $isHoliday = in_array($from_date, $holidays);
        $isWeekend = in_array(date('w', strtotime($from_date)), [0, 6]);
        if (!$isHoliday && !$isWeekend) {
            $deduct_days = 0.5;
        }
    } else {
        $from = new DateTime($from_date);
        $to = new DateTime($to_date);
        $period = new DatePeriod($from, new DateInterval('P1D'), $to->modify('+1 day'));
        $dates = array_map(fn($d) => $d->format('Y-m-d'), iterator_to_array($period));

        $day_before = date('Y-m-d', strtotime($dates[0] . ' -1 day'));
        $day_after = date('Y-m-d', strtotime(end($dates) . ' +1 day'));

        $is_sandwich = (in_array($day_before, $holidays) || in_array(date('w', strtotime($day_before)), [0,6]))
                    && (in_array($day_after, $holidays) || in_array(date('w', strtotime($day_after)), [0,6]));

        foreach ($dates as $date) {
            $dow = date('w', strtotime($date));
            if (!in_array($date, $holidays) && !in_array($dow, [0, 6])) {
                $deduct_days++;
            }
        }

        if ($is_sandwich) {
            $deduct_days = count($dates); // Include all
        }
    }

    if ($deduct_days > 0) {
        $stmt = $conn->prepare("UPDATE users SET leave_balance = GREATEST(leave_balance - ?, 0) WHERE id = ?");
        $stmt->bind_param("di", $deduct_days, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>
