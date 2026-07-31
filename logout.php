<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear customer-related session variables
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);

// Optional: Destroy the entire session if no admin is logged in
if (!isset($_SESSION['admin_id'])) {
    session_unset();
    session_destroy();
    session_start();
}

session_start();
$_SESSION['success_message'] = "You have been logged out successfully.";
header("Location: /FoodExpress/login.php");
exit();
?>
