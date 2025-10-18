<?php
session_start();
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../connecting_fIle/config.php';

// Check user session and role
if (empty($_SESSION['csrftoken'])) {
    $_SESSION['csrftoken'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header("Location: login.php?error=" . urlencode("Session expired. Please log in again."));
    exit;
}
$user = $_SESSION['user'] ?? [];
$userRole = strtolower($user['role'] ?? '');
if ($userRole !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle AJAX POST to add employee
if (isset($_POST['submitemployee'])) {
    header('Content-Type: application/json');
    try {
        $name = trim($_POST['fullname'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
        $dateofbirth = $_POST['dob'] ?? null;
        $dateofjoining = $_POST['joiningdate'] ?? null;
        $password = password_hash('Welcome123', PASSWORD_DEFAULT);
        $role = strtolower(trim($_POST['position'] ?? ''));
        $teamid = !empty($_POST['teamid']) ? intval($_POST['teamid']) : null;
        $managerid = !empty($_POST['manager']) ? intval($_POST['manager']) : null;
        $isactive = 1;
        $firstlogin = 1;
        $leavebalance = floatval($_POST['leavebalance'] ?? 0);
        $employeeid = trim($_POST['employeeid'] ?? '');
        $gender = $_POST['gender'] ?? null;
        // Validation for required fields
        if (empty($name) || empty($email) || empty($employeeid) || empty($role)) {
            throw new Exception("Required fields are missing.");
        }
        // Check duplicates
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email already exists.");
        }
        $stmt = $conn->prepare("SELECT id FROM users WHERE employeeid = ?");
        $stmt->bind_param('s', $employeeid);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Employee ID already exists.");
        }
        // Insert employee data
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, dateofbirth, dateofjoining, password, role, teamid, managerid, isactive, firstlogin, leavebalance, createdat, updatedat, employeeid, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?)");
        $stmt->bind_param("sssssssiiiiiiss", $name, $email, $phone, $dateofbirth, $dateofjoining, $password, $role, $teamid, $managerid, $isactive, $firstlogin, $leavebalance, $employeeid, $gender);
        if (!$stmt->execute()) {
            throw new Exception("Error adding employee: " . $stmt->error);
        }
        echo json_encode(['success' => true, 'message' => 'Employee added successfully! Default password is Welcome123']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle AJAX request for manager list
if (isset($_POST['action']) && $_POST['action'] === 'getmanagers') {
    header('Content-Type: application/json');
    $position = strtolower(trim($_POST['position'] ?? ''));
    $managers = [];
    if ($position) {
        $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE role = ? AND isactive = 1");
        $stmt->bind_param('s', $position);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $managers[] = $row;
        }
    }
    echo json_encode(['success' => true, 'managers' => $managers]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Add Employee Modal</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body>


<!-- Employee Modal -->
<div id="employeeModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden">
  <div class="bg-white max-w-4xl w-full rounded-xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col animate-fadeIn">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6 flex justify-between items-center">
      <h3 class="text-2xl font-bold text-white">Add New Employee</h3>
      <button onclick="closeModal()" class="text-white hover:text-blue-200" aria-label="Close modal">&times;</button>
    </div>
    <!-- Modal Body -->
    <div class="overflow-y-auto flex-1 p-6 space-y-6">
      <form id="employeeForm" onsubmit="return false">
        <!-- Personal Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="fullname" class="block font-semibold mb-1">Full Name</label>
            <input id="fullname" name="fullname" type="text" required class="w-full border border-gray-300 rounded px-3 py-2" />
          </div>
          <div>
            <label for="email" class="block font-semibold mb-1">Email</label>
            <input id="email" name="email" type="email" required class="w-full border border-gray-300 rounded px-3 py-2" />
          </div>
          <div>
            <label for="phone" class="block font-semibold mb-1">Phone</label>
            <input id="phone" name="phone" type="tel" class="w-full border border-gray-300 rounded px-3 py-2" />
          </div>
          <div>
            <label for="dob" class="block font-semibold mb-1">Date of Birth</label>
            <input id="dob" name="dob" type="date" class="w-full border border-gray-300 rounded px-3 py-2" />
          </div>
          <div>
            <label for="gender" class="block font-semibold mb-1">Gender</label>
            <select id="gender" name="gender" class="w-full border border-gray-300 rounded px-3 py-2">
              <option value="" selected>Select Gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>
        <!-- Employment Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div>
            <label for="employeeid" class="block font-semibold mb-1">Employee ID</label>
            <input id="employeeid" name="employeeid" type="text" required class="w-full border border-gray-300 rounded px-3 py-2" />
          </div>
          <div>
            <label for="joiningdate" class="block font-semibold mb-1">Joining Date</label>
            <input id="joiningdate" name="joiningdate" type="date" required class="w-full border border-gray-300 rounded px-3 py-2" />
            <p id="dateHelpText" class="text-xs text-gray-500 mt-1"></p>
          </div>
          <div>
            <label for="leavebalance" class="block font-semibold mb-1">Leave Balance</label>
            <input id="leavebalance" name="leavebalance" type="number" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2" />
          </div>
          <div>
            <label for="positionSelect" class="block font-semibold mb-1">Position</label>
            <select id="positionSelect" name="position" required class="w-full border border-gray-300 rounded px-3 py-2">
              <option value="" selected>Select Position</option>
              <option value="admin">Admin</option>
              <option value="manager">Manager</option>
              <option value="sr.manager">Senior Manager</option>
              <option value="user">User</option>
            </select>
          </div>
          <div id="reportingManagerDiv" class="hidden">
            <label for="managerSelect" class="block font-semibold mb-1">Reporting Manager</label>
            <select id="managerSelect" name="manager" class="w-full border border-gray-300 rounded px-3 py-2">
              <option value="">Select Manager</option>
            </select>
          </div>
          <div>
            <label class="block font-semibold mb-1">Weekly Off</label>
            <div class="flex flex-wrap space-x-3">
              <label><input type="checkbox" name="weeklyoffdays[]" value="Monday" class="mr-1" />Monday</label>
              <label><input type="checkbox" name="weeklyoffdays[]" value="Tuesday" class="mr-1" />Tuesday</label>
              <label><input type="checkbox" name="weeklyoffdays[]" value="Wednesday" class="mr-1" />Wednesday</label>
              <label><input type="checkbox" name="weeklyoffdays[]" value="Thursday" class="mr-1" />Thursday</label>
              <label><input type="checkbox" name="weeklyoffdays[]" value="Friday" class="mr-1" />Friday</label>
              <label><input type="checkbox" name="weeklyoffdays[]" value="Saturday" class="mr-1" />Saturday</label>
              <label><input type="checkbox" name="weeklyoffdays[]" value="Sunday" class="mr-1" />Sunday</label>
            </div>
          </div>
        </div>
        <input type="hidden" name="submitemployee" value="1" />
      </form>
    </div>
    <!-- Modal Footer -->
    <div class="flex justify-end p-4 border-t">
      <button type="button" onclick="closeModal()" class="border border-gray-400 text-gray-700 px-6 py-2 rounded hover:bg-gray-100">Cancel</button>
      <button type="button" id="submitBtn" onclick="submitEmployeeForm()" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Save Employee</button>
    </div>
  </div>
</div>
<script>

// Close employee modal, reset form, enable body scroll
function closeModal() {
  const modal = document.getElementById('employeeModal');
  if (modal) {
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    const form = document.getElementById('employeeForm');
    if (form) {
      form.reset();
    }
    const managerSelect = document.getElementById('managerSelect');
    if (managerSelect) {
      managerSelect.innerHTML = '<option value="">Select Manager</option>';
    }
    const reportingManagerDiv = document.getElementById('reportingManagerDiv');
    if(reportingManagerDiv){
      reportingManagerDiv.classList.add('hidden');
    }
  }
}

// Submit employee form via fetch POST, handle response and UI states
function submitEmployeeForm() {
  const submitBtn = document.getElementById('submitBtn');
  const form = document.getElementById('employeeForm');
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="animate-pulse">Processing...</span>';

  // Gather form data including checkboxes
  const formData = new FormData(form);
  formData.append('submitemployee', 1);

  fetch(window.location.href, {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.success) {
      closeModal();
      setTimeout(() => {
        window.location.reload();
      }, 1500);
    }
  })
  .catch(() => {
    alert('An error occurred.');
  })
  .finally(() => {
    submitBtn.disabled = false;
    submitBtn.innerHTML = 'Save Employee';
  });
}

// Show/hide reporting manager field based on position
const positionSelect = document.getElementById('positionSelect');
const reportingManagerDiv = document.getElementById('reportingManagerDiv');
const managerSelect = document.getElementById('managerSelect');

if (positionSelect) {
  positionSelect.addEventListener('change', function () {
    const pos = this.value.trim().toLowerCase();
    if (!pos || pos === 'admin') {
      reportingManagerDiv.classList.add('hidden');
      if (managerSelect) {
        managerSelect.value = '';
        managerSelect.removeAttribute('required');
      }
    } else {
      reportingManagerDiv.classList.remove('hidden');
      if (managerSelect) {
        managerSelect.setAttribute('required', 'required');
      }
      loadManagers(pos);
    }
  });
}

// Load managers for the selected position via AJAX
function loadManagers(position) {
  if (!managerSelect) return;
  managerSelect.innerHTML = '<option>Loading...</option>';
  const formData = new FormData();
  formData.append('action', 'getmanagers');
  formData.append('position', position);

  fetch(window.location.href, {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    managerSelect.innerHTML = '';
    if (data.success && Array.isArray(data.managers) && data.managers.length > 0) {
      data.managers.forEach(manager => {
        const option = document.createElement('option');
        option.value = manager.id;
        option.textContent = manager.name + (manager.role ? ' (' + manager.role + ')' : '');
        managerSelect.appendChild(option);
      });
    } else {
      managerSelect.innerHTML = '<option value="">No managers available</option>';
    }
  })
  .catch(() => {
    managerSelect.innerHTML = '<option value="">Error loading managers</option>';
  });
}

/* ------------------ DOB Validation ------------------ */
const dobInput = document.querySelector('input[name="dob"]');
if (dobInput) {
  const d = new Date();
  d.setFullYear(d.getFullYear() - 18);
  dobInput.max = d.toISOString().split('T')[0];
}

/* ------------------ Joining Date Validation ------------------ */
function updateDateLimits() {
  const isOld = document.getElementById('isOldEmployee')?.checked;
  const input = document.getElementById('joiningdate');
  const helpText = document.getElementById('dateHelpText');
  if (!input) return;

  if (isOld) {
    input.min = "2013-01-01";
    input.max = "";
    if (helpText) helpText.textContent = "Old employees can have joining dates from 2013 onwards";
  } else {
    const today = new Date();
    const min = new Date(today.getTime() - 30 * 86400000);
    const max = new Date(today.getTime() + 30 * 86400000);
    input.min = min.toISOString().split('T')[0];
    input.max = max.toISOString().split('T')[0];
    if (helpText) helpText.textContent = "Date must be within 30 days from today";
  }
}

document.getElementById('isOldEmployee')?.addEventListener('change', updateDateLimits);
updateDateLimits();

document.getElementById('joiningdate')?.addEventListener('change', function () {
  const input = this;
  const isOld = document.getElementById('isOldEmployee')?.checked;
  const selected = new Date(input.value);
  const today = new Date();

  if (isOld) {
    const minOld = new Date("2013-01-01");
    input.setCustomValidity(selected < minOld ? 'Joining date must be from 2013 onwards' : '');
  } else {
    const min = new Date(today.getTime() - 30 * 86400000);
    const max = new Date(today.getTime() + 30 * 86400000);
    input.setCustomValidity(
      selected < min || selected > max
        ? 'Joining date must be within 30 days from today'
        : ''
    );
  }
});
</script>

</body>
</html>
