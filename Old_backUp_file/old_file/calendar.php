<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$user_role = strtolower($_SESSION['user']['role']);

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

// Fetch all approved leaves
$stmt = $conn->prepare("SELECT from_date, to_date, leave_type, leave_duration FROM leave_requests WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$leaveDates = [];
while ($row = $result->fetch_assoc()) {
    $start = new DateTime($row['from_date']);
    $end = new DateTime($row['to_date']);
    while ($start <= $end) {
        $dateStr = $start->format('Y-m-d');
        $leaveDates[$dateStr] = [
            'type' => $row['leave_type'],
            'duration' => $row['leave_duration']
        ];
        $start->modify('+1 day');
    }
}
$stmt->close();

// Detect sandwich leaves
$sandwichLeaves = [];
foreach ($leaveDates as $date => $info) {
    $timestamp = strtotime($date);
    $prevDay = date('Y-m-d', strtotime('-1 day', $timestamp));
    $nextDay = date('Y-m-d', strtotime('+1 day', $timestamp));

    if ((in_array($prevDay, array_keys($holidays)) || date('N', strtotime($prevDay)) >= 6) &&
        (in_array($nextDay, array_keys($holidays)) || date('N', strtotime($nextDay)) >= 6)) {
        $sandwichLeaves[] = $date;
    }
}

$calendarEvents = [];
foreach ($holidays as $date => $title) {
    $calendarEvents[] = [
        'title' => $title,
        'start' => $date,
        'color' => '#f87171'
    ];
}
foreach ($leaveDates as $date => $leave) {
    $calendarEvents[] = [
        'title' => "Leave",
        'start' => $date,
        'color' => in_array($date, $sandwichLeaves) ? '#facc15' : '#60a5fa'
    ];
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
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Leave & Holiday Calendar 2025</h2>
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
