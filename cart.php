<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Secure customer pages
checkCustomerAuth();

$page_title = "My Shopping Cart";
require_once __DIR__ . '/includes/header.php';

$user_id = $_SESSION['user_id'];

// Query all items in user's cart
$cart_items = fetchAll(
    "SELECT c.id as cart_id, c.quantity, f.id as food_id, f.name, f.price, f.image, cat.name as category_name 
     FROM cart c 
     JOIN foods f ON c.food_id = f.id 
     JOIN categories cat ON f.category_id = cat.id 
     WHERE c.user_id = ? ORDER BY c.id DESC",
    [$user_id]
);

// Calculate totals
$cart_total = 0;
foreach ($cart_items as $item) {
    $cart_total += floatval($item['price']) * intval($item['quantity']);
}
?>

<div class="container my-5">
    <div class="section-title">
        <h2>My Shopping Cart</h2>
        <p class="text-muted">Review your selected meals before checking out</p>
    </div>

    <?php if (!empty($cart_items)): ?>
        <div class="row g-4">
            <!-- Cart Items List -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover cart-table mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Dish</th>
                                    <th scope="col">Price</th>
                                    <th scope="col" class="text-center">Quantity</th>
                                    <th scope="col">Total</th>
                                    <th scope="col" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): 
                                    $item_total = floatval($item['price']) * intval($item['quantity']);
                                ?>
                                    <tr id="cart_row_<?php echo $item['cart_id']; ?>" class="cart-item-row">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo getFoodImageUrl($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-product-img">
                                                <div>
                                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <span class="badge bg-light text-muted border"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-semibold">$<?php echo number_format($item['price'], 2); ?></td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <div class="quantity-control">
                                                    <button type="button" class="quantity-btn" onclick="changeQty(<?php echo $item['cart_id']; ?>, -1)">-</button>
                                                    <input type="number" id="qty_input_<?php echo $item['cart_id']; ?>" class="quantity-input cart-qty-input" data-cart-id="<?php echo $item['cart_id']; ?>" value="<?php echo $item['quantity']; ?>" min="1" readonly>
                                                    <button type="button" class="quantity-btn" onclick="changeQty(<?php echo $item['cart_id']; ?>, 1)">+</button>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-bold text-dark" id="row_total_<?php echo $item['cart_id']; ?>">$<?php echo number_format($item_total, 2); ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-danger border-0 rounded-circle p-2" onclick="removeCartItem(<?php echo $item['cart_id']; ?>)" title="Remove item">
                                                <i class="fa-solid fa-trash-can fa-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-outline-custom rounded-pill px-4"><i class="fa-solid fa-arrow-left me-2"></i>Continue Shopping</a>
                </div>
            </div>

            <!-- Summary Card -->
            <div class="col-lg-4">
                <div class="summary-card shadow-sm">
                    <h5 class="fw-bold pb-3 border-bottom mb-4">Order Summary</h5>
                    
                    <div class="d-flex justify-content-between mb-3 text-muted">
                        <span>Cart Subtotal</span>
                        <span id="cart-subtotal" class="fw-semibold text-dark">$<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3 text-muted">
                        <span>Delivery Fee</span>
                        <span class="text-success fw-bold">FREE</span>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between mb-4">
                        <span class="fs-5 fw-bold">Grand Total</span>
                        <span id="cart-grandtotal" class="fs-5 fw-bold text-primary" style="color: var(--primary-color) !important;">$<?php echo number_format($cart_total, 2); ?></span>
                    </div>

                    <a href="checkout.php" class="btn btn-primary-custom w-100 py-3 rounded-pill fw-bold fs-6"><i class="fa-solid fa-credit-card me-2"></i>Proceed to Checkout</a>
                    
                    <div class="mt-4 p-3 bg-light rounded-3 text-center">
                        <span class="small text-muted"><i class="fa-solid fa-shield-halved text-success me-2"></i>100% Safe Payments & Hot Delivery</span>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Empty Cart -->
        <div class="row justify-content-center py-5">
            <div class="col-md-6 text-center">
                <div class="text-muted mb-4"><i class="fa-solid fa-cart-shopping fa-5x text-warning"></i></div>
                <h3 class="fw-bold">Your Cart is Empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added any delicious items to your cart yet. Browse our menu and find something tasty!</p>
                <a href="index.php" class="btn btn-primary-custom rounded-pill px-5 py-2fw-bold">Explore Food Menu</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
