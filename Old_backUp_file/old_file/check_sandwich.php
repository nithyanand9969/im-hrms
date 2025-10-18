<?php
require_once 'config.php';
require_once 'sandwich_leave_handler.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['from_date'], $_POST['to_date'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$from_date = $_POST['from_date'];
$to_date = $_POST['to_date'];

// This will give you the sandwich check result
$result = checkSandwichLeave($from_date, $to_date);

// Calculate days to be deducted
$holidays = $result['holidays'];
$all_considered_dates = array_unique(array_merge($result['all_dates'], $result['sandwich_dates']));
sort($all_considered_dates);

$deduct_days = 0;
foreach ($all_considered_dates as $date) {
    if (!isWeekend($date) && !in_array($date, $holidays)) {
        $deduct_days++;
    } elseif (!in_array($date, $result['all_dates'])) {
        $deduct_days++; // Only sandwich
    }
}

echo json_encode([
    'is_sandwich' => $result['is_sandwich'],
    'sandwich_dates' => $result['sandwich_dates'],
    'deducted_days' => $deduct_days
]);
