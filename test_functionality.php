<?php
/**
 * Test Cart and Wishlist Functionality
 */

require_once 'config/config.php';
require_once 'config/languages.php';
require_once 'includes/navbar.php';
require_once 'includes/cart.php';

// Start session and set up test customer
session_start();

// Set up test customer session if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'customer' LIMIT 1");
    $stmt->execute();
    $test_user = $stmt->fetch();
    
    if ($test_user) {
        $_SESSION['user_id'] = $test_user['id'];
        $_SESSION['role'] = 'customer';
    }
}

// Include wishlist functions
function isInWishlist($customer_id, $product_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM wishlist WHERE customer_id = ? AND product_id = ?");
        $stmt->execute([$customer_id, $product_id]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

// Get sample products
$db = getDB();
$stmt = $db->prepare("
    SELECT p.*, s.shop_name 
    FROM products p 
    JOIN shops s ON p.shop_id = s.id 
    WHERE p.is_active = 1 AND s.status = 'approved' 
    LIMIT 3
");
$stmt->execute();
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Functionality - DesiDukaan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/customer.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .test-container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
        }
        .test-header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .test-title {
            color: #DC143C;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .status-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .product-test-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .product-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }
        .product-info {
            flex: 1;
        }
        .product-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-test {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-cart {
            background: #DC143C;
            color: white;
        }
        .btn-cart:hover {
            background: #B91C3C;
        }
        .btn-wishlist {
            background: #f8f9fa;
            color: #DC143C;
            border: 2px solid #DC143C;
        }
        .btn-wishlist:hover {
            background: #DC143C;
            color: white;
        }
        .btn-wishlist.in-wishlist {
            background: #DC143C;
            color: white;
        }
        .results-area {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 10px;
            font-family: monospace;
            max-height: 300px;
            overflow-y: auto;
            margin: 20px 0;
        }
        .success { color: #2ecc71; }
        .error { color: #e74c3c; }
        .info { color: #3498db; }
        .nav-links {
            text-align: center;
            margin: 30px 0;
        }
        .nav-link {
            background: #667eea;
            color: white;
            padding: 15px 25px;
            text-decoration: none;
            border-radius: 25px;
            margin: 10px;
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php render_navbar('customer', ''); ?>

    <div class="test-container">
        <div class="test-header">
            <h1 class="test-title">🧪 Cart & Wishlist Functionality Test</h1>
            <p>Test the add to cart and wishlist functionality</p>
        </div>

        <div class="status-info">
            <h3>📊 Current Status</h3>
            <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
            <p><strong>Role:</strong> <?php echo $_SESSION['role'] ?? 'Not set'; ?></p>
            <p><strong>Cart Count:</strong> <span id="current-cart-count"><?php echo getCartItemsCount(); ?></span></p>
            <p><strong>Products Available:</strong> <?php echo count($products); ?></p>
        </div>

        <div class="results-area" id="test-results">
            <p class="info">[READY] Click the buttons below to test functionality...</p>
        </div>

        <h2>🛍️ Test Products</h2>
        
        <?php foreach ($products as $product): ?>
            <?php
            $images = $product['images'] ? json_decode($product['images'], true) : [];
            $first_image = !empty($images) ? $images[0] : null;
            $in_cart = isInCart($product['id']);
            $in_wishlist = isInWishlist($_SESSION['user_id'], $product['id']);
            ?>
            <div class="product-test-card" data-product-id="<?php echo $product['id']; ?>">
                <div class="product-image-container">
                    <?php if ($first_image && file_exists(UPLOAD_PATH . 'products/' . $first_image)): ?>
                        <img src="<?php echo UPLOAD_URL . 'products/' . $first_image; ?>" 
                             alt="<?php echo htmlspecialchars($product['title']); ?>"
                             class="product-image">
                    <?php else: ?>
                        <div class="product-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 2rem;">🎨</div>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                    <p>by <?php echo htmlspecialchars($product['shop_name']); ?></p>
                    <p><strong>Rs. <?php echo number_format($product['price'], 2); ?></strong></p>
                    <p><small>Product ID: <?php echo $product['id']; ?></small></p>
                </div>
                
                <div class="product-actions">
                    <button class="btn-test btn-cart" onclick="testAddToCart(<?php echo $product['id']; ?>)">
                        <?php echo $in_cart ? '✅ In Cart' : '🛒 Add to Cart'; ?>
                    </button>
                    
                    <button class="btn-test btn-wishlist <?php echo $in_wishlist ? 'in-wishlist' : ''; ?>" 
                            data-product-id="<?php echo $product['id']; ?>" 
                            onclick="testToggleWishlist(<?php echo $product['id']; ?>)">
                        <?php echo $in_wishlist ? '❤️ In Wishlist' : '🤍 Add to Wishlist'; ?>
                    </button>
                    
                    <button class="btn-test" style="background: #28a745; color: white;" onclick="testRemoveFromCart(<?php echo $product['id']; ?>)">
                        ❌ Remove from Cart
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="nav-links">
            <a href="customer/dashboard.php" class="nav-link">🚀 Go to Dashboard</a>
            <a href="customer/cart.php" class="nav-link">🛒 View Cart</a>
            <a href="customer/wishlist.php" class="nav-link">❤️ View Wishlist</a>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/customer.js"></script>
    
    <script>
        function addTestResult(message, type = 'info') {
            const results = document.getElementById('test-results');
            const timestamp = new Date().toLocaleTimeString();
            const className = type;
            results.innerHTML += `<p class="${className}">[${timestamp}] ${message}</p>`;
            results.scrollTop = results.scrollHeight;
        }
        
        function testAddToCart(productId) {
            addTestResult(`🛒 Testing Add to Cart for product ${productId}...`, 'info');
            addToCart(productId);
        }
        
        function testRemoveFromCart(productId) {
            addTestResult(`🗑️ Testing Remove from Cart for product ${productId}...`, 'info');
            removeFromCart(productId);
        }
        
        function testToggleWishlist(productId) {
            addTestResult(`💖 Testing Toggle Wishlist for product ${productId}...`, 'info');
            toggleWishlist(productId);
        }
        
        // Override the notification function to also log to test results
        const originalShowNotification = window.showNotification;
        window.showNotification = function(message, type) {
            addTestResult(message, type);
            if (originalShowNotification) {
                originalShowNotification(message, type);
            }
        };
        
        // Override updateCartCount to also update our display
        const originalUpdateCartCount = window.updateCartCount;
        window.updateCartCount = function(count) {
            if (originalUpdateCartCount) {
                originalUpdateCartCount(count);
            }
            if (count !== null && count !== undefined) {
                document.getElementById('current-cart-count').textContent = count;
            }
        };
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            addTestResult('🎉 Test page loaded successfully!', 'success');
        });
    </script>
</body>
</html>
