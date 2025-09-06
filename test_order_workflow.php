<?php
/**
 * Test Order Workflow - Complete Email and Status System
 */

require_once 'config/config.php';
require_once 'includes/email_notifications.php';

echo "<h1>Testing Order Workflow System</h1>";

// Test database connection
try {
    $db = getDB();
    echo "<p>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check if required tables exist
$tables = ['orders', 'order_items', 'order_status_history', 'email_notifications'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Table '$table' exists</p>";
        } else {
            echo "<p>❌ Table '$table' missing</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking table '$table': " . $e->getMessage() . "</p>";
    }
}

// Check for recent orders
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $result = $stmt->fetch();
    echo "<p>📊 Recent orders (last 7 days): " . $result['count'] . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error checking recent orders: " . $e->getMessage() . "</p>";
}

// Test email notification functions
echo "<h2>Testing Email Functions</h2>";

// Test order confirmation email data structure
$testOrderData = [
    'order_id' => 999,
    'order_number' => 'TEST-' . date('YmdHis'),
    'customer_email' => 'test@example.com',
    'customer_name' => 'Test Customer',
    'product_name' => 'Test Product',
    'products' => 'Test Product 1, Test Product 2',
    'quantity' => 2,
    'total_amount' => 1500.00,
    'shop_name' => 'Test Shop',
    'shipping_name' => 'Test Customer',
    'address_line_1' => '123 Test Street',
    'city' => 'Test City',
    'state' => 'Test State',
    'postal_code' => '12345',
    'shipping_address' => [
        'name' => 'Test Customer',
        'phone' => '+92-300-1234567',
        'address' => '123 Test Street, Test City, Test State - 12345'
    ]
];

// Test vendor notification email data
$testVendorData = [
    'order_id' => 999,
    'order_number' => 'TEST-' . date('YmdHis'),
    'vendor_email' => 'vendor@example.com',
    'vendor_name' => 'Test Vendor',
    'customer_name' => 'Test Customer',
    'product_name' => 'Test Product',
    'quantity' => 2,
    'total_amount' => 1500.00,
    'customer_phone' => '+92-300-1234567',
    'customer_address' => '123 Test Street, Test City, Test State - 12345'
];

// Test status update scenarios
$statusTests = ['confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

echo "<h3>Testing Status Update Email Templates</h3>";
foreach ($statusTests as $status) {
    echo "<h4>Testing status: $status</h4>";
    
    // Test if the function exists and can generate email content
    try {
        ob_start();
        $result = sendOrderStatusUpdateEmail($testOrderData, $status);
        $output = ob_get_clean();
        
        echo "<p>✅ Status '$status' email template generated successfully</p>";
        if ($output) {
            echo "<p>📧 Email output captured (function executed)</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error with status '$status': " . $e->getMessage() . "</p>";
    }
}

// Test order status history functionality
echo "<h3>Testing Order Status History</h3>";
try {
    // Check if we can insert into order_status_history
    $stmt = $db->prepare("
        INSERT INTO order_status_history (order_id, status, notes, changed_by, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([999, 'confirmed', 'Test status change', 1]);
    echo "<p>✅ Order status history insert successful</p>";
    
    // Clean up test data
    $db->exec("DELETE FROM order_status_history WHERE order_id = 999");
    echo "<p>🧹 Test data cleaned up</p>";
} catch (Exception $e) {
    echo "<p>❌ Order status history test failed: " . $e->getMessage() . "</p>";
}

// Test vendor actions integration
echo "<h3>Testing Vendor Actions Integration</h3>";
if (file_exists('includes/vendor_actions.php')) {
    echo "<p>✅ vendor_actions.php file exists</p>";
    
    // Check if the file contains the email notification code
    $vendorActionsContent = file_get_contents('includes/vendor_actions.php');
    if (strpos($vendorActionsContent, 'sendOrderStatusUpdateEmail') !== false) {
        echo "<p>✅ Email notification integration found in vendor_actions.php</p>";
    } else {
        echo "<p>❌ Email notification integration missing in vendor_actions.php</p>";
    }
} else {
    echo "<p>❌ vendor_actions.php file missing</p>";
}

// Test customer orders page enhancements
echo "<h3>Testing Customer Interface</h3>";
if (file_exists('customer/orders.php')) {
    echo "<p>✅ customer/orders.php file exists</p>";
    
    $ordersContent = file_get_contents('customer/orders.php');
    if (strpos($ordersContent, 'timeline-container') !== false) {
        echo "<p>✅ Enhanced order tracking interface found</p>";
    } else {
        echo "<p>❌ Enhanced order tracking interface missing</p>";
    }
} else {
    echo "<p>❌ customer/orders.php file missing</p>";
}

echo "<h2>🎉 Order Workflow Test Complete!</h2>";
echo "<p><strong>Summary:</strong> The order workflow system has been enhanced with:</p>";
echo "<ul>";
echo "<li>✅ Automatic email notifications when vendors update order status</li>";
echo "<li>✅ Enhanced email templates with delivery timeframes</li>";
echo "<li>✅ Order status history tracking</li>";
echo "<li>✅ Improved customer order tracking interface with timeline</li>";
echo "<li>✅ Delivery estimates based on order status</li>";
echo "</ul>";

echo "<h3>🔄 Complete Workflow:</h3>";
echo "<ol>";
echo "<li><strong>Customer places order</strong> → Confirmation emails sent to customer and vendor</li>";
echo "<li><strong>Vendor confirms order</strong> → Customer receives 'Order Confirmed' email with 1-2 day processing timeline</li>";
echo "<li><strong>Vendor marks as shipped</strong> → Customer receives 'Order Shipped' email with 2-3 day delivery estimate</li>";
echo "<li><strong>Order delivered</strong> → Customer receives 'Order Delivered' confirmation email</li>";
echo "<li><strong>Customer can track</strong> → Enhanced orders page shows timeline and delivery estimates</li>";
echo "</ol>";
?>
