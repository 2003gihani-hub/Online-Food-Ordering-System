<?php
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// Secure Admin area
checkAdminAuth();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    $_SESSION['error_message'] = "Invalid access to food operations.";
    header("Location: foods.php");
    exit();
}

$upload_dir = dirname(dirname(__DIR__)) . '/FoodExpress/assets/uploads/foods/';
// Auto-create folder
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

switch ($action) {
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = sanitizeInput($_POST['name'] ?? '');
            $category_id = intval($_POST['category_id'] ?? 0);
            $price = floatval($_POST['price'] ?? 0.00);
            $description = sanitizeInput($_POST['description'] ?? '');
            $status = sanitizeInput($_POST['status'] ?? 'active');
            $stock_quantity = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 50;

            if (empty($name) || $category_id <= 0 || $price <= 0 || $stock_quantity < 0) {
                $_SESSION['error_message'] = "Name, Category, valid Price, and non-negative Stock are required.";
                header("Location: foods.php");
                exit();
            }

            // Image file upload
            $image_name = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['image']['tmp_name'];
                $original_name = $_FILES['image']['name'];
                $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($file_ext, $allowed)) {
                    $image_name = 'food_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                    move_uploaded_file($file_tmp, $upload_dir . $image_name);
                } else {
                    $_SESSION['error_message'] = "Invalid image type. Allowed: JPG, PNG, GIF, WEBP.";
                    header("Location: foods.php");
                    exit();
                }
            } else {
                $_SESSION['error_message'] = "Dish image is required.";
                header("Location: foods.php");
                exit();
            }

            // Duplicate Check
            $duplicate = fetchOne("SELECT id FROM foods WHERE name = ?", [$name]);
            if ($duplicate) {
                $_SESSION['error_message'] = "A food dish with this name already exists.";
                if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
                    @unlink($upload_dir . $image_name);
                }
                header("Location: foods.php");
                exit();
            }

            // Insert into DB
            $res = executeUpdate(
                "INSERT INTO foods (category_id, name, description, price, stock_quantity, image, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$category_id, $name, $description, $price, $stock_quantity, $image_name, $status]
            );

            if ($res['affected_rows'] > 0) {
                $_SESSION['success_message'] = "Dish added successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to create food item.";
            }
        }
        break;

    case 'edit':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $category_id = intval($_POST['category_id'] ?? 0);
            $price = floatval($_POST['price'] ?? 0.00);
            $description = sanitizeInput($_POST['description'] ?? '');
            $status = sanitizeInput($_POST['status'] ?? 'active');
            $stock_quantity = isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : 50;

            if ($id <= 0 || empty($name) || $category_id <= 0 || $price <= 0 || $stock_quantity < 0) {
                $_SESSION['error_message'] = "All fields are required and stock cannot be negative.";
                header("Location: foods.php?edit=" . $id);
                exit();
            }

            $food = fetchOne("SELECT * FROM foods WHERE id = ?", [$id]);
            if (!$food) {
                $_SESSION['error_message'] = "Dish not found.";
                header("Location: foods.php");
                exit();
            }

            // Duplicate Check (on other items)
            $duplicate = fetchOne("SELECT id FROM foods WHERE name = ? AND id != ?", [$name, $id]);
            if ($duplicate) {
                $_SESSION['error_message'] = "Another dish with this name already exists.";
                header("Location: foods.php?edit=" . $id);
                exit();
            }

            $image_name = $food['image'];
            // If new image uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['image']['tmp_name'];
                $original_name = $_FILES['image']['name'];
                $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($file_ext, $allowed)) {
                    $new_image_name = 'food_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                    if (move_uploaded_file($file_tmp, $upload_dir . $new_image_name)) {
                        // Remove old
                        if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
                            @unlink($upload_dir . $image_name);
                        }
                        $image_name = $new_image_name;
                    }
                } else {
                    $_SESSION['error_message'] = "Invalid file type.";
                    header("Location: foods.php?edit=" . $id);
                    exit();
                }
            }

            // Update database row
            $res = executeUpdate(
                "UPDATE foods SET category_id = ?, name = ?, description = ?, price = ?, stock_quantity = ?, image = ?, status = ? WHERE id = ?",
                [$category_id, $name, $description, $price, $stock_quantity, $image_name, $status, $id]
            );

            if ($res['affected_rows'] >= 0) {
                $_SESSION['success_message'] = "Dish updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update food item.";
            }
        }
        break;

    case 'delete':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            $food = fetchOne("SELECT * FROM foods WHERE id = ?", [$id]);
            if ($food) {
                $image_name = $food['image'];
                if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
                    @unlink($upload_dir . $image_name);
                }

                $res = executeUpdate("DELETE FROM foods WHERE id = ?", [$id]);
                if ($res['affected_rows'] > 0) {
                    $_SESSION['success_message'] = "Dish deleted successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to delete dish.";
                }
            } else {
                $_SESSION['error_message'] = "Dish not found.";
            }
        }
        break;
}

header("Location: foods.php");
exit();
?>
