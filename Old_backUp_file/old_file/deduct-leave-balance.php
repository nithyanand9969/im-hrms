<?php
function deductLeaveBalance($conn, $user_id, $from_date, $to_date, $leave_duration, $leave_id) {
    // Check if already deducted
    $stmt = $conn->prepare("SELECT is_deducted FROM leave_approvals_status WHERE leave_id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row || $row['is_deducted']) {
        return; // Already deducted or missing record
    }

    $leave_days = 0;
    $leave_duration = strtolower(trim($leave_duration));

    if ($leave_duration === 'half day') {
        $leave_days = 0.5;
    } else {
        $from = new DateTime($from_date);
        $to = new DateTime($to_date);
        $leave_days = $from->diff($to)->days + 1;
    }

    // Deduct leave from user table
    $stmt = $conn->prepare("UPDATE users SET leave_balance = leave_balance - ? WHERE id = ?");
    $stmt->bind_param("di", $leave_days, $user_id);
    $stmt->execute();
    $stmt->close();

    // Mark as deducted
    $stmt = $conn->prepare("UPDATE leave_approvals_status SET is_deducted = 1 WHERE leave_id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $stmt->close();
}
?>
