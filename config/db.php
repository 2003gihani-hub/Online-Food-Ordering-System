<?php
// Prevent direct access to this configuration file
if (count(get_included_files()) === 1) {
    exit("Direct access not permitted.");
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'foodexpress';

// Enable error reporting for MySQLi
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error . ". Please make sure MySQL is running in XAMPP.");
}

// Try to select database, if not exists, try to create it or prompt
if (!$conn->select_db($db_name)) {
    // If it doesn't exist, let's try to create it
    if ($conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`")) {
        $conn->select_db($db_name);
        // Load schema if database was just created
        $sql_file = dirname(__DIR__) . '/database/database.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            // Multi-query execution for database setup
            if ($conn->multi_query($sql)) {
                do {
                    // Flush multi_queries
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->next_result());
            }
        }
    } else {
        die("Database '$db_name' could not be found or created. Please run the SQL file manually.");
    }
}

// Automatically check and add stock_quantity column if it doesn't exist
$checkColumn = $conn->query("SHOW COLUMNS FROM `foods` LIKE 'stock_quantity'");
if ($checkColumn && $checkColumn->num_rows === 0) {
    $conn->query("ALTER TABLE `foods` ADD COLUMN `stock_quantity` INT NOT NULL DEFAULT 50");
}

// After loading database.sql, ensure admin credentials are correct
$checkAdmin = $conn->query("SELECT * FROM admins WHERE username = 'admin'");
if($checkAdmin && $checkAdmin->num_rows > 0) {
    $admin = $checkAdmin->fetch_assoc();
    // If password is not 'admin123', update it
    if(!password_verify('admin123', $admin['password'])) {
        $newHash = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("UPDATE admins SET password = '$newHash' WHERE username = 'admin'");
        echo "<!-- Admin password reset to 'admin123' -->";
    }
} else {
    // If no admin exists, create one
    $newHash = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO admins (username, password, email) VALUES ('admin', '$newHash', 'admin@foodexpress.com')");
}

$conn->set_charset("utf8mb4");

/**
 * Execute a prepared query
 * @param string $query SQL Query string
 * @param array $params parameters
 * @param string $types type definition string (optional)
 * @return mysqli_stmt
 */
function executeQuery($query, $params = [], $types = "") {
    global $conn;
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error . " | Query: " . $query);
    }
    
    if (!empty($params)) {
        if (empty($types)) {
            $types = "";
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= "i";
                } elseif (is_double($param)) {
                    $types .= "d";
                } else {
                    $types .= "s";
                }
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        die("Query execution failed: " . $stmt->error);
    }
    
    return $stmt;
}

/**
 * Fetch all matching rows
 */
function fetchAll($query, $params = [], $types = "") {
    $stmt = executeQuery($query, $params, $types);
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

/**
 * Fetch a single row
 */
function fetchOne($query, $params = [], $types = "") {
    $stmt = executeQuery($query, $params, $types);
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

/**
 * Run Insert/Update/Delete and return details
 */
function executeUpdate($query, $params = [], $types = "") {
    global $conn;
    $stmt = executeQuery($query, $params, $types);
    $affected = $stmt->affected_rows;
    $insert_id = $conn->insert_id;
    $stmt->close();
    return ['affected_rows' => $affected, 'insert_id' => $insert_id];
}

/**
 * Clean and sanitize user input
 */
function sanitizeInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Get Category Image URL with Unsplash fallback
 */
function getCategoryImageUrl($image) {
    $localPath = dirname(__DIR__) . '/assets/uploads/categories/' . $image;
    if (!empty($image) && file_exists($localPath)) {
        return '/FoodExpress/assets/uploads/categories/' . $image;
    }
    
    // Aesthetic fallback images based on standard category names
    $imgName = strtolower(trim($image));
    if (strpos($imgName, 'pizza') !== false) {
        return 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=400&auto=format&fit=crop&q=60';
    } elseif (strpos($imgName, 'burger') !== false) {
        return 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&auto=format&fit=crop&q=60';
    } elseif (strpos($imgName, 'pasta') !== false) {
        return 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=400&auto=format&fit=crop&q=60';
    } elseif (strpos($imgName, 'beverage') !== false || strpos($imgName, 'drink') !== false) {
        return 'https://images.unsplash.com/photo-1497534446932-c925b458314e?w=400&auto=format&fit=crop&q=60';
    } elseif (strpos($imgName, 'dessert') !== false || strpos($imgName, 'sweet') !== false) {
        return 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=400&auto=format&fit=crop&q=60';
    }
    return 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=400&auto=format&fit=crop&q=60';
}

/**
 * Get Food Image URL with Unsplash fallback
 */
function getFoodImageUrl($image) {
    $localPath = dirname(__DIR__) . '/assets/uploads/foods/' . $image;
    if (!empty($image) && file_exists($localPath)) {
        return '/FoodExpress/assets/uploads/foods/' . $image;
    }
    
    // Aesthetic fallback images based on standard food names
    $name = strtolower(trim($image));
    if (strpos($name, 'margherita') !== false) {
        return 'https://images.unsplash.com/photo-1604068549290-dea0e4a305ca?w=500&auto=format&fit=crop&q=60';
    } elseif (strpos($name, 'pepperoni') !== false) {
        return 'https://images.unsplash.com/photo-1628840042765-356cda07504e?w=500&auto=format&fit=crop&q=60';
    } elseif (strpos($name, 'vegetarian') !== false || strpos($name, 'veg') !== false) {
        return 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=500&auto=format&fit=crop&q=60';
    } elseif (strpos($name, 'cheese') !== false && strpos($name, 'burger') !== false) {
        return 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=500&auto=format&fit=crop&q=60';
    } elseif (strpos($name, 'bacon') !== false) {
        return 'https://images.unsplash.com/photo-1553979459-d2229ba7433b?w=500&auto=format&fit=crop&q=60';
    } elseif (strpos($name, 'chicken') !== false && strpos($name, 'burger') !== false) {
        return 'https://images.unsplash.com/photo-1625813506062-0aeb1d7a094b?w=500&auto=format&fit=crop&q=60';
    } elseif (strpos($name, 'alfredo') !== false) {
        return 'https://images.unsplash.com/photo-1645112411341-6c4fd023714a?w=500&auto=format&fit=crop&q=60';
    } elseif (strpos($name, 'bolognese') !== false || strpos($name, 'spaghetti') !== false) {
        return 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?w=500&auto=format&fit=crop&q=60';
    } elseif (strpos($name, 'latte') !== false || strpos($name, 'coffee') !== false) {
        return 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?w=500&auto=format&fit=crop&q=60';
    } elseif (strpos($name, 'orange') !== false || strpos($name, 'juice') !== false) {
        return 'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?w=500&auto=format&fit=crop&q=60';
    } elseif (strpos($name, 'lava') !== false || strpos($name, 'cake') !== false) {
        return 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?w=500&auto=format&fit=crop&q=60';
    } elseif (strpos($name, 'cheesecake') !== false) {
        return 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=500&auto=format&fit=crop&q=60';
    }
    
    return 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&auto=format&fit=crop&q=60';
}
?>
