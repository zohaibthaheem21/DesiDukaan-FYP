<?php
/**
 * DesiDukaan - Database Setup Script
 * Run this file once to set up the database
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'desidukaan';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>DesiDukaan Database Setup</h2>";
    echo "<p>Setting up database...</p>";

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Database '$database' created successfully.</p>";

    // Connect to the new database
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create tables manually to avoid issues
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('customer', 'seller', 'admin') NOT NULL DEFAULT 'customer',
            cnic VARCHAR(15) NULL,
            mobile_number VARCHAR(15) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE
        )",

        "CREATE TABLE IF NOT EXISTS shops (
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
            approved_at TIMESTAMP NULL DEFAULT NULL,
            approved_by INT NULL,
            rejection_reason TEXT NULL,
            FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        )",

        "CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_id INT NOT NULL,
            details TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS user_sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        "INSERT IGNORE INTO users (username, email, password_hash, role) VALUES
         ('admin', 'admin@desidukaan.com', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')"
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    
    echo "<p>✅ Database tables created successfully.</p>";
    echo "<p>✅ Default admin user created.</p>";
    echo "<p><strong>🔑 Admin Login Credentials:</strong></p>";
    echo "<p><strong>Email:</strong> admin@desidukaan.com</p>";
    echo "<p><strong>Password:</strong> password</p>";
    echo "<p><strong>⚠️ Important:</strong> Change the admin password after first login!</p>";
    
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
    echo "<h3>Setup Complete!</h3>";
    echo "<p>Your DesiDukaan application is ready to use.</p>";
    echo "<p><a href='index.php' style='background: #DC143C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Application</a></p>";
    echo "<p><strong>Note:</strong> Delete this setup.php file for security after setup is complete.</p>";
    
} catch (PDOException $e) {
    echo "<h2>Database Setup Error</h2>";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "<p>Make sure XAMPP MySQL service is running.</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>DesiDukaan Setup</title>
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
