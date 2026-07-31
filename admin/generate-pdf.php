<?php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Secure Admin area
checkAdminAuth();

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    die("Error: Invalid order ID.");
}

// Fetch order and customer details
$order = fetchOne(
    "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     WHERE o.id = ?",
    [$order_id]
);

if (!$order) {
    die("Error: Order not found.");
}

// Fetch order items
$items = fetchAll(
    "SELECT oi.quantity, oi.price, f.name 
     FROM order_items oi 
     LEFT JOIN foods f ON oi.food_id = f.id 
     WHERE oi.order_id = ?",
    [$order_id]
);

// Include FPDF Library
$fpdf_path = __DIR__ . '/includes/fpdf.php';
if (!file_exists($fpdf_path)) {
    die("Error: PDF library missing. Please wait for FPDF setup to finish.");
}
require_once $fpdf_path;

class FE_Invoice extends FPDF {
    // Header page layout
    function Header() {
        // Logo Title
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(255, 94, 58); // Primary orange color #ff5e3a
        $this->Cell(100, 10, 'FoodExpress', 0, 0);
        
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(90, 10, 'INVOICE / RECEIPT', 0, 1, 'R');
        
        // Subtitle Address
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(100, 4, '123 Food Street, Gourmet City', 0, 0);
        $this->Cell(90, 4, 'Support: support@foodexpress.com', 0, 1, 'R');
        $this->Cell(100, 4, 'Phone: +1 234 567 890', 0, 1);
        
        // Line break
        $this->Ln(8);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(6);
    }

    // Page footer layout
    function Footer() {
        $this->SetY(-25);
        $this->SetDrawColor(220, 220, 220);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(4);
        
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Thank you for ordering with FoodExpress!', 0, 1, 'C');
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 4, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
    }
}

// Instantiate PDF
$pdf = new FE_Invoice();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Invoice Details Info Box
$pdf->SetFillColor(245, 246, 250);
$pdf->Rect(10, $pdf->GetY(), 190, 35, 'F');

$pdf->SetX(15);
$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 5, 'INVOICE TO:', 0, 0);
$pdf->Cell(95, 5, 'INVOICE DETAILS:', 0, 1);

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(80, 80, 80);
$pdf->Cell(95, 5, 'Customer: ' . $order['customer_name'], 0, 0);
$pdf->Cell(95, 5, 'Invoice ID: #FE-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT), 0, 1);

$pdf->Cell(95, 5, 'Phone: ' . ($order['shipping_phone'] ? $order['shipping_phone'] : $order['customer_phone']), 0, 0);
$pdf->Cell(95, 5, 'Date: ' . date("F j, Y, g:i a", strtotime($order['order_date'])), 0, 1);

$pdf->Cell(95, 5, 'Address: ' . substr($order['shipping_address'], 0, 45) . (strlen($order['shipping_address']) > 45 ? '...' : ''), 0, 0);
$pdf->Cell(95, 5, 'Payment Method: ' . $order['payment_method'], 0, 1);

$pdf->Ln(12);

// Order Status Alert Ribbon
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(255, 255, 255);
if ($order['status'] === 'Cancelled') {
    $pdf->SetFillColor(220, 53, 69); // Red
    $pdf->Cell(190, 8, '   ORDER STATUS: CANCELLED', 0, 1, 'L', true);
} elseif ($order['status'] === 'Delivered') {
    $pdf->SetFillColor(40, 167, 69); // Green
    $pdf->Cell(190, 8, '   ORDER STATUS: DELIVERED', 0, 1, 'L', true);
} else {
    $pdf->SetFillColor(26, 26, 46); // Dark
    $pdf->Cell(190, 8, '   ORDER STATUS: ' . strtoupper($order['status']), 0, 1, 'L', true);
}

$pdf->Ln(6);

// Table Header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(10, 8, 'S.N', 1, 0, 'C', true);
$pdf->Cell(100, 8, 'Food Item', 1, 0, 'L', true);
$pdf->Cell(25, 8, 'Price', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Qty', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Total', 1, 1, 'C', true);

// Table Body Rows
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(50, 50, 50);
$sn = 1;
foreach ($items as $item) {
    $item_total = floatval($item['price']) * intval($item['quantity']);
    $pdf->Cell(10, 8, $sn++, 1, 0, 'C');
    $pdf->Cell(100, 8, ' ' . ($item['name'] ?? 'Removed Food Item'), 1, 0, 'L');
    $pdf->Cell(25, 8, '$' . number_format($item['price'], 2), 1, 0, 'C');
    $pdf->Cell(25, 8, $item['quantity'], 1, 0, 'C');
    $pdf->Cell(30, 8, '$' . number_format($item_total, 2), 1, 1, 'C');
}

// Totals Row
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(135, 8, '', 0, 0);
$pdf->Cell(25, 8, 'Grand Total:', 0, 0, 'R');
$pdf->Cell(30, 8, ' $' . number_format($order['total_amount'], 2), 1, 1, 'C');

// Generate and Output PDF
$pdf->Output('I', 'Invoice_FE_' . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . '.pdf');
?>
