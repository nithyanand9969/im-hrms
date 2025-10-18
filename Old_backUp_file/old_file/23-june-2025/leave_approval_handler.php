
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
$current_level = $leave['approval_level'];
$required_level = $leave['approval_required'];
$from_date = $leave['from_date'];
$to_date = $leave['to_date'];

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

if ($action === 'reject') {
    $stmt = $conn->prepare("UPDATE leave_requests SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $leave_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

if ($action === 'approve') {
    // Log approval
    $stmt = $conn->prepare("INSERT INTO leave_approvals (leave_id, admin_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $leave_id, $user_id);
    $stmt->execute();
    $stmt->close();

    $current_level++;

    if ($current_level >= $required_level) {
        // Final approval - Check sandwich leave and update balance
        include 'sandwich_leave_handler.php'; // Will handle sandwich logic & balance update

        $stmt = $conn->prepare("UPDATE leave_requests SET status = 'approved', approval_level = ? WHERE id = ?");
        $stmt->bind_param("ii", $current_level, $leave_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Intermediate approval
        $stmt = $conn->prepare("UPDATE leave_requests SET approval_level = ? WHERE id = ?");
        $stmt->bind_param("ii", $current_level, $leave_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
?>
