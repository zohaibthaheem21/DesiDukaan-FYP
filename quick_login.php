<?php
/**
 * Quick Login for Testing
 */

require_once 'config/config.php';
require_once 'config/languages.php';

session_start();

// Handle quick login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_login'])) {
    $db = getDB();
    
    // Get first customer user
    $stmt = $db->prepare("SELECT * FROM users WHERE role = 'customer' LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username'] = $user['username'];
        
        $success = "Logged in as: " . $user['username'];
    } else {
        $error = "No customer users found";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    session_start();
    $success = "Logged out successfully";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Login - DesiDukaan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 100px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            text-align: center;
        }
        .login-title {
            color: #DC143C;
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        .status-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
        .btn {
            background: #DC143C;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 10px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: #B91C3C;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .links {
            margin-top: 30px;
        }
        .links a {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">🚀 Quick Login</h1>
        <p>Quick login for testing cart and wishlist functionality</p>
        
        <?php if (isset($success)): ?>
            <div class="success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="status-info">
            <h3>Current Status:</h3>
            <p><strong>Logged In:</strong> <?php echo isset($_SESSION['user_id']) ? 'Yes' : 'No'; ?></p>
            <?php if (isset($_SESSION['user_id'])): ?>
                <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
                <p><strong>Username:</strong> <?php echo $_SESSION['username'] ?? 'Unknown'; ?></p>
                <p><strong>Role:</strong> <?php echo $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'Unknown'; ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <form method="POST">
                <button type="submit" name="quick_login" class="btn">
                    🔐 Quick Login as Customer
                </button>
            </form>
            <p><small>This will log you in as the first customer user in the database</small></p>
        <?php else: ?>
            <a href="?logout=1" class="btn btn-secondary">🚪 Logout</a>
        <?php endif; ?>
        
        <div class="links">
            <h3>Test Pages:</h3>
            <a href="debug_cart_wishlist.php" class="btn">🔍 Debug Tool</a>
            <a href="customer/dashboard.php" class="btn">🛍️ Dashboard</a>
            <a href="customer/cart.php" class="btn">🛒 Cart</a>
            <a href="customer/wishlist.php" class="btn">❤️ Wishlist</a>
        </div>
        
        <div class="links">
            <h3>Regular Login:</h3>
            <a href="auth/login.php" class="btn btn-secondary">🔐 Regular Login</a>
            <a href="auth/register.php" class="btn btn-secondary">📝 Register</a>
        </div>
    </div>
</body>
</html>
