<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Unauthorized access.");
}

$user      = $_SESSION['user'];
$user_id   = $user['id'];
$role      = strtolower($user['role']);
$leave_id  = intval($_POST['leave_id']);
$action    = $_POST['action'] ?? '';

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

$current_level  = (int)$leave['approval_level'];
$applicant_id   = (int)$leave['user_id'];
$from_date      = $leave['from_date'];
$to_date        = $leave['to_date'] ?: $from_date; // Fallback if to_date is NULL or empty
$leave_duration = $leave['leave_duration'];

// Handle rejection
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

// Manager Approval
if ($role === 'manager' && $current_level === 0) {
    $stmt = $conn->prepare("UPDATE leave_requests SET approval_level = 1 WHERE id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $stmt->close();
}

// Admin Final Approval + Deduct Balance
if ($role === 'admin' && $current_level === 1) {
    $stmt = $conn->prepare("UPDATE leave_requests SET approval_level = 2, status = 'approved' WHERE id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $stmt->close();

    require_once 'deduct-leave-balance.php';
    applySandwichLeavePolicy($conn, $from_date, $to_date, $applicant_id, $leave_duration);
}

$_SESSION['toast'] = ['type' => 'success', 'message' => 'Leave approved successfully.'];
header("Location: {$role}-dashboard.php");
exit();
?>