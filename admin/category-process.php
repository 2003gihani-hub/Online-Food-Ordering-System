<?php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Secure Admin area
checkAdminAuth();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    $_SESSION['error_message'] = "Invalid access to category operations.";
    header("Location: categories.php");
    exit();
}

$upload_dir = dirname(dirname(__DIR__)) . '/FoodExpress/assets/uploads/categories/';
// Auto-create category uploads directory if not exists
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = sanitizeInput($_POST['name'] ?? '');
            $status = sanitizeInput($_POST['status'] ?? 'active');
            
            if (empty($name)) {
                $_SESSION['error_message'] = "Category name is required.";
                header("Location: categories.php");
                exit();
            }

            // Handle Image Upload
            $image_name = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['image']['tmp_name'];
                $original_name = $_FILES['image']['name'];
                $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                
                // Allow only images
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($file_ext, $allowed)) {
                    // Safe unique filename
                    $image_name = 'cat_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                    move_uploaded_file($file_tmp, $upload_dir . $image_name);
                } else {
                    $_SESSION['error_message'] = "Invalid image extension. Allowed: JPG, PNG, GIF, WEBP.";
                    header("Location: categories.php");
                    exit();
                }
            } else {
                $_SESSION['error_message'] = "Category image is required.";
                header("Location: categories.php");
                exit();
            }

            // Check duplicate name
            $duplicate = fetchOne("SELECT id FROM categories WHERE name = ?", [$name]);
            if ($duplicate) {
                $_SESSION['error_message'] = "Category name already exists.";
                // Clean uploaded file if duplicate
                if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
                    unlink($upload_dir . $image_name);
                }
                header("Location: categories.php");
                exit();
            }

            // Insert into DB
            $res = executeUpdate(
                "INSERT INTO categories (name, image, status) VALUES (?, ?, ?)",
                [$name, $image_name, $status]
            );

            if ($res['affected_rows'] > 0) {
                $_SESSION['success_message'] = "Category added successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to add category.";
            }
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $status = sanitizeInput($_POST['status'] ?? 'active');

            if ($id <= 0 || empty($name)) {
                $_SESSION['error_message'] = "All fields are required.";
                header("Location: categories.php");
                exit();
            }

            // Fetch current category record
            $category = fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
            if (!$category) {
                $_SESSION['error_message'] = "Category not found.";
                header("Location: categories.php");
                exit();
            }

            // Check duplicate name on OTHER categories
            $duplicate = fetchOne("SELECT id FROM categories WHERE name = ? AND id != ?", [$name, $id]);
            if ($duplicate) {
                $_SESSION['error_message'] = "Another category with this name already exists.";
                header("Location: categories.php");
                exit();
            }

            $image_name = $category['image'];
            // If new image uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['image']['tmp_name'];
                $original_name = $_FILES['image']['name'];
                $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($file_ext, $allowed)) {
                    $new_image_name = 'cat_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                    if (move_uploaded_file($file_tmp, $upload_dir . $new_image_name)) {
                        // Delete old file
                        if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
                            @unlink($upload_dir . $image_name);
                        }
                        $image_name = $new_image_name;
                    }
                } else {
                    $_SESSION['error_message'] = "Invalid file type.";
                    header("Location: categories.php?edit=" . $id);
                    exit();
                }
            }

            // Update database
            $res = executeUpdate(
                "UPDATE categories SET name = ?, image = ?, status = ? WHERE id = ?",
                [$name, $image_name, $status, $id]
            );

            if ($res['affected_rows'] >= 0) {
                $_SESSION['success_message'] = "Category updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update category.";
            }
        }
        break;

    case 'delete':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            $category = fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
            if ($category) {
                // Delete image from folder
                $image_name = $category['image'];
                if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
                    @unlink($upload_dir . $image_name);
                }
                
                // Delete DB record (Foreign Keys will cascade delete foods!)
                $res = executeUpdate("DELETE FROM categories WHERE id = ?", [$id]);
                
                if ($res['affected_rows'] > 0) {
                    $_SESSION['success_message'] = "Category and associated foods deleted successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to delete category.";
                }
            } else {
                $_SESSION['error_message'] = "Category not found.";
            }
        }
        break;
}

header("Location: categories.php");
exit();
?>
