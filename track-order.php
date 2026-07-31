<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

checkCustomerAuth();

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch order details
$order = fetchOne("SELECT * FROM orders WHERE id = ? AND user_id = ?", [$order_id, $user_id]);

if (!$order) {
    $_SESSION['error_message'] = "Order not found.";
    header("Location: orders.php");
    exit();
}

$page_title = "Track Order #FE-" . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
require_once __DIR__ . '/includes/header.php';

// Calculate Progress Metrics
$current_status = $order['status'];
$progress_pct = 0;
$step_active = [1 => false, 2 => false, 3 => false, 4 => false, 5 => false];

if ($current_status !== 'Cancelled') {
    switch ($current_status) {
        case 'Pending':
            $progress_pct = 0;
            $step_active[1] = true;
            break;
        case 'Confirmed':
            $progress_pct = 25;
            $step_active[1] = true;
            $step_active[2] = true;
            break;
        case 'Preparing':
            $progress_pct = 50;
            $step_active[1] = true;
            $step_active[2] = true;
            $step_active[3] = true;
            break;
        case 'Out For Delivery':
            $progress_pct = 75;
            $step_active[1] = true;
            $step_active[2] = true;
            $step_active[3] = true;
            $step_active[4] = true;
            break;
        case 'Delivered':
            $progress_pct = 100;
            $step_active[1] = true;
            $step_active[2] = true;
            $step_active[3] = true;
            $step_active[4] = true;
            $step_active[5] = true;
            break;
    }
}
?>

<div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item"><a href="orders.php" class="text-decoration-none text-muted">Orders</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Track Order</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-lg-5 bg-white">
                <!-- Head -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-4 mb-4 gap-3">
                    <div>
                        <h2 class="fw-bold mb-1"><i class="fa-solid fa-route me-2 text-warning"></i>Track Live Order</h2>
                        <p class="text-muted mb-0">Invoice: <strong>#FE-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong> | Payment: <strong><?php echo $order['payment_method']; ?></strong></p>
                    </div>
                    <div class="text-md-end">
                        <span class="text-muted small d-block">Estimated Delivery Time</span>
                        <h4 class="fw-bold text-success mb-0">
                            <?php 
                            if ($order['status'] === 'Delivered') {
                                echo "Arrived / Delivered";
                            } elseif ($order['status'] === 'Cancelled') {
                                echo "Order Cancelled";
                            } else {
                                echo "30 - 45 Minutes"; 
                            }
                            ?>
                        </h4>
                    </div>
                </div>

                <?php if ($order['status'] === 'Cancelled'): ?>
                    <!-- Cancelled Message -->
                    <div class="alert alert-danger p-4 rounded-4 text-center my-4">
                        <div class="mb-3 text-danger"><i class="fa-solid fa-circle-xmark fa-4x"></i></div>
                        <h4 class="fw-bold">Your Order was Cancelled</h4>
                        <p class="mb-0">This order was cancelled by the store administrator. If you have already paid online via PayPal, a full refund will be processed back to your original payment account shortly. If you have questions, please reach support at support@foodexpress.com.</p>
                    </div>
                <?php else: ?>
                    <!-- Live Tracker Timeline -->
                    <div class="position-relative py-4">
                        <div class="tracking-steps">
                            <!-- Background Progress Line -->
                            <div class="tracking-line-progress" style="width: <?php echo $progress_pct; ?>%;"></div>
                            
                            <!-- Node 1: Pending -->
                            <div class="step-node <?php echo $step_active[1] ? 'active' : ''; ?>">
                                <div class="step-icon">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </div>
                                <div class="step-label">Placed</div>
                            </div>
                            
                            <!-- Node 2: Confirmed -->
                            <div class="step-node <?php echo $step_active[2] ? 'active' : ''; ?>">
                                <div class="step-icon">
                                    <i class="fa-solid fa-thumbs-up"></i>
                                </div>
                                <div class="step-label">Confirmed</div>
                            </div>
                            
                            <!-- Node 3: Preparing -->
                            <div class="step-node <?php echo $step_active[3] ? 'active' : ''; ?>">
                                <div class="step-icon">
                                    <i class="fa-solid fa-fire-burner"></i>
                                </div>
                                <div class="step-label">Preparing</div>
                            </div>
                            
                            <!-- Node 4: Out For Delivery -->
                            <div class="step-node <?php echo $step_active[4] ? 'active' : ''; ?>">
                                <div class="step-icon">
                                    <i class="fa-solid fa-motorcycle"></i>
                                </div>
                                <div class="step-label">On the Way</div>
                            </div>
                            
                            <!-- Node 5: Delivered -->
                            <div class="step-node <?php echo $step_active[5] ? 'active' : ''; ?>">
                                <div class="step-icon">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>
                                <div class="step-label">Delivered</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Order Details Summary -->
                <div class="row mt-5 g-4 pt-4 border-top">
                    <div class="col-md-6">
                        <h5 class="fw-bold mb-3"><i class="fa-solid fa-map-location-dot me-2 text-warning"></i>Shipping Address</h5>
                        <div class="p-3 bg-light rounded-3">
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($order['shipping_name']); ?></h6>
                            <p class="text-muted small mb-2"><i class="fa-solid fa-phone me-2"></i><?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                            <p class="text-muted small mb-0"><i class="fa-solid fa-location-dot me-2"></i><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6 text-md-end justify-content-end d-flex flex-column">
                        <p class="text-muted mb-2">Need help with your order?</p>
                        <div>
                            <a href="mailto:support@foodexpress.com" class="btn btn-sm btn-outline-secondary rounded-pill px-3 me-2"><i class="fa-solid fa-envelope me-1"></i>Email Store</a>
                           <a href="https://wa.me/+94702062165?text=Hello%20Driver,%20I%20want%20to%20know%20my%20order%20status."target="_blank"class="btn btn-sm btn-outline-success rounded-pill px-3"> <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp Driver</a>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
