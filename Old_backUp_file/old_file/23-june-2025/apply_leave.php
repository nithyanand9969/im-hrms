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

    // Sandwich policy calculation
    $from_day = date('N', strtotime($from_date));
    $to_day   = date('N', strtotime($to_date));
    $sandwich_days = 0;

    if (($from_day == 5 && $to_day == 1) || // Friday to Monday
        ($from_day == 5 && $leave_duration == 'Second Half' && $to_day == 1 && $leave_duration == 'First Half')) {
        $sandwich_days = 2;
    }

    $datediff = (strtotime($to_date) - strtotime($from_date)) / (60 * 60 * 24) + 1;
    $leave_days = ($leave_duration == 'Half Day') ? 0.5 : $datediff + $sandwich_days;

    $approval_level = 0;
    $approval_required = ($role == 'admin') ? 1 : 2;

    $stmt = $conn->prepare("INSERT INTO leave_requests 
        (user_id, leave_type, from_date, to_date, leave_duration, reason, status, applied_on, approval_level, approval_required)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
    $stmt->bind_param("issssssii", $user_id, $leave_type, $from_date, $to_date, $leave_duration, $reason, $today, $approval_level, $approval_required);

    if ($stmt->execute()) {
        echo "Leave request submitted successfully.";
    } else {
        echo "Database error.";
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>

<!-- Leave Form -->
<div class="max-w-xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4 text-indigo-700">Apply for Leave</h2>
    <form method="POST">
        <label class="block mb-2">Leave Type</label>
        <select name="leave_type" class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:outline-none transition mb-4" required>
            <option value="">Select</option>
            <option value="Casual Leave">Casual Leave</option>
            <option value="Sick Leave">Sick Leave</option>
        </select>

        <label class="block mb-2">From Date</label>
        <input type="date" name="from_date" class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:outline-none transition mb-4" required>

        <label class="block mb-2">To Date</label>
        <input type="date" name="to_date" class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:outline-none transition mb-4" required>

        <label class="block mb-2">Leave Duration</label>
        <select name="leave_duration" class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:outline-none transition mb-4" required>
            <option value="Full Day">Full Day</option>
            <option value="Half Day">Half Day</option>
            <option value="First Half">First Half</option>
            <option value="Second Half">Second Half</option>
        </select>

        <label class="block mb-2">Reason</label>
        <textarea name="reason" class="w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:outline-none transition mb-4" required></textarea>

        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition duration-200">Submit</button>
    </form>
</div>
