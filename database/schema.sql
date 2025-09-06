-- DesiDukaan Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS desidukaan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE desidukaan;

-- Users table (for both customers and sellers)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    role ENUM('customer', 'seller', 'admin') NOT NULL DEFAULT 'customer',
    cnic VARCHAR(15) NULL, -- Only for sellers
    mobile_number VARCHAR(15) NULL, -- Only for sellers
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Shops table
CREATE TABLE shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    shop_name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    cnic_number VARCHAR(15) NOT NULL,
    cnic_picture VARCHAR(255) NULL, -- File path
    shop_logo VARCHAR(255) NULL, -- File path
    shop_description TEXT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL DEFAULT NULL,
    approved_by INT NULL, -- Admin user ID
    rejection_reason TEXT NULL,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Admin logs table
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NOT NULL, -- 'shop', 'user', etc.
    target_id INT NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sessions table for session management
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT DEFAULT 0,
    sizes JSON NULL,
    colors JSON NULL,
    images JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    size VARCHAR(50) NULL,
    color VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Wishlist table
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (customer_id, product_id),
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create default admin user (password: password)
INSERT IGNORE INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@desidukaan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Notifications table for real-time vendor notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error', 'order') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    order_id INT NULL,
    action_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Order events table for audit logging
CREATE TABLE order_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    event_type ENUM('created', 'confirmed', 'rejected', 'shipped', 'delivered', 'cancelled', 'email_sent', 'notification_sent') NOT NULL,
    event_data JSON NULL,
    user_id INT NULL,
    user_type ENUM('customer', 'vendor', 'admin', 'system') DEFAULT 'system',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Email notifications log table
CREATE TABLE email_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_type ENUM('customer', 'vendor', 'admin') NOT NULL,
    email_type ENUM('order_confirmation', 'order_accepted', 'order_rejected', 'status_update', 'vendor_notification') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    email_body TEXT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- Order status history table for tracking all status changes
CREATE TABLE order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    old_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'rejected') NULL,
    new_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'rejected') NOT NULL,
    changed_by INT NULL,
    change_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Enhance orders table with additional fields
ALTER TABLE orders
ADD COLUMN IF NOT EXISTS shop_id INT AFTER customer_id,
ADD COLUMN IF NOT EXISTS confirmed_at TIMESTAMP NULL AFTER updated_at,
ADD COLUMN IF NOT EXISTS rejected_at TIMESTAMP NULL AFTER confirmed_at,
ADD COLUMN IF NOT EXISTS shipped_at TIMESTAMP NULL AFTER rejected_at,
ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL AFTER shipped_at,
ADD COLUMN IF NOT EXISTS vendor_notes TEXT AFTER delivered_at,
ADD COLUMN IF NOT EXISTS customer_can_cancel BOOLEAN DEFAULT TRUE AFTER vendor_notes;

-- Add foreign key for shop_id if it doesn't exist
ALTER TABLE orders ADD CONSTRAINT fk_orders_shop_id FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE SET NULL;

-- Update order status enum to include 'rejected'
ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'rejected') DEFAULT 'pending';

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_shops_seller_id ON shops(seller_id);
CREATE INDEX idx_shops_status ON shops(status);
CREATE INDEX idx_sessions_user_id ON user_sessions(user_id);
CREATE INDEX idx_sessions_expires ON user_sessions(expires_at);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
CREATE INDEX idx_notifications_order_id ON notifications(order_id);
CREATE INDEX idx_order_events_order_id ON order_events(order_id);
CREATE INDEX idx_order_events_type ON order_events(event_type);
CREATE INDEX idx_email_notifications_order_id ON email_notifications(order_id);
CREATE INDEX idx_email_notifications_status ON email_notifications(status);
CREATE INDEX idx_order_status_history_order_id ON order_status_history(order_id);
CREATE INDEX idx_orders_shop_id ON orders(shop_id);
CREATE INDEX idx_orders_status ON orders(status);
