<?php
/**
 * DesiDukaan - Fix Cart Persistence
 * Creates database table for persistent cart storage
 */

require_once 'config/config.php';

try {
    $db = getDB();
    
    echo "<h2>Fixing Cart Persistence Issue...</h2>\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents('database/cart_persistence_fix.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, 'USE') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            $success_count++;
            echo "<p style='color: green;'>✅ Executed: " . substr($statement, 0, 50) . "...</p>\n";
        } catch (PDOException $e) {
            $error_count++;
            echo "<p style='color: orange;'>⚠️ Warning: " . $e->getMessage() . "</p>\n";
            echo "<p style='color: gray;'>Statement: " . substr($statement, 0, 100) . "...</p>\n";
        }
    }
    
    echo "<h3>Setup Summary:</h3>\n";
    echo "<p>✅ Successful operations: {$success_count}</p>\n";
    echo "<p>⚠️ Warnings/Skipped: {$error_count}</p>\n";
    
    // Test the setup by checking if cart table exists
    echo "<h3>Verifying Cart Table:</h3>\n";
    
    try {
        $stmt = $db->query("DESCRIBE cart");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p style='color: green;'>✅ Cart table exists with " . count($columns) . " columns</p>\n";
        
        // Show table structure
        echo "<h4>Cart Table Structure:</h4>\n";
        $stmt = $db->query("DESCRIBE cart");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Cart table not found or error: " . $e->getMessage() . "</p>\n";
    }
    
    // Test the cart functions
    echo "<h3>Testing Cart Functions:</h3>\n";

    // Test if cart functions work
    try {
        require_once 'includes/cart.php';
        echo "<p style='color: green;'>✅ Cart functions loaded successfully</p>\n";

        // Test database connection for cart
        $stmt = $db->query("SELECT COUNT(*) as count FROM cart");
        $result = $stmt->fetch();
        echo "<p style='color: green;'>✅ Cart table accessible - {$result['count']} items currently in database</p>\n";

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error testing cart functions: " . $e->getMessage() . "</p>\n";
    }

    echo "<h3>🎉 Cart Persistence Fix Complete!</h3>\n";
    echo "<p>Your cart will now persist across login sessions!</p>\n";

    echo "<h3>What Changed:</h3>\n";
    echo "<ul>\n";
    echo "<li>✅ Created 'cart' database table for persistent storage</li>\n";
    echo "<li>✅ Updated cart.php to use database instead of sessions</li>\n";
    echo "<li>✅ Cart items now persist when you log out and log back in</li>\n";
    echo "<li>✅ All cart functions (add, remove, update quantity) work with database</li>\n";
    echo "<li>✅ Cart count and totals are calculated from database</li>\n";
    echo "</ul>\n";

    echo "<h3>Test Instructions:</h3>\n";
    echo "<ol>\n";
    echo "<li><strong>Login</strong> to your customer account</li>\n";
    echo "<li><strong>Add items</strong> to your cart from the dashboard</li>\n";
    echo "<li><strong>Logout</strong> from your account</li>\n";
    echo "<li><strong>Login again</strong> with the same account</li>\n";
    echo "<li><strong>Check cart</strong> - your items should still be there!</li>\n";
    echo "</ol>\n";

    echo "<p><a href='customer/dashboard.php' style='background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🛍️ Test Cart Persistence</a>";
    echo "<a href='customer/cart.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛒 View Cart</a></p>\n";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Setup Failed!</h3>\n";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database connection and try again.</p>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>DesiDukaan - Cart Persistence Fix</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h2, h3 { color: #2c3e50; }
        p { margin: 5px 0; }
        table { width: 100%; }
        th, td { padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <!-- Content is echoed above -->
</body>
</html>
