<?php
$page_title = "Manage Foods";
require_once __DIR__ . '/includes/header.php';

// Fetch all food items with category names
$foods = fetchAll(
    "SELECT f.*, c.name as category_name FROM foods f 
     LEFT JOIN categories c ON f.category_id = c.id 
     ORDER BY f.id DESC"
);

// Fetch categories for dropdown
$categories = fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");

// Check if in Edit Mode
$edit_food = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_food = fetchOne("SELECT * FROM foods WHERE id = ?", [$edit_id]);
}
?>

<div class="row g-4">
    <!-- Food List Column -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="fw-bold"><i class="fa-solid fa-bowl-food me-2 text-warning"></i>All Food Items</span>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($foods)): ?>
                                <?php foreach ($foods as $fd): ?>
                                    <tr>
                                        <td>#<?php echo $fd['id']; ?></td>
                                        <td>
                                            <img src="<?php echo getFoodImageUrl($fd['image']); ?>" alt="<?php echo htmlspecialchars($fd['name']); ?>" class="table-img">
                                        </td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($fd['name']); ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($fd['category_name'] ?? 'Uncategorized'); ?></span>
                                        </td>
                                        <td class="fw-bold text-primary">$<?php echo number_format($fd['price'], 2); ?></td>
                                        <td>
                                            <?php if ($fd['stock_quantity'] == 0): ?>
                                                <span class="status-badge status-cancelled">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i>0 (Out of Stock)
                                                </span>
                                            <?php elseif ($fd['stock_quantity'] <= 10): ?>
                                                <span class="status-badge status-pending">
                                                    <i class="fa-solid fa-triangle-exclamation me-1"></i><?php echo $fd['stock_quantity']; ?> (Low Stock)
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-delivered">
                                                    <i class="fa-solid fa-circle-check me-1"></i><?php echo $fd['stock_quantity']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge status-badge <?php echo ($fd['status'] === 'active') ? 'status-delivered' : 'status-cancelled'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($fd['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="foods.php?edit=<?php echo $fd['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle me-1" title="Edit">
                                                <i class="fa-solid fa-pencil"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $fd['id']; ?>)" class="btn btn-sm btn-outline-danger rounded-circle" title="Delete">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No food items found. Add your first dish!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Food Form Column (Add / Edit) -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="fw-bold">
                    <i class="fa-solid <?php echo $edit_food ? 'fa-pen-to-square' : 'fa-plus'; ?> me-2 text-warning"></i>
                    <?php echo $edit_food ? 'Edit Food Item' : 'Add Food Item'; ?>
                </span>
                <?php if ($edit_food): ?>
                    <a href="foods.php" class="btn btn-sm btn-link text-decoration-none">Cancel</a>
                <?php endif; ?>
            </div>
            <div class="admin-card-body">
                <form action="food-process.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $edit_food ? 'edit' : 'add'; ?>">
                    <?php if ($edit_food): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_food['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="name" class="form-label-custom">Food Item Name *</label>
                        <input type="text" class="form-control form-control-custom" id="name" name="name" value="<?php echo $edit_food ? htmlspecialchars($edit_food['name']) : ''; ?>" placeholder="e.g. Classic Margherita Pizza" required>
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label-custom">Food Category *</label>
                        <select class="form-select form-control-custom" id="category_id" name="category_id" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_food && $edit_food['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="price" class="form-label-custom">Unit Price ($) *</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-custom" id="price" name="price" value="<?php echo $edit_food ? htmlspecialchars($edit_food['price']) : ''; ?>" placeholder="e.g. 12.99" required>
                    </div>

                    <div class="mb-3">
                        <label for="stock_quantity" class="form-label-custom">Stock Quantity *</label>
                        <input type="number" min="0" class="form-control form-control-custom" id="stock_quantity" name="stock_quantity" value="<?php echo $edit_food ? htmlspecialchars($edit_food['stock_quantity']) : '50'; ?>" placeholder="e.g. 50" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label-custom">Description</label>
                        <textarea class="form-control form-control-custom" id="description" name="description" rows="3" placeholder="Describe the ingredients, size, toppings..."><?php echo $edit_food ? htmlspecialchars($edit_food['description']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label-custom">Food Image <?php echo $edit_food ? '' : '*'; ?></label>
                        <input type="file" class="form-control form-control-custom" id="image" name="image" <?php echo $edit_food ? '' : 'required'; ?> accept="image/*">
                        <?php if ($edit_food && !empty($edit_food['image'])): ?>
                            <div class="mt-2 text-muted small">Current: <?php echo htmlspecialchars($edit_food['image']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="form-label-custom">Status</label>
                        <select class="form-select form-control-custom" id="status" name="status">
                            <option value="active" <?php echo ($edit_food && $edit_food['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_food && $edit_food['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-admin-accent w-100 py-2.5 rounded-3">
                        <i class="fa-solid fa-floppy-disk me-2"></i><?php echo $edit_food ? 'Update Dish' : 'Create Dish'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Delete Food Item?',
        text: "Are you sure you want to delete this dish permanently?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#1a1a2e',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'food-process.php?action=delete&id=' + id;
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
