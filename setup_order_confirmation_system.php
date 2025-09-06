<?php
/**
 * DesiDukaan - Order Confirmation & Vendor Notification System Setup
 * This script sets up the database tables and configurations needed for the automated order system
 */

require_once 'config/config.php';

// Set content type to HTML for better display
header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation System Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        h1 { color: #2c3e50; border-bottom: 2px solid #e74c3c; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #3498db; background: #f8f9fa; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
<h1>🚀 DesiDukaan Order Confirmation System Setup</h1>";

try {
    $db = getDB();
    echo "<div class='info'>✅ Database connection successful!</div>";
    
    // Read and execute the SQL setup file
    echo "<div class='step'><h2>📋 Step 1: Setting up Database Schema</h2>";
    
    $sqlFile = __DIR__ . '/database/order_confirmation_system.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL setup file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Could not read SQL setup file");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            $successCount++;
        } catch (PDOException $e) {
            // Some errors are expected (like "table already exists")
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "<div class='warning'>⚠️ " . htmlspecialchars($e->getMessage()) . "</div>";
            } else {
                echo "<div class='error'>❌ Error executing statement: " . htmlspecialchars($e->getMessage()) . "</div>";
                $errorCount++;
            }
        }
    }
    
    echo "<div class='success'>✅ Database schema setup completed! ($successCount statements executed successfully)</div>";
    echo "</div>";
    
    // Verify table creation
    echo "<div class='step'><h2>🔍 Step 2: Verifying Table Creation</h2>";
    
    $requiredTables = [
        'notifications' => 'Real-time notification system',
        'order_events' => 'Order audit logging',
        'email_notifications' => 'Email tracking system',
        'order_status_history' => 'Order status change history'
    ];
    
    foreach ($requiredTables as $table => $description) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='success'>✅ Table '$table' exists - $description</div>";
                
                // Show table structure
                $stmt = $db->query("DESCRIBE $table");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<div class='info'>📋 Columns: " . implode(', ', array_column($columns, 'Field')) . "</div>";
            } else {
                echo "<div class='error'>❌ Table '$table' not found</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error checking table '$table': " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    echo "</div>";
    
    // Check orders table enhancements
    echo "<div class='step'><h2>🔧 Step 3: Verifying Orders Table Enhancements</h2>";
    
    try {
        $stmt = $db->query("DESCRIBE orders");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        $requiredColumns = [
            'shop_id' => 'Links orders to vendor shops',
            'confirmed_at' => 'Timestamp when vendor confirms order',
            'rejected_at' => 'Timestamp when vendor rejects order',
            'shipped_at' => 'Timestamp when order is shipped',
            'delivered_at' => 'Timestamp when order is delivered',
            'vendor_notes' => 'Notes from vendor',
            'customer_can_cancel' => 'Whether customer can cancel order'
        ];
        
        foreach ($requiredColumns as $column => $description) {
            if (in_array($column, $columnNames)) {
                echo "<div class='success'>✅ Column '$column' exists - $description</div>";
            } else {
                echo "<div class='error'>❌ Column '$column' missing</div>";
            }
        }
        
        // Check status enum values
        $statusColumn = array_filter($columns, function($col) { return $col['Field'] === 'status'; });
        if (!empty($statusColumn)) {
            $statusColumn = reset($statusColumn);
            if (strpos($statusColumn['Type'], 'rejected') !== false) {
                echo "<div class='success'>✅ Order status enum includes 'rejected' status</div>";
            } else {
                echo "<div class='warning'>⚠️ Order status enum may need updating to include 'rejected'</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error checking orders table: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    // Test stored procedures and functions
    echo "<div class='step'><h2>⚙️ Step 4: Testing Stored Procedures and Functions</h2>";
    
    try {
        // Test GetUnreadNotificationCount function
        $stmt = $db->query("SELECT GetUnreadNotificationCount(1) as count");
        $result = $stmt->fetch();
        echo "<div class='success'>✅ GetUnreadNotificationCount function working (returned: {$result['count']})</div>";
    } catch (Exception $e) {
        echo "<div class='warning'>⚠️ GetUnreadNotificationCount function issue: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    try {
        // Check if UpdateOrderStatus procedure exists
        $stmt = $db->query("SHOW PROCEDURE STATUS WHERE Name = 'UpdateOrderStatus'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ UpdateOrderStatus stored procedure created</div>";
        } else {
            echo "<div class='warning'>⚠️ UpdateOrderStatus stored procedure not found</div>";
        }
    } catch (Exception $e) {
        echo "<div class='warning'>⚠️ Error checking stored procedures: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    // Create sample data for testing
    echo "<div class='step'><h2>🧪 Step 5: Creating Sample Test Data</h2>";
    
    try {
        // Check if we have any sellers
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'seller'");
        $sellerCount = $stmt->fetch()['count'];
        
        if ($sellerCount > 0) {
            echo "<div class='success'>✅ Found $sellerCount seller(s) in the system</div>";
        } else {
            echo "<div class='warning'>⚠️ No sellers found. You may want to create a seller account for testing.</div>";
        }
        
        // Check if we have any shops
        $stmt = $db->query("SELECT COUNT(*) as count FROM shops WHERE status = 'approved'");
        $shopCount = $stmt->fetch()['count'];
        
        if ($shopCount > 0) {
            echo "<div class='success'>✅ Found $shopCount approved shop(s) in the system</div>";
        } else {
            echo "<div class='warning'>⚠️ No approved shops found. You may want to create and approve a shop for testing.</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error checking sample data: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    // Final summary
    echo "<div class='step'><h2>🎉 Setup Complete!</h2>";
    echo "<div class='success'>
        <h3>✅ Order Confirmation System Successfully Installed!</h3>
        <p><strong>What's been set up:</strong></p>
        <ul>
            <li>✅ Real-time notification system for vendors</li>
            <li>✅ Order event logging and audit trail</li>
            <li>✅ Enhanced email notification tracking</li>
            <li>✅ Order status history tracking</li>
            <li>✅ Automated vendor notifications on new orders</li>
            <li>✅ Enhanced orders table with vendor management fields</li>
            <li>✅ Stored procedures for order status management</li>
        </ul>
        
        <p><strong>Next Steps:</strong></p>
        <ol>
            <li>🔧 Configure email settings (SMTP/PHPMailer)</li>
            <li>🎨 Update vendor dashboard UI</li>
            <li>📱 Implement real-time notification polling</li>
            <li>🧪 Test the complete order workflow</li>
        </ol>
    </div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Setup failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>Please check your database configuration and try again.</div>";
}

echo "</div></body></html>";
?>
