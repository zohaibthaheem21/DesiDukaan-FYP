<?php
/**
 * Debug Vendor Orders Page Issues
 */

require_once 'config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>🔍 Debug Vendor Orders Page</h1>";
echo "<p>This script debugs why the vendor orders page might be showing blank.</p>";

try {
    $db = getDB();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check if user is logged in
    echo "<h2>1. Session Check</h2>";
    if (isset($_SESSION['user_id'])) {
        echo "<p style='color: green;'>✅ User logged in: ID {$_SESSION['user_id']}</p>";
        
        if (isset($_SESSION['role'])) {
            echo "<p style='color: green;'>✅ User role: {$_SESSION['role']}</p>";
        } else {
            echo "<p style='color: red;'>❌ No role in session</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No user logged in</p>";
        echo "<p><a href='auth/login.php'>Login here</a> and then run this debug script again.</p>";
    }
    
    // Check shops table
    echo "<h2>2. Shop Check</h2>";
    
    if (isset($_SESSION['user_id'])) {
        $stmt = $db->prepare("SELECT * FROM shops WHERE seller_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $shop = $stmt->fetch();
        
        if ($shop) {
            echo "<p style='color: green;'>✅ Shop found: ID {$shop['id']}, Name: {$shop['shop_name']}, Status: {$shop['status']}</p>";
            $shop_id = $shop['id'];
        } else {
            echo "<p style='color: red;'>❌ No shop found for user {$_SESSION['user_id']}</p>";
            echo "<p>Create a shop first: <a href='seller/shop_setup.php'>Shop Setup</a></p>";
            $shop_id = 0;
        }
    } else {
        $shop_id = 0;
    }
    
    // Check orders for this shop
    echo "<h2>3. Orders Check</h2>";
    
    if ($shop_id > 0) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE shop_id = ?");
        $stmt->execute([$shop_id]);
        $order_count = $stmt->fetchColumn();
        
        echo "<p>Orders for shop $shop_id: $order_count</p>";
        
        if ($order_count > 0) {
            // Get sample orders
            $stmt = $db->prepare("SELECT id, order_number, status, total_amount, customer_id FROM orders WHERE shop_id = ? LIMIT 3");
            $stmt->execute([$shop_id]);
            $orders = $stmt->fetchAll();
            
            echo "<p style='color: green;'>✅ Sample orders:</p>";
            echo "<ul>";
            foreach ($orders as $order) {
                echo "<li>Order #{$order['order_number']}, Status: {$order['status']}, Amount: Rs. {$order['total_amount']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠️ No orders found for this shop</p>";
        }
    }
    
    // Test the vendor orders query
    echo "<h2>4. Query Test</h2>";
    
    if ($shop_id > 0) {
        try {
            $stmt = $db->prepare("
                SELECT o.*,
                       COALESCE(u.full_name, u.username) as customer_name,
                       u.email as customer_email,
                       COALESCE(u.phone, '') as customer_phone,
                       COALESCE(sa.customer_name, '') as shipping_name,
                       COALESCE(sa.phone_number, '') as phone_number,
                       COALESCE(sa.address_line_1, '') as address_line_1,
                       COALESCE(sa.city, '') as city,
                       COALESCE(sa.state, '') as state,
                       COALESCE(sa.postal_code, '') as postal_code,
                       GROUP_CONCAT(COALESCE(oi.product_name, 'Unknown Product') SEPARATOR ', ') as products,
                       COUNT(oi.id) as item_count,
                       SUM(COALESCE(oi.quantity, 0)) as total_quantity
                FROM orders o
                JOIN users u ON o.customer_id = u.id
                LEFT JOIN shipping_addresses sa ON o.id = sa.order_id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.shop_id = ?
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$shop_id]);
            $test_orders = $stmt->fetchAll();
            
            echo "<p style='color: green;'>✅ Vendor orders query executed successfully</p>";
            echo "<p>Query returned " . count($test_orders) . " orders</p>";
            
            if (!empty($test_orders)) {
                echo "<p style='color: green;'>✅ Sample query results:</p>";
                echo "<ul>";
                foreach ($test_orders as $order) {
                    echo "<li>Order #{$order['order_number']}: Customer {$order['customer_name']}, Products: {$order['products']}</li>";
                }
                echo "</ul>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Vendor orders query failed: " . $e->getMessage() . "</p>";
        }
    }
    
    // Check required files
    echo "<h2>5. File Check</h2>";
    
    $required_files = [
        'seller/orders.php' => 'Vendor orders page',
        'includes/seller_navbar.php' => 'Seller navbar',
        'config/config.php' => 'Configuration',
        'config/languages.php' => 'Language configuration'
    ];
    
    foreach ($required_files as $file => $description) {
        if (file_exists($file)) {
            echo "<p style='color: green;'>✅ $description ($file)</p>";
        } else {
            echo "<p style='color: red;'>❌ $description ($file) - Missing</p>";
        }
    }
    
    // Check database tables
    echo "<h2>6. Database Tables Check</h2>";
    
    $required_tables = ['orders', 'order_items', 'shipping_addresses', 'users', 'shops', 'products'];
    
    foreach ($required_tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing</p>";
        }
    }
    
    // Check user table columns
    echo "<h2>7. User Table Columns Check</h2>";
    
    $stmt = $db->query("DESCRIBE users");
    $user_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>User table columns:</strong> " . implode(', ', $user_columns) . "</p>";
    
    $required_user_columns = ['id', 'username', 'email', 'role'];
    $missing_columns = array_diff($required_user_columns, $user_columns);
    
    if (empty($missing_columns)) {
        echo "<p style='color: green;'>✅ All required user columns exist</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing user columns: " . implode(', ', $missing_columns) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Debug failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🎯 Debug Summary</h2>";

echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3 style='color: #856404; margin-top: 0;'>💡 Common Issues and Solutions:</h3>";
echo "<ul style='color: #856404;'>";
echo "<li><strong>User not logged in:</strong> Login as seller first</li>";
echo "<li><strong>No shop found:</strong> Create and set up a shop</li>";
echo "<li><strong>No orders:</strong> Place some test orders</li>";
echo "<li><strong>Query errors:</strong> Check database table structure</li>";
echo "<li><strong>Missing files:</strong> Ensure all required files exist</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🔗 Quick Actions:</h3>";
echo "<div style='display: flex; gap: 10px; flex-wrap: wrap; margin: 20px 0;'>";
echo "<a href='auth/login.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; display: inline-block;'>🔐 Login</a>";
echo "<a href='seller/shop_setup.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; display: inline-block;'>🏪 Setup Shop</a>";
echo "<a href='seller/orders.php' style='background: #17a2b8; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; display: inline-block;'>📦 Try Orders Page</a>";
echo "<a href='customer/dashboard.php' style='background: #6c757d; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; display: inline-block;'>🛒 Place Test Order</a>";
echo "</div>";

echo "<p style='margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 4px; font-size: 0.9em; color: #6c757d;'>";
echo "<strong>🔍 Debug complete!</strong><br>";
echo "Use the information above to identify and fix the vendor orders page issues.";
echo "</p>";
?>
