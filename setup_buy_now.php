<?php
/**
 * DesiDukaan - Buy Now Feature Setup
 * Sets up database tables and enhancements for Buy Now functionality
 */

require_once 'config/config.php';

try {
    $db = getDB();
    
    echo "<h2>Setting up Buy Now functionality...</h2>\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents('database/buy_now_enhancements.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
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
    
    // Test the setup by checking if tables exist
    echo "<h3>Verifying Tables:</h3>\n";
    
    $tables_to_check = [
        'orders',
        'order_items', 
        'shipping_addresses',
        'product_reviews',
        'order_status_history',
        'email_notifications',
        'customer_addresses'
    ];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $db->query("DESCRIBE {$table}");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p style='color: green;'>✅ Table '{$table}' exists with " . count($columns) . " columns</p>\n";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Table '{$table}' not found or error: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // Check if orders table has new columns
    echo "<h3>Checking Enhanced Columns:</h3>\n";
    
    try {
        $stmt = $db->query("DESCRIBE orders");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $required_columns = ['shop_id', 'payment_status', 'payment_method', 'order_type', 'confirmed_at', 'shipped_at', 'delivered_at', 'customer_name'];
        
        foreach ($required_columns as $col) {
            $found = false;
            foreach ($columns as $column) {
                if ($column['Field'] === $col) {
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                echo "<p style='color: green;'>✅ Column 'orders.{$col}' exists</p>\n";
            } else {
                echo "<p style='color: red;'>❌ Column 'orders.{$col}' missing</p>\n";
            }
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error checking orders table: " . $e->getMessage() . "</p>\n";
    }
    
    echo "<h3>🎉 Buy Now Setup Complete!</h3>\n";
    echo "<p>Your DesiDukaan website now supports:</p>\n";
    echo "<ul>\n";
    echo "<li>✅ Buy Now functionality</li>\n";
    echo "<li>✅ Order management for vendors</li>\n";
    echo "<li>✅ Email notifications</li>\n";
    echo "<li>✅ Order status tracking</li>\n";
    echo "<li>✅ Customer address management</li>\n";
    echo "<li>✅ Product reviews system (ready for future)</li>\n";
    echo "</ul>\n";
    
    echo "<h3>Next Steps:</h3>\n";
    echo "<ol>\n";
    echo "<li>Test the Buy Now functionality on product pages</li>\n";
    echo "<li>Check vendor order management in seller dashboard</li>\n";
    echo "<li>Verify email notifications are working</li>\n";
    echo "<li>Test order status updates</li>\n";
    echo "</ol>\n";
    
    echo "<p><a href='customer/dashboard.php' style='background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛍️ Test Buy Now Feature</a></p>\n";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Setup Failed!</h3>\n";
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database connection and try again.</p>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>DesiDukaan - Buy Now Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h2, h3 { color: #2c3e50; }
        p { margin: 5px 0; }
        ul, ol { margin: 10px 0; }
    </style>
</head>
<body>
    <!-- Content is echoed above -->
</body>
</html>
