<?php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Redirect if admin already logged in
if (isAdminLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username)) $errors[] = "Username is required.";
    if (empty($password)) $errors[] = "Password is required.";

    if (empty($errors)) {
        // Query database
        $admin = fetchOne("SELECT * FROM admins WHERE username = ?", [$username]);
        
        if ($admin) {
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['success_message'] = "Admin login successful. Welcome to the Control Panel!";
                
                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "Admin username not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - FoodExpress</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16161a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: 1px solid rgba(255,255,255,0.08);
            background-color: rgba(25, 25, 45, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            color: white;
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }
        .form-control {
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: white !important;
            border-radius: 8px;
            padding: 12px 15px;
        }
        .form-control:focus {
            background-color: rgba(255,255,255,0.08);
            border-color: #ff5e3a;
            box-shadow: 0 0 0 0.2rem rgba(255, 94, 58, 0.25);
        }
        .btn-admin {
            background: linear-gradient(135deg, #ff5e3a 0%, #ff7b5f 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 30px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 94, 58, 0.4);
        }
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 94, 58, 0.5);
            color: white;
        }
        .input-group-text {
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.6);
        }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="login-card mx-auto p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="text-warning mb-2"><i class="fa-solid fa-user-shield fa-3x"></i></div>
            <h3 class="fw-bold mb-0">Admin Portal</h3>
            <p class="text-white-50 small mt-1">FoodExpress Management Control Panel</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 text-white bg-danger bg-opacity-75 small alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label small fw-semibold text-white-50">Admin Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label small fw-semibold text-white-50">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-admin"><i class="fa-solid fa-right-to-bracket me-2"></i>Access Dashboard</button>
            </div>
            
            <div class="text-center mt-4">
                <a href="/FoodExpress/index.php" class="text-white-50 small text-decoration-none"><i class="fa-solid fa-arrow-left me-1"></i>Back to Customer Site</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
