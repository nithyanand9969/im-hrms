<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'admin') {
    die("Unauthorized.");
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=leave-report-" . date("Y-m-d") . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border='1'>";
echo "<tr>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Leave Balance</th>
        <th>Leaves Taken (Current Month)</th>
        <th>Days Present (Assumed)</th>
    </tr>";

$currentMonth = date('m');
$currentYear = date('Y');

$sql = "SELECT id, name, email, role, leave_balance FROM users WHERE role != 'admin'";
$result = $conn->query($sql);

while ($user = $result->fetch_assoc()) {
    $userId = $user['id'];

    // Fetch leave taken this month
    $stmt = $conn->prepare("SELECT SUM(
        CASE 
            WHEN leave_duration = 'Half Day' THEN 0.5 
            ELSE DATEDIFF(to_date, from_date) + 1 
        END
    ) AS taken 
    FROM leave_requests 
    WHERE user_id = ? 
    AND status = 'approved' 
    AND MONTH(from_date) = ? 
    AND YEAR(from_date) = ?");
    $stmt->bind_param("iii", $userId, $currentMonth, $currentYear);
    $stmt->execute();
    $taken = $stmt->get_result()->fetch_assoc()['taken'] ?? 0;
    $stmt->close();

    // Simulate attendance (assuming 22 working days - leaves)
    $daysPresent = 22 - $taken;

    echo "<tr>
            <td>{$user['name']}</td>
            <td>{$user['email']}</td>
            <td>{$user['role']}</td>
            <td>{$user['leave_balance']}</td>
            <td>{$taken}</td>
            <td>{$daysPresent}</td>
        </tr>";
}
echo "</table>";
?>
