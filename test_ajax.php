<?php
/**
 * Simple AJAX Test for Cart and Wishlist
 */

require_once 'config/config.php';
require_once 'config/languages.php';

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
?>
<!DOCTYPE html>
<html>
<head>
    <title>AJAX Test - DesiDukaan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            background: #DC143C;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            margin: 5px;
            cursor: pointer;
        }
        .btn:hover {
            background: #B91C3C;
        }
        .results {
            background: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            max-height: 300px;
            overflow-y: auto;
            margin: 20px 0;
        }
        .success { color: #2ecc71; }
        .error { color: #e74c3c; }
        .info { color: #3498db; }
    </style>
</head>
<body>
    <h1>🧪 AJAX Endpoints Test</h1>
    
    <div class="test-section">
        <h2>Session Info</h2>
        <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
        <p><strong>Role:</strong> <?php echo $_SESSION['role'] ?? 'Not set'; ?></p>
    </div>
    
    <div class="test-section">
        <h2>Cart Tests</h2>
        <button class="btn" onclick="testAddToCart(1)">🛒 Add Product 1 to Cart</button>
        <button class="btn" onclick="testAddToCart(2)">🛒 Add Product 2 to Cart</button>
        <button class="btn" onclick="testRemoveFromCart(1)">❌ Remove Product 1</button>
        <button class="btn" onclick="testGetCartCount()">📊 Get Cart Count</button>
    </div>
    
    <div class="test-section">
        <h2>Wishlist Tests</h2>
        <button class="btn" onclick="testToggleWishlist(1)">❤️ Toggle Product 1 Wishlist</button>
        <button class="btn" onclick="testToggleWishlist(2)">❤️ Toggle Product 2 Wishlist</button>
        <button class="btn" onclick="testAddToWishlist(3)">💖 Add Product 3 to Wishlist</button>
        <button class="btn" onclick="testRemoveFromWishlist(3)">💔 Remove Product 3 from Wishlist</button>
    </div>
    
    <div class="test-section">
        <h2>Results</h2>
        <div class="results" id="results">
            <p class="info">Click buttons above to test AJAX endpoints...</p>
        </div>
    </div>

    <script>
        function addResult(message, type = 'info') {
            const results = document.getElementById('results');
            const timestamp = new Date().toLocaleTimeString();
            results.innerHTML += `<p class="${type}">[${timestamp}] ${message}</p>`;
            results.scrollTop = results.scrollHeight;
        }
        
        // Cart Tests
        function testAddToCart(productId) {
            addResult(`Testing Add to Cart for product ${productId}...`, 'info');
            
            fetch('includes/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_to_cart&product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addResult(`✅ SUCCESS: ${data.message} (Cart count: ${data.cart_count})`, 'success');
                } else {
                    addResult(`❌ FAILED: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                addResult(`❌ ERROR: ${error}`, 'error');
            });
        }
        
        function testRemoveFromCart(productId) {
            addResult(`Testing Remove from Cart for product ${productId}...`, 'info');
            
            fetch('includes/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_from_cart&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addResult(`✅ SUCCESS: ${data.message} (Cart count: ${data.cart_count})`, 'success');
                } else {
                    addResult(`❌ FAILED: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                addResult(`❌ ERROR: ${error}`, 'error');
            });
        }
        
        function testGetCartCount() {
            addResult(`Testing Get Cart Count...`, 'info');
            
            fetch('includes/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_cart_count'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addResult(`✅ SUCCESS: Cart has ${data.cart_count} items`, 'success');
                } else {
                    addResult(`❌ FAILED: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                addResult(`❌ ERROR: ${error}`, 'error');
            });
        }
        
        // Wishlist Tests
        function testToggleWishlist(productId) {
            addResult(`Testing Toggle Wishlist for product ${productId}...`, 'info');
            
            fetch('includes/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_wishlist&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const action = data.action === 'added' ? 'ADDED ❤️' : 'REMOVED 💔';
                    addResult(`✅ SUCCESS: ${action} - ${data.message} (Wishlist count: ${data.wishlist_count})`, 'success');
                } else {
                    addResult(`❌ FAILED: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                addResult(`❌ ERROR: ${error}`, 'error');
            });
        }
        
        function testAddToWishlist(productId) {
            addResult(`Testing Add to Wishlist for product ${productId}...`, 'info');
            
            fetch('includes/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add_to_wishlist&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addResult(`✅ SUCCESS: ${data.message} (Wishlist count: ${data.wishlist_count})`, 'success');
                } else {
                    addResult(`❌ FAILED: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                addResult(`❌ ERROR: ${error}`, 'error');
            });
        }
        
        function testRemoveFromWishlist(productId) {
            addResult(`Testing Remove from Wishlist for product ${productId}...`, 'info');
            
            fetch('includes/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_from_wishlist&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addResult(`✅ SUCCESS: ${data.message} (Wishlist count: ${data.wishlist_count})`, 'success');
                } else {
                    addResult(`❌ FAILED: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                addResult(`❌ ERROR: ${error}`, 'error');
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            addResult('🎉 AJAX Test page loaded!', 'success');
        });
    </script>
</body>
</html>
