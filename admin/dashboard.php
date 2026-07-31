<?php
$page_title = "Overview Dashboard";
require_once __DIR__ . '/includes/header.php';

// Fetch Statistics Metrics
$total_orders_res = fetchOne("SELECT COUNT(*) as cnt FROM orders");
$total_orders = $total_orders_res['cnt'] ?? 0;

$total_revenue_res = fetchOne("SELECT SUM(total_amount) as rev FROM orders WHERE status != 'Cancelled'");
$total_revenue = $total_revenue_res['rev'] ?? 0.00;

$total_users_res = fetchOne("SELECT COUNT(*) as cnt FROM users");
$total_users = $total_users_res['cnt'] ?? 0;

$total_foods_res = fetchOne("SELECT COUNT(*) as cnt FROM foods WHERE status = 'active'");
$total_foods = $total_foods_res['cnt'] ?? 0;

// Stock Management Statistics
$total_items_res = fetchOne("SELECT COUNT(*) as cnt FROM foods");
$total_items = $total_items_res['cnt'] ?? 0;

$in_stock_items_res = fetchOne("SELECT COUNT(*) as cnt FROM foods WHERE stock_quantity > 0");
$in_stock_items = $in_stock_items_res['cnt'] ?? 0;

$low_stock_items_res = fetchOne("SELECT COUNT(*) as cnt FROM foods WHERE stock_quantity <= 10 AND stock_quantity > 0");
$low_stock_items = $low_stock_items_res['cnt'] ?? 0;

$out_of_stock_items_res = fetchOne("SELECT COUNT(*) as cnt FROM foods WHERE stock_quantity = 0");
$out_of_stock_items = $out_of_stock_items_res['cnt'] ?? 0;

// Fetch Recent Orders (Limit 5)
$recent_orders = fetchAll(
    "SELECT o.*, u.name as customer_name FROM orders o 
     JOIN users u ON o.user_id = u.id 
     ORDER BY o.order_date DESC LIMIT 5"
);

// Fetch Order Status Counts for Charts
$status_counts = fetchAll("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
$chart_data = [
    'Pending' => 0,
    'Confirmed' => 0,
    'Preparing' => 0,
    'Out For Delivery' => 0,
    'Delivered' => 0,
    'Cancelled' => 0
];
foreach ($status_counts as $row) {
    if (isset($chart_data[$row['status']])) {
        $chart_data[$row['status']] = intval($row['cnt']);
    }
}
?>

<!-- Statistics Metrics Row -->
<div class="row g-4 mb-4">
    <!-- Earnings Metric -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="stat-label mb-1">Total Revenue</h5>
                    <h3 class="stat-number">$<?php echo number_format($total_revenue, 2); ?></h3>
                </div>
                <div class="stat-icon-wrapper icon-success">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Orders Metric -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="stat-label mb-1">Total Orders</h5>
                    <h3 class="stat-number"><?php echo $total_orders; ?></h3>
                </div>
                <div class="stat-icon-wrapper icon-orange">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Users Metric -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="stat-label mb-1">Customers</h5>
                    <h3 class="stat-number"><?php echo $total_users; ?></h3>
                </div>
                <div class="stat-icon-wrapper icon-info">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Foods Metric -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="stat-label mb-1">Active Foods</h5>
                    <h3 class="stat-number"><?php echo $total_foods; ?></h3>
                </div>
                <div class="stat-icon-wrapper icon-primary">
                    <i class="fa-solid fa-bowl-food"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Metrics Row -->
<div class="row g-4 mb-4">
    <!-- Total Items -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="stat-label mb-1">Total Items</h5>
                    <h3 class="stat-number"><?php echo $total_items; ?></h3>
                </div>
                <div class="stat-icon-wrapper icon-primary">
                    <i class="fa-solid fa-list"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- In Stock Items -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="stat-label mb-1">In Stock Items</h5>
                    <h3 class="stat-number"><?php echo $in_stock_items; ?></h3>
                </div>
                <div class="stat-icon-wrapper icon-success">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Low Stock Items -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="stat-label mb-1">Low Stock Items</h5>
                    <h3 class="stat-number"><?php echo $low_stock_items; ?></h3>
                </div>
                <div class="stat-icon-wrapper icon-orange">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Out of Stock Items -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="stat-label mb-1">Out of Stock Items</h5>
                    <h3 class="stat-number"><?php echo $out_of_stock_items; ?></h3>
                </div>
                <div class="stat-icon-wrapper icon-danger">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Chart Section -->
    <div class="col-lg-5">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <span class="fw-bold"><i class="fa-solid fa-chart-pie me-2 text-warning"></i>Order Status Distribution</span>
            </div>
            <div class="admin-card-body d-flex flex-column align-items-center justify-content-center">
                <div style="max-height: 250px; width: 100%; max-width: 250px;">
                    <canvas id="orderStatusChart"></canvas>
                </div>
                <!-- Mini legends with counts -->
                <div class="row g-2 w-100 mt-3 small justify-content-center">
                    <?php foreach ($chart_data as $status => $cnt): ?>
                        <div class="col-auto">
                            <span class="badge status-badge <?php echo 'status-' . strtolower(str_replace(' ', '', $status)); ?>">
                                <?php echo htmlspecialchars($status) . ": " . $cnt; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Section -->
    <div class="col-lg-7">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <span class="fw-bold"><i class="fa-solid fa-clock-rotate-left me-2 text-warning"></i>Recent Incoming Orders</span>
                <a href="orders.php" class="btn btn-xs btn-outline-dark rounded-pill py-1 px-3 fs-7" style="font-size: 0.75rem;">View All</a>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_orders)): ?>
                                <?php foreach ($recent_orders as $ord): 
                                    $status_badge_class = '';
                                    switch ($ord['status']) {
                                        case 'Pending': $status_badge_class = 'status-pending'; break;
                                        case 'Confirmed': $status_badge_class = 'status-confirmed'; break;
                                        case 'Preparing': $status_badge_class = 'status-preparing'; break;
                                        case 'Out For Delivery': $status_badge_class = 'status-outfordelivery'; break;
                                        case 'Delivered': $status_badge_class = 'status-delivered'; break;
                                        case 'Cancelled': $status_badge_class = 'status-cancelled'; break;
                                    }
                                ?>
                                    <tr>
                                        <td class="fw-bold text-dark">#FE-<?php echo str_pad($ord['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                                        <td class="text-muted small"><?php echo htmlspecialchars($ord['payment_method']); ?></td>
                                        <td class="fw-semibold">$<?php echo number_format($ord['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $status_badge_class; ?>">
                                                <?php echo htmlspecialchars($ord['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('orderStatusChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Confirmed', 'Preparing', 'Out For Delivery', 'Delivered', 'Cancelled'],
            datasets: [{
                data: [
                    <?php echo $chart_data['Pending']; ?>,
                    <?php echo $chart_data['Confirmed']; ?>,
                    <?php echo $chart_data['Preparing']; ?>,
                    <?php echo $chart_data['Out For Delivery']; ?>,
                    <?php echo $chart_data['Delivered']; ?>,
                    <?php echo $chart_data['Cancelled']; ?>
                ],
                backgroundColor: [
                    '#ffc107', // Pending - Warning
                    '#17a2b8', // Confirmed - Info
                    '#007bff', // Preparing - Primary Blue
                    '#6f42c1', // Out For Delivery - Purple
                    '#28a745', // Delivered - Success
                    '#dc3545'  // Cancelled - Danger
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // We use our custom list below the chart
                }
            },
            cutout: '65%'
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
