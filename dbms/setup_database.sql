-- Drop all tables in the correct order to handle foreign key constraints
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

-- Create the users table first
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    stock INT NOT NULL DEFAULT 0
);

-- Create the orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create the cart table
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create the order_items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Insert a default user for testing
INSERT INTO users (username, password, email) VALUES
('test_user', 'password123', 'test@example.com');

-- Insert sample products
INSERT INTO products (product_name, description, price, image_url, stock) VALUES
('Apples', 'Fresh and juicy apples', 150.00, 'apple.webp', 100),
('Bananas', 'Sweet and ripe bananas', 50.00, 'banana.jpg', 150),
('Carrots', 'Fresh and crunchy carrots', 80.00, 'carrots.webp', 75),
('Milk', 'Pure and fresh milk', 60.00, 'milk.jpg', 50);

-- Verify the setup
SELECT 'Users table:' as '';
SELECT * FROM users;

SELECT 'Products table:' as '';
SELECT * FROM products;

SELECT 'Cart table structure:' as '';
DESCRIBE cart;

SELECT 'Orders table structure:' as '';
DESCRIBE orders;

SELECT 'Order items table structure:' as '';
DESCRIBE order_items; 