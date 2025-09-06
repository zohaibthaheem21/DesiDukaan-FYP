<?php
/**
 * Test Database Connection
 */

require_once 'config/config.php';

try {
    $db = getDB();
    echo "<h2>✅ Database Connection Successful!</h2>";
    
    // Test if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Users table exists</p>";
        
        // Check if admin user exists
        $stmt = $db->prepare("SELECT * FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<p>✅ Admin user exists: " . $admin['email'] . "</p>";
        } else {
            echo "<p>❌ Admin user not found</p>";
        }
    } else {
        echo "<p>❌ Users table does not exist</p>";
        echo "<p>Please run the database setup first.</p>";
    }
    
    // Show all tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Existing tables:</strong> " . implode(', ', $tables) . "</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Database Connection Failed!</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
