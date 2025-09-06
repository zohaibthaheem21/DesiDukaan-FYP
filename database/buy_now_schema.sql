-- DesiDukaan Buy Now Functionality Database Schema
-- This file contains the database structure for implementing Buy Now functionality

-- Orders table to store order information
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    shop_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method ENUM('cod', 'online', 'wallet') DEFAULT 'cod',
    order_type ENUM('cart', 'buy_now') DEFAULT 'cart',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    
    INDEX idx_customer_orders (customer_id),
    INDEX idx_shop_orders (shop_id),
    INDEX idx_order_status (status),
    INDEX idx_order_date (created_at)
);

-- Order items table to store individual products in each order
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    product_name VARCHAR(255) NOT NULL, -- Store product name at time of order
    product_image VARCHAR(255), -- Store product image at time of order
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    INDEX idx_order_items (order_id),
    INDEX idx_product_orders (product_id)
);

-- Shipping addresses table to store delivery information
CREATE TABLE IF NOT EXISTS shipping_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) DEFAULT 'India',
    landmark VARCHAR(255),
    address_type ENUM('home', 'office', 'other') DEFAULT 'home',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    
    INDEX idx_order_shipping (order_id)
);

-- Customer addresses table for saved addresses (for future use)
CREATE TABLE IF NOT EXISTS customer_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) DEFAULT 'India',
    landmark VARCHAR(255),
    address_type ENUM('home', 'office', 'other') DEFAULT 'home',
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_customer_addresses (customer_id)
);

-- Product reviews table (for future implementation)
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    customer_id INT NOT NULL,
    order_id INT NOT NULL, -- Link to order to ensure customer bought the product
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_title VARCHAR(255),
    review_text TEXT,
    is_verified_purchase BOOLEAN DEFAULT TRUE,
    is_approved BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_customer_product_order (customer_id, product_id, order_id),
    INDEX idx_product_reviews (product_id),
    INDEX idx_customer_reviews (customer_id),
    INDEX idx_review_rating (rating)
);

-- Order status history table to track order status changes
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL,
    notes TEXT,
    changed_by INT, -- User ID who changed the status
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_order_status_history (order_id)
);

-- Email notifications log table
CREATE TABLE IF NOT EXISTS email_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_type ENUM('customer', 'vendor', 'admin') NOT NULL,
    email_type ENUM('order_confirmation', 'order_update', 'delivery_notification') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    email_body TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    
    INDEX idx_email_notifications (order_id),
    INDEX idx_email_status (status)
);

-- Insert sample data for testing (optional)
-- You can uncomment these lines for testing purposes

/*
-- Sample order
INSERT INTO orders (order_number, customer_id, shop_id, total_amount, status, order_type) 
VALUES ('ORD-2024-001', 1, 1, 299.99, 'pending', 'buy_now');

-- Sample order item
INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price, product_name) 
VALUES (1, 1, 2, 149.99, 299.98, 'Sample Product');

-- Sample shipping address
INSERT INTO shipping_addresses (order_id, customer_name, phone_number, address_line_1, city, state, postal_code) 
VALUES (1, 'John Doe', '+91-9876543210', '123 Main Street', 'Mumbai', 'Maharashtra', '400001');
*/

-- Indexes for better performance
CREATE INDEX idx_orders_created_at ON orders(created_at DESC);
CREATE INDEX idx_orders_status_date ON orders(status, created_at DESC);
CREATE INDEX idx_order_items_product ON order_items(product_id, created_at DESC);

-- Views for common queries
CREATE VIEW order_summary AS
SELECT 
    o.id,
    o.order_number,
    o.customer_id,
    u.name as customer_name,
    u.email as customer_email,
    o.shop_id,
    s.shop_name,
    o.total_amount,
    o.status,
    o.payment_status,
    o.order_type,
    o.created_at,
    sa.customer_name as shipping_name,
    sa.phone_number as shipping_phone,
    CONCAT(sa.address_line_1, ', ', sa.city, ', ', sa.state, ' - ', sa.postal_code) as shipping_address
FROM orders o
JOIN users u ON o.customer_id = u.id
JOIN shops s ON o.shop_id = s.id
LEFT JOIN shipping_addresses sa ON o.id = sa.order_id;

-- Trigger to automatically update order total when order items change
DELIMITER //
CREATE TRIGGER update_order_total 
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET total_amount = (
        SELECT SUM(total_price) 
        FROM order_items 
        WHERE order_id = NEW.order_id
    )
    WHERE id = NEW.order_id;
END//
DELIMITER ;
