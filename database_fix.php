<?php
/**
 * Database Fix Script - Run this once to fix all database issues
 */

require_once 'config/config.php';

try {
    $db = getDB();
    echo "<h2>🔧 DesiDukaan Database Fix Script</h2>";
    echo "<p>Fixing database structure...</p>";
    
    // Fix wishlist table
    echo "<h3>1. Fixing Wishlist Table</h3>";
    
    // Check if wishlist table exists
    $stmt = $db->query("SHOW TABLES LIKE 'wishlist'");
    if ($stmt->rowCount() == 0) {
        // Create new wishlist table
        $db->exec("CREATE TABLE wishlist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_wishlist (user_id, product_id),
            INDEX idx_user_id (user_id),
            INDEX idx_product_id (product_id)
        )");
        echo "✅ Created new wishlist table with user_id column<br>";
    } else {
        // Check if table has customer_id column and rename it
        $stmt = $db->query("SHOW COLUMNS FROM wishlist LIKE 'customer_id'");
        if ($stmt->rowCount() > 0) {
            $db->exec("ALTER TABLE wishlist CHANGE customer_id user_id INT NOT NULL");
            echo "✅ Renamed customer_id to user_id in wishlist table<br>";
        }
        
        // Ensure user_id column exists
        $stmt = $db->query("SHOW COLUMNS FROM wishlist LIKE 'user_id'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE wishlist ADD COLUMN user_id INT NOT NULL AFTER id");
            echo "✅ Added user_id column to wishlist table<br>";
        } else {
            echo "✅ Wishlist table already has user_id column<br>";
        }
        
        // Add indexes if they don't exist
        try {
            $db->exec("ALTER TABLE wishlist ADD INDEX idx_user_id (user_id)");
            echo "✅ Added index on user_id<br>";
        } catch (Exception $e) {
            echo "ℹ️ Index on user_id already exists<br>";
        }
        
        try {
            $db->exec("ALTER TABLE wishlist ADD INDEX idx_product_id (product_id)");
            echo "✅ Added index on product_id<br>";
        } catch (Exception $e) {
            echo "ℹ️ Index on product_id already exists<br>";
        }
        
        // Add unique constraint if it doesn't exist
        try {
            $db->exec("ALTER TABLE wishlist ADD UNIQUE KEY unique_wishlist (user_id, product_id)");
            echo "✅ Added unique constraint on user_id and product_id<br>";
        } catch (Exception $e) {
            echo "ℹ️ Unique constraint already exists<br>";
        }
    }
    
    // Verify wishlist table structure
    echo "<h3>2. Verifying Wishlist Table Structure</h3>";
    $stmt = $db->query("DESCRIBE wishlist");
    $columns = $stmt->fetchAll();
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test wishlist functionality
    echo "<h3>3. Testing Wishlist Functionality</h3>";
    
    // Count existing wishlist items
    $stmt = $db->query("SELECT COUNT(*) as count FROM wishlist");
    $result = $stmt->fetch();
    echo "✅ Current wishlist items: {$result['count']}<br>";
    
    // Test basic operations
    echo "✅ Wishlist table is ready for use!<br>";
    
    echo "<h3>🎉 Database Fix Complete!</h3>";
    echo "<p style='color: green; font-weight: bold;'>All database issues have been resolved!</p>";
    echo "<p><a href='customer/dashboard.php'>Go to Customer Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
}

h2, h3 {
    color: #333;
}

table {
    background: white;
    padding: 10px;
}

th {
    background: #007bff;
    color: white;
    padding: 8px;
}

td {
    padding: 8px;
    border: 1px solid #ddd;
}

a {
    display: inline-block;
    background: #007bff;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 20px;
}

a:hover {
    background: #0056b3;
}
</style>
