<?php
/**
 * Reset Database - Clean Setup
 */

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'desidukaan';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔄 Resetting DesiDukaan Database</h2>";
    
    // Drop and recreate database
    $pdo->exec("DROP DATABASE IF EXISTS $database");
    $pdo->exec("CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Database recreated successfully.</p>";
    
    // Connect to the new database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $pdo->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NULL,
            phone VARCHAR(20) NULL,
            address TEXT NULL,
            role ENUM('customer', 'seller', 'admin') NOT NULL DEFAULT 'customer',
            cnic VARCHAR(15) NULL,
            mobile_number VARCHAR(15) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE
        )
    ");
    echo "<p>✅ Users table created.</p>";
    
    // Create shops table
    $pdo->exec("
        CREATE TABLE shops (
            id INT AUTO_INCREMENT PRIMARY KEY,
            seller_id INT NOT NULL,
            shop_name VARCHAR(100) NOT NULL,
            address TEXT NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone_number VARCHAR(15) NOT NULL,
            cnic_number VARCHAR(15) NOT NULL,
            cnic_picture VARCHAR(255) NULL,
            shop_logo VARCHAR(255) NULL,
            shop_description TEXT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            approved_at TIMESTAMP NULL,
            approved_by INT NULL,
            rejection_reason TEXT NULL,
            FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "<p>✅ Shops table created.</p>";
    
    // Create admin_logs table
    $pdo->exec("
        CREATE TABLE admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_id INT NOT NULL,
            details TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Admin logs table created.</p>";
    
    // Create user_sessions table
    $pdo->exec("
        CREATE TABLE user_sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ User sessions table created.</p>";

    // Create notifications table
    $pdo->exec("
        CREATE TABLE notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Notifications table created.</p>";

    // Create products table
    $pdo->exec("
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
        )
    ");
    echo "<p>✅ Products table created.</p>";

    // Create orders table
    $pdo->exec("
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
        )
    ");
    echo "<p>✅ Orders table created.</p>";

    // Create order_items table
    $pdo->exec("
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
        )
    ");
    echo "<p>✅ Order items table created.</p>";

    // Create wishlist table
    $pdo->exec("
        CREATE TABLE wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_wishlist (customer_id, product_id),
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Wishlist table created.</p>";

    // Insert admin user
    $admin_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // password
    $pdo->exec("
        INSERT INTO users (username, email, password_hash, role) VALUES
        ('admin', 'admin@desidukaan.com', '$admin_hash', 'admin')
    ");
    echo "<p>✅ Admin user created.</p>";
    
    // Create upload directories
    $upload_dirs = [
        __DIR__ . '/uploads/',
        __DIR__ . '/uploads/cnic/',
        __DIR__ . '/uploads/logos/',
        __DIR__ . '/uploads/products/'
    ];
    
    foreach ($upload_dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "<p>✅ Created directory: " . basename($dir) . "</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>🎉 Database Reset Complete!</h3>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 6px; border-left: 4px solid #28a745;'>";
    echo "<h4>🔑 Admin Login Credentials:</h4>";
    echo "<p><strong>Email:</strong> admin@desidukaan.com</p>";
    echo "<p><strong>Password:</strong> password</p>";
    echo "</div>";
    echo "<p><a href='index.php' style='background: #DC143C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Go to Application</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Database Reset Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>DesiDukaan Database Reset</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2, h3, h4 {
            color: #DC143C;
        }
        p {
            line-height: 1.6;
        }
        hr {
            margin: 30px 0;
            border: none;
            border-top: 2px solid #DC143C;
        }
    </style>
</head>
<body>
    <!-- Content is generated by PHP above -->
</body>
</html>
