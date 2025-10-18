<?php
require_once('config.php');

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="active_employees.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Role', 'Joining Date']);

$sql = "SELECT id, name, email, phone, role, date_of_joining 
        FROM users 
        WHERE is_active = 1 AND (has_left IS NULL OR has_left = 0)
        ORDER BY name";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit;
