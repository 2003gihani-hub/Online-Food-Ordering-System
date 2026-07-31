<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$food_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch food details with category name
$food = fetchOne("SELECT f.*, c.name as category_name, c.id as cat_id FROM foods f 
                   JOIN categories c ON f.category_id = c.id 
                   WHERE f.id = ? AND f.status = 'active'", [$food_id]);

if (!$food) {
    $_SESSION['error_message'] = "Dish not found or is currently unavailable.";
    header("Location: index.php");
    exit();
}

$page_title = htmlspecialchars($food['name']);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item"><a href="categories.php" class="text-decoration-none text-muted">Categories</a></li>
            <li class="breadcrumb-item"><a href="category-foods.php?id=<?php echo $food['cat_id']; ?>" class="text-decoration-none text-muted"><?php echo htmlspecialchars($food['category_name']); ?></a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?php echo htmlspecialchars($food['name']); ?></li>
        </ol>
    </nav>

    <!-- Details Card -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-5">
        <div class="row g-0">
            <div class="col-md-6" style="background-color: #f7f7f7;">
                <div class="position-relative h-100 min-vh-50" style="min-height: 400px;">
                    <img src="<?php echo getFoodImageUrl($food['image']); ?>" alt="<?php echo htmlspecialchars($food['name']); ?>" class="w-100 h-100 object-fit-cover position-absolute">
                    <span class="badge bg-dark text-white px-3 py-2 rounded-pill position-absolute" style="top: 20px; left: 20px; font-weight: 600; font-size: 0.95rem; backdrop-filter: blur(5px); background-color: rgba(26,26,46,0.8) !important;">
                        <i class="fa-solid fa-utensils me-2 text-warning"></i><?php echo htmlspecialchars($food['category_name']); ?>
                    </span>
                    <?php if ($food['stock_quantity'] <= 0): ?>
                        <span class="badge bg-danger text-white px-3 py-2 rounded-pill position-absolute" style="top: 20px; right: 20px; font-weight: 600; font-size: 0.95rem; box-shadow: 0 4px 8px rgba(220,53,69,0.3);">
                            <i class="fa-solid fa-circle-xmark me-2"></i>Out of Stock
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-6 d-flex flex-column justify-content-center p-4 p-lg-5">
                <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($food['name']); ?></h1>
                
                <!-- Ratings Placeholder -->
                <div class="d-flex align-items-center gap-2 mb-4">
                    <div class="text-warning">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star-half-stroke"></i>
                    </div>
                    <span class="text-muted small fw-semibold">(4.5/5 Customer Rating)</span>
                </div>

                <h3 class="text-primary fw-bold mb-4" style="color: var(--primary-color) !important;">$<?php echo number_format($food['price'], 2); ?></h3>
                
                <h5 class="fw-bold mb-2">Description</h5>
                <p class="text-muted mb-4 lead fs-6" style="line-height: 1.7;"><?php echo nl2br(htmlspecialchars($food['description'])); ?></p>
                
                <hr class="my-4 text-muted">

                <!-- Order Controls -->
                <?php if ($food['stock_quantity'] > 0): ?>
                    <div class="row align-items-center g-3">
                        <div class="col-auto">
                            <label for="qty_<?php echo $food['id']; ?>" class="form-label fw-bold mb-0">Quantity:</label>
                        </div>
                        <div class="col-auto">
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn" onclick="let input=document.getElementById('qty_<?php echo $food['id']; ?>'); let v=parseInt(input.value); if(v>1) input.value=v-1;">-</button>
                                <input type="number" id="qty_<?php echo $food['id']; ?>" class="quantity-input" value="1" min="1" readonly>
                                <button type="button" class="quantity-btn" onclick="let input=document.getElementById('qty_<?php echo $food['id']; ?>'); let v=parseInt(input.value); if(v < <?php echo $food['stock_quantity']; ?>) input.value=v+1;">+</button>
                            </div>
                            <div class="small text-muted mt-1 ps-1">Available: <?php echo $food['stock_quantity']; ?></div>
                        </div>
                        <div class="col">
                            <button class="btn btn-primary-custom add-to-cart-btn w-100 rounded-pill py-2" data-food-id="<?php echo $food['id']; ?>">
                                <i class="fa-solid fa-cart-plus me-2"></i>Add To Cart
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger border-0 shadow-sm rounded-3 py-3 mb-0" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>Sorry, this item is currently Out of Stock.
                    </div>
                    <div class="row align-items-center g-3 mt-3">
                        <div class="col">
                            <button class="btn btn-secondary w-100 rounded-pill py-2" disabled>
                                <i class="fa-solid fa-ban me-2"></i>Out of Stock
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Features list -->
                <div class="row mt-4 pt-3 text-center border-top g-3">
                    <div class="col-4">
                        <div class="text-success fs-4 mb-1"><i class="fa-solid fa-shield-halved"></i></div>
                        <span class="small fw-semibold text-muted">Hygiene Certified</span>
                    </div>
                    <div class="col-4">
                        <div class="text-danger fs-4 mb-1"><i class="fa-solid fa-bowl-hot"></i></div>
                        <span class="small fw-semibold text-muted">Freshly Prepared</span>
                    </div>
                    <div class="col-4">
                        <div class="text-info fs-4 mb-1"><i class="fa-solid fa-truck-fast"></i></div>
                        <span class="small fw-semibold text-muted">Eco-friendly Box</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
