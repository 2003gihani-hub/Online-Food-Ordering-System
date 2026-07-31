<?php
// Prevent direct access
if (count(get_included_files()) === 1) {
    exit("Direct access not permitted.");
}

require_once dirname(dirname(__DIR__)) . '/config/db.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';

// Secure Admin area
checkAdminAuth();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - Admin Dashboard" : "Admin Panel - FoodExpress"; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Admin custom styling -->
    <link href="/FoodExpress/assets/css/admin.css" rel="stylesheet">
</head>
<body>

<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-heading text-center">
            <i class="fa-solid fa-utensils text-warning me-2"></i>Food<span>Express</span>
            <div class="small fw-semibold text-white-50 mt-1" style="font-size: 0.75rem; letter-spacing: 1.5px; text-transform: uppercase;">Owner Panel</div>
        </div>
        
        <div class="list-group list-group-flush mt-3">
            <a href="/FoodExpress/admin/dashboard.php" class="sidebar-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge"></i>Dashboard
            </a>
            <a href="/FoodExpress/admin/categories.php" class="sidebar-link <?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-folder-open"></i>Categories
            </a>
            <a href="/FoodExpress/admin/foods.php" class="sidebar-link <?php echo ($current_page == 'foods.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-bowl-food"></i>Food Items
            </a>
            <a href="/FoodExpress/admin/users.php" class="sidebar-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i>Customers
            </a>
            <a href="/FoodExpress/admin/orders.php" class="sidebar-link <?php echo ($current_page == 'orders.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-receipt"></i>Orders
            </a>
            <a href="/FoodExpress/admin/sales-report.php" class="sidebar-link <?php echo ($current_page == 'sales-report.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-line"></i>Sales Report
            </a>
            <a href="/FoodExpress/admin/profile.php" class="sidebar-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-gear"></i>Settings
            </a>
            <a href="/FoodExpress/admin/logout.php" class="sidebar-link text-danger">
                <i class="fa-solid fa-power-off"></i>Logout
            </a>
        </div>
    </div>
    <!-- /#sidebar-wrapper -->

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light admin-navbar">
            <div class="container-fluid p-0">
                <button class="btn btn-outline-dark btn-sm rounded-circle me-3" id="menu-toggle" aria-label="Toggle Menu" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-bars"></i>
                </button>
                
                <h4 class="mb-0 fw-bold d-none d-md-block"><?php echo isset($page_title) ? $page_title : "Dashboard"; ?></h4>
                
                <div class="ms-auto d-flex align-items-center">
                    <span class="navbar-text me-3 text-dark fw-semibold">
                        <i class="fa-solid fa-user-lock me-1 text-muted"></i><?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </span>
                    <a href="/FoodExpress/index.php" class="btn btn-sm btn-outline-custom rounded-pill" target="_blank">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>View Site
                    </a>
                </div>
            </div>
        </nav>

        <div class="admin-container">
