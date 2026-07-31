<?php
header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Secure customer pages
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve variables
$transaction_id = sanitizeInput($_POST['transaction_id'] ?? '');
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0.00;
$shipping_name = sanitizeInput($_POST['shipping_name'] ?? '');
$shipping_phone = sanitizeInput($_POST['shipping_phone'] ?? '');
$shipping_address = sanitizeInput($_POST['shipping_address'] ?? '');

if (empty($transaction_id) || $amount <= 0 || empty($shipping_name) || empty($shipping_phone) || empty($shipping_address)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required payment or shipping details.']);
    exit();
}

// Get user's cart items
$cart_items = fetchAll(
    "SELECT c.quantity, f.id as food_id, f.price 
     FROM cart c 
     JOIN foods f ON c.food_id = f.id 
     WHERE c.user_id = ?",
    [$user_id]
);

if (empty($cart_items)) {
    echo json_encode(['status' => 'error', 'message' => 'Shopping cart is empty.']);
    exit();
}

// Start database transaction
$conn->begin_transaction();

try {
    // 0. Recheck stock and lock rows to prevent race conditions
    foreach ($cart_items as $item) {
        $food_id = $item['food_id'];
        $qty_ordered = intval($item['quantity']);
        
        $lock_stmt = $conn->prepare("SELECT name, stock_quantity, status FROM foods WHERE id = ? FOR UPDATE");
        $lock_stmt->bind_param("i", $food_id);
        $lock_stmt->execute();
        $res = $lock_stmt->get_result()->fetch_assoc();
        $lock_stmt->close();
        
        if (!$res || $res['status'] !== 'active' || intval($res['stock_quantity']) < $qty_ordered) {
            throw new Exception("This item is no longer available because it is Out of Stock.");
        }
    }

    // 1. Create Order (Since paid online, order status is 'Confirmed' directly!)
    $order_query = "INSERT INTO orders (user_id, total_amount, status, payment_method, shipping_name, shipping_phone, shipping_address) 
                    VALUES (?, ?, 'Confirmed', 'PayPal', ?, ?, ?)";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("idsss", $user_id, $amount, $shipping_name, $shipping_phone, $shipping_address);
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();

    // 2. Create Order Items
    $item_query = "INSERT INTO order_items (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)";
    $item_stmt = $conn->prepare($item_query);
    foreach ($cart_items as $item) {
        $item_stmt->bind_param("iiid", $order_id, $item['food_id'], $item['quantity'], $item['price']);
        $item_stmt->execute();
    }
    $item_stmt->close();

    // 3. Deduct Stock
    $update_stock_query = "UPDATE foods SET stock_quantity = stock_quantity - ? WHERE id = ?";
    $update_stock_stmt = $conn->prepare($update_stock_query);
    foreach ($cart_items as $item) {
        $update_stock_stmt->bind_param("ii", $item['quantity'], $item['food_id']);
        $update_stock_stmt->execute();
    }
    $update_stock_stmt->close();

    // 4. Record Payment
    $payment_query = "INSERT INTO payments (order_id, transaction_id, payment_amount, payment_status) VALUES (?, ?, ?, 'COMPLETED')";
    $pay_stmt = $conn->prepare($payment_query);
    $pay_stmt->bind_param("isd", $order_id, $transaction_id, $amount);
    $pay_stmt->execute();
    $pay_stmt->close();

    // 5. Empty Cart
    $clear_query = "DELETE FROM cart WHERE user_id = ?";
    $clear_stmt = $conn->prepare($clear_query);
    $clear_stmt->bind_param("i", $user_id);
    $clear_stmt->execute();
    $clear_stmt->close();

    // Commit changes
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    // Rollback if any query errors
    $conn->rollback();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
