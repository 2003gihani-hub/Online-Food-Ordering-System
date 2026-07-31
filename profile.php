<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

checkCustomerAuth();

$user_id = $_SESSION['user_id'];
$errors = [];
$success_msg = '';

// Fetch current user details
$user = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

// Handle Forms
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Edit Profile
        $name = sanitizeInput($_POST['name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        
        if (empty($name)) {
            $errors[] = "Name cannot be empty.";
        }
        
        if (empty($errors)) {
            $update = executeUpdate(
                "UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?",
                [$name, $phone, $address, $user_id]
            );
            
            if ($update['affected_rows'] >= 0) {
                $_SESSION['user_name'] = $name;
                $_SESSION['success_message'] = "Profile details updated successfully!";
                header("Location: profile.php");
                exit();
            } else {
                $errors[] = "Failed to update profile details.";
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Change Password
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
            if (password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_new = password_hash($new_password, PASSWORD_DEFAULT);
                $update = executeUpdate("UPDATE users SET password = ? WHERE id = ?", [$hashed_new, $user_id]);
                
                if ($update['affected_rows'] > 0) {
                    $_SESSION['success_message'] = "Password changed successfully!";
                    header("Location: profile.php");
                    exit();
                } else {
                    $errors[] = "Failed to change password.";
                }
            } else {
                $errors[] = "Incorrect current password.";
            }
        }
    }
}

$page_title = "My Profile";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">My Profile</li>
        </ol>
    </nav>

    <div class="section-title">
        <h2>My Account Profile</h2>
        <p class="text-muted">Manage your personal delivery details and passwords</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Edit Profile Column -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-lg-5 bg-white">
                <h4 class="fw-bold mb-4"><i class="fa-solid fa-user-pen text-warning me-2"></i>Profile Information</h4>
                
                <form action="profile.php" method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name *</label>
                        <input type="text" class="form-control rounded-3 py-2" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address (Read-only)</label>
                        <input type="email" class="form-control rounded-3 py-2 bg-light text-muted" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" class="form-control rounded-3 py-2" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g. 1234567890">
                    </div>

                    <div class="mb-4">
                        <label for="address" class="form-label fw-semibold">Delivery Address</label>
                        <textarea class="form-control rounded-3" id="address" name="address" rows="3" placeholder="Enter your full home or office delivery address..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary-custom px-4 py-2.5 rounded-pill fw-bold">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password Column -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-lg-5 bg-white h-100">
                <h4 class="fw-bold mb-4"><i class="fa-solid fa-key text-warning me-2"></i>Security Settings</h4>
                
                <form action="profile.php" method="POST" id="passwordForm">
                    <input type="hidden" name="change_password" value="1">

                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold">Current Password</label>
                        <input type="password" class="form-control rounded-3 py-2" id="current_password" name="current_password" placeholder="Enter current password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold">New Password</label>
                        <input type="password" class="form-control rounded-3 py-2" id="new_password" name="new_password" placeholder="Min 6 characters" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_new_password" class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" class="form-control rounded-3 py-2" id="confirm_new_password" name="confirm_new_password" placeholder="Re-enter new password" required>
                    </div>

                    <button type="submit" class="btn btn-outline-custom w-100 py-2.5 rounded-pill fw-bold">
                        <i class="fa-solid fa-shield-halved me-2"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
