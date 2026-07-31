<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$query = isset($_GET['query']) ? trim(sanitizeInput($_GET['query'])) : '';

$page_title = "Search Food Menu";
require_once __DIR__ . '/includes/header.php';

$foods = [];
if (!empty($query)) {
    // Search query template
    $search_term = "%" . $query . "%";
    
    // MySQLi Prepared Statement
    $foods = fetchAll(
        "SELECT f.*, c.name as category_name FROM foods f 
         JOIN categories c ON f.category_id = c.id 
         WHERE f.status = 'active' AND (f.name LIKE ? OR f.description LIKE ? OR c.name LIKE ?)",
        [$search_term, $search_term, $search_term]
    );
} else {
    // If search query is empty, load all active foods as a default browse page
    $foods = fetchAll(
        "SELECT f.*, c.name as category_name FROM foods f 
         JOIN categories c ON f.category_id = c.id 
         WHERE f.status = 'active' ORDER BY f.name ASC"
    );
}
?>

<div class="container my-5">
    <div class="row justify-content-center mb-5">
        <div class="col-lg-8 text-center">
            <h2 class="fw-bold mb-4">Search Our Menu</h2>
            <form action="search.php" method="GET" class="search-wrapper">
                <input type="text" name="query" class="search-input-custom text-center" placeholder="What food item are you craving today?" value="<?php echo htmlspecialchars($query); ?>">
                <button type="submit" class="search-btn-custom" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
    </div>

    <div class="section-title">
        <h3>
            <?php 
            if (!empty($query)) {
                echo "Search Results for '" . htmlspecialchars($query) . "' (" . count($foods) . " items)";
            } else {
                echo "All Dishes Available (" . count($foods) . " items)";
            }
            ?>
        </h3>
        <p class="text-muted">Pick your meal and get it delivered in minutes</p>
    </div>

    <!-- Foods Grid -->
    <div class="row g-4">
        <?php if (!empty($foods)): ?>
            <?php foreach ($foods as $food): ?>
                <div class="col-12 col-md-6 col-lg-4 col-xl-3">
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
                <div class="text-muted mb-3"><i class="fa-solid fa-pizza-slice fa-3x"></i></div>
                <h4>No Dishes Found Matching "<?php echo htmlspecialchars($query); ?>"</h4>
                <p class="text-muted">Try using different keywords, or check our category listings instead.</p>
                <a href="categories.php" class="btn btn-primary-custom mt-3 rounded-pill px-4">Browse Categories</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
