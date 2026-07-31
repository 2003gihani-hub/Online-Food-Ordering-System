<?php
$page_title = "Customer Orders";
require_once __DIR__ . '/includes/header.php';

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    
    $allowed_statuses = ['Pending', 'Confirmed', 'Preparing', 'Out For Delivery', 'Delivered', 'Cancelled'];
    
    if ($order_id > 0 && in_array($status, $allowed_statuses)) {
        $update_res = executeUpdate("UPDATE orders SET status = ? WHERE id = ?", [$status, $order_id]);
        
        if ($update_res['affected_rows'] >= 0) {
            $_SESSION['success_message'] = "Order #FE-" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " status updated to " . strtoupper($status) . " successfully!";
            
            // Retrieve customer details for notification
            $order_info = fetchOne(
                "SELECT o.id, u.name as customer_name, u.email as customer_email 
                 FROM orders o 
                 JOIN users u ON o.user_id = u.id 
                 WHERE o.id = ?",
                [$order_id]
            );
            
            if ($order_info && !empty($order_info['customer_email'])) {
                require_once dirname(__DIR__) . '/includes/mail-helper.php';
                
                $formatted_order_id = "#FE-" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
                // Send email notification (fails silently to admin user, but logs error if SMTP fails)
                sendOrderStatusEmail(
                    $order_info['customer_name'], 
                    $order_info['customer_email'], 
                    $formatted_order_id, 
                    $status
                );
            }
        } else {
            $_SESSION['error_message'] = "Failed to update order status.";
        }
    } else {
        $_SESSION['error_message'] = "Invalid order ID or status selection.";
    }
    
    header("Location: orders.php");
    exit();
}

// Fetch all orders
$orders = fetchAll(
    "SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o 
     JOIN users u ON o.user_id = u.id 
     ORDER BY o.order_date DESC"
);
?>

<div class="admin-card">
    <div class="admin-card-header">
        <span class="fw-bold"><i class="fa-solid fa-receipt me-2 text-warning"></i>All Store Orders</span>
    </div>
    <div class="admin-card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">Invoice No</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Payment Mode</th>
                        <th>Update Status</th>
                        <th class="text-center">Items</th>
                        <th class="pe-4 text-end">Bill PDF</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $ord): 
                            $order_id = $ord['id'];
                            
                            // Map colors for background select boxes
                            $select_bg = 'bg-light';
                            switch ($ord['status']) {
                                case 'Pending': $select_bg = 'bg-warning-subtle text-warning-emphasis'; break;
                                case 'Confirmed': $select_bg = 'bg-info-subtle text-info-emphasis'; break;
                                case 'Preparing': $select_bg = 'bg-primary-subtle text-primary-emphasis'; break;
                                case 'Out For Delivery': $select_bg = 'bg-purple-subtle'; break; // Handled below in style
                                case 'Delivered': $select_bg = 'bg-success-subtle text-success-emphasis'; break;
                                case 'Cancelled': $select_bg = 'bg-danger-subtle text-danger-emphasis'; break;
                            }
                            
                            // Fetch items
                            $items = fetchAll(
                                "SELECT oi.quantity, oi.price, f.name 
                                 FROM order_items oi 
                                 LEFT JOIN foods f ON oi.food_id = f.id 
                                 WHERE oi.order_id = ?",
                                [$order_id]
                            );
                        ?>
                            <!-- Main Row -->
                            <tr class="border-bottom">
                                <td class="ps-4 fw-bold text-dark">#FE-<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <h6 class="mb-0 fw-semibold text-dark"><?php echo htmlspecialchars($ord['customer_name']); ?></h6>
                                    <span class="small text-muted d-block"><?php echo htmlspecialchars($ord['customer_email']); ?></span>
                                </td>
                                <td class="text-muted small"><?php echo date("M d, Y, g:i a", strtotime($ord['order_date'])); ?></td>
                                <td class="fw-bold text-dark">$<?php echo number_format($ord['total_amount'], 2); ?></td>
                                <td class="fw-semibold text-muted small"><?php echo htmlspecialchars($ord['payment_method']); ?></td>
                                <td>
                                    <form action="orders.php" method="POST" class="m-0">
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                        <select name="status" onchange="this.form.submit()" class="form-select form-select-sm fw-bold border-0 <?php echo $select_bg; ?>" style="<?php echo ($ord['status'] === 'Out For Delivery') ? 'background-color: rgba(111, 66, 193, 0.15) !important; color: #6f42c1 !important;' : ''; ?>">
                                            <option value="Pending" <?php echo ($ord['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Confirmed" <?php echo ($ord['status'] === 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="Preparing" <?php echo ($ord['status'] === 'Preparing') ? 'selected' : ''; ?>>Preparing</option>
                                            <option value="Out For Delivery" <?php echo ($ord['status'] === 'Out For Delivery') ? 'selected' : ''; ?>>Out For Delivery</option>
                                            <option value="Delivered" <?php echo ($ord['status'] === 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="Cancelled" <?php echo ($ord['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-link text-decoration-none fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#items_<?php echo $order_id; ?>" aria-expanded="false" aria-controls="items_<?php echo $order_id; ?>">
                                        <i class="fa-solid fa-chevron-down me-1"></i>Items (<?php echo count($items); ?>)
                                    </button>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="generate-pdf.php?id=<?php echo $order_id; ?>" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                        <i class="fa-solid fa-file-pdf me-1"></i>Invoice
                                    </a>
                                </td>
                            </tr>
                            
                            <!-- Collapsible Row -->
                            <tr>
                                <td colspan="8" class="p-0 border-0">
                                    <div class="collapse bg-light" id="items_<?php echo $order_id; ?>">
                                        <div class="p-4 border-bottom">
                                            <div class="row g-3">
                                                <!-- Items list -->
                                                <div class="col-md-6">
                                                    <h6 class="fw-bold mb-3 small text-muted">ORDERED DISHES</h6>
                                                    <div class="card border-0 shadow-xs p-3">
                                                        <?php foreach ($items as $item): ?>
                                                            <div class="d-flex justify-content-between mb-2 small pb-2 border-bottom last-border-0">
                                                                <span><?php echo htmlspecialchars($item['name'] ?? 'Removed Food'); ?> <strong class="text-dark">x <?php echo $item['quantity']; ?></strong></span>
                                                                <strong class="text-dark">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <!-- Shipping Address -->
                                                <div class="col-md-6">
                                                    <h6 class="fw-bold mb-3 small text-muted">SHIPPING DETAILS</h6>
                                                    <div class="card border-0 shadow-xs p-3 h-100 justify-content-center">
                                                        <p class="mb-1 text-muted small"><strong>Receiver:</strong> <?php echo htmlspecialchars($ord['shipping_name']); ?></p>
                                                        <p class="mb-1 text-muted small"><strong>Phone:</strong> <?php echo htmlspecialchars($ord['shipping_phone']); ?></p>
                                                        <p class="mb-0 text-muted small"><strong>Address:</strong> <?php echo htmlspecialchars($ord['shipping_address']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No orders found in the store.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
