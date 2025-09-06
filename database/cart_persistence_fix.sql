-- DesiDukaan - Cart Persistence Fix
-- Create database table for persistent cart storage

USE desidukaan;

-- Create cart table for persistent cart storage
CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    -- Ensure one cart entry per user per product
    UNIQUE KEY unique_user_product (user_id, product_id),
    
    INDEX idx_user_cart (user_id),
    INDEX idx_product_cart (product_id),
    INDEX idx_cart_created (created_at)
);

-- Migrate existing session cart data if any exists
-- This is a placeholder for future migration if needed

-- Add some sample data for testing (optional)
-- INSERT IGNORE INTO cart (user_id, product_id, quantity) VALUES
-- (1, 1, 2),
-- (1, 2, 1);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_cart_user_product ON cart(user_id, product_id);
CREATE INDEX IF NOT EXISTS idx_cart_updated ON cart(updated_at);
