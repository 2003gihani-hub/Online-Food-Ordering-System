<?php
$page_title = "Admin Profile Settings";
require_once __DIR__ . '/includes/header.php';

$admin_id = $_SESSION['admin_id'];
$errors = [];

// Fetch current admin details
$admin = fetchOne("SELECT * FROM admins WHERE id = ?", [$admin_id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Handle Admin Profile Update
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (empty($username)) $errors[] = "Username is required.";
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        
        if (empty($errors)) {
            // Check duplicates
            $dup_user = fetchOne("SELECT id FROM admins WHERE username = ? AND id != ?", [$username, $admin_id]);
            $dup_email = fetchOne("SELECT id FROM admins WHERE email = ? AND id != ?", [$email, $admin_id]);
            
            if ($dup_user) $errors[] = "Username is already in use.";
            if ($dup_email) $errors[] = "Email address is already in use.";
            
            if (empty($errors)) {
                $res = executeUpdate("UPDATE admins SET username = ?, email = ? WHERE id = ?", [$username, $email, $admin_id]);
                if ($res['affected_rows'] >= 0) {
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['admin_email'] = $email;
                    $_SESSION['success_message'] = "Admin profile settings updated successfully!";
                    header("Location: profile.php");
                    exit();
                } else {
                    $errors[] = "Failed to update profile settings.";
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Handle Admin Password Change
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_new_password = $_POST['confirm_new_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
            $errors[] = "All password fields are required.";
        }
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        }
        if ($new_password !== $confirm_new_password) {
            $errors[] = "New passwords do not match.";
        }
        
        if (empty($errors)) {
            // Verify current password
            if (password_verify($current_password, $admin['password'])) {
                // Update new hashed password
                $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
                $res = executeUpdate("UPDATE admins SET password = ? WHERE id = ?", [$hashed_new, $admin_id]);
                
                if ($res['affected_rows'] > 0) {
                    $_SESSION['success_message'] = "Admin password changed successfully!";
                    header("Location: profile.php");
                    exit();
                } else {
                    $errors[] = "Failed to change admin password.";
                }
            } else {
                $errors[] = "Incorrect current password.";
            }
        }
    }
}
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Profile Info card -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="fw-bold"><i class="fa-solid fa-user-pen text-warning me-2"></i>Admin Information</span>
            </div>
            <div class="admin-card-body">
                <form action="profile.php" method="POST">
                    <input type="hidden" name="update_profile" value="1">

                    <div class="mb-3">
                        <label for="username" class="form-label-custom">Username *</label>
                        <input type="text" class="form-control form-control-custom" id="username" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label-custom">Email Address *</label>
                        <input type="email" class="form-control form-control-custom" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>

                    <button type="submit" class="btn btn-admin-accent px-4 py-2.5 rounded-3 fw-bold">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Password change card -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="fw-bold"><i class="fa-solid fa-key text-warning me-2"></i>Security settings</span>
            </div>
            <div class="admin-card-body">
                <form action="profile.php" method="POST">
                    <input type="hidden" name="change_password" value="1">

                    <div class="mb-3">
                        <label for="current_password" class="form-label-custom">Current Password *</label>
                        <input type="password" class="form-control form-control-custom" id="current_password" name="current_password" placeholder="Enter current password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label-custom">New Password *</label>
                        <input type="password" class="form-control form-control-custom" id="new_password" name="new_password" placeholder="Min 6 characters" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_new_password" class="form-label-custom">Confirm New Password *</label>
                        <input type="password" class="form-control form-control-custom" id="confirm_new_password" name="confirm_new_password" placeholder="Re-enter new password" required>
                    </div>

                    <button type="submit" class="btn btn-outline-custom py-2.5 rounded-3 fw-bold w-100">
                        <i class="fa-solid fa-shield-halved me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
