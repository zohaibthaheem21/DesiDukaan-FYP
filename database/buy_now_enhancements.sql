-- DesiDukaan Buy Now Functionality - Database Enhancements
-- This file enhances the existing database structure for Buy Now feature

USE desidukaan;

-- Enhance existing orders table for Buy Now functionality
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS shop_id INT AFTER customer_id,
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' AFTER status,
ADD COLUMN IF NOT EXISTS payment_method ENUM('cod', 'online', 'wallet') DEFAULT 'cod' AFTER payment_status,
ADD COLUMN IF NOT EXISTS order_type ENUM('cart', 'buy_now') DEFAULT 'cart' AFTER payment_method,
ADD COLUMN IF NOT EXISTS confirmed_at TIMESTAMP NULL AFTER updated_at,
ADD COLUMN IF NOT EXISTS shipped_at TIMESTAMP NULL AFTER confirmed_at,
ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL AFTER shipped_at,
ADD COLUMN IF NOT EXISTS notes TEXT AFTER delivered_at,
ADD COLUMN IF NOT EXISTS customer_name VARCHAR(100) AFTER notes;

-- Add foreign key for shop_id if it doesn't exist
ALTER TABLE orders ADD CONSTRAINT fk_orders_shop_id 
FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE;

-- Enhance existing order_items table
ALTER TABLE order_items
ADD COLUMN IF NOT EXISTS unit_price DECIMAL(10,2) AFTER quantity,
ADD COLUMN IF NOT EXISTS total_price DECIMAL(10,2) AFTER unit_price,
ADD COLUMN IF NOT EXISTS product_name VARCHAR(255) AFTER total_price,
ADD COLUMN IF NOT EXISTS product_image VARCHAR(255) AFTER product_name;

-- Update existing order_items to populate new columns
UPDATE order_items SET 
    unit_price = price,
    total_price = price * quantity
WHERE unit_price IS NULL;

-- Create shipping addresses table for detailed delivery information
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

-- Create product reviews table for future implementation
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    customer_id INT NOT NULL,
    order_id INT NOT NULL,
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

-- Create order status history table
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT,
    change_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_history (order_id)
);

-- Create email notifications log table
CREATE TABLE IF NOT EXISTS email_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_type ENUM('customer', 'vendor', 'admin') NOT NULL,
    email_type ENUM('order_confirmation', 'order_update', 'shipping_notification', 'delivery_confirmation') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    email_body TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    error_message TEXT,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_email_order (order_id),
    INDEX idx_email_status (status)
);

-- Create customer saved addresses table
CREATE TABLE IF NOT EXISTS customer_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    address_name VARCHAR(100) NOT NULL,
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
    INDEX idx_customer_addresses (customer_id),
    INDEX idx_default_address (customer_id, is_default)
);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_orders_shop_id ON orders(shop_id);
CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON orders(payment_status);
CREATE INDEX IF NOT EXISTS idx_orders_order_type ON orders(order_type);
CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items(product_id);

-- Update existing orders to have shop_id from products
UPDATE orders o
JOIN order_items oi ON o.id = oi.order_id
JOIN products p ON oi.product_id = p.id
SET o.shop_id = p.shop_id
WHERE o.shop_id IS NULL;
