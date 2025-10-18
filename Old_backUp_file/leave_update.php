<?php
session_start();
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the default timezone to IST (Asia/Kolkata)
date_default_timezone_set('Asia/Kolkata');

require_once 'config.php'; // Ensure this file exists and provides $conn (your database connection)

use DateTime;
use DateInterval;
use DatePeriod;

// --- START: Helper Functions Definition ---

/**
 * Fetches all holiday dates from the 'holiday' table.
 *
 * @param mysqli $conn The database connection.
 * @return array An array of holiday dates in 'YYYY-MM-DD' format.
 */
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

/**
 * Applies the sandwich leave policy by calculating the total contiguous leave block
 * and adjusting the user's leave balance.
 *
 * This function identifies a continuous block of approved leaves, including any
 * sandwiched weekends or holidays, and then calculates the total deduction
 * for that entire block, handling special rules like "Fri 2H + Mon 1H = 4 days".
 * It also intelligently adjusts for any previous deductions made for parts of this block.
 *
 * @param mysqli $conn The database connection.
 * @param int $current_leave_id The ID of the leave request that was just approved.
 * @param int $user_id The ID of the user whose leave is being processed.
 */
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

    // For half-day leaves, the 'to_date' in DB might be the same as 'from_date'.
    // Ensure current_to accurately reflects the single day if it's a half-day.
    if (strtolower($current_leave['leave_duration']) === 'half day') {
        $current_to = clone $current_from; // Still same day, but a DateTime object
    }

    // Initialize the boundaries of the contiguous block with the current leave's dates
    $min_block_date = clone $current_from;
    $max_block_date = clone $current_to;

    // --- Search backwards for contiguous approved leaves or non-working days ---
    // The loop condition ensures we only check dates before the current min_block_date
    while (true) {
        $temp_date_obj = clone $min_block_date; // Clone to avoid modifying $min_block_date directly in the loop
        $prev_day_dt = $temp_date_obj->modify('-1 day');
        $prev_day = $prev_day_dt->format('Y-m-d');
        $prev_weekday = (int)$prev_day_dt->format('w'); // 0 = Sunday, 6 = Saturday

        $found_contiguous = false;

        // Check for approved full-day leave ending on the previous day
        $stmt_prev_full = $conn->prepare("SELECT from_date FROM leave_requests WHERE user_id = ? AND to_date = ? AND leave_duration != 'Half Day' AND status = 'approved'");
        $stmt_prev_full->bind_param("is", $user_id, $prev_day);
        $stmt_prev_full->execute();
        $res_prev_full = $stmt_prev_full->get_result();
        if ($res_prev_full->num_rows > 0) {
            $prev_leave = $res_prev_full->fetch_assoc();
            $min_block_date = new DateTime($prev_leave['from_date']); // Extend block start
            $found_contiguous = true;
        }
        $stmt_prev_full->close();

        if ($found_contiguous) {
            continue; // Keep searching backwards from the new start of the block
        }

        // Check for approved half-day (second half) leave on the previous day
        $stmt_prev_half = $conn->prepare("SELECT from_date FROM leave_requests WHERE user_id = ? AND from_date = ? AND leave_duration = 'Half Day' AND half_day_type = 'second half' AND status = 'approved'");
        $stmt_prev_half->bind_param("is", $user_id, $prev_day);
        $stmt_prev_half->execute();
        $res_prev_half = $stmt_prev_half->get_result();
        if ($res_prev_half->num_rows > 0) {
            $prev_leave = $res_prev_half->fetch_assoc();
            $min_block_date = new DateTime($prev_leave['from_date']); // Extend block start (half-day from_date)
            $found_contiguous = true;
        }
        $stmt_prev_half->close();

        if ($found_contiguous) {
            continue; // Keep searching backwards from the new start of the block
        }

        // Check if previous day is a weekend or holiday
        if (in_array($prev_day, $holidays) || in_array($prev_weekday, [0, 6])) {
            $min_block_date = $prev_day_dt; // Include this non-working day in the block
            continue; // Keep searching backwards through non-working days
        }

        // If no contiguous leave, weekend, or holiday found, the backward block ends
        break;
    }

    // --- Search forwards for contiguous approved leaves or non-working days ---
    // The loop condition ensures we only check dates after the current max_block_date
    while (true) {
        $temp_date_obj = clone $max_block_date; // Clone to avoid modifying $max_block_date directly in the loop
        $next_day_dt = $temp_date_obj->modify('+1 day');
        $next_day = $next_day_dt->format('Y-m-d');
        $next_weekday = (int)$next_day_dt->format('w'); // 0 = Sunday, 6 = Saturday

        $found_contiguous = false;

        // Check for approved full-day leave starting on the next day
        $stmt_next_full = $conn->prepare("SELECT to_date FROM leave_requests WHERE user_id = ? AND from_date = ? AND leave_duration != 'Half Day' AND status = 'approved'");
        $stmt_next_full->bind_param("is", $user_id, $next_day);
        $stmt_next_full->execute();
        $res_next_full = $stmt_next_full->get_result();
        if ($res_next_full->num_rows > 0) {
            $next_leave = $res_next_full->fetch_assoc();
            $max_block_date = new DateTime($next_leave['to_date']); // Extend block end
            $found_contiguous = true;
        }
        $stmt_next_full->close();

        if ($found_contiguous) {
            continue; // Keep searching forwards from the new end of the block
        }

        // Check for approved half-day (first half) leave on the next day
        $stmt_next_half = $conn->prepare("SELECT to_date FROM leave_requests WHERE user_id = ? AND from_date = ? AND leave_duration = 'Half Day' AND half_day_type = 'first half' AND status = 'approved'");
        $stmt_next_half->bind_param("is", $user_id, $next_day);
        $stmt_next_half->execute();
        $res_next_half = $stmt_next_half->get_result();
        if ($res_next_half->num_rows > 0) {
            $next_leave = $res_next_half->fetch_assoc();
            $max_block_date = new DateTime($next_leave['to_date']); // Extend block end (half-day to_date)
            $found_contiguous = true;
        }
        $stmt_next_half->close();

        if ($found_contiguous) {
            continue; // Keep searching forwards from the new end of the block
        }

        // Check if next day is a weekend or holiday
        if (in_array($next_day, $holidays) || in_array($next_weekday, [0, 6])) {
            $max_block_date = $next_day_dt; // Include this non-working day in the block
            continue; // Keep searching forwards through non-working days
        }

        // If no contiguous leave, weekend, or holiday found, the forward block ends
        break;
    }

    // Now we have the full contiguous block from $min_block_date to $max_block_date
    $block_start_str = $min_block_date->format('Y-m-d');
    $block_end_str = $max_block_date->format('Y-m-d');

    // Create a DatePeriod that includes the end date for iteration
    $period_for_deduction = new DatePeriod($min_block_date, new DateInterval('P1D'), (clone $max_block_date)->modify('+1 day'));

    // Fetch all leaves within this determined block (including the current one for its half-day type)
    $stmt_block_leaves = $conn->prepare(
        "SELECT id, from_date, to_date, leave_duration, half_day_type, deducted_amount FROM leave_requests
        WHERE user_id = ? AND from_date BETWEEN ? AND ? AND (status = 'approved' OR id = ?)" // Include the current leave as its now "approved" for calc
    );
    $stmt_block_leaves->bind_param("isss", $user_id, $block_start_str, $block_end_str, $current_leave_id);
    $stmt_block_leaves->execute();
    $result_block_leaves = $stmt_block_leaves->get_result();
    $leaves_in_block = []; // Indexed by date for easy lookup, store full row for 'deducted_amount'
    $leave_ids_in_block = []; // Store just IDs for resetting
    $previous_deductions_in_block = 0.0;

    while ($row = $result_block_leaves->fetch_assoc()) {
        $leaves_in_block[$row['from_date']] = $row;
        $leave_ids_in_block[] = $row['id'];
        // Sum up previous deductions for leaves within this block.
        // We will add this back to balance and then re-deduct the final calculated amount.
        $previous_deductions_in_block += $row['deducted_amount'];
    }
    $stmt_block_leaves->close();

    $final_deduction_for_block = 0.0;

    // Determine if the *entire calculated block* (min_block_date to max_block_date) is "sandwiched"
    $is_block_sandwiched = false;

    // Check if the block is flanked by non-working days on its true edges
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

    // Also consider it sandwiched if it contains any internal non-working days
    foreach ($period_for_deduction as $date_obj_internal) {
        $internal_day_str = $date_obj_internal->format('Y-m-d');
        $internal_weekday = (int)$date_obj_internal->format('w');
        if (in_array($internal_day_str, $holidays) || in_array($internal_weekday, [0, 6])) {
            $is_block_sandwiched = true;
            break;
        }
    }

    if ($is_block_sandwiched) {
        // Apply specific sandwich rules or general rule
        $start_weekday = (int)date('w', strtotime($block_start_str));
        $end_weekday = (int)date('w', strtotime($block_end_str));

        // Rule: Fri 2H + Mon 1H = 4 days
        if ($start_weekday == 5 && $end_weekday == 1 && // Friday to Monday
            isset($leaves_in_block[$block_start_str]) && strtolower($leaves_in_block[$block_start_str]['leave_duration']) === 'half day' && strtolower($leaves_in_block[$block_start_str]['half_day_type']) === 'second half' && // Friday is 2H
            isset($leaves_in_block[$block_end_str]) && strtolower($leaves_in_block[$block_end_str]['leave_duration']) === 'half day' && strtolower($leaves_in_block[$block_end_str]['half_day_type']) === 'first half' // Monday is 1H
        ) {
            $final_deduction_for_block = 4;
            error_log("DEBUG: Specific rule matched: Fri 2H + Mon 1H. Deduction: 4 days for block ($block_start_str to $block_end_str).");
        }
        // Rule: Thu 2H + No Mon 1H (but Mon is end of block) = 4.5 days
        // This implies Thursday leave, and the period extends *through* Monday but Monday might not have a specific 1H leave.
        // This rule is more ambiguous - if Monday is not a leave day, why deduct a full day for it?
        // Re-interpreting to mean if Thursday 2H is taken, and Friday, Sat, Sun are not working/taken, and Monday is a working day (but not taken as 1H leave)
        // This specific rule implies charging for sandwiched non-working days + the implicit working days if not taken as leave.
        // Given your previous explicit 4.5 day instruction, this assumes all days in this sequence are charged.
        elseif ($start_weekday == 4 && $end_weekday == 1 && // Block spans Thursday to Monday
                 isset($leaves_in_block[$block_start_str]) && strtolower($leaves_in_block[$block_start_str]['leave_duration']) === 'half day' && strtolower($leaves_in_block[$block_start_str]['half_day_type']) === 'second half'
                 // The "No Mon 1H" part implies that if the Mon is not a 1H leave, it's still part of the 4.5 days.
                 // This is covered by being part of the $block_end_str which is Mon.
                 // This rule needs careful validation to ensure it's precisely what's desired.
        ) {
            $final_deduction_for_block = 4.5; // 0.5 (Thu) + 1 (Fri) + 1 (Sat) + 1 (Sun) + 1 (Mon)
            error_log("DEBUG: Specific rule matched: Thu 2H + Mon boundary. Deduction: 4.5 days for block ($block_start_str to $block_end_str).");
        }
        else {
            // General sandwich rule: deduct all working days AND all sandwiched non-working days.
            // This is the most common interpretation.
            foreach ($period_for_deduction as $date_obj) {
                $current_day_str = $date_obj->format('Y-m-d');
                $current_weekday = (int)$date_obj->format('w');

                // If it's a working day and has a leave, count it based on duration.
                // If it's a working day but *no explicit leave* (i.e., user was present, but it's part of a sandwich),
                // or if it's a non-working day, count it as a full day in a sandwich.
                if (isset($leaves_in_block[$current_day_str])) {
                    $leave_info = $leaves_in_block[$current_day_str];
                    if (strtolower($leave_info['leave_duration']) === 'half day') {
                        $final_deduction_for_block += 0.5; // Only deduct 0.5 if it's explicitly a half-day leave
                    } else {
                        $final_deduction_for_block += 1.0; // Full day leave
                    }
                } elseif (!in_array($current_day_str, $holidays) && !in_array($current_weekday, [0, 6])) {
                    // It's a working day with no explicit leave, but it's within a sandwiched block.
                    // This implies it should be charged as a full day.
                    $final_deduction_for_block += 1.0;
                    error_log("DEBUG: Added 1.0 for working day $current_day_str in sandwiched block with no explicit leave.");
                } else {
                    // It's a non-working day (weekend or holiday) within a sandwiched block.
                    $final_deduction_for_block += 1.0;
                    error_log("DEBUG: Added 1.0 for non-working day $current_day_str in sandwiched block.");
                }
            }
            error_log("DEBUG: General sandwich rule applied for block ($block_start_str to $block_end_str). Deduction: $final_deduction_for_block");
        }
    } else {
        // If the block is NOT sandwiched, deduct only actual leave days (half or full).
        foreach ($period_for_deduction as $date_obj) {
            $current_day_str = $date_obj->format('Y-m-d');
            $current_weekday = (int)$date_obj->format('w');

            // Only consider working days that are not holidays AND have an explicit leave
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
        error_log("DEBUG: Block is NOT sandwiched. Deducting actual leave days for block ($block_start_str to $block_end_str): $final_deduction_for_block");
    }

    // --- Adjust Leave Balance ---

    // 1. Get current leave balance
    $stmt_balance = $conn->prepare("SELECT leave_balance FROM users WHERE id = ?");
    $stmt_balance->bind_param("i", $user_id);
    $stmt_balance->execute();
    $current_balance = $stmt_balance->get_result()->fetch_assoc()['leave_balance'];
    $stmt_balance->close();

    // 2. Add back previously deducted amounts for all leaves in this block
    if ($previous_deductions_in_block > 0) {
        $stmt_add_back = $conn->prepare("UPDATE users SET leave_balance = leave_balance + ? WHERE id = ?");
        $stmt_add_back->bind_param("di", $previous_deductions_in_block, $user_id);
        $stmt_add_back->execute();
        $stmt_add_back->close();
        error_log("DEBUG: Added back $previous_deductions_in_block days to user $user_id's balance (for re-evaluation of block $block_start_str to $block_end_str).");
    }

    // 3. Reset `deducted_amount` for all leaves within this block
    if (!empty($leave_ids_in_block)) {
        $ids_placeholder = implode(',', array_fill(0, count($leave_ids_in_block), '?'));
        $stmt_reset_deducted_amount = $conn->prepare("UPDATE leave_requests SET deducted_amount = 0 WHERE id IN ($ids_placeholder)");
        $types = str_repeat('i', count($leave_ids_in_block));
        $stmt_reset_deducted_amount->bind_param($types, ...$leave_ids_in_block);
        $stmt_reset_deducted_amount->execute();
        $stmt_reset_deducted_amount->close();
        error_log("DEBUG: Reset deducted_amount to 0 for leaves: " . implode(', ', $leave_ids_in_block));
    }

    // 4. Deduct the *new total* for the entire block
    if ($final_deduction_for_block > 0) {
        $stmt_deduct_final = $conn->prepare("UPDATE users SET leave_balance = leave_balance - ? WHERE id = ?");
        $stmt_deduct_final->bind_param("di", $final_deduction_for_block, $user_id);
        $stmt_deduct_final->execute();
        $stmt_deduct_final->close();
        error_log("DEBUG: Final deduction for user $user_id's contiguous block ($block_start_str to $block_end_str): $final_deduction_for_block days.");

        // 5. Update the `deducted_amount` for the *current* leave (the one just approved)
        // This leaf now "holds" the total deduction for the entire contiguous block.
        $stmt_update_current_deduction = $conn->prepare("UPDATE leave_requests SET deducted_amount = ? WHERE id = ?");
        $stmt_update_current_deduction->bind_param("di", $final_deduction_for_block, $current_leave_id);
        $stmt_update_current_deduction->execute();
        $stmt_update_current_deduction->close();
        error_log("DEBUG: Set deducted_amount of current leave ($current_leave_id) to $final_deduction_for_block.");
    }

    error_log("👀 Final Deduction process complete for user $user_id.");
}

// These functions are no longer directly used for calculating deduction as their
// logic is now integrated into applySandwichLeavePolicy. Kept as empty shells
// for clarity if they are referenced elsewhere or for future expansion.
function handleHalfDayLogic($conn, $leave_date, $user_id, $holidays, $half_day_type) {
    return 0; // This function is effectively deprecated for deduction calculations
}

function handleFullDayLogic($from_date, $to_date, $holidays, $conn, $user_id) {
    return 0; // This function is effectively deprecated for deduction calculations
}

// --- END: Helper Functions Definition ---


// --- START: Main Script Logic ---

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Unauthorized access.";
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$role = strtolower($user['role']);
$email = strtolower($user['email']);

// ✅ NEW LEAVE SUBMISSION
if (!isset($_POST['leave_id'])) {
    $leave_type     = $_POST['leave_type'];
    $leave_duration = $_POST['leave_duration'];
    $half_day_type  = $_POST['half_day_type'] ?? '';
    $from_date      = $_POST['from_date'];
    $reason         = $_POST['reason'];
    $to_date        = ($leave_duration === 'Half Day') ? $from_date : ($_POST['to_date'] ?? $from_date);

    if (!$leave_type || !$leave_duration || !$from_date || !$reason) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'All required fields must be filled.'];
        header("Location: {$role}-dashboard.php");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO leave_requests
        (user_id, leave_type, leave_duration, half_day_type, from_date, to_date, reason, status, deducted_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 0.0)"); // Initialize deducted_amount to 0
    $stmt->bind_param("issssss", $user_id, $leave_type, $leave_duration, $half_day_type, $from_date, $to_date, $reason);
    $stmt->execute();
    if ($stmt->error) {
        error_log("Error inserting leave request: " . $stmt->error);
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error submitting leave request.'];
    } else {
        $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave request submitted.'];
    }
    $stmt->close();

    header("Location: {$role}-dashboard.php");
    exit;
}

// ✅ APPROVAL FLOW
$leave_id = intval($_POST['leave_id']);
$action   = $_POST['action'] ?? '';

if (!in_array($action, ['approve', 'reject'])) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Invalid action.'];
    header("Location: {$role}-dashboard.php");
    exit;
}

// ✅ FETCH LEAVE details for the current request
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
$is_rachna       = ($email === 'rachna@integritymatters.in');
$is_applicant_rachna = ($applicant_email === 'rachna@integritymatters.in');

// ✅ Self Approval Block
if ($email === $applicant_email) {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'You cannot approve your own leave.'];
    header("Location: {$role}-dashboard.php");
    exit;
}

// ✅ Prevent Duplicate Action
$stmt = $conn->prepare("SELECT 1 FROM leave_approvals WHERE leave_id = ? AND approved_by = ?");
$stmt->bind_param("is", $leave_id, $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['toast'] = ['type' => 'warning', 'message' => 'You have already taken action on this leave.'];
    header("Location: {$role}-dashboard.php");
    exit;
}
$stmt->close();

// ✅ REJECTION FLOW
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
    header("Location: {$role}-dashboard.php");
    exit;
}

// ✅ APPROVAL FLOW (action is 'approve')
$stmt = $conn->prepare("INSERT INTO leave_approvals (leave_id, approved_by, decision) VALUES (?, ?, 'approved')");
$stmt->bind_param("is", $leave_id, $email);
$stmt->execute();
if ($stmt->error) {
    error_log("Error inserting approval: " . $stmt->error);
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error logging approval.'];
    header("Location: {$role}-dashboard.php");
    exit;
}
$stmt->close();

// ✅ Update Approval Level
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
    header("Location: {$role}-dashboard.php");
    exit;
}
$stmt->close();

// ✅ Final Approval Check
$stmt = $conn->prepare("SELECT approval_level, approval_level_2 FROM leave_requests WHERE id = ?");
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$levels = $stmt->get_result()->fetch_assoc();
$stmt->close();

$is_final = false;
if ($is_applicant_rachna) {
    // If applicant is Rachna, only one approval level is needed (by a non-Rachna admin)
    $is_final = ($levels['approval_level'] == 1);
} elseif ($role === 'admin' && !$is_rachna) {
    // If approver is Admin (not Rachna), they are level 1 approver for non-Rachna applicants.
    // Final approval depends on Rachna's approval (level 2) being already set, OR if Rachna is not required for this user's leave.
    // Assuming level 2 is always required for non-Rachna applicants.
    $is_final = ($levels['approval_level'] == 1 && $levels['approval_level_2'] == 1);
} else {
    // General case: Both level 1 and level 2 are required
    $is_final = ($levels['approval_level'] == 1 && $levels['approval_level_2'] == 1);
}

// Determine new status based on final approval check
$new_status = $is_final ? 'approved' : 'pending';
$stmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
$stmt->bind_param("si", $new_status, $leave_id);
$stmt->execute();
if ($stmt->error) {
    error_log("Error updating leave status to $new_status: " . $stmt->error);
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Error updating leave status.'];
    header("Location: {$role}-dashboard.php");
    exit;
}
$stmt->close();

// ✅ Final Deduction Logic - ONLY if leave is fully approved
if ($is_final) {
    applySandwichLeavePolicy($conn, $leave['id'], $leave['user_id']); // Call the new unified function
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave fully approved and balance deducted.'];
} else {
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave approved. Awaiting final approval.'];
}

header("Location: {$role}-dashboard.php");
exit;

// --- END: Main Script Logic ---

?>