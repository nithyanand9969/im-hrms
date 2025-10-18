<?php
session_start();
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the default timezone to IST (Asia/Kolkata)
date_default_timezone_set('Asia/Kolkata');

require_once '../connecting_fIle/config.php'; // Ensure this file exists and provides $conn (your database connection)

use DateTime;
use DateInterval;
use DatePeriod;

function fetchHolidays($conn) {
    $holidays = [];
    $res = $conn->query("SELECT holiday_date FROM holiday");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $holidays[] = $row['holiday_date'];
        }
    } else {
        error_log("Error fetching holidays: " . $conn->error);
    }
    return $holidays;
}

function redirectByRole($role) {
    switch ($role) {
        case 'admin':
            $dashboard = '../admin_pages/admin-dashboard.php';
            break;
        case 'manager':
            $dashboard = '../manager_pages/manager-dashboard.php';
            break;
        case 'senior_manager':
            $dashboard = '../senior_manager_pages/senior-manager-dashboard.php';
            break;
        default:
            $dashboard = '../user_pages/user-dashboard.php';
            break;
    }
    header("Location: $dashboard");
    exit;
}

function applySandwichLeavePolicy($conn, $current_leave_id, $user_id) {
    $holidays = fetchHolidays($conn);

    // Fetch the details of the current leave being approved
    $stmt_current = $conn->prepare("SELECT from_date, to_date, leave_duration, half_day_type FROM leave_requests WHERE id = ? AND user_id = ?");
    $stmt_current->bind_param("ii", $current_leave_id, $user_id);
    $stmt_current->execute();
    $current_leave = $stmt_current->get_result()->fetch_assoc();
    $stmt_current->close();

    if (!$current_leave) {
        error_log("Error: Current leave (ID: $current_leave_id, User: $user_id) not found for sandwich policy calculation.");
        return;
    }

    $current_from = new DateTime($current_leave['from_date']);
    $current_to = new DateTime($current_leave['to_date']);

    if (strtolower($current_leave['leave_duration']) === 'half day') {
        $current_to = clone $current_from;
    }

    $min_block_date = clone $current_from;
    $max_block_date = clone $current_to;

    while (true) {
        $temp_date_obj = clone $min_block_date;
        $prev_day_dt = $temp_date_obj->modify('-1 day');
        $prev_day = $prev_day_dt->format('Y-m-d');
        $prev_weekday = (int)$prev_day_dt->format('w');

        $found_contiguous = false;

        $stmt_prev_full = $conn->prepare("SELECT from_date FROM leave_requests WHERE user_id = ? AND to_date = ? AND leave_duration != 'Half Day' AND status = 'approved'");
        $stmt_prev_full->bind_param("is", $user_id, $prev_day);
        $stmt_prev_full->execute();
        $res_prev_full = $stmt_prev_full->get_result();
        if ($res_prev_full->num_rows > 0) {
            $prev_leave = $res_prev_full->fetch_assoc();
            $min_block_date = new DateTime($prev_leave['from_date']);
            $found_contiguous = true;
        }
        $stmt_prev_full->close();

        if ($found_contiguous) {
            continue;
        }

        $stmt_prev_half = $conn->prepare("SELECT from_date FROM leave_requests WHERE user_id = ? AND from_date = ? AND leave_duration = 'Half Day' AND half_day_type = 'second half' AND status = 'approved'");
        $stmt_prev_half->bind_param("is", $user_id, $prev_day);
        $stmt_prev_half->execute();
        $res_prev_half = $stmt_prev_half->get_result();
        if ($res_prev_half->num_rows > 0) {
            $prev_leave = $res_prev_half->fetch_assoc();
            $min_block_date = new DateTime($prev_leave['from_date']);
            $found_contiguous = true;
        }
        $stmt_prev_half->close();

        if ($found_contiguous) {
            continue;
        }

        if (in_array($prev_day, $holidays) || in_array($prev_weekday, [0, 6])) {
            $min_block_date = $prev_day_dt;
            continue;
        }

        break;
    }

    while (true) {
        $temp_date_obj = clone $max_block_date;
        $next_day_dt = $temp_date_obj->modify('+1 day');
        $next_day = $next_day_dt->format('Y-m-d');
        $next_weekday = (int)$next_day_dt->format('w');

        $found_contiguous = false;

        $stmt_next_full = $conn->prepare("SELECT to_date FROM leave_requests WHERE user_id = ? AND from_date = ? AND leave_duration != 'Half Day' AND status = 'approved'");
        $stmt_next_full->bind_param("is", $user_id, $next_day);
        $stmt_next_full->execute();
        $res_next_full = $stmt_next_full->get_result();
        if ($res_next_full->num_rows > 0) {
            $next_leave = $res_next_full->fetch_assoc();
            $max_block_date = new DateTime($next_leave['to_date']);
            $found_contiguous = true;
        }
        $stmt_next_full->close();

        if ($found_contiguous) {
            continue;
        }

        $stmt_next_half = $conn->prepare("SELECT to_date FROM leave_requests WHERE user_id = ? AND from_date = ? AND leave_duration = 'Half Day' AND half_day_type = 'first half' AND status = 'approved'");
        $stmt_next_half->bind_param("is", $user_id, $next_day);
        $stmt_next_half->execute();
        $res_next_half = $stmt_next_half->get_result();
        if ($res_next_half->num_rows > 0) {
            $next_leave = $res_next_half->fetch_assoc();
            $max_block_date = new DateTime($next_leave['to_date']);
            $found_contiguous = true;
        }
        $stmt_next_half->close();

        if ($found_contiguous) {
            continue;
        }

        if (in_array($next_day, $holidays) || in_array($next_weekday, [0, 6])) {
            $max_block_date = $next_day_dt;
            continue;
        }

        break;
    }

    $block_start_str = $min_block_date->format('Y-m-d');
    $block_end_str = $max_block_date->format('Y-m-d');
    $period_for_deduction = new DatePeriod($min_block_date, new DateInterval('P1D'), (clone $max_block_date)->modify('+1 day'));

    $stmt_block_leaves = $conn->prepare(
        "SELECT id, from_date, to_date, leave_duration, half_day_type, deducted_amount FROM leave_requests
        WHERE user_id = ? AND from_date BETWEEN ? AND ? AND (status = 'approved' OR id = ?)"
    );
    $stmt_block_leaves->bind_param("isss", $user_id, $block_start_str, $block_end_str, $current_leave_id);
    $stmt_block_leaves->execute();
    $result_block_leaves = $stmt_block_leaves->get_result();
    $leaves_in_block = [];
    $leave_ids_in_block = [];
    $previous_deductions_in_block = 0.0;

    while ($row = $result_block_leaves->fetch_assoc()) {
        $leaves_in_block[$row['from_date']] = $row;
        $leave_ids_in_block[] = $row['id'];
        $previous_deductions_in_block += $row['deducted_amount'];
    }
    $stmt_block_leaves->close();

    $final_deduction_for_block = 0.0;

    $is_block_sandwiched = false;
    $temp_start_dt_for_check = clone $min_block_date;
    $day_before_block_start = $temp_start_dt_for_check->modify('-1 day')->format('Y-m-d');
    $day_before_block_start_weekday = (int)date('w', strtotime($day_before_block_start));
    $temp_end_dt_for_check = clone $max_block_date;
    $day_after_block_end = $temp_end_dt_for_check->modify('+1 day')->format('Y-m-d');
    $day_after_block_end_weekday = (int)date('w', strtotime($day_after_block_end));

    if (
        (in_array($day_before_block_start, $holidays) || in_array($day_before_block_start_weekday, [0, 6])) ||
        (in_array($day_after_block_end, $holidays) || in_array($day_after_block_end_weekday, [0, 6]))
    ) {
        $is_block_sandwiched = true;
    }

    foreach ($period_for_deduction as $date_obj_internal) {
        $internal_day_str = $date_obj_internal->format('Y-m-d');
        $internal_weekday = (int)$date_obj_internal->format('w');
        if (in_array($internal_day_str, $holidays) || in_array($internal_weekday, [0, 6])) {
            $is_block_sandwiched = true;
            break;
        }
    }

    if ($is_block_sandwiched) {
        $start_weekday = (int)date('w', strtotime($block_start_str));
        $end_weekday = (int)date('w', strtotime($block_end_str));

        if ($start_weekday == 5 && $end_weekday == 1 &&
            isset($leaves_in_block[$block_start_str]) && strtolower($leaves_in_block[$block_start_str]['leave_duration']) === 'half day' && strtolower($leaves_in_block[$block_start_str]['half_day_type']) === 'second half' &&
            isset($leaves_in_block[$block_end_str]) && strtolower($leaves_in_block[$block_end_str]['leave_duration']) === 'half day' && strtolower($leaves_in_block[$block_end_str]['half_day_type']) === 'first half'
        ) {
            $final_deduction_for_block = 4;
        }
        elseif ($start_weekday == 4 && $end_weekday == 1 &&
                 isset($leaves_in_block[$block_start_str]) && strtolower($leaves_in_block[$block_start_str]['leave_duration']) === 'half day' && strtolower($leaves_in_block[$block_start_str]['half_day_type']) === 'second half'
        ) {
            $final_deduction_for_block = 4.5;
        } else {
            foreach ($period_for_deduction as $date_obj) {
                $current_day_str = $date_obj->format('Y-m-d');
                $current_weekday = (int)$date_obj->format('w');

                if (isset($leaves_in_block[$current_day_str])) {
                    $leave_info = $leaves_in_block[$current_day_str];
                    if (strtolower($leave_info['leave_duration']) === 'half day') {
                        $final_deduction_for_block += 0.5;
                    } else {
                        $final_deduction_for_block += 1.0;
                    }
                } elseif (!in_array($current_day_str, $holidays) && !in_array($current_weekday, [0, 6])) {
                    $final_deduction_for_block += 1.0;
                } else {
                    $final_deduction_for_block += 1.0;
                }
            }
        }
    } else {
        foreach ($period_for_deduction as $date_obj) {
            $current_day_str = $date_obj->format('Y-m-d');
            $current_weekday = (int)$date_obj->format('w');
            if (!in_array($current_day_str, $holidays) && !in_array($current_weekday, [0, 6])) {
                if (isset($leaves_in_block[$current_day_str])) {
                    $leave_info = $leaves_in_block[$current_day_str];
                    if (strtolower($leave_info['leave_duration']) === 'half day') {
                        $final_deduction_for_block += 0.5;
                    } else {
                        $final_deduction_for_block += 1.0;
                    }
                }
            }
        }
    }

    $stmt_balance = $conn->prepare("SELECT leave_balance FROM users WHERE id = ?");
    $stmt_balance->bind_param("i", $user_id);
    $stmt_balance->execute();
    $current_balance = $stmt_balance->get_result()->fetch_assoc()['leave_balance'];
    $stmt_balance->close();

    if ($previous_deductions_in_block > 0) {
        $stmt_add_back = $conn->prepare("UPDATE users SET leave_balance = leave_balance + ? WHERE id = ?");
        $stmt_add_back->bind_param("di", $previous_deductions_in_block, $user_id);
        $stmt_add_back->execute();
        $stmt_add_back->close();
    }

    if (!empty($leave_ids_in_block)) {
        $ids_placeholder = implode(',', array_fill(0, count($leave_ids_in_block), '?'));
        $stmt_reset_deducted_amount = $conn->prepare("UPDATE leave_requests SET deducted_amount = 0 WHERE id IN ($ids_placeholder)");
        $types = str_repeat('i', count($leave_ids_in_block));
        $stmt_reset_deducted_amount->bind_param($types, ...$leave_ids_in_block);
        $stmt_reset_deducted_amount->execute();
        $stmt_reset_deducted_amount->close();
    }

    if ($final_deduction_for_block > 0) {
        $stmt_deduct_final = $conn->prepare("UPDATE users SET leave_balance = leave_balance - ? WHERE id = ?");
        $stmt_deduct_final->bind_param("di", $final_deduction_for_block, $user_id);
        $stmt_deduct_final->execute();
        $stmt_deduct_final->close();

        $stmt_update_current_deduction = $conn->prepare("UPDATE leave_requests SET deducted_amount = ? WHERE id = ?");
        $stmt_update_current_deduction->bind_param("di", $final_deduction_for_block, $current_leave_id);
        $stmt_update_current_deduction->execute();
        $stmt_update_current_deduction->close();
    }
}
// --- END: Helper Functions Definition ---

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Unauthorized access.";
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$role = strtolower($user['role']);
$email = strtolower($user['email']);

// NEW LEAVE SUBMISSION
if (!isset($_POST['leave_id'])) {
    $leave_type     = $_POST['leave_type'];
    $leave_duration = $_POST['leave_duration'];
    $half_day_type  = $_POST['half_day_type'] ?? '';
    $from_date      = $_POST['from_date'];
    $reason         = $_POST['reason'];
    $to_date        = ($leave_duration === 'Half Day') ? $from_date : ($_POST['to_date'] ?? $from_date);

    if (!$leave_type || !$leave_duration || !$from_date || !$reason) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'All required fields must be filled.'];
        redirectByRole($role);
    }

    $stmt = $conn->prepare("INSERT INTO leave_requests (user_id, leave_type, leave_duration, half_day_type, from_date, to_date, reason, status, deducted_amount)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 0.0)");
    $stmt->bind_param("issssss", $user_id, $leave_type, $leave_duration, $half_day_type, $from_date, $to_date, $reason);
    $stmt->execute();
    if ($stmt->error) {
        error_log("Error inserting leave request: " . $stmt->error);
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error submitting leave request.'];
    } else {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave request submitted.'];
    }
    $stmt->close();

    redirectByRole($role);
}

// APPROVAL FLOW
$leave_id = intval($_POST['leave_id']);
$action   = $_POST['action'] ?? '';

if (!in_array($action, ['approve', 'reject'])) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid action.'];
    redirectByRole($role);
}

// FETCH LEAVE details for the current request
$stmt = $conn->prepare("SELECT lr.*, u.email as applicant_email FROM leave_requests lr JOIN users u ON lr.user_id = u.id WHERE lr.id = ?");
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$leave = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$leave) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Leave not found.'];
    redirectByRole($role);
}

$applicant_email = strtolower($leave['applicant_email']);
$is_rachna       = ($email === 'rachna@integritymatters.in');
$is_applicant_rachna = ($applicant_email === 'rachna@integritymatters.in');

// Self Approval Block
if ($email === $applicant_email) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'You cannot approve your own leave.'];
    redirectByRole($role);
}

// Prevent Duplicate Action
$stmt = $conn->prepare("SELECT 1 FROM leave_approvals WHERE leave_id = ? AND approved_by = ?");
$stmt->bind_param("is", $leave_id, $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['toast'] = ['type' => 'warning', 'message' => 'You have already taken action on this leave.'];
    redirectByRole($role);
}
$stmt->close();

// REJECTION FLOW
if ($action === 'reject') {
    $stmt = $conn->prepare("UPDATE leave_requests SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    if ($stmt->error) {
        error_log("Error rejecting leave request: " . $stmt->error);
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error rejecting leave.'];
    } else {
        $stmt = $conn->prepare("INSERT INTO leave_approvals (leave_id, approved_by, decision) VALUES (?, ?, 'rejected')");
        $stmt->bind_param("is", $leave_id, $email);
        $stmt->execute();
        if ($stmt->error) {
            error_log("Error inserting rejection approval: " . $stmt->error);
            $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error logging rejection.'];
        } else {
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave rejected.'];
        }
        $stmt->close();
    }
    redirectByRole($role);
}

// APPROVAL FLOW (action is 'approve')
$stmt = $conn->prepare("INSERT INTO leave_approvals (leave_id, approved_by, decision) VALUES (?, ?, 'approved')");
$stmt->bind_param("is", $leave_id, $email);
$stmt->execute();
if ($stmt->error) {
    error_log("Error inserting approval: " . $stmt->error);
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error logging approval.'];
    redirectByRole($role);
}
$stmt->close();

// Update Approval Level
if ($is_rachna) {
    $stmt = $conn->prepare("UPDATE leave_requests SET approval_level_2 = 1 WHERE id = ?");
} else {
    $stmt = $conn->prepare("UPDATE leave_requests SET approval_level = 1 WHERE id = ?");
}
$stmt->bind_param("i", $leave_id);
$stmt->execute();
if ($stmt->error) {
    error_log("Error updating approval level: " . $stmt->error);
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error updating approval level.'];
    redirectByRole($role);
}
$stmt->close();

// Final Approval Check
$stmt = $conn->prepare("SELECT approval_level, approval_level_2 FROM leave_requests WHERE id = ?");
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$levels = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_final = false;
if ($is_applicant_rachna) {
    $is_final = ($levels['approval_level'] == 1);
} elseif ($role === 'admin' && !$is_rachna) {
    $is_final = ($levels['approval_level'] == 1 && $levels['approval_level_2'] == 1);
} else {
    $is_final = ($levels['approval_level'] == 1 && $levels['approval_level_2'] == 1);
}

$new_status = $is_final ? 'approved' : 'pending';
$stmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
$stmt->bind_param("si", $new_status, $leave_id);
$stmt->execute();
if ($stmt->error) {
    error_log("Error updating leave status to $new_status: " . $stmt->error);
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error updating leave status.'];
    redirectByRole($role);
}
$stmt->close();

// Final Deduction Logic - ONLY if leave is fully approved
if ($is_final) {
    applySandwichLeavePolicy($conn, $leave['id'], $leave['user_id']);
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave fully approved and balance deducted.'];
} else {
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave approved. Awaiting final approval.'];
}

redirectByRole($role);

?>
