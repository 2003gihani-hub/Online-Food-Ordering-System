<?php
header('Content-Type: application/json');

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// If not logged in, reject AJAX cart operations with unauthorized status
if (!isLoggedIn()) {
    echo json_encode([
        'status' => 'unauthorized',
        'message' => 'Login required to modify shopping cart.'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($action)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request.']);
    exit();
}

/**
 * Recalculate total quantity and amount of current user's cart
 */
function getCartStatus($user_id) {
    // Total count of items
    $count_res = fetchOne("SELECT SUM(quantity) as total_qty FROM cart WHERE user_id = ?", [$user_id]);
    $cart_count = $count_res && $count_res['total_qty'] ? intval($count_res['total_qty']) : 0;
    
    // Total sum amount
    $total_res = fetchOne(
        "SELECT SUM(c.quantity * f.price) as total_amount FROM cart c 
         JOIN foods f ON c.food_id = f.id 
         WHERE c.user_id = ?",
        [$user_id]
    );
    $cart_total = $total_res && $total_res['total_amount'] ? floatval($total_res['total_amount']) : 0.00;

    return ['cart_count' => $cart_count, 'cart_total' => $cart_total];
}

switch ($action) {
    case 'add':
        $food_id = isset($_POST['food_id']) ? intval($_POST['food_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        if ($food_id <= 0 || $quantity <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid food ID or quantity.']);
            exit();
        }

        // Verify food item is active and fetch stock
        $food = fetchOne("SELECT id, name, stock_quantity, status FROM foods WHERE id = ?", [$food_id]);
        if (!$food || $food['status'] !== 'active') {
            echo json_encode(['status' => 'error', 'message' => 'This food item is currently not available.']);
            exit();
        }

        // Check if item is already in user's cart
        $existing = fetchOne("SELECT id, quantity FROM cart WHERE user_id = ? AND food_id = ?", [$user_id, $food_id]);
        $current_in_cart = $existing ? intval($existing['quantity']) : 0;
        $new_qty = $current_in_cart + $quantity;

        // Check stock availability
        if (intval($food['stock_quantity']) < $new_qty) {
            if (intval($food['stock_quantity']) <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Sorry, this item is currently Out of Stock.']);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Only ' . $food['stock_quantity'] . ' units of ' . htmlspecialchars($food['name']) . ' are available in stock. You already have ' . $current_in_cart . ' in your cart.'
                ]);
            }
            exit();
        }

        if ($existing) {
            executeUpdate("UPDATE cart SET quantity = ? WHERE id = ?", [$new_qty, $existing['id']]);
        } else {
            executeUpdate("INSERT INTO cart (user_id, food_id, quantity) VALUES (?, ?, ?)", [$user_id, $food_id, $quantity]);
        }

        $cart_status = getCartStatus($user_id);
        echo json_encode([
            'status' => 'success',
            'message' => htmlspecialchars($food['name']) . ' added to cart!',
            'cart_count' => $cart_status['cart_count']
        ]);
        break;

    case 'update':
        $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

        if ($cart_id <= 0 || $quantity <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
            exit();
        }

        // Verify ownership and fetch stock
        $cart_item = fetchOne(
            "SELECT c.id, c.food_id, f.price, f.name, f.stock_quantity FROM cart c 
             JOIN foods f ON c.food_id = f.id 
             WHERE c.id = ? AND c.user_id = ?",
            [$cart_id, $user_id]
        );

        if (!$cart_item) {
            echo json_encode(['status' => 'error', 'message' => 'Cart item not found.']);
            exit();
        }

        // Check stock availability
        if (intval($cart_item['stock_quantity']) < $quantity) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Only ' . $cart_item['stock_quantity'] . ' units of ' . htmlspecialchars($cart_item['name']) . ' are available in stock.'
            ]);
            exit();
        }

        // Update quantity
        executeUpdate("UPDATE cart SET quantity = ? WHERE id = ?", [$quantity, $cart_id]);

        $row_total = floatval($cart_item['price']) * $quantity;
        $cart_status = getCartStatus($user_id);

        echo json_encode([
            'status' => 'success',
            'message' => 'Quantity updated for ' . htmlspecialchars($cart_item['name']),
            'row_total' => $row_total,
            'cart_total' => $cart_status['cart_total'],
            'cart_count' => $cart_status['cart_count']
        ]);
        break;

    case 'remove':
        $cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;

        if ($cart_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
            exit();
        }

        // Verify ownership and delete
        $cart_item = fetchOne("SELECT id FROM cart WHERE id = ? AND user_id = ?", [$cart_id, $user_id]);
        if (!$cart_item) {
            echo json_encode(['status' => 'error', 'message' => 'Item not found in your cart.']);
            exit();
        }

        executeUpdate("DELETE FROM cart WHERE id = ?", [$cart_id]);

        $cart_status = getCartStatus($user_id);
        echo json_encode([
            'status' => 'success',
            'message' => 'Item removed from your cart.',
            'cart_total' => $cart_status['cart_total'],
            'cart_count' => $cart_status['cart_count']
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
        break;
}
?>
