<?php
// Prevent direct access to header template
if (count(get_included_files()) === 1) {
    exit("Direct access not permitted.");
}

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Calculate Cart Item Count
$cart_count = 0;
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $cart_result = fetchOne("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = ?", [$user_id]);
    if ($cart_result && $cart_result['total_qty']) {
        $cart_count = intval($cart_result['total_qty']);
    }
}

// Get active page name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - FoodExpress" : "FoodExpress - Fast & Delicious Food Delivery"; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/FoodExpress/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Customer Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/FoodExpress/index.php">
            <i class="fa-solid fa-utensils me-2 text-warning"></i>Food<span>Express</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="/FoodExpress/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>" href="/FoodExpress/categories.php">Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'search.php') ? 'active' : ''; ?>" href="/FoodExpress/search.php">Search Foods</a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center">
                <!-- Cart Button -->
                <a href="/FoodExpress/cart.php" class="btn btn-link text-white position-relative p-2 me-3 cart-badge-container" aria-label="Shopping Cart">
                    <i class="fa-solid fa-cart-shopping fa-lg"></i>
                    <span class="cart-badge" style="<?php echo ($cart_count > 0) ? '' : 'display: none;'; ?>">
                        <?php echo $cart_count; ?>
                    </span>
                </a>
                
                <?php if (isLoggedIn()): ?>
                    <!-- Logged In Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle rounded-pill px-3" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user me-2"></i>Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="/FoodExpress/profile.php"><i class="fa-solid fa-id-card me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="/FoodExpress/orders.php"><i class="fa-solid fa-clock-rotate-left me-2"></i>Order History</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/FoodExpress/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <!-- Guest Login/Register Buttons -->
                    <a href="/FoodExpress/login.php" class="btn btn-outline-light rounded-pill me-2 px-3">Login</a>
                    <a href="/FoodExpress/register.php" class="btn btn-primary-custom rounded-pill">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Page Content Wrapper -->
<div class="main-content-body py-4" style="min-height: 60vh;">
