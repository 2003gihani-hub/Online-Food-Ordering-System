<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

checkCustomerAuth();

$user_id = $_SESSION['user_id'];

// Fetch all orders of this customer
$orders = fetchAll("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC", [$user_id]);

$page_title = "My Orders";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="section-title">
        <h2>Order History</h2>
        <p class="text-muted">Review your past and current food orders</p>
    </div>

    <?php if (!empty($orders)): ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr class="py-3">
                            <th class="ps-4">Order ID</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th class="text-center">Details</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): 
                            $order_id = $order['id'];
                            
                            // Map status to visual classes
                            $status_class = '';
                            switch ($order['status']) {
                                case 'Pending': $status_class = 'bg-warning text-dark'; break;
                                case 'Confirmed': $status_class = 'bg-info text-white'; break;
                                case 'Preparing': $status_class = 'bg-primary text-white'; break;
                                case 'Out For Delivery': $status_class = 'bg-purple text-white'; break; // We will handle .bg-purple in inline or style.css
                                case 'Delivered': $status_class = 'bg-success text-white'; break;
                                case 'Cancelled': $status_class = 'bg-danger text-white'; break;
                            }
                            
                            // Fetch items for this order
                            $items = fetchAll(
                                "SELECT oi.quantity, oi.price, f.name, f.image 
                                 FROM order_items oi 
                                 LEFT JOIN foods f ON oi.food_id = f.id 
                                 WHERE oi.order_id = ?",
                                [$order_id]
                            );
                        ?>
                            <!-- Main Order Row -->
                            <tr class="border-bottom">
                                <td class="ps-4 fw-bold text-dark">#FE-<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></td>
                                <td class="text-muted"><?php echo date("M d, Y, g:i a", strtotime($order['order_date'])); ?></td>
                                <td class="fw-bold text-dark">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td class="fw-semibold text-muted"><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 <?php echo $status_class; ?>" style="<?php echo ($order['status'] === 'Out For Delivery') ? 'background-color: #6f42c1 !important;' : ''; ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-link text-decoration-none fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#items_<?php echo $order_id; ?>" aria-expanded="false" aria-controls="items_<?php echo $order_id; ?>">
                                        <i class="fa-solid fa-chevron-down me-1"></i>View Items (<?php echo count($items); ?>)
                                    </button>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="track-order.php?id=<?php echo $order_id; ?>" class="btn btn-sm btn-outline-custom rounded-pill px-3">
                                        <i class="fa-solid fa-route me-1"></i>Track Status
                                    </a>
                                </td>
                            </tr>
                            
                            <!-- Collapsible Order Items Details Row -->
                            <tr>
                                <td colspan="7" class="p-0 border-0">
                                    <div class="collapse bg-light" id="items_<?php echo $order_id; ?>">
                                        <div class="p-4 border-bottom">
                                            <h6 class="fw-bold mb-3"><i class="fa-solid fa-circle-info me-2 text-warning"></i>Itemized Costs</h6>
                                            <div class="row g-3">
                                                <div class="col-md-7">
                                                    <div class="card border-0 shadow-xs p-3">
                                                        <?php foreach ($items as $item): ?>
                                                            <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom last-border-0">
                                                                <div class="d-flex align-items-center gap-3">
                                                                    <div style="width: 45px; height: 45px; border-radius: 8px; overflow: hidden; background-color:#ddd;">
                                                                        <img src="<?php echo getFoodImageUrl($item['image']); ?>" class="w-100 h-100 object-fit-cover" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                                    </div>
                                                                    <div>
                                                                        <span class="fw-bold small d-block"><?php echo htmlspecialchars($item['name'] ?? 'Removed Dish'); ?></span>
                                                                        <span class="text-muted small">$<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></span>
                                                                    </div>
                                                                </div>
                                                                <span class="fw-bold text-dark small">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="card border-0 shadow-xs p-3 h-100 justify-content-center">
                                                        <p class="mb-1 text-muted small"><strong>Shipping Name:</strong> <?php echo htmlspecialchars($order['shipping_name']); ?></p>
                                                        <p class="mb-1 text-muted small"><strong>Shipping Phone:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                                                        <p class="mb-0 text-muted small"><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <!-- No Orders Found -->
        <div class="row justify-content-center py-5">
            <div class="col-md-6 text-center">
                <div class="text-muted mb-4"><i class="fa-solid fa-clock-rotate-left fa-5x"></i></div>
                <h3 class="fw-bold">No Orders Yet</h3>
                <p class="text-muted mb-4">You haven't placed any food orders with us yet. Browse our categories and find something delicious!</p>
                <a href="index.php" class="btn btn-primary-custom rounded-pill px-5 py-2fw-bold">Go To Home</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
