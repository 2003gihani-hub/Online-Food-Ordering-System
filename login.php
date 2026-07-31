<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$page_title = "Login";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors[] = "Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $user = fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        
        if ($user) {
            // Check if user is blocked
            if ($user['status'] === 'blocked') {
                $errors[] = "Your account has been blocked by the administrator. Please contact customer support.";
            } else {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session values
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['success_message'] = "Welcome back, " . htmlspecialchars($user['name']) . "!";
                    
                    // Redirect to intended page or index
                    $redirect = $_SESSION['redirect_to'] ?? 'index.php';
                    unset($_SESSION['redirect_to']);
                    header("Location: " . $redirect);
                    exit();
                } else {
                    $errors[] = "Invalid password.";
                }
            }
        } else {
            $errors[] = "Email not registered.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white text-center py-4">
                    <h3 class="mb-0"><i class="fa-solid fa-right-to-bracket me-2 text-warning"></i>Welcome Back</h3>
                    <p class="text-white-50 mb-0 mt-1">Sign in to order your favorite meals</p>
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

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope text-muted"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="john@example.com" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary-custom py-2 rounded-pill"><i class="fa-solid fa-right-to-bracket me-2"></i>Sign In</button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <p class="mb-0 text-muted">Don't have an account yet? <a href="register.php" class="text-primary fw-semibold text-decoration-none">Sign Up Here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
