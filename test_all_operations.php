<?php
/**
 * Comprehensive Test for DesiDukaan Database Operations
 */

require_once 'config/config.php';

echo "<h1>🧪 DesiDukaan - Comprehensive Database Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
    .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .info { color: #007bff; }
    h2 { color: #DC143C; border-bottom: 2px solid #DC143C; padding-bottom: 10px; }
</style>";

$tests_passed = 0;
$tests_failed = 0;

function test_result($test_name, $success, $message = '') {
    global $tests_passed, $tests_failed;
    if ($success) {
        $tests_passed++;
        echo "<p class='success'>✅ $test_name: PASSED</p>";
    } else {
        $tests_failed++;
        echo "<p class='error'>❌ $test_name: FAILED - $message</p>";
    }
}

// Test 1: Database Connection
echo "<div class='test-section'>";
echo "<h2>1. Database Connection Test</h2>";
try {
    $db = getDB();
    test_result("Database Connection", true);
} catch (Exception $e) {
    test_result("Database Connection", false, $e->getMessage());
    exit;
}

// Test 2: Check Tables Exist
echo "<h2>2. Database Tables Test</h2>";
$required_tables = ['users', 'shops', 'products', 'admin_logs', 'user_sessions'];
foreach ($required_tables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        test_result("Table '$table' exists", $exists);
    } catch (Exception $e) {
        test_result("Table '$table' exists", false, $e->getMessage());
    }
}

// Test 3: Admin User Test
echo "<h2>3. Admin User Test</h2>";
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE role = 'admin' AND email = 'admin@desidukaan.com'");
    $stmt->execute();
    $admin = $stmt->fetch();
    test_result("Admin user exists", !empty($admin));
    
    if ($admin) {
        $password_valid = password_verify('password', $admin['password_hash']);
        test_result("Admin password is correct", $password_valid);
    }
} catch (Exception $e) {
    test_result("Admin user check", false, $e->getMessage());
}

// Test 4: Products Table Schema Test
echo "<h2>4. Products Table Schema Test</h2>";
try {
    $stmt = $db->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = ['id', 'shop_id', 'title', 'description', 'price', 'quantity', 'sizes', 'colors', 'images'];
    foreach ($required_columns as $column) {
        $exists = in_array($column, $columns);
        test_result("Products table has '$column' column", $exists);
    }
} catch (Exception $e) {
    test_result("Products table schema check", false, $e->getMessage());
}

// Test 5: User Registration Simulation
echo "<h2>5. User Registration Test</h2>";
try {
    // Test customer registration
    $test_email = 'test_customer_' . time() . '@example.com';
    $test_username = 'test_customer_' . time();
    $password_hash = hash_password('testpassword123');
    
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'customer')");
    $result = $stmt->execute([$test_username, $test_email, $password_hash]);
    test_result("Customer registration", $result);
    
    // Clean up test user
    $stmt = $db->prepare("DELETE FROM users WHERE email = ?");
    $stmt->execute([$test_email]);
    
} catch (Exception $e) {
    test_result("Customer registration", false, $e->getMessage());
}

// Test 6: Shop Creation Simulation
echo "<h2>6. Shop Creation Test</h2>";
try {
    // Create a test seller first
    $test_seller_email = 'test_seller_' . time() . '@example.com';
    $test_seller_username = 'test_seller_' . time();
    $password_hash = hash_password('testpassword123');
    
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'seller')");
    $stmt->execute([$test_seller_username, $test_seller_email, $password_hash]);
    $seller_id = $db->lastInsertId();
    
    // Create a test shop
    $stmt = $db->prepare("INSERT INTO shops (seller_id, shop_name, address, email, phone_number, cnic_number, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $result = $stmt->execute([$seller_id, 'Test Shop', 'Test Address', $test_seller_email, '03001234567', '1234567890123']);
    test_result("Shop creation", $result);
    
    // Clean up test data
    $stmt = $db->prepare("DELETE FROM shops WHERE seller_id = ?");
    $stmt->execute([$seller_id]);
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$seller_id]);
    
} catch (Exception $e) {
    test_result("Shop creation", false, $e->getMessage());
}

echo "</div>";

// Summary
echo "<div class='test-section'>";
echo "<h2>📊 Test Summary</h2>";
echo "<p class='success'><strong>Tests Passed: $tests_passed</strong></p>";
echo "<p class='error'><strong>Tests Failed: $tests_failed</strong></p>";

if ($tests_failed === 0) {
    echo "<p class='success'><strong>🎉 All tests passed! Your DesiDukaan application is ready to use.</strong></p>";
    echo "<p class='info'><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Visit <a href='index.php'>DesiDukaan Homepage</a></li>";
    echo "<li>Login as admin: admin@desidukaan.com / password</li>";
    echo "<li>Register as a customer or seller to test the full functionality</li>";
    echo "</ul>";
} else {
    echo "<p class='error'><strong>⚠️ Some tests failed. Please check the errors above and fix them before using the application.</strong></p>";
}
echo "</div>";
?>
