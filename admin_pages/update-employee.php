<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_user') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];

    // Prepare and execute update query
    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, role=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $phone, $role, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    exit;
}
?>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 relative animate-fadeIn">
    <h2 class="text-2xl font-semibold mb-4">Edit User</h2>
    <form id="editUserForm" class="space-y-4">
      <input type="hidden" name="action" value="update_user" />
      <input type="hidden" name="id" id="editUserId" />
      
      <div>
        <label class="block text-sm font-medium mb-1">Name</label>
        <input type="text" id="editUserName" name="name" class="w-full border rounded px-3 py-2" required />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input type="email" id="editUserEmail" name="email" class="w-full border rounded px-3 py-2" required />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Phone</label>
        <input type="text" id="editUserPhone" name="phone" class="w-full border rounded px-3 py-2" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Role</label>
        <select id="editUserRole" name="role" class="w-full border rounded px-3 py-2">
          <option value="user">User</option>
          <option value="manager">Manager</option>
          <option value="sr.manager">Senior Manager</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <!-- Weekly Off Checkboxes -->
      <div class="mt-3">
        <label class="block text-sm font-medium text-gray-700 mb-2">Weekly Off*</label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
          <label class="inline-flex items-center">
            <input type="checkbox" name="weekly_off_days[]" value="monday" class="mr-2" /> Monday
          </label>
          <label class="inline-flex items-center">
            <input type="checkbox" name="weekly_off_days[]" value="tuesday" class="mr-2" /> Tuesday
          </label>
          <label class="inline-flex items-center">
            <input type="checkbox" name="weekly_off_days[]" value="wednesday" class="mr-2" /> Wednesday
          </label>
          <label class="inline-flex items-center">
            <input type="checkbox" name="weekly_off_days[]" value="thursday" class="mr-2" /> Thursday
          </label>
          <label class="inline-flex items-center">
            <input type="checkbox" name="weekly_off_days[]" value="friday" class="mr-2" /> Friday
          </label>
          <label class="inline-flex items-center">
            <input type="checkbox" name="weekly_off_days[]" value="saturday" class="mr-2" /> Saturday
          </label>
          <label class="inline-flex items-center">
            <input type="checkbox" name="weekly_off_days[]" value="sunday" class="mr-2" /> Sunday
          </label>
        </div>
      </div>

      <div class="flex justify-end gap-2 mt-4">
        <button type="button" onclick="closeEditModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">Cancel</button>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openEditModal(user) {
    $('#editUserId').val(user.id);
    $('#editUserName').val(user.name);
    $('#editUserEmail').val(user.email);
    $('#editUserPhone').val(user.phone);
    $('#editUserRole').val(user.role);

    // Weekly Off checkboxes
    $('#editUserForm input[name="weekly_off_days[]"]').prop('checked', false);
    if (user.weekly_off_days) {
      user.weekly_off_days.forEach(day => {
        $(`#editUserForm input[value="${day}"]`).prop('checked', true);
      });
    }

    $('#editUserModal').removeClass('hidden');
    $('body').css('overflow', 'hidden');
  }

  function closeEditModal() {
    $('#editUserModal').addClass('hidden');
    $('body').css('overflow', 'auto');
    $('#editUserForm')[0].reset();
  }
</script>
