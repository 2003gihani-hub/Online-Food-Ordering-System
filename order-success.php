<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

checkCustomerAuth();

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate that order belongs to current user
$order = fetchOne("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$order_id, $user_id]);

if (!$order) {
    $_SESSION['error_message'] = "Order details could not be retrieved.";
    header("Location: index.php");
    exit();
}

$page_title = "Order Confirmed";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="card border-0 shadow-sm rounded-4 p-5 bg-white">
                <!-- Success icon -->
                <div class="text-success mb-4">
                    <i class="fa-solid fa-circle-check fa-5x"></i>
                </div>
                
                <h1 class="fw-bold mb-3">Order Confirmed!</h1>
                <p class="text-muted fs-5 mb-4">Thank you for your order, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! We are preparing to make it fresh for you.</p>
                
                <div class="bg-light rounded-4 p-4 mb-4 text-start">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Invoice No:</span>
                        <strong class="text-dark">#FE-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Payment Mode:</span>
                        <strong class="text-dark"><?php echo htmlspecialchars($order['payment_method']); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Paid/COD Amount:</span>
                        <strong class="text-primary" style="color: var(--primary-color) !important;">$<?php echo number_format($order['total_amount'], 2); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Order Date:</span>
                        <strong class="text-dark"><?php echo date("F j, Y, g:i a", strtotime($order['order_date'])); ?></strong>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <a href="track-order.php?id=<?php echo $order['id']; ?>" class="btn btn-primary-custom w-100 py-2.5 rounded-pill fw-bold">
                            <i class="fa-solid fa-route me-2"></i>Track Live Order
                        </a>
                    </div>
                    <div class="col-12 col-md-6">
                        <a href="orders.php" class="btn btn-outline-custom w-100 py-2.5 rounded-pill fw-bold">
                            View Order History
                        </a>
                    </div>
                </div>
                
                <div class="mt-4 pt-3 border-top">
                    <p class="mb-0 text-muted small"><i class="fa-solid fa-bell me-2 text-warning"></i>Status updates will update in real-time on your dashboard.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
