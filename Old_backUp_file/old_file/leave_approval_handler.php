<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Unauthorized access.";
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$role = strtolower($user['role']);
$leave_id = intval($_POST['leave_id']);
$action = $_POST['action']; // 'approve' or 'reject'

// Fetch leave request
$stmt = $conn->prepare("SELECT * FROM leave_requests WHERE id = ?");
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$result = $stmt->get_result();
$leave = $result->fetch_assoc();
$stmt->close();

if (!$leave) {
    echo "Leave request not found.";
    exit();
}

$applicant_id = $leave['user_id'];
$from_date = $leave['from_date'];
$to_date = $leave['to_date'];
$leave_duration = strtolower($leave['leave_duration']);

// Check if already approved by this admin
$stmt = $conn->prepare("SELECT * FROM leave_approvals WHERE leave_id = ? AND admin_id = ?");
$stmt->bind_param("ii", $leave_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    echo "You have already approved this leave.";
    exit();
}
$stmt->close();

// Fetch applicant's role
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $applicant_id);
$stmt->execute();
$result = $stmt->get_result();
$applicant = $result->fetch_assoc();
$stmt->close();

$applicant_role = strtolower($applicant['role']);

// Rejection flow
if ($action === 'reject') {
    $stmt = $conn->prepare("UPDATE leave_requests SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// Log approval
$stmt = $conn->prepare("INSERT INTO leave_approvals (leave_id, admin_id) VALUES (?, ?)");
$stmt->bind_param("ii", $leave_id, $user_id);
$stmt->execute();
$stmt->close();

// Insert or update leave_approvals_status
$stmt = $conn->prepare("SELECT * FROM leave_approvals_status WHERE leave_id = ?");
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$status_result = $stmt->get_result();
$status = $status_result->fetch_assoc();
$stmt->close();

if (!$status) {
    $stmt = $conn->prepare("INSERT INTO leave_approvals_status (leave_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $leave_id, $applicant_id);
    $stmt->execute();
    $stmt->close();

    $status = [
        'is_manager_approved' => 0,
        'is_admin_approved_level1' => 0,
        'is_admin_approved_level2' => 0,
        'is_deducted' => 0,
    ];
}

// Update flags based on role
if ($applicant_role === 'user') {
    if ($role === 'manager') {
        $conn->query("UPDATE leave_approvals_status SET is_manager_approved = 1 WHERE leave_id = $leave_id");
    } elseif ($role === 'admin' && $status['is_manager_approved']) {
        if (!$status['is_admin_approved_level1']) {
            $conn->query("UPDATE leave_approvals_status SET is_admin_approved_level1 = 1 WHERE leave_id = $leave_id");
        } elseif (!$status['is_admin_approved_level2']) {
            $conn->query("UPDATE leave_approvals_status SET is_admin_approved_level2 = 1 WHERE leave_id = $leave_id");
        }
    }
    
    $check = $conn->query("SELECT * FROM leave_approvals_status WHERE leave_id = $leave_id");
    $row = $check->fetch_assoc();

    if ($row['is_manager_approved'] && $row['is_admin_approved_level1'] && $row['is_admin_approved_level2'] && !$row['is_deducted']) {
        $days = ($leave_duration === 'half day') ? 0.5 : (new DateTime($to_date))->diff(new DateTime($from_date))->days + 1;
        $stmt = $conn->prepare("UPDATE users SET leave_balance = leave_balance - ? WHERE id = ?");
        $stmt->bind_param("di", $days, $applicant_id);
        $stmt->execute();
        $stmt->close();

        $conn->query("UPDATE leave_approvals_status SET is_deducted = 1, status = 2 WHERE leave_id = $leave_id");
        $conn->query("UPDATE leave_requests SET status = 'approved' WHERE id = $leave_id");
    }

} elseif ($applicant_role === 'manager') {
    if ($role === 'admin') {
        if (!$status['is_admin_approved_level1']) {
            $conn->query("UPDATE leave_approvals_status SET is_admin_approved_level1 = 1 WHERE leave_id = $leave_id");
        } elseif (!$status['is_admin_approved_level2']) {
            $conn->query("UPDATE leave_approvals_status SET is_admin_approved_level2 = 1 WHERE leave_id = $leave_id");
        }

        $check = $conn->query("SELECT * FROM leave_approvals_status WHERE leave_id = $leave_id");
        $row = $check->fetch_assoc();

        if ($row['is_admin_approved_level1'] && $row['is_admin_approved_level2'] && !$row['is_deducted']) {
            $days = ($leave_duration === 'half day') ? 0.5 : (new DateTime($to_date))->diff(new DateTime($from_date))->days + 1;
            $stmt = $conn->prepare("UPDATE users SET leave_balance = leave_balance - ? WHERE id = ?");
            $stmt->bind_param("di", $days, $applicant_id);
            $stmt->execute();
            $stmt->close();

            $conn->query("UPDATE leave_approvals_status SET is_deducted = 1, status = 2 WHERE leave_id = $leave_id");
            $conn->query("UPDATE leave_requests SET status = 'approved' WHERE id = $leave_id");
        }
    }
} elseif ($applicant_role === 'admin') {
    if ($role === 'admin' && $applicant_id !== $user_id && !$status['is_deducted']) {
        $days = ($leave_duration === 'half day') ? 0.5 : (new DateTime($to_date))->diff(new DateTime($from_date))->days + 1;
        $stmt = $conn->prepare("UPDATE users SET leave_balance = leave_balance - ? WHERE id = ?");
        $stmt->bind_param("di", $days, $applicant_id);
        $stmt->execute();
        $stmt->close();

        $conn->query("UPDATE leave_approvals_status SET is_deducted = 1, status = 2 WHERE leave_id = $leave_id");
        $conn->query("UPDATE leave_requests SET status = 'approved' WHERE id = $leave_id");
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
