<?php
$page_title = "Manage Categories";
require_once __DIR__ . '/includes/header.php';

// Fetch all categories
$categories = fetchAll("SELECT * FROM categories ORDER BY id DESC");

// Check if we are in Edit Mode
$edit_cat = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_cat = fetchOne("SELECT * FROM categories WHERE id = ?", [$edit_id]);
}
?>

<div class="row g-4">
    <!-- Categories List Column -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="fw-bold"><i class="fa-solid fa-folder-open me-2 text-warning"></i>All Categories</span>
            </div>
            <div class="admin-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td>#<?php echo $cat['id']; ?></td>
                                        <td>
                                            <img src="<?php echo getCategoryImageUrl($cat['image']); ?>" alt="<?php echo htmlspecialchars($cat['name']); ?>" class="table-img">
                                        </td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></td>
                                        <td>
                                            <span class="badge status-badge <?php echo ($cat['status'] === 'active') ? 'status-delivered' : 'status-cancelled'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($cat['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="categories.php?edit=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-primary rounded-circle me-1" title="Edit">
                                                <i class="fa-solid fa-pencil"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $cat['id']; ?>)" class="btn btn-sm btn-outline-danger rounded-circle" title="Delete">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No categories found. Add your first category!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Form Column (Add / Edit) -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <span class="fw-bold">
                    <i class="fa-solid <?php echo $edit_cat ? 'fa-pen-to-square' : 'fa-plus'; ?> me-2 text-warning"></i>
                    <?php echo $edit_cat ? 'Edit Category' : 'Add New Category'; ?>
                </span>
                <?php if ($edit_cat): ?>
                    <a href="categories.php" class="btn btn-sm btn-link text-decoration-none">Cancel</a>
                <?php endif; ?>
            </div>
            <div class="admin-card-body">
                <form action="category-process.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $edit_cat ? 'edit' : 'add'; ?>">
                    <?php if ($edit_cat): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_cat['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="name" class="form-label-custom">Category Name *</label>
                        <input type="text" class="form-control form-control-custom" id="name" name="name" value="<?php echo $edit_cat ? htmlspecialchars($edit_cat['name']) : ''; ?>" placeholder="e.g. Italian Pizza" required>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label-custom">Category Image <?php echo $edit_cat ? '' : '*'; ?></label>
                        <input type="file" class="form-control form-control-custom" id="image" name="image" <?php echo $edit_cat ? '' : 'required'; ?> accept="image/*">
                        <?php if ($edit_cat && !empty($edit_cat['image'])): ?>
                            <div class="mt-2 text-muted small">Current: <?php echo htmlspecialchars($edit_cat['image']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="form-label-custom">Category Status</label>
                        <select class="form-select form-control-custom" id="status" name="status">
                            <option value="active" <?php echo ($edit_cat && $edit_cat['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_cat && $edit_cat['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-admin-accent w-100 py-2.5 rounded-3">
                        <i class="fa-solid fa-floppy-disk me-2"></i><?php echo $edit_cat ? 'Update Category' : 'Create Category'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Delete Category?',
        text: "This will delete the category and all associated food dishes permanently!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#1a1a2e',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'category-process.php?action=delete&id=' + id;
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
