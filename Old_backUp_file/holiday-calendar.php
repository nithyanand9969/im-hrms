<?php
session_start();
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');

// Fetch holidays from the database
$sql = "SELECT holiday_date, holiday_name FROM holiday ORDER BY holiday_date ASC";
$result = $conn->query($sql);

$holidays = [];
while ($row = $result->fetch_assoc()) {
    $holidays[] = [
        'date' => $row['holiday_date'],
        'name' => $row['holiday_name'],
        'day'  => date('l', strtotime($row['holiday_date']))
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Holiday Calendar</title>
  <style>
    .hidden { display: none; }
    .bg-overlay { background: rgba(0,0,0,0.5); }
    .modal { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; position: relative; }
    .modal table { width: 100%; border-collapse: collapse; }
    .modal th, .modal td { padding: 10px; border: 1px solid #ccc; text-align: left; }
    .modal-header { text-align: center; font-weight: bold; font-size: 20px; margin-bottom: 15px; }
    .close-btn { position: absolute; top: 10px; right: 15px; font-size: 20px; cursor: pointer; color: #888; }
    .close-btn:hover { color: red; }
  </style>
</head>
<body style="font-family: sans-serif; background: #f9fafb; padding: 20px;">

<!-- Modal -->
<div id="holidayModal" class="fixed inset-0 z-50 flex items-center justify-center bg-overlay">
  <div class="modal">
    <span class="close-btn" onclick="closeHolidayModal()">&times;</span>
    <div class="modal-header">All Holidays</div>
    <table>
      <thead>
        <tr style="background:#f3f4f6;">
          <th>Date</th>
          <th>Day</th>
          <th>Holiday Name</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($holidays as $holiday): ?>
        <tr>
          <td><?= htmlspecialchars($holiday['date']) ?></td>
          <td><?= htmlspecialchars($holiday['day']) ?></td>
          <td><?= htmlspecialchars($holiday['name']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  function closeHolidayModal() {
    document.getElementById('holidayModal').classList.add('hidden');
    document.removeEventListener('keydown', escCloseHandler);
  }

  function escCloseHandler(e) {
    if (e.key === 'Escape') closeHolidayModal();
  }

  // Auto-open modal when page loads
  document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('holidayModal').classList.remove('hidden');
    document.addEventListener('keydown', escCloseHandler);
  });

  // Optional: clicking on background closes modal
  document.getElementById('holidayModal').addEventListener('click', function (e) {
    if (e.target === this) closeHolidayModal();
  });
</script>

</body>
</html>
