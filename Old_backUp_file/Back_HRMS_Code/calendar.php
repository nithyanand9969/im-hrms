<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['user'];
$user_id = $currentUser['id'];
$user_role = strtolower($currentUser['role']);

// 2025 Holiday Dates
$holidays = [
    '2025-01-01' => "New Year's Day",
    '2025-01-14' => "Makar Sankranti",
    '2025-01-26' => "Republic Day",
    '2025-02-26' => "Maha Shiv Ratri",
    '2025-03-14' => "Holi",
    '2025-04-18' => "Good Friday",
    '2025-05-01' => "Maharashtra Formation Day",
    '2025-08-15' => "Independence Day",
    '2025-08-27' => "Ganesh Chaturthi",
    '2025-10-02' => "Dusshera & Gandhi Jayanti",
    '2025-10-21' => "Diwali",
    '2025-10-22' => "Diwali",
    '2025-12-25' => "Christmas Day"
];

// Get approved leaves
if ($user_role === 'admin') {
    $stmt = $conn->prepare("SELECT lr.from_date, lr.to_date, lr.leave_type, lr.leave_duration, u.name 
                            FROM leave_requests lr 
                            JOIN users u ON lr.user_id = u.id 
                            WHERE lr.status = 'approved'");
} else {
    $stmt = $conn->prepare("SELECT lr.from_date, lr.to_date, lr.leave_type, lr.leave_duration, u.name 
                            FROM leave_requests lr 
                            JOIN users u ON lr.user_id = u.id 
                            WHERE lr.user_id = ? AND lr.status = 'approved'");
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

$leaveDates = [];
while ($row = $result->fetch_assoc()) {
    $start = new DateTime($row['from_date']);
    $end = new DateTime($row['to_date']);
    while ($start <= $end) {
        $dateStr = $start->format('Y-m-d');
        $leaveDates[$dateStr][] = [
            'name' => $row['name'],
            'type' => $row['leave_type'],
            'duration' => $row['leave_duration']
        ];
        $start->modify('+1 day');
    }
}
$stmt->close();

// Detect sandwich leaves
$sandwichDates = [];
foreach ($leaveDates as $date => $entries) {
    $timestamp = strtotime($date);
    $prevDay = date('Y-m-d', strtotime('-1 day', $timestamp));
    $nextDay = date('Y-m-d', strtotime('+1 day', $timestamp));

    $isPrevHoliday = isset($holidays[$prevDay]) || date('N', strtotime($prevDay)) >= 6;
    $isNextHoliday = isset($holidays[$nextDay]) || date('N', strtotime($nextDay)) >= 6;

    if ($isPrevHoliday && $isNextHoliday) {
        $sandwichDates[] = $date;
    }
}

// Build events array
$calendarEvents = [];

// Add holidays
foreach ($holidays as $date => $title) {
    $calendarEvents[] = [
        'title' => $title,
        'start' => $date,
        'color' => '#f87171'  // red
    ];
}

// Add leaves
foreach ($leaveDates as $date => $entries) {
    foreach ($entries as $entry) {
        $calendarEvents[] = [
            'title' => $entry['name'] . ' - ' . $entry['type'] . ' (' . $entry['duration'] . ')',
            'start' => $date,
            'color' => in_array($date, $sandwichDates) ? '#facc15' : '#60a5fa', // yellow or blue
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Leave Calendar</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 p-6">
    <div class="bg-white shadow p-6 rounded-lg max-w-6xl mx-auto">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">
            <?= $user_role === 'admin' ? 'All Employees Leave & Holiday Calendar 2025' : 'Your Leave & Holiday Calendar 2025' ?>
        </h2>
        <div id="calendar"></div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#calendar').fullCalendar({
            height: 650,
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek'
            },
            defaultDate: '2025-01-01',
            editable: false,
            eventLimit: true,
            events: <?= json_encode($calendarEvents) ?>
        });
    });
    </script>
</body>
</html>
