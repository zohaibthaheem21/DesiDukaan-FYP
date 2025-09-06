-- Order Status History Enhancements
-- Ensure order status history table exists and has proper structure

-- Create order status history table if it doesn't exist
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL,
    notes TEXT,
    changed_by INT, -- User ID who changed the status
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_order_status_history (order_id),
    INDEX idx_status_history_date (created_at)
);

-- Add missing timestamp columns to orders table if they don't exist
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS confirmed_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS shipped_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Create trigger to automatically log status changes
DELIMITER //

DROP TRIGGER IF EXISTS order_status_change_log//

CREATE TRIGGER order_status_change_log
    AFTER UPDATE ON orders
    FOR EACH ROW
BEGIN
    -- Only log if status actually changed
    IF OLD.status != NEW.status THEN
        INSERT INTO order_status_history (order_id, status, notes, changed_by, created_at)
        VALUES (NEW.id, NEW.status, CONCAT('Status changed from ', OLD.status, ' to ', NEW.status), NULL, NOW());
    END IF;
END//

DELIMITER ;

-- Insert initial status history for existing orders that don't have history
INSERT IGNORE INTO order_status_history (order_id, status, notes, created_at)
SELECT id, status, 'Initial order status', created_at
FROM orders o
WHERE NOT EXISTS (
    SELECT 1 FROM order_status_history osh 
    WHERE osh.order_id = o.id
);
