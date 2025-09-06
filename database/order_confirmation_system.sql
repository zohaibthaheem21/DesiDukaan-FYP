-- DesiDukaan Order Confirmation & Vendor Notification System
-- Database Schema Enhancement

USE desidukaan;

-- Create notifications table for real-time vendor notifications
CREATE TABLE IF NOT EXISTS notifications (
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
    INDEX idx_notifications_user_id (user_id),
    INDEX idx_notifications_read (is_read),
    INDEX idx_notifications_order_id (order_id)
);

-- Create order events table for comprehensive audit logging
CREATE TABLE IF NOT EXISTS order_events (
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_events_order_id (order_id),
    INDEX idx_order_events_type (event_type),
    INDEX idx_order_events_date (created_at)
);

-- Create enhanced email notifications log table
CREATE TABLE IF NOT EXISTS email_notifications (
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
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_email_notifications_order_id (order_id),
    INDEX idx_email_notifications_status (status),
    INDEX idx_email_notifications_type (email_type)
);

-- Create order status history table for tracking all status changes
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    old_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'rejected') NULL,
    new_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'rejected') NOT NULL,
    changed_by INT NULL,
    change_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_status_history_order_id (order_id),
    INDEX idx_order_status_history_date (created_at)
);

-- Enhance existing orders table with additional fields for vendor management
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS shop_id INT AFTER customer_id,
ADD COLUMN IF NOT EXISTS confirmed_at TIMESTAMP NULL AFTER updated_at,
ADD COLUMN IF NOT EXISTS rejected_at TIMESTAMP NULL AFTER confirmed_at,
ADD COLUMN IF NOT EXISTS shipped_at TIMESTAMP NULL AFTER rejected_at,
ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL AFTER shipped_at,
ADD COLUMN IF NOT EXISTS vendor_notes TEXT AFTER delivered_at,
ADD COLUMN IF NOT EXISTS customer_can_cancel BOOLEAN DEFAULT TRUE AFTER vendor_notes;

-- Add foreign key constraint for shop_id (ignore if already exists)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = 'desidukaan' 
     AND TABLE_NAME = 'orders' 
     AND CONSTRAINT_NAME = 'fk_orders_shop_id') = 0,
    'ALTER TABLE orders ADD CONSTRAINT fk_orders_shop_id FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE SET NULL',
    'SELECT "Foreign key already exists"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update order status enum to include 'rejected' status
ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'rejected') DEFAULT 'pending';

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_orders_shop_id ON orders(shop_id);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_confirmed_at ON orders(confirmed_at);

-- Create stored procedure for order status updates with logging
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS UpdateOrderStatus(
    IN p_order_id INT,
    IN p_new_status VARCHAR(20),
    IN p_changed_by INT,
    IN p_notes TEXT
)
BEGIN
    DECLARE v_old_status VARCHAR(20);
    DECLARE v_shop_id INT;
    DECLARE v_customer_id INT;
    
    -- Get current status and related info
    SELECT status, shop_id, customer_id INTO v_old_status, v_shop_id, v_customer_id
    FROM orders WHERE id = p_order_id;
    
    -- Update order status and timestamp
    UPDATE orders 
    SET status = p_new_status,
        updated_at = NOW(),
        confirmed_at = CASE WHEN p_new_status = 'confirmed' THEN NOW() ELSE confirmed_at END,
        rejected_at = CASE WHEN p_new_status = 'rejected' THEN NOW() ELSE rejected_at END,
        shipped_at = CASE WHEN p_new_status = 'shipped' THEN NOW() ELSE shipped_at END,
        delivered_at = CASE WHEN p_new_status = 'delivered' THEN NOW() ELSE delivered_at END,
        customer_can_cancel = CASE WHEN p_new_status IN ('confirmed', 'processing', 'shipped') THEN FALSE ELSE customer_can_cancel END,
        vendor_notes = COALESCE(p_notes, vendor_notes)
    WHERE id = p_order_id;
    
    -- Log status change
    INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, notes)
    VALUES (p_order_id, v_old_status, p_new_status, p_changed_by, p_notes);
    
    -- Log event
    INSERT INTO order_events (order_id, event_type, user_id, user_type, event_data)
    VALUES (p_order_id, p_new_status, p_changed_by, 'vendor', JSON_OBJECT('old_status', v_old_status, 'new_status', p_new_status));
    
END //
DELIMITER ;

-- Create function to get unread notification count
DELIMITER //
CREATE FUNCTION IF NOT EXISTS GetUnreadNotificationCount(p_user_id INT)
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE v_count INT DEFAULT 0;
    SELECT COUNT(*) INTO v_count 
    FROM notifications 
    WHERE user_id = p_user_id AND is_read = FALSE;
    RETURN v_count;
END //
DELIMITER ;

-- Insert sample notification types for testing (optional)
-- These will be used by the application to create notifications
INSERT IGNORE INTO notifications (user_id, title, message, type, order_id) 
SELECT 
    u.id,
    'Welcome to Order Management',
    'You can now receive real-time notifications for new orders.',
    'info',
    NULL
FROM users u 
WHERE u.role = 'seller' 
LIMIT 1;

-- Create trigger to automatically create vendor notification when order is placed
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_order_insert
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    DECLARE v_vendor_id INT;
    
    -- Get vendor ID from shop
    SELECT seller_id INTO v_vendor_id 
    FROM shops 
    WHERE id = NEW.shop_id;
    
    -- Create notification for vendor
    IF v_vendor_id IS NOT NULL THEN
        INSERT INTO notifications (user_id, title, message, type, order_id, action_url)
        VALUES (
            v_vendor_id,
            'New Order Received!',
            CONCAT('You have received a new order #', NEW.order_number, '. Please accept or reject.'),
            'order',
            NEW.id,
            CONCAT('/seller/orders.php?order_id=', NEW.id)
        );
        
        -- Log notification event
        INSERT INTO order_events (order_id, event_type, user_id, user_type, event_data)
        VALUES (
            NEW.id,
            'notification_sent',
            v_vendor_id,
            'system',
            JSON_OBJECT('notification_type', 'new_order', 'vendor_id', v_vendor_id)
        );
    END IF;
END //
DELIMITER ;
