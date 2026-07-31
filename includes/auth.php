<?php
// Prevent direct access to this helper file
if (count(get_included_files()) === 1) {
    exit("Direct access not permitted.");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a customer user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Secure customer-only pages
 */
function checkCustomerAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }
    
    // Check if user has been blocked in real-time
    global $conn;
    if (isset($conn)) {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if ($row['status'] === 'blocked') {
                    // Log out blocked user instantly
                    session_unset();
                    session_destroy();
                    session_start();
                    $_SESSION['error_message'] = "Your account has been blocked by the administrator.";
                    header("Location: login.php");
                    exit();
                }
            }
            $stmt->close();
        }
    }
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/**
 * Secure admin-only pages
 */
function checkAdminAuth() {
    if (!isAdminLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}
?>
