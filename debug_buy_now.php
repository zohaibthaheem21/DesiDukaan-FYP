<?php
/**
 * Debug Buy Now Page
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Buy Now Page</h2>";

try {
    require_once 'config/config.php';
    echo "<p>✅ Config loaded successfully</p>";
    
    require_once 'config/languages.php';
    echo "<p>✅ Languages loaded successfully</p>";
    
    // Test database connection
    $db = getDB();
    echo "<p>✅ Database connection successful</p>";
    
    // Test product query
    $product_id = 6;
    $stmt = $db->prepare("
        SELECT p.*, s.shop_name, s.id as shop_id, s.seller_id, s.email as shop_email, s.phone_number as shop_phone,
               u.email as seller_email, u.username as seller_name
        FROM products p
        JOIN shops s ON p.shop_id = s.id
        JOIN users u ON s.seller_id = u.id
        WHERE p.id = ? AND p.is_active = 1 AND s.status = 'approved'
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        echo "<p>✅ Product found: " . htmlspecialchars($product['title']) . "</p>";
        echo "<p>Price: Rs. " . $product['price'] . "</p>";
        echo "<p>Shop: " . htmlspecialchars($product['shop_name']) . "</p>";
        
        // Test image processing
        $images = $product['images'] ? json_decode($product['images'], true) : [];
        $first_image = !empty($images) ? $images[0] : null;
        echo "<p>Images: " . count($images) . " found</p>";
        if ($first_image) {
            echo "<p>First image: " . htmlspecialchars($first_image) . "</p>";
        }
    } else {
        echo "<p>❌ Product not found or not available</p>";
    }
    
    // Test language functions
    echo "<p>Current language: " . get_current_language() . "</p>";
    echo "<p>Translation test: " . t('buy_now') . "</p>";
    echo "<p>Translation test: " . t('complete_your_purchase') . "</p>";
    
    // Test session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ User logged in: ID " . $_SESSION['user_id'] . "</p>";
    } else {
        echo "<p>❌ User not logged in</p>";
    }
    
    echo "<h3>Available Products:</h3>";
    $stmt = $db->query("SELECT id, title, price FROM products WHERE is_active = 1 LIMIT 5");
    $products = $stmt->fetchAll();
    foreach ($products as $p) {
        echo "<p>ID: {$p['id']} - {$p['title']} - Rs. {$p['price']}</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Buy Now</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        p { margin: 5px 0; }
    </style>
</head>
<body>
    <h3>Test Links:</h3>
    <p><a href="customer/buy_now.php?product_id=6&quantity=1">Test Buy Now Page</a></p>
    <p><a href="customer/dashboard.php">Customer Dashboard</a></p>
    <p><a href="auth/login.php">Login Page</a></p>
</body>
</html>
