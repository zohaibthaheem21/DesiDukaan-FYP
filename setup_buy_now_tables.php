<?php
/**
 * DesiDukaan - Buy Now Tables Setup
 * Creates the required tables for Buy Now functionality
 */

require_once 'config/config.php';

try {
    $db = getDB();
    
    echo "<h2>Setting up Buy Now Tables...</h2>\n";
    
    // Check and create orders table with required columns
    echo "<h3>Setting up Orders table...</h3>\n";
    try {
        // First check if orders table exists
        $stmt = $db->query("SHOW TABLES LIKE 'orders'");
        if ($stmt->rowCount() == 0) {
            // Create orders table
            $db->exec("
                CREATE TABLE orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_number VARCHAR(20) UNIQUE NOT NULL,
                    customer_id INT NOT NULL,
                    shop_id INT,
                    total_amount DECIMAL(10,2) NOT NULL,
                    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
                    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
                    payment_method ENUM('cod', 'online', 'wallet') DEFAULT 'cod',
                    order_type ENUM('cart', 'buy_now') DEFAULT 'cart',
                    customer_name VARCHAR(100),
                    shipping_address TEXT,
                    phone_number VARCHAR(20),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
            echo "<p style='color: green;'>✅ Orders table created</p>\n";
        } else {
            // Check if required columns exist and add them if missing
            $columns = $db->query("DESCRIBE orders")->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('shop_id', $columns)) {
                $db->exec("ALTER TABLE orders ADD COLUMN shop_id INT AFTER customer_id");
                echo "<p style='color: green;'>✅ Added shop_id column to orders</p>\n";
            }
            
            if (!in_array('payment_status', $columns)) {
                $db->exec("ALTER TABLE orders ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' AFTER status");
                echo "<p style='color: green;'>✅ Added payment_status column to orders</p>\n";
            }
            
            if (!in_array('payment_method', $columns)) {
                $db->exec("ALTER TABLE orders ADD COLUMN payment_method ENUM('cod', 'online', 'wallet') DEFAULT 'cod' AFTER payment_status");
                echo "<p style='color: green;'>✅ Added payment_method column to orders</p>\n";
            }
            
            if (!in_array('order_type', $columns)) {
                $db->exec("ALTER TABLE orders ADD COLUMN order_type ENUM('cart', 'buy_now') DEFAULT 'cart' AFTER payment_method");
                echo "<p style='color: green;'>✅ Added order_type column to orders</p>\n";
            }
            
            if (!in_array('customer_name', $columns)) {
                $db->exec("ALTER TABLE orders ADD COLUMN customer_name VARCHAR(100) AFTER order_type");
                echo "<p style='color: green;'>✅ Added customer_name column to orders</p>\n";
            }
            
            echo "<p style='color: blue;'>ℹ️ Orders table already exists and updated</p>\n";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error with orders table: " . $e->getMessage() . "</p>\n";
    }
    
    // Check and create order_items table with required columns
    echo "<h3>Setting up Order Items table...</h3>\n";
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'order_items'");
        if ($stmt->rowCount() == 0) {
            $db->exec("
                CREATE TABLE order_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    product_id INT NOT NULL,
                    quantity INT NOT NULL DEFAULT 1,
                    unit_price DECIMAL(10,2) NOT NULL,
                    total_price DECIMAL(10,2) NOT NULL,
                    product_name VARCHAR(255) NOT NULL,
                    product_image VARCHAR(255),
                    size VARCHAR(50) NULL,
                    color VARCHAR(50) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
                )
            ");
            echo "<p style='color: green;'>✅ Order items table created</p>\n";
        } else {
            // Check if required columns exist
            $columns = $db->query("DESCRIBE order_items")->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('unit_price', $columns)) {
                $db->exec("ALTER TABLE order_items ADD COLUMN unit_price DECIMAL(10,2) AFTER quantity");
                echo "<p style='color: green;'>✅ Added unit_price column to order_items</p>\n";
            }
            
            if (!in_array('total_price', $columns)) {
                $db->exec("ALTER TABLE order_items ADD COLUMN total_price DECIMAL(10,2) AFTER unit_price");
                echo "<p style='color: green;'>✅ Added total_price column to order_items</p>\n";
            }
            
            if (!in_array('product_name', $columns)) {
                $db->exec("ALTER TABLE order_items ADD COLUMN product_name VARCHAR(255) AFTER total_price");
                echo "<p style='color: green;'>✅ Added product_name column to order_items</p>\n";
            }
            
            if (!in_array('product_image', $columns)) {
                $db->exec("ALTER TABLE order_items ADD COLUMN product_image VARCHAR(255) AFTER product_name");
                echo "<p style='color: green;'>✅ Added product_image column to order_items</p>\n";
            }
            
            echo "<p style='color: blue;'>ℹ️ Order items table already exists and updated</p>\n";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error with order_items table: " . $e->getMessage() . "</p>\n";
    }
    
    // Create shipping_addresses table
    echo "<h3>Setting up Shipping Addresses table...</h3>\n";
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'shipping_addresses'");
        if ($stmt->rowCount() == 0) {
            $db->exec("
                CREATE TABLE shipping_addresses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
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
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                )
            ");
            echo "<p style='color: green;'>✅ Shipping addresses table created</p>\n";
        } else {
            echo "<p style='color: blue;'>ℹ️ Shipping addresses table already exists</p>\n";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error with shipping_addresses table: " . $e->getMessage() . "</p>\n";
    }
    
    // Create order_status_history table
    echo "<h3>Setting up Order Status History table...</h3>\n";
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'order_status_history'");
        if ($stmt->rowCount() == 0) {
            $db->exec("
                CREATE TABLE order_status_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    old_status VARCHAR(50),
                    new_status VARCHAR(50) NOT NULL,
                    changed_by INT,
                    change_reason TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
                )
            ");
            echo "<p style='color: green;'>✅ Order status history table created</p>\n";
        } else {
            echo "<p style='color: blue;'>ℹ️ Order status history table already exists</p>\n";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error with order_status_history table: " . $e->getMessage() . "</p>\n";
    }
    
    // Create product_reviews table
    echo "<h3>Setting up Product Reviews table...</h3>\n";
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'product_reviews'");
        if ($stmt->rowCount() == 0) {
            $db->exec("
                CREATE TABLE product_reviews (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    product_id INT NOT NULL,
                    customer_id INT NOT NULL,
                    order_id INT,
                    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                    review_text TEXT,
                    is_approved BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                )
            ");
            echo "<p style='color: green;'>✅ Product reviews table created</p>\n";
        } else {
            echo "<p style='color: blue;'>ℹ️ Product reviews table already exists</p>\n";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error with product_reviews table: " . $e->getMessage() . "</p>\n";
    }

    // Create email_notifications table
    echo "<h3>Setting up Email Notifications table...</h3>\n";
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'email_notifications'");
        if ($stmt->rowCount() == 0) {
            $db->exec("
                CREATE TABLE email_notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT,
                    recipient_email VARCHAR(255) NOT NULL,
                    recipient_type ENUM('customer', 'vendor', 'admin') NOT NULL,
                    email_type ENUM('order_confirmation', 'order_update', 'shipping_notification', 'delivery_confirmation') NOT NULL,
                    subject VARCHAR(255) NOT NULL,
                    email_body TEXT,
                    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
                    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    error_message TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
                )
            ");
            echo "<p style='color: green;'>✅ Email notifications table created</p>\n";
        } else {
            echo "<p style='color: blue;'>ℹ️ Email notifications table already exists</p>\n";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error with email_notifications table: " . $e->getMessage() . "</p>\n";
    }

    echo "<h3>✅ Buy Now tables setup completed!</h3>\n";
    echo "<p>You can now use the Buy Now functionality.</p>\n";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Setup Error</h2>\n";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Buy Now Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2, h3 {
            color: #DC143C;
        }
        p {
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <!-- Content is generated by PHP above -->
</body>
</html>
