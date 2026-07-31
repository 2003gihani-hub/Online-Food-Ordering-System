-- FoodExpress Database SQL Script
-- Generates all tables, relationships, indexes, and populates sample data.

CREATE DATABASE IF NOT EXISTS foodexpress;
USE foodexpress;

-- --------------------------------------------------------
-- Table: admins
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    status ENUM('active', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    image VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: foods
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 50,
    image VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: cart
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: orders
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Preparing', 'Out For Delivery', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    payment_method ENUM('COD', 'PayPal') NOT NULL,
    shipping_name VARCHAR(100) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: order_items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_id INT DEFAULT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: payments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_status VARCHAR(50) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Indexes for Performance Optimization
-- --------------------------------------------------------
CREATE INDEX idx_foods_name ON foods(name);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_payments_order_id ON payments(order_id);

-- --------------------------------------------------------
-- Seed Data Insertion
-- --------------------------------------------------------

-- Insert Admin User (Username: admin, Password: admin123, email: admin@foodexpress.com)
-- Password hash generated using password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO admins (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@foodexpress.com');

-- Insert Customers (Password: password123 for both)
-- Password hash generated using password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO users (name, email, password, phone, address, status) VALUES
('John Doe', 'john@example.com', '$2y$10$n4.b2M.n7iF6KjXG2aTf0O/N/qI9Rpt156tq3e6pX5xYjP/cQGf.u', '1234567890', '123 Main St, New York, NY 10001', 'active'),
('Jane Smith', 'jane@example.com', '$2y$10$n4.b2M.n7iF6KjXG2aTf0O/N/qI9Rpt156tq3e6pX5xYjP/cQGf.u', '9876543210', '456 Oak Ave, Los Angeles, CA 90001', 'active'),
('Blocked User', 'blocked@example.com', '$2y$10$n4.b2M.n7iF6KjXG2aTf0O/N/qI9Rpt156tq3e6pX5xYjP/cQGf.u', '5555555555', '789 Pine Rd, Chicago, IL 60601', 'blocked');

-- Insert Categories
INSERT INTO categories (name, image, status) VALUES
('Pizza', 'pizza.jpg', 'active'),
('Burgers', 'burgers.jpg', 'active'),
('Pasta', 'pasta.jpg', 'active'),
('Beverages', 'beverages.jpg', 'active'),
('Desserts', 'desserts.jpg', 'active');

-- Insert Foods
INSERT INTO foods (category_id, name, description, price, image, status) VALUES
(1, 'Margherita Pizza', 'Classic delight with 100% real mozzarella cheese, fresh basil, and signature marinara sauce.', 12.99, 'margherita.jpg', 'active'),
(1, 'Pepperoni Pizza', 'Freshly baked pizza loaded with extra pepperoni slices and gooey mozzarella cheese.', 15.99, 'pepperoni.jpg', 'active'),
(1, 'Vegetarian Pizza', 'Onions, green peppers, mushrooms, sweet corn, black olives, and tomatoes topped with mozzarella.', 14.49, 'vegetarian.jpg', 'active'),
(2, 'Cheeseburger', 'Juicy beef patty with cheddar cheese, crisp lettuce, tomato, onions, and our signature secret sauce.', 8.99, 'cheeseburger.jpg', 'active'),
(2, 'Double Bacon Burger', 'Two flame-grilled beef patties with crispy smoked bacon, melted cheese, and BBQ sauce.', 11.99, 'bacon_burger.jpg', 'active'),
(2, 'Crispy Chicken Burger', 'Crispy golden fried chicken breast, fresh cabbage coleslaw, and spicy mayo on a brioche bun.', 9.49, 'chicken_burger.jpg', 'active'),
(3, 'Alfredo Fettuccine', 'Rich and creamy Alfredo sauce tossed with tender fettuccine pasta and grilled chicken breast slices.', 13.99, 'alfredo.jpg', 'active'),
(3, 'Bolognese Spaghetti', 'Slow-cooked ground beef in a rich marinara sauce, served over spaghetti with fresh parmesan cheese.', 12.49, 'bolognese.jpg', 'active'),
(4, 'Iced Latte', 'Freshly brewed espresso shot poured over cold milk and ice cubes, sweetened with vanilla syrup.', 4.49, 'iced_latte.jpg', 'active'),
(4, 'Fresh Orange Juice', '100% natural freshly squeezed orange juice served chilled, rich in Vitamin C.', 3.99, 'orange_juice.jpg', 'active'),
(5, 'Chocolate Lava Cake', 'Decadent chocolate cake with a warm, gooey liquid chocolate center, served with vanilla ice cream.', 6.99, 'lava_cake.jpg', 'active'),
(5, 'New York Cheesecake', 'Rich and creamy classic baked cheesecake with a sweet graham cracker crust and strawberry glaze.', 7.49, 'cheesecake.jpg', 'active');

-- Insert Sample Orders for Testing / Dashboard Charts
-- Order 1: Delivered COD order by John Doe
INSERT INTO orders (id, user_id, order_date, total_amount, status, payment_method, shipping_name, shipping_phone, shipping_address) VALUES
(1, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), 30.97, 'Delivered', 'COD', 'John Doe', '1234567890', '123 Main St, New York, NY 10001');

INSERT INTO order_items (order_id, food_id, quantity, price) VALUES
(1, 1, 1, 12.99), -- Margherita Pizza
(1, 4, 2, 8.99);  -- Cheeseburgers

-- Order 2: Confirmed PayPal order by Jane Smith
INSERT INTO orders (id, user_id, order_date, total_amount, status, payment_method, shipping_name, shipping_phone, shipping_address) VALUES
(2, 2, DATE_SUB(NOW(), INTERVAL 1 DAY), 23.47, 'Confirmed', 'PayPal', 'Jane Smith', '9876543210', '456 Oak Ave, Los Angeles, CA 90001');

INSERT INTO order_items (order_id, food_id, quantity, price) VALUES
(2, 5, 1, 11.99), -- Double Bacon Burger
(2, 9, 1, 4.49),  -- Iced Latte
(2, 11, 1, 6.99); -- Chocolate Lava Cake

INSERT INTO payments (order_id, transaction_id, payment_amount, payment_status) VALUES
(2, 'TXN-PAYPAL-99882211', 23.47, 'COMPLETED');

-- Order 3: Pending COD order by John Doe
INSERT INTO orders (id, user_id, order_date, total_amount, status, payment_method, shipping_name, shipping_phone, shipping_address) VALUES
(3, 1, NOW(), 28.48, 'Pending', 'COD', 'John Doe', '1234567890', '123 Main St, New York, NY 10001');

INSERT INTO order_items (order_id, food_id, quantity, price) VALUES
(3, 2, 1, 15.99), -- Pepperoni Pizza
(3, 8, 1, 12.49); -- Bolognese Spaghetti
