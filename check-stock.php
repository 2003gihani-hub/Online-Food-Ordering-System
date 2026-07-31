<?php
header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// If not logged in, return unauthorized
if (!isLoggedIn()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Login required to check stock.'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's cart items
$cart_items = fetchAll(
    "SELECT c.quantity, f.id as food_id, f.name, f.stock_quantity, f.status 
     FROM cart c 
     JOIN foods f ON c.food_id = f.id 
     WHERE c.user_id = ?",
    [$user_id]
);

if (empty($cart_items)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Your shopping cart is empty.'
    ]);
    exit();
}

foreach ($cart_items as $item) {
    if ($item['status'] !== 'active' || intval($item['stock_quantity']) < intval($item['quantity'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'This item is no longer available because it is Out of Stock.'
        ]);
        exit();
    }
}

echo json_encode(['status' => 'success']);
?>
