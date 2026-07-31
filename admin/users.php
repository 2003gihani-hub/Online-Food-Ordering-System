<?php
$page_title = "Customer Accounts";
require_once __DIR__ . '/includes/header.php';

// Handle Block/Unblock toggle actions
if (isset($_GET['toggle_id'])) {
    $toggle_id = intval($_GET['toggle_id']);
    
    // Fetch current status
    $user_record = fetchOne("SELECT status, name FROM users WHERE id = ?", [$toggle_id]);
    
    if ($user_record) {
        $new_status = ($user_record['status'] === 'active') ? 'blocked' : 'active';
        $update_res = executeUpdate("UPDATE users SET status = ? WHERE id = ?", [$new_status, $toggle_id]);
        
        if ($update_res['affected_rows'] > 0) {
            $_SESSION['success_message'] = "Customer '" . htmlspecialchars($user_record['name']) . "' status changed to " . strtoupper($new_status) . " successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update customer status.";
        }
    } else {
        $_SESSION['error_message'] = "Customer account not found.";
    }
    
    header("Location: users.php");
    exit();
}

// Fetch all registered customers
$customers = fetchAll("SELECT * FROM users ORDER BY created_at DESC");
?>

<div class="admin-card">
    <div class="admin-card-header">
        <span class="fw-bold"><i class="fa-solid fa-users me-2 text-warning"></i>Customer Accounts Management</span>
    </div>
    <div class="admin-card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Joined Date</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $cust): ?>
                            <tr>
                                <td>#<?php echo $cust['id']; ?></td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($cust['name']); ?></td>
                                <td><?php echo htmlspecialchars($cust['email']); ?></td>
                                <td class="text-muted small"><?php echo htmlspecialchars($cust['phone'] ?? 'N/A'); ?></td>
                                <td class="text-muted small" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($cust['address'] ?? 'N/A'); ?>
                                </td>
                                <td class="text-muted small"><?php echo date("M d, Y", strtotime($cust['created_at'])); ?></td>
                                <td>
                                    <span class="badge status-badge <?php echo ($cust['status'] === 'active') ? 'status-delivered' : 'status-cancelled'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($cust['status'])); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($cust['status'] === 'active'): ?>
                                        <button onclick="confirmToggle(<?php echo $cust['id']; ?>, 'block')" class="btn btn-sm btn-danger rounded-pill px-3 py-1" style="font-size: 0.8rem; font-weight: 600;">
                                            <i class="fa-solid fa-user-slash me-1"></i>Block Account
                                        </button>
                                    <?php else: ?>
                                        <button onclick="confirmToggle(<?php echo $cust['id']; ?>, 'unblock')" class="btn btn-sm btn-success rounded-pill px-3 py-1" style="font-size: 0.8rem; font-weight: 600;">
                                            <i class="fa-solid fa-user-check me-1"></i>Unblock Account
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No registered customer accounts found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmToggle(id, action) {
    const text_msg = action === 'block' 
        ? "Blocking this customer will terminate their active sessions and prevent them from logging in or ordering foods!"
        : "Unblocking this customer will allow them to log back into their account.";
        
    Swal.fire({
        title: action === 'block' ? 'Block Customer Account?' : 'Unblock Customer Account?',
        text: text_msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: action === 'block' ? '#dc3545' : '#28a745',
        cancelButtonColor: '#1a1a2e',
        confirmButtonText: action === 'block' ? 'Yes, block!' : 'Yes, unblock!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'users.php?toggle_id=' + id;
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
