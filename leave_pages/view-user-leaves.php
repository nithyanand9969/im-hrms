<?php
session_start();
require_once '../connecting_fIle/config.php';

// Check if user is logged in and has manager role
if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'manager') {
    header("Location: login.php");
    exit();
}

$manager_id = $_SESSION['user']['id'];
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Validate that the requested user is under this manager
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND manager_id = ?");
$stmt->bind_param("ii", $user_id, $manager_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['error'] = "You don't have permission to view this user's leaves";
    header("Location: dashboard.php");
    exit();
}
$stmt->close();

// Get user details
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get all leave requests for this user
$stmt = $conn->prepare("
    SELECT 
        lr.*,
        COUNT(la.id) AS approvals_count,
        GROUP_CONCAT(CONCAT(u.name, ' (', u.role, ')') AS approvers
    FROM leave_requests lr
    LEFT JOIN leave_approvals la ON la.leave_id = lr.id
    LEFT JOIN users u ON u.id = la.admin_id
    WHERE lr.user_id = ?
    GROUP BY lr.id
    ORDER BY lr.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$leaves = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User Leaves</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'navbar.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Leave Requests for <?= htmlspecialchars($user['name']) ?></h1>
            <a href="team_leaves.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-1"></i> Back to Team
            </a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead class="bg-blue-50">
                        <tr class="text-left text-sm text-blue-800">
                            <th class="px-6 py-3 font-medium">Leave Type</th>
                            <th class="px-6 py-3 font-medium">Dates</th>
                            <th class="px-6 py-3 font-medium">Days</th>
                            <th class="px-6 py-3 font-medium">Reason</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium">Approvals</th>
                            <th class="px-6 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php foreach ($leaves as $leave): 
                            $status_class = '';
                            switch (strtolower($leave['status'])) {
                                case 'approved': $status_class = 'bg-green-100 text-green-800'; break;
                                case 'rejected': $status_class = 'bg-red-100 text-red-800'; break;
                                case 'pending': $status_class = 'bg-yellow-100 text-yellow-800'; break;
                                default: $status_class = 'bg-gray-100 text-gray-800';
                            }
                            
                            // Check if this manager has already approved
                            $stmt = $conn->prepare("SELECT id FROM leave_approvals WHERE leave_id = ? AND admin_id = ?");
                            $stmt->bind_param("ii", $leave['id'], $manager_id);
                            $stmt->execute();
                            $has_approved = $stmt->get_result()->num_rows > 0;
                            $stmt->close();
                            
                            $can_approve = !$has_approved && $leave['status'] === 'pending' 
                                && $leave['approval_level'] < $leave['approval_required'];
                        ?>
                            <tr>
                                <td class="px-6 py-4 capitalize"><?= htmlspecialchars($leave['leave_type']) ?></td>
                                <td class="px-6 py-4">
                                    <?= date('M j, Y', strtotime($leave['from_date'])) ?> - 
                                    <?= date('M j, Y', strtotime($leave['to_date'])) ?>
                                </td>
                                <td class="px-6 py-4"><?= $leave['days'] ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($leave['reason']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $status_class ?>">
                                        <?= ucfirst($leave['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($leave['approvers']): ?>
                                        <div class="text-xs text-gray-600">
                                            Approved by: <?= htmlspecialchars($leave['approvers']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-xs text-gray-400">No approvals yet</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($can_approve): ?>
                                        <form action="process_leave_approval.php" method="post" class="inline">
                                            <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                            <button type="submit" name="action" value="approve" 
                                                class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded mr-1 text-sm">
                                                Approve
                                            </button>
                                            <button type="submit" name="action" value="reject" 
                                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                                Reject
                                            </button>
                                        </form>
                                    <?php elseif ($has_approved): ?>
                                        <span class="text-green-600 text-sm">You approved</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>