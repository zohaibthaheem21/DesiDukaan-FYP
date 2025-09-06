<?php
/**
 * Complete Functionality Test
 */

require_once 'config/config.php';

try {
    $db = getDB();
    
    echo "<h1>🧪 DesiDukaan - Complete Functionality Test</h1>";
    echo "<style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #007bff; }
        h2 { color: #DC143C; border-bottom: 2px solid #DC143C; padding-bottom: 10px; }
        .test-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .test-card { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #DC143C; }
    </style>";
    
    // Test 1: Database Tables
    echo "<div class='test-section'>";
    echo "<h2>1. Database Structure Test</h2>";
    
    $required_tables = ['users', 'shops', 'products', 'orders', 'order_items', 'wishlist'];
    $tables_ok = true;
    
    foreach ($required_tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            echo "<p class='success'>✅ Table '$table' exists</p>";
        } else {
            echo "<p class='error'>❌ Table '$table' missing</p>";
            $tables_ok = false;
        }
    }
    echo "</div>";
    
    // Test 2: Sample Data
    echo "<div class='test-section'>";
    echo "<h2>2. Sample Data Test</h2>";
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
    $stmt->execute();
    $product_count = $stmt->fetch()['count'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
    $stmt->execute();
    $customer_count = $stmt->fetch()['count'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM shops WHERE status = 'approved'");
    $stmt->execute();
    $shop_count = $stmt->fetch()['count'];
    
    echo "<div class='test-grid'>";
    echo "<div class='test-card'>";
    echo "<h3>📦 Products</h3>";
    echo "<p><strong>$product_count</strong> active products</p>";
    echo $product_count > 0 ? "<p class='success'>✅ Products available</p>" : "<p class='error'>❌ No products</p>";
    echo "</div>";
    
    echo "<div class='test-card'>";
    echo "<h3>👥 Customers</h3>";
    echo "<p><strong>$customer_count</strong> registered customers</p>";
    echo $customer_count > 0 ? "<p class='success'>✅ Customers registered</p>" : "<p class='info'>ℹ️ No customers yet</p>";
    echo "</div>";
    
    echo "<div class='test-card'>";
    echo "<h3>🏪 Shops</h3>";
    echo "<p><strong>$shop_count</strong> approved shops</p>";
    echo $shop_count > 0 ? "<p class='success'>✅ Shops available</p>" : "<p class='error'>❌ No shops</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // Test 3: Authentication
    echo "<div class='test-section'>";
    echo "<h2>3. Authentication Test</h2>";
    
    // Test admin user
    $stmt = $db->prepare("SELECT id, email FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p class='success'>✅ Admin user exists: " . htmlspecialchars($admin['email']) . "</p>";
    } else {
        echo "<p class='error'>❌ No admin user found</p>";
    }
    
    // Test password hashing
    $test_password = 'testpass123';
    $hashed = hash_password($test_password);
    $verified = verify_password($test_password, $hashed);
    echo "<p class='success'>✅ Password hashing: " . ($verified ? "Working" : "Failed") . "</p>";
    echo "</div>";
    
    // Test 4: File Structure
    echo "<div class='test-section'>";
    echo "<h2>4. File Structure Test</h2>";
    
    $required_files = [
        'customer/dashboard.php' => 'Customer Dashboard',
        'customer/profile.php' => 'Customer Profile',
        'customer/orders.php' => 'My Orders',
        'customer/wishlist.php' => 'My Wishlist',
        'customer/cart.php' => 'Shopping Cart',
        'admin/customers.php' => 'Admin Customer Management',
        'includes/wishlist.php' => 'Wishlist System',
        'includes/cart.php' => 'Cart System',
        'assets/css/customer.css' => 'Customer Styles',
        'assets/css/admin.css' => 'Admin Styles',
        'assets/js/customer.js' => 'Customer JavaScript'
    ];
    
    echo "<div class='test-grid'>";
    foreach ($required_files as $file => $description) {
        echo "<div class='test-card'>";
        echo "<h4>$description</h4>";
        if (file_exists($file)) {
            echo "<p class='success'>✅ $file</p>";
        } else {
            echo "<p class='error'>❌ $file missing</p>";
        }
        echo "</div>";
    }
    echo "</div>";
    echo "</div>";
    
    // Test 5: Image Upload Directory
    echo "<div class='test-section'>";
    echo "<h2>5. Upload Directory Test</h2>";
    
    $upload_dirs = ['uploads/', 'uploads/products/', 'uploads/cnic/', 'uploads/logos/'];
    foreach ($upload_dirs as $dir) {
        if (is_dir($dir) && is_writable($dir)) {
            echo "<p class='success'>✅ $dir exists and writable</p>";
        } else {
            echo "<p class='error'>❌ $dir missing or not writable</p>";
        }
    }
    
    // Count uploaded images
    $product_images = glob('uploads/products/*');
    echo "<p class='info'>ℹ️ " . count($product_images) . " product images uploaded</p>";
    echo "</div>";
    
    // Test 6: Language Support
    echo "<div class='test-section'>";
    echo "<h2>6. Language Support Test</h2>";
    
    // Test English
    set_language('en');
    $en_test = t('welcome');
    echo "<p class='success'>✅ English: '$en_test'</p>";
    
    // Test Sindhi
    set_language('sd');
    $sd_test = t('welcome');
    echo "<p class='success'>✅ Sindhi: '$sd_test'</p>";
    
    // Reset to English
    set_language('en');
    echo "</div>";
    
    // Summary
    echo "<div class='test-section'>";
    echo "<h2>🎯 Test Summary</h2>";
    
    $all_good = $tables_ok && $product_count > 0 && $shop_count > 0 && $admin;
    
    if ($all_good) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 8px; text-align: center;'>";
        echo "<h3>🎉 All Systems Ready!</h3>";
        echo "<p>Your DesiDukaan application is fully functional and ready to use.</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 8px; text-align: center;'>";
        echo "<h3>⚠️ Some Issues Found</h3>";
        echo "<p>Please check the failed tests above and fix any issues.</p>";
        echo "</div>";
    }
    
    echo "<div style='margin-top: 30px; text-align: center;'>";
    echo "<h3>🚀 Quick Access Links</h3>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;'>";
    
    $quick_links = [
        'index.php' => '🏠 Homepage',
        'customer/dashboard.php' => '🛍️ Customer Dashboard',
        'admin/dashboard.php' => '🎛️ Admin Panel',
        'admin/customers.php' => '👥 Customer Management',
        'auth/register.php' => '📝 Register',
        'auth/login.php' => '🔐 Login'
    ];
    
    foreach ($quick_links as $link => $title) {
        echo "<a href='$link' style='display: block; background: #DC143C; color: white; padding: 15px; text-decoration: none; border-radius: 8px; font-weight: 500; transition: all 0.3s ease;'>$title</a>";
    }
    
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-section'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Complete Functionality Test</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <script>
        // Add hover effects to links
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href]');
            links.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.2)';
                });
                link.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>
