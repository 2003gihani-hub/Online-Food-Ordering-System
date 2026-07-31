E<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$page_title = "Register";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');

    // Form validations
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // Check if email already exists
        $existing = fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            $errors[] = "Email is already registered. Please login or use a different email.";
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (name, email, password, phone, address, status) VALUES (?, ?, ?, ?, ?, 'active')";
            $result = executeUpdate($query, [$name, $email, $hashed_password, $phone, $address]);
            
            if ($result['affected_rows'] > 0) {
                $_SESSION['success_message'] = "Registration successful! You can now log in.";
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white text-center py-4">
                    <h3 class="mb-0"><i class="fa-solid fa-user-plus me-2 text-warning"></i>Create Account</h3>
                    <p class="text-white-50 mb-0 mt-1">Join FoodExpress and start ordering delicious food!</p>
                </div>
                <div class="card-body p-4">
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

                    <form action="register.php" method="POST" id="registerForm" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Full Name *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-user text-muted"></i></span>
                                <input type="text" class="form-control" id="name" name="name" placeholder="John Doe" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope text-muted"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="john@example.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="password" class="form-label fw-semibold">Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Min 6 characters" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-semibold">Confirm Password *</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-phone text-muted"></i></span>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="e.g. 1234567890" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label fw-semibold">Delivery Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-location-dot text-muted"></i></span>
                                <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter your full home or office delivery address..."><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary-custom py-2 rounded-pill"><i class="fa-solid fa-user-plus me-2"></i>Register Now</button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <p class="mb-0 text-muted">Already have an account? <a href="login.php" class="text-primary fw-semibold text-decoration-none">Login Here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
