<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    echo "Access denied.";
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$role = strtolower($user['role']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type     = $_POST['leave_type'] ?? '';
    $from_date      = $_POST['from_date'] ?? '';
    $to_date        = $_POST['to_date'] ?? '';
    $leave_duration = $_POST['leave_duration'] ?? 'Full Day';
    $reason         = $_POST['reason'] ?? '';
    $today          = date('Y-m-d');

    // Insert into leave_requests
    $stmt = $conn->prepare("INSERT INTO leave_requests (user_id, leave_type, from_date, to_date, leave_duration, reason, status, approval_level, approval_required, applied_date) VALUES (?, ?, ?, ?, ?, ?, 'pending', 0, ?, ?)");
    $required_approval = ($role === 'user') ? 3 : (($role === 'manager') ? 2 : 1);
    $stmt->bind_param("isssssis", $user_id, $leave_type, $from_date, $to_date, $leave_duration, $reason, $required_approval, $today);
    $stmt->execute();
    $leave_id = $stmt->insert_id;
    $stmt->close();

    // Insert into leave_approvals_status
    $stmt = $conn->prepare("INSERT INTO leave_approvals_status (leave_id, user_id, is_manager_approved, is_admin_approved_level1, is_admin_approved_level2, is_deducted, status) VALUES (?, ?, 0, 0, 0, 0, 0)");
    $stmt->bind_param("ii", $leave_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['toast'] = [
        'type' => 'success',
        'message' => 'Leave request submitted successfully.'
    ];
    header("Location: user-dashboard.php");
    exit();
} else {
    echo "Invalid request.";
    exit();
}
?>
