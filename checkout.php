<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

checkCustomerAuth();

$user_id = $_SESSION['user_id'];

// Get user's cart items
$cart_items = fetchAll(
    "SELECT c.quantity, f.id as food_id, f.name, f.price, f.stock_quantity, f.status 
     FROM cart c 
     JOIN foods f ON c.food_id = f.id 
     WHERE c.user_id = ?",
    [$user_id]
);

if (empty($cart_items)) {
    $_SESSION['error_message'] = "Your cart is empty. Please add items before checking out.";
    header("Location: cart.php");
    exit();
}

// Get user profile defaults for autofill
$user = fetchOne("SELECT name, phone, address FROM users WHERE id = ?", [$user_id]);

// Calculate totals
$grand_total = 0;
foreach ($cart_items as $item) {
    $grand_total += floatval($item['price']) * intval($item['quantity']);
}

$errors = [];

// Recheck stock during checkout page load
$stock_ok = true;
foreach ($cart_items as $item) {
    if ($item['status'] !== 'active' || intval($item['stock_quantity']) < intval($item['quantity'])) {
        $stock_ok = false;
        $errors[] = "This item is no longer available because it is Out of Stock.";
        break;
    }
}

// Handle Cash on Delivery (COD) form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order_cod'])) {
    $shipping_name = sanitizeInput($_POST['shipping_name'] ?? '');
    $shipping_phone = sanitizeInput($_POST['shipping_phone'] ?? '');
    $shipping_address = sanitizeInput($_POST['shipping_address'] ?? '');

    if (empty($shipping_name)) $errors[] = "Shipping name is required.";
    if (empty($shipping_phone)) $errors[] = "Shipping phone number is required.";
    if (empty($shipping_address)) $errors[] = "Shipping address is required.";

    if (empty($errors)) {
        // Begin Transaction
        $conn->begin_transaction();
        
        try {
            // Recheck stock and lock rows to prevent race conditions
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

            // Insert Order
            $order_query = "INSERT INTO orders (user_id, total_amount, status, payment_method, shipping_name, shipping_phone, shipping_address) 
                            VALUES (?, ?, 'Pending', 'COD', ?, ?, ?)";
            $stmt = $conn->prepare($order_query);
            $stmt->bind_param("idsss", $user_id, $grand_total, $shipping_name, $shipping_phone, $shipping_address);
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();

            // Insert Order Items
            $item_query = "INSERT INTO order_items (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)";
            $item_stmt = $conn->prepare($item_query);
            
            foreach ($cart_items as $item) {
                $item_stmt->bind_param("iiid", $order_id, $item['food_id'], $item['quantity'], $item['price']);
                $item_stmt->execute();
            }
            $item_stmt->close();

            // Deduct Stock
            $update_stock_query = "UPDATE foods SET stock_quantity = stock_quantity - ? WHERE id = ?";
            $update_stock_stmt = $conn->prepare($update_stock_query);
            foreach ($cart_items as $item) {
                $update_stock_stmt->bind_param("ii", $item['quantity'], $item['food_id']);
                $update_stock_stmt->execute();
            }
            $update_stock_stmt->close();

            // Clear Cart
            $clear_query = "DELETE FROM cart WHERE user_id = ?";
            $clear_stmt = $conn->prepare($clear_query);
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();

            // Commit transaction
            $conn->commit();

            $_SESSION['success_message'] = "Order placed successfully via Cash on Delivery!";
            header("Location: order-success.php?id=" . $order_id);
            exit();

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

$page_title = "Checkout";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item"><a href="cart.php" class="text-decoration-none text-muted">Cart</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Checkout</li>
        </ol>
    </nav>

    <div class="section-title">
        <h2>Checkout Details</h2>
        <p class="text-muted">Fill in your delivery details and choose payment method</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Delivery Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-lg-5 bg-white">
                <h4 class="fw-bold mb-4"><i class="fa-solid fa-truck-ramp-box me-2 text-warning"></i>Delivery Information</h4>
                
                <form action="checkout.php" method="POST" id="checkoutForm">
                    <div class="mb-3">
                        <label for="shipping_name" class="form-label fw-semibold">Receiver's Name *</label>
                        <input type="text" class="form-control rounded-3 py-2" id="shipping_name" name="shipping_name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="shipping_phone" class="form-label fw-semibold">Phone Number *</label>
                        <input type="tel" class="form-control rounded-3 py-2" id="shipping_phone" name="shipping_phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Receiver's contact number" required>
                    </div>

                    <div class="mb-4">
                        <label for="shipping_address" class="form-label fw-semibold">Complete Delivery Address *</label>
                        <textarea class="form-control rounded-3" id="shipping_address" name="shipping_address" rows="4" placeholder="House/Flat No, Street Name, Land Mark, City, Zipcode" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <!-- Hidden parameter for COD form identification -->
                    <input type="hidden" name="place_order_cod" value="1">
                    
                    <div id="cod-submit-btn-wrapper">
                        <?php if ($stock_ok): ?>
                            <button type="submit" class="btn btn-primary-custom w-100 py-3 rounded-pill fw-bold fs-6">
                                <i class="fa-solid fa-check me-2"></i>Place Order (Cash on Delivery)
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary w-100 py-3 rounded-pill fw-bold fs-6" disabled>
                                <i class="fa-solid fa-ban me-2"></i>Checkout Disabled (Out of Stock)
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Order Summary & Payments -->
        <div class="col-lg-5">
            <!-- Items summary card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold pb-2 border-bottom mb-3">Order Summary</h5>
                <div class="mb-3">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted"><?php echo htmlspecialchars($item['name']); ?> <strong class="text-dark">x <?php echo $item['quantity']; ?></strong></span>
                            <span class="fw-semibold">$<?php echo number_format(floatval($item['price']) * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <hr class="text-muted">
                
                <div class="d-flex justify-content-between pt-2">
                    <span class="fs-5 fw-bold text-dark">Grand Total</span>
                    <span class="fs-5 fw-bold text-primary" id="total_to_pay" style="color: var(--primary-color) !important;">$<?php echo number_format($grand_total, 2); ?></span>
                </div>
            </div>

            <!-- Payment Method Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold pb-2 border-bottom mb-4">Payment Method</h5>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment_method" id="pay_cod" value="COD" <?php echo $stock_ok ? 'checked' : 'disabled'; ?>>
                    <label class="form-check-label fw-bold <?php echo !$stock_ok ? 'text-muted' : ''; ?>" for="pay_cod">
                        <i class="fa-solid fa-hand-holding-dollar me-2 <?php echo $stock_ok ? 'text-success' : 'text-muted'; ?>"></i>Cash on Delivery (COD)
                    </label>
                    <p class="text-muted small ms-4">Pay in cash when our delivery driver arrives at your door.</p>
                </div>
                
                <div class="form-check mb-4">
                    <input class="form-check-input" type="radio" name="payment_method" id="pay_online" value="PayPal" <?php echo $stock_ok ? '' : 'disabled'; ?>>
                    <label class="form-check-label fw-bold <?php echo !$stock_ok ? 'text-muted' : ''; ?>" for="pay_online">
                        <i class="fa-brands fa-paypal me-2 <?php echo $stock_ok ? 'text-primary' : 'text-muted'; ?>"></i>Pay Online (PayPal / Card)
                    </label>
                    <p class="text-muted small ms-4">Pay online instantly using PayPal account or credit/debit card.</p>
                </div>

                <!-- PayPal Button Container (Hidden by default, shown when PayPal is selected) -->
                <div id="paypal-button-container" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Load the PayPal JavaScript SDK -->
<script src="https://www.paypal.com/sdk/js?client-id=sb&currency=USD"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const payCod = document.getElementById("pay_cod");
    const payOnline = document.getElementById("pay_online");
    const codBtnWrapper = document.getElementById("cod-submit-btn-wrapper");
    const paypalBtnContainer = document.getElementById("paypal-button-container");
    const totalToPay = <?php echo $grand_total; ?>;

    // Toggle Payment Forms
    payCod.addEventListener("change", function() {
        if(this.checked) {
            codBtnWrapper.style.display = "block";
            paypalBtnContainer.style.display = "none";
        }
    });

    payOnline.addEventListener("change", function() {
        if(this.checked) {
            codBtnWrapper.style.display = "none";
            paypalBtnContainer.style.display = "block";
        }
    });

    // Render PayPal Button
    paypal.Buttons({
        createOrder: function(data, actions) {
            // First check stock via AJAX check-stock endpoint
            return fetch("check-stock.php")
                .then(response => response.json())
                .then(res => {
                    if (res.status !== "success") {
                        Swal.fire({
                            title: 'Stock Error',
                            text: res.message || 'This item is no longer available because it is Out of Stock.',
                            icon: 'error',
                            confirmButtonColor: '#ff5e3a'
                        }).then(() => {
                            location.reload();
                        });
                        return Promise.reject(new Error("Out of stock"));
                    }
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: totalToPay.toFixed(2)
                            }
                        }]
                    });
                });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                // Get form shipping info
                const sName = document.getElementById("shipping_name").value;
                const sPhone = document.getElementById("shipping_phone").value;
                const sAddress = document.getElementById("shipping_address").value;

                if (!sName || !sPhone || !sAddress) {
                    Swal.fire({
                        title: 'Incomplete Details',
                        text: 'Please fill out all delivery information on the left before completing payment.',
                        icon: 'warning',
                        confirmButtonColor: '#ff5e3a'
                    });
                    return;
                }

                // Send payment data to server
                const payFormData = new FormData();
                payFormData.append("transaction_id", details.id);
                payFormData.append("amount", totalToPay.toFixed(2));
                payFormData.append("shipping_name", sName);
                payFormData.append("shipping_phone", sPhone);
                payFormData.append("shipping_address", sAddress);

                fetch("paypal-payment.php", {
                    method: "POST",
                    body: payFormData
                })
                .then(response => response.json())
                .then(resData => {
                    if (resData.status === "success") {
                        Swal.fire({
                            title: 'Payment Confirmed!',
                            text: 'Your order was successfully paid and created.',
                            icon: 'success',
                            confirmButtonColor: '#ff5e3a'
                        }).then(() => {
                            window.location.href = "order-success.php?id=" + resData.order_id;
                        });
                    } else {
                        Swal.fire({
                            title: 'Order Processing Failed',
                            text: resData.message || 'Error creating order records.',
                            icon: 'error',
                            confirmButtonColor: '#ff5e3a'
                        });
                    }
                })
                .catch(err => {
                    console.error("Payment Record Error:", err);
                    Swal.fire({
                        title: 'Connection Error',
                        text: 'Failed to notify order processing server.',
                        icon: 'error',
                        confirmButtonColor: '#ff5e3a'
                    });
                });
            });
        },
        onError: function(err) {
            console.error("PayPal Error:", err);
            Swal.fire({
                title: 'Payment Error',
                text: 'There was an issue processing your transaction with PayPal.',
                icon: 'error',
                confirmButtonColor: '#ff5e3a'
            });
        }
    }).render('#paypal-button-container');
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
