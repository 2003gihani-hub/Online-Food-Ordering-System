<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = "Fast & Delicious Food Delivery";
require_once __DIR__ . '/includes/header.php';

// Fetch Active Categories
$categories = fetchAll("SELECT * FROM categories WHERE status = 'active' LIMIT 6");

// Fetch Featured/Best Selling Foods
$featured_foods = fetchAll("SELECT f.*, c.name as category_name FROM foods f 
                             JOIN categories c ON f.category_id = c.id 
                             WHERE f.status = 'active' LIMIT 8");
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3"><i class="fa-solid fa-bolt me-2"></i>SUPER FAST DELIVERY</span>
                <h1 class="hero-title mb-4">Hungry? Order from <span>FoodExpress</span> in Minutes!</h1>
                <p class="lead text-white-50 mb-5">Discover delicious foods from local favorites. Hot, fresh meals delivered directly to your doorstep with safe Cash on Delivery or secure online PayPal checkout.</p>
                
                <!-- Search bar in Hero -->
                <form action="search.php" method="GET" class="search-wrapper">
                    <input type="text" name="query" class="search-input-custom" placeholder="Search for burgers, pizza, desserts..." required>
                    <button type="submit" class="search-btn-custom" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>
            <div class="col-lg-6 text-center">
                <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&auto=format&fit=crop&q=80" alt="Delicious Food Express Platter" class="img-fluid rounded-4 shadow-lg border border-secondary" style="max-height: 450px; object-fit: cover; width: 100%;">
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-5 container">
    <div class="section-title center-title">
        <h2>Browse Food Categories</h2>
        <p class="text-muted">Explore our wide selection of delicious cuisines</p>
    </div>

    <div class="row g-4 justify-content-center">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="category-foods.php?id=<?php echo $cat['id']; ?>" class="category-card">
                        <div class="category-image-wrapper">
                            <img src="<?php echo getCategoryImageUrl($cat['image']); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>">
                        </div>
                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($cat['name']); ?></h6>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <p class="text-muted">No categories available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Featured Foods Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="section-title center-title">
            <h2>Best Selling Dishes</h2>
            <p class="text-muted">Most ordered items by our customers</p>
        </div>

        <div class="row g-4">
            <?php if (!empty($featured_foods)): ?>
                <?php foreach ($featured_foods as $food): ?>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="food-card">
                            <div class="food-card-img-wrapper">
                                <span class="food-badge"><i class="fa-solid fa-tag me-1 text-warning"></i><?php echo htmlspecialchars($food['category_name']); ?></span>
                                <?php if ($food['stock_quantity'] <= 0): ?>
                                    <span class="badge bg-danger text-white position-absolute" style="top: 15px; right: 15px; z-index: 2; font-weight: 600; font-size: 0.8rem; border-radius: 20px; padding: 6px 12px; box-shadow: 0 4px 8px rgba(220,53,69,0.3);"><i class="fa-solid fa-circle-xmark me-1"></i>Out of Stock</span>
                                <?php endif; ?>
                                <img src="<?php echo getFoodImageUrl($food['image']); ?>" class="food-card-img" alt="<?php echo htmlspecialchars($food['name']); ?>">
                            </div>
                            <div class="food-card-body">
                                <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($food['name']); ?></h5>
                                <p class="text-muted small flex-grow-1"><?php echo htmlspecialchars(substr($food['description'], 0, 85)) . (strlen($food['description']) > 85 ? '...' : ''); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="food-price">$<?php echo number_format($food['price'], 2); ?></span>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="food-details.php?id=<?php echo $food['id']; ?>" class="btn btn-sm btn-outline-custom rounded-pill">Details</a>
                                        <?php if ($food['stock_quantity'] > 0): ?>
                                            <button class="btn btn-sm btn-primary-custom add-to-cart-btn px-3 rounded-pill" data-food-id="<?php echo $food['id']; ?>"><i class="fa-solid fa-cart-plus me-1"></i>Add</button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary px-3 rounded-pill" disabled><i class="fa-solid fa-ban me-1"></i>Out of Stock</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No food items available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="search.php" class="btn btn-outline-custom rounded-pill px-4 py-2 fw-semibold">View All Food Items<i class="fa-solid fa-arrow-right ms-2"></i></a>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="py-5 container">
    <div class="row g-4 text-center mt-3">
        <div class="col-md-4">
            <div class="p-4">
                <div class="text-warning mb-3"><i class="fa-solid fa-motorcycle fa-3x"></i></div>
                <h5 class="fw-bold">Fastest Delivery</h5>
                <p class="text-muted">Our delivery partners are optimized to deliver your meal within 30-40 minutes at hot temperatures.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4">
                <div class="text-warning mb-3"><i class="fa-solid fa-hand-holding-dollar fa-3x"></i></div>
                <h5 class="fw-bold">Cash on Delivery</h5>
                <p class="text-muted">Pay comfortably in cash at your doorstep once you verify your ordered food items.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-4">
                <div class="text-warning mb-3"><i class="fa-brands fa-paypal fa-3x"></i></div>
                <h5 class="fw-bold">Secure Online Payment</h5>
                <p class="text-muted">Use PayPal integration to pay securely using your PayPal account or direct credit/debit card.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
