<?php
/**
 * Debug Cart and Wishlist Issues
 */

require_once 'config/config.php';
require_once 'config/languages.php';

// Start session
session_start();

echo "<h1>🔍 Cart & Wishlist Debug Tool</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
    .debug-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .info { color: #007bff; }
    .warning { color: #ffc107; }
    h2 { color: #DC143C; border-bottom: 2px solid #DC143C; padding-bottom: 10px; }
    .btn { background: #DC143C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; border: none; cursor: pointer; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    .status-good { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; border-radius: 5px; }
    .status-bad { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 5px; }
</style>";

try {
    $db = getDB();
    
    // Debug 1: Session Information
    echo "<div class='debug-section'>";
    echo "<h2>1. Session Information</h2>";
    
    echo "<h3>Current Session Data:</h3>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    
    $session_status = "Unknown";
    switch (session_status()) {
        case PHP_SESSION_DISABLED:
            $session_status = "Sessions are disabled";
            break;
        case PHP_SESSION_NONE:
            $session_status = "Sessions are enabled, but none exists";
            break;
        case PHP_SESSION_ACTIVE:
            $session_status = "Sessions are enabled, and one exists";
            break;
    }
    echo "<p><strong>Session Status:</strong> $session_status</p>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    
    // Check authentication
    $is_logged_in = isset($_SESSION['user_id']);
    $user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'none';
    
    echo "<div class='" . ($is_logged_in ? 'status-good' : 'status-bad') . "'>";
    echo "<strong>Authentication Status:</strong> " . ($is_logged_in ? "✅ Logged In" : "❌ Not Logged In") . "<br>";
    echo "<strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
    echo "<strong>User Role:</strong> $user_role";
    echo "</div>";
    
    echo "</div>";
    
    // Debug 2: Database Connection
    echo "<div class='debug-section'>";
    echo "<h2>2. Database Connection</h2>";
    
    try {
        $stmt = $db->query("SELECT 1");
        echo "<div class='status-good'>✅ Database connection successful</div>";
        
        // Check required tables
        $tables = ['users', 'products', 'shops', 'wishlist'];
        foreach ($tables as $table) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->fetch()) {
                echo "<p class='success'>✅ Table '$table' exists</p>";
            } else {
                echo "<p class='error'>❌ Table '$table' missing</p>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='status-bad'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
    
    // Debug 3: User Information
    if ($is_logged_in) {
        echo "<div class='debug-section'>";
        echo "<h2>3. User Information</h2>";
        
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo "<div class='status-good'>✅ User found in database</div>";
                echo "<p><strong>Username:</strong> " . htmlspecialchars($user['username']) . "</p>";
                echo "<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
                echo "<p><strong>Role:</strong> " . htmlspecialchars($user['role']) . "</p>";
                echo "<p><strong>Active:</strong> " . ($user['is_active'] ? 'Yes' : 'No') . "</p>";
            } else {
                echo "<div class='status-bad'>❌ User not found in database</div>";
            }
        } catch (Exception $e) {
            echo "<div class='status-bad'>❌ Error fetching user: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
    }
    
    // Debug 4: Cart Information
    echo "<div class='debug-section'>";
    echo "<h2>4. Cart Information</h2>";
    
    echo "<h3>Session Cart Data:</h3>";
    echo "<pre>" . print_r($_SESSION['cart'] ?? [], true) . "</pre>";
    
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $cart_count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $cart_count += $item['quantity'];
        }
        echo "<p class='success'>✅ Cart has $cart_count items</p>";
    } else {
        echo "<p class='info'>ℹ️ Cart is empty</p>";
    }
    
    echo "</div>";
    
    // Debug 5: Wishlist Information
    if ($is_logged_in) {
        echo "<div class='debug-section'>";
        echo "<h2>5. Wishlist Information</h2>";
        
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM wishlist WHERE customer_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $wishlist_count = $stmt->fetch()['count'];
            
            echo "<p class='success'>✅ Wishlist has $wishlist_count items</p>";
            
            if ($wishlist_count > 0) {
                $stmt = $db->prepare("
                    SELECT w.*, p.title 
                    FROM wishlist w 
                    JOIN products p ON w.product_id = p.id 
                    WHERE w.customer_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $wishlist_items = $stmt->fetchAll();
                
                echo "<h3>Wishlist Items:</h3>";
                foreach ($wishlist_items as $item) {
                    echo "<p>• " . htmlspecialchars($item['title']) . " (ID: {$item['product_id']})</p>";
                }
            }
        } catch (Exception $e) {
            echo "<div class='status-bad'>❌ Error fetching wishlist: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
    }
    
    // Debug 6: File Permissions
    echo "<div class='debug-section'>";
    echo "<h2>6. File Permissions</h2>";
    
    $files_to_check = [
        'includes/cart.php',
        'includes/wishlist.php',
        'assets/js/customer.js'
    ];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            $readable = is_readable($file);
            echo "<p class='" . ($readable ? 'success' : 'error') . "'>";
            echo ($readable ? '✅' : '❌') . " $file - " . ($readable ? 'Readable' : 'Not readable');
            echo "</p>";
        } else {
            echo "<p class='error'>❌ $file - File not found</p>";
        }
    }
    
    echo "</div>";
    
    // Debug 7: Test AJAX Endpoints
    echo "<div class='debug-section'>";
    echo "<h2>7. Test AJAX Endpoints</h2>";
    
    echo "<div id='ajax-results'></div>";
    echo "<button class='btn' onclick='testCartEndpoint()'>🛒 Test Cart Endpoint</button>";
    echo "<button class='btn' onclick='testWishlistEndpoint()'>❤️ Test Wishlist Endpoint</button>";
    echo "<button class='btn' onclick='clearResults()'>🧹 Clear Results</button>";
    
    echo "</div>";
    
    // Debug 8: Quick Actions
    echo "<div class='debug-section'>";
    echo "<h2>8. Quick Actions</h2>";
    
    if (!$is_logged_in) {
        echo "<a href='auth/login.php' class='btn'>🔐 Login</a>";
        echo "<a href='auth/register.php' class='btn'>📝 Register</a>";
    } else {
        echo "<a href='customer/dashboard.php' class='btn'>🛍️ Dashboard</a>";
        echo "<a href='customer/cart.php' class='btn'>🛒 Cart</a>";
        echo "<a href='customer/wishlist.php' class='btn'>❤️ Wishlist</a>";
        echo "<a href='auth/logout.php' class='btn'>🚪 Logout</a>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='debug-section'>";
    echo "<h2>❌ Critical Error</h2>";
    echo "<div class='status-bad'>Error: " . $e->getMessage() . "</div>";
    echo "</div>";
}
?>

<script>
function addResult(message, type = 'info') {
    const results = document.getElementById('ajax-results');
    const timestamp = new Date().toLocaleTimeString();
    const color = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#007bff';
    results.innerHTML += `<p style="color: ${color}; margin: 5px 0;">[${timestamp}] ${message}</p>`;
}

function clearResults() {
    document.getElementById('ajax-results').innerHTML = '';
}

function testCartEndpoint() {
    addResult('Testing cart endpoint...', 'info');
    
    fetch('includes/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_cart_count'
    })
    .then(response => {
        addResult(`Cart endpoint response status: ${response.status}`, 'info');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            addResult(`✅ Cart endpoint working! Count: ${data.cart_count}`, 'success');
        } else {
            addResult(`❌ Cart endpoint error: ${data.message}`, 'error');
        }
    })
    .catch(error => {
        addResult(`❌ Cart endpoint failed: ${error}`, 'error');
    });
}

function testWishlistEndpoint() {
    addResult('Testing wishlist endpoint...', 'info');
    
    fetch('includes/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_wishlist_count'
    })
    .then(response => {
        addResult(`Wishlist endpoint response status: ${response.status}`, 'info');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            addResult(`✅ Wishlist endpoint working! Count: ${data.wishlist_count}`, 'success');
        } else {
            addResult(`❌ Wishlist endpoint error: ${data.message}`, 'error');
        }
    })
    .catch(error => {
        addResult(`❌ Wishlist endpoint failed: ${error}`, 'error');
    });
}

// Auto-run basic tests
document.addEventListener('DOMContentLoaded', function() {
    addResult('🎉 Debug page loaded successfully!', 'success');
});
</script>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Cart & Wishlist</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
</body>
</html>
