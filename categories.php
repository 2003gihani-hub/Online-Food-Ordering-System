<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = "Food Categories";
require_once __DIR__ . '/includes/header.php';

// Fetch all active categories
$categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC");
?>

<div class="container my-5">
    <div class="section-title">
        <h2>All Categories</h2>
        <p class="text-muted">Choose your favorite style of cuisine</p>
    </div>

    <div class="row g-4">
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                    <a href="category-foods.php?id=<?php echo $cat['id']; ?>" class="category-card py-4">
                        <div class="category-image-wrapper mb-3" style="width: 100px; height: 100px;">
                            <img src="<?php echo getCategoryImageUrl($cat['image']); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>">
                        </div>
                        <h5 class="mb-0 fw-bold fs-6"><?php echo htmlspecialchars($cat['name']); ?></h5>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted mb-3"><i class="fa-solid fa-folder-open fa-3x"></i></div>
                <h4>No Categories Found</h4>
                <p class="text-muted">Please check back later, we are expanding our kitchen!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
