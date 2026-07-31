<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_email']);

// Optional: destroy complete session if user not logged in
if (!isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    session_start();
}

session_start();
$_SESSION['success_message'] = "You have logged out from Admin Control Panel.";
header("Location: index.php");
exit();
?>
