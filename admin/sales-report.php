<?php
$page_title = "Sales Report & Analytics";
require_once __DIR__ . '/includes/header.php';

// Date Filters (default: last 30 days)
$default_start = date('Y-m-d', strtotime('-30 days'));
$default_end = date('Y-m-d');

$start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : $default_start;
$end_date = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : $default_end;

// Make date range inclusive for times (00:00:00 to 23:59:59)
$db_start = $start_date . ' 00:00:00';
$db_end = $end_date . ' 23:59:59';

// 1. Fetch Summary Stats (Delivered Orders Only)
$stats = fetchOne(
    "SELECT COUNT(*) as sales_count, SUM(total_amount) as total_revenue 
     FROM orders 
     WHERE status = 'Delivered' AND order_date BETWEEN ? AND ?",
    [$db_start, $db_end]
);

$sales_count = intval($stats['sales_count'] ?? 0);
$total_revenue = floatval($stats['total_revenue'] ?? 0.00);
$avg_order_value = $sales_count > 0 ? ($total_revenue / $sales_count) : 0.00;

// 2. Fetch Delivered Orders list for table
$report_orders = fetchAll(
    "SELECT o.*, u.name as customer_name FROM orders o 
     JOIN users u ON o.user_id = u.id 
     WHERE o.status = 'Delivered' AND o.order_date BETWEEN ? AND ? 
     ORDER BY o.order_date DESC",
    [$db_start, $db_end]
);

// 3. Fetch daily trend metrics for Chart.js
$daily_sales = fetchAll(
    "SELECT DATE(order_date) as sales_day, SUM(total_amount) as total_amount, COUNT(*) as order_count 
     FROM orders 
     WHERE status = 'Delivered' AND order_date BETWEEN ? AND ? 
     GROUP BY DATE(order_date) 
     ORDER BY DATE(order_date) ASC",
    [$db_start, $db_end]
);

// Format chart variables
$chart_labels = [];
$chart_revenue = [];
$chart_counts = [];

foreach ($daily_sales as $row) {
    $chart_labels[] = date('M d', strtotime($row['sales_day']));
    $chart_revenue[] = floatval($row['total_amount']);
    $chart_counts[] = intval($row['order_count']);
}
?>

<!-- Filter Form -->
<div class="admin-card mb-4">
    <div class="admin-card-body">
        <form action="sales-report.php" method="GET" class="row align-items-end g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label-custom">Start Date</label>
                <input type="date" class="form-control form-control-custom" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            
            <div class="col-md-3">
                <label for="end_date" class="form-label-custom">End Date</label>
                <input type="date" class="form-control form-control-custom" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-admin-accent py-2.5 rounded-3">
                    <i class="fa-solid fa-filter me-2"></i>Apply Date Filter
                </button>
            </div>

            <div class="col-md-3 d-grid">
                <a href="export-sales-report.php?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="btn btn-outline-danger py-2.5 rounded-3 fw-semibold">
                    <i class="fa-solid fa-file-pdf me-2"></i>Download PDF Report
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Metrics Overview -->
<div class="row g-4 mb-4">
    <!-- Revenue -->
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="stat-label mb-1">Delivered Revenue</h5>
                    <h3 class="stat-number text-success">$<?php echo number_format($total_revenue, 2); ?></h3>
                </div>
                <div class="stat-icon-wrapper icon-success">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Count -->
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="stat-label mb-1">Delivered Orders</h5>
                    <h3 class="stat-number"><?php echo $sales_count; ?></h3>
                </div>
                <div class="stat-icon-wrapper icon-orange">
                    <i class="fa-solid fa-truck-ramp-box"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Average -->
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="stat-label mb-1">Avg. Order Value</h5>
                    <h3 class="stat-number text-primary">$<?php echo number_format($avg_order_value, 2); ?></h3>
                </div>
                <div class="stat-icon-wrapper icon-info">
                    <i class="fa-solid fa-calculator"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Chart Visualizer -->
    <div class="col-lg-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="fw-bold"><i class="fa-solid fa-chart-area me-2 text-warning"></i>Revenue Trend Graph</span>
            </div>
            <div class="admin-card-body">
                <div style="height: 300px;">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Sales Log -->
<div class="admin-card">
    <div class="admin-card-header">
        <span class="fw-bold"><i class="fa-solid fa-list-check me-2 text-warning"></i>Delivered Sales Ledger</span>
    </div>
    <div class="admin-card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">Invoice No</th>
                        <th>Customer</th>
                        <th>Date Sold</th>
                        <th>Payment Method</th>
                        <th class="pe-4 text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($report_orders)): ?>
                        <?php foreach ($report_orders as $ro): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark">#FE-<?php echo str_pad($ro['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($ro['customer_name']); ?></td>
                                <td class="text-muted small"><?php echo date("M d, Y, g:i a", strtotime($ro['order_date'])); ?></td>
                                <td class="fw-semibold text-muted small"><?php echo htmlspecialchars($ro['payment_method']); ?></td>
                                <td class="pe-4 text-end fw-bold text-success">$<?php echo number_format($ro['total_amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No delivered orders found in this date range.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const trendCtx = document.getElementById('salesTrendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Daily Revenue ($)',
                data: <?php echo json_encode($chart_revenue); ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: '#28a745'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return '$' + value; }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
