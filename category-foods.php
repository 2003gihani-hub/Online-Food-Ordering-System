<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$cat_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate Category
$category = fetchOne("SELECT * FROM categories WHERE id = ? AND status = 'active'", [$cat_id]);

if (!$category) {
    $_SESSION['error_message'] = "Category not found or inactive.";
    header("Location: categories.php");
    exit();
}

$page_title = htmlspecialchars($category['name']) . " Menu";
require_once __DIR__ . '/includes/header.php';

// Fetch all active foods for this category
$foods = fetchAll("SELECT * FROM foods WHERE category_id = ? AND status = 'active' ORDER BY name ASC", [$cat_id]);
?>

<div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item"><a href="categories.php" class="text-decoration-none text-muted">Categories</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page"><?php echo htmlspecialchars($category['name']); ?></li>
        </ol>
    </nav>

    <!-- Header Block -->
    <div class="d-flex align-items-center gap-4 mb-5 pb-4 border-bottom">
        <div class="rounded-4 overflow-hidden border shadow-sm" style="width: 100px; height: 100px; flex-shrink: 0;">
            <img src="<?php echo getCategoryImageUrl($category['image']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="w-100 h-100 object-fit-cover">
        </div>
        <div>
            <h1 class="mb-1 fw-bold"><?php echo htmlspecialchars($category['name']); ?> Specialities</h1>
            <p class="text-muted mb-0">Browse through our handpicked selections of fresh <?php echo htmlspecialchars(strtolower($category['name'])); ?> items.</p>
        </div>
    </div>

    <!-- Foods Grid -->
    <div class="row g-4">
        <?php if (!empty($foods)): ?>
            <?php foreach ($foods as $food): ?>
                <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                    <div class="food-card">
                        <div class="food-card-img-wrapper">
                            <?php if ($food['stock_quantity'] <= 0): ?>
                                <span class="badge bg-danger text-white position-absolute" style="top: 15px; right: 15px; z-index: 2; font-weight: 600; font-size: 0.8rem; border-radius: 20px; padding: 6px 12px; box-shadow: 0 4px 8px rgba(220,53,69,0.3);"><i class="fa-solid fa-circle-xmark me-1"></i>Out of Stock</span>
                            <?php endif; ?>
                            <img src="<?php echo getFoodImageUrl($food['image']); ?>" class="food-card-img" alt="<?php echo htmlspecialchars($food['name']); ?>">
                        </div>
                        <div class="food-card-body">
                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($food['name']); ?></h5>
                            <p class="text-muted small flex-grow-1"><?php echo htmlspecialchars(substr($food['description'], 0, 90)) . (strlen($food['description']) > 90 ? '...' : ''); ?></p>
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
            <div class="col-12 text-center py-5">
                <div class="text-muted mb-3"><i class="fa-solid fa-face-frown fa-3x"></i></div>
                <h4>No Dishes Found</h4>
                <p class="text-muted">We haven't added any dishes to this category yet. Check back soon!</p>
                <a href="categories.php" class="btn btn-primary-custom mt-3 rounded-pill px-4">Go to Categories</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
