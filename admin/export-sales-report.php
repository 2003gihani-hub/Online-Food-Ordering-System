<?php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Secure Admin area
checkAdminAuth();

// Retrieve and validate dates
$default_start = date('Y-m-d', strtotime('-30 days'));
$default_end = date('Y-m-d');

$start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : $default_start;
$end_date = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : $default_end;

if (strtotime($start_date) === false || strtotime($end_date) === false) {
    die("Error: Invalid date range.");
}

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

// Include FPDF Library
$fpdf_path = __DIR__ . '/includes/fpdf.php';
if (!file_exists($fpdf_path)) {
    die("Error: PDF library missing.");
}
require_once $fpdf_path;

class FE_SalesReport extends FPDF {
    protected $start_date;
    protected $end_date;

    function __construct($start_date, $end_date) {
        parent::__construct();
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    // Header layout
    function Header() {
        // Logo
        $this->SetFont('Arial', 'B', 22);
        $this->SetTextColor(255, 94, 58); // #ff5e3a
        $this->Cell(100, 12, 'FoodExpress', 0, 0);
        
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(90, 12, 'SALES REPORT', 0, 1, 'R');
        
        // Date details
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(120, 120, 120);
        $formatted_range = date('M d, Y', strtotime($this->start_date)) . ' - ' . date('M d, Y', strtotime($this->end_date));
        $this->Cell(100, 5, 'Date Range: ' . $formatted_range, 0, 0);
        $this->Cell(90, 5, 'Generated: ' . date("F j, Y, g:i a"), 0, 1, 'R');
        
        // Line break
        $this->Ln(4);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(6);
    }

    // Footer layout
    function Footer() {
        $this->SetY(-20);
        $this->SetDrawColor(220, 220, 220);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(3);
        
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'FoodExpress Administration Sales Analytics', 0, 1, 'C');
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 4, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
    }
}

// Instantiate PDF
$pdf = new FE_SalesReport($start_date, $end_date);
$pdf->AliasNbPages();
$pdf->AddPage();

// Summary Cards Block
$pdf->SetFillColor(245, 246, 250);
$pdf->Rect(10, $pdf->GetY(), 190, 24, 'F');

$pdf->SetY($pdf->GetY() + 3);

// Column Labels
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->SetX(15);
$pdf->Cell(60, 4, 'TOTAL REVENUE', 0, 0, 'C');
$pdf->Cell(60, 4, 'TOTAL ORDERS', 0, 0, 'C');
$pdf->Cell(55, 4, 'AVG. ORDER VALUE', 0, 1, 'C');

// Column Values
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(26, 26, 46);
$pdf->SetX(15);
$pdf->Cell(60, 8, '$' . number_format($total_revenue, 2), 0, 0, 'C');
$pdf->Cell(60, 8, $sales_count, 0, 0, 'C');
$pdf->Cell(55, 8, '$' . number_format($avg_order_value, 2), 0, 1, 'C');

$pdf->Ln(10);

if (empty($report_orders)) {
    $pdf->SetFont('Arial', 'I', 11);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(190, 15, 'No delivered orders found.', 0, 1, 'C');
} else {
    // Table Header
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(35, 8, 'Invoice No', 1, 0, 'C', true);
    $pdf->Cell(55, 8, 'Customer Name', 1, 0, 'L', true);
    $pdf->Cell(45, 8, 'Order Date', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Payment Method', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Amount', 1, 1, 'R', true);

    // Table Body Rows
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(50, 50, 50);

    foreach ($report_orders as $ro) {
        $invoice_no = '#FE-' . str_pad($ro['id'], 6, '0', STR_PAD_LEFT);
        $customer_name = utf8_decode($ro['customer_name']);
        $order_date = date("M d, Y, g:i a", strtotime($ro['order_date']));
        $payment_method = $ro['payment_method'];
        $amount = '$' . number_format($ro['total_amount'], 2);
        
        $pdf->Cell(35, 8, $invoice_no, 1, 0, 'C');
        $pdf->Cell(55, 8, ' ' . $customer_name, 1, 0, 'L');
        $pdf->Cell(45, 8, $order_date, 1, 0, 'C');
        $pdf->Cell(30, 8, $payment_method, 1, 0, 'C');
        $pdf->Cell(25, 8, $amount, 1, 1, 'R');
    }

    // Grand Total Row
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(135, 8, '', 0, 0);
    $pdf->Cell(30, 8, 'Grand Total:', 0, 0, 'R');
    $pdf->Cell(25, 8, ' $' . number_format($total_revenue, 2), 1, 1, 'R');
}

// Clear any buffered output before sending PDF headers/content
if (ob_get_length()) {
    ob_end_clean();
}

// Download PDF file
$pdf->Output('D', 'Sales_Report_' . date('Y-m-d') . '.pdf');
exit();
?>
