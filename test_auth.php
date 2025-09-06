<?php
/**
 * Test Authentication System
 */

require_once 'config/config.php';

try {
    $db = getDB();
    
    echo "<h1>🔐 Authentication Test</h1>";
    echo "<style>body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }</style>";
    
    // Test 1: Check if users exist
    $stmt = $db->prepare("SELECT id, username, email, password_hash, role FROM users WHERE role = 'customer' LIMIT 5");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<h2>📋 Customer Users in Database:</h2>";
    if (empty($users)) {
        echo "<p>❌ No customer users found in database.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Password Hash</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . substr($user['password_hash'], 0, 20) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 2: Test password hashing
    echo "<h2>🔑 Password Hashing Test:</h2>";
    $test_password = 'password123';
    $hashed = hash_password($test_password);
    $verified = verify_password($test_password, $hashed);
    
    echo "<p><strong>Test Password:</strong> $test_password</p>";
    echo "<p><strong>Hashed:</strong> $hashed</p>";
    echo "<p><strong>Verification:</strong> " . ($verified ? "✅ PASSED" : "❌ FAILED") . "</p>";
    
    // Test 3: Test with actual user data
    if (!empty($users)) {
        echo "<h2>🧪 Login Test with Actual Users:</h2>";
        foreach ($users as $user) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<h3>Testing user: " . htmlspecialchars($user['email']) . "</h3>";
            
            // Try common passwords
            $test_passwords = ['password', 'password123', '123456', 'admin'];
            foreach ($test_passwords as $pwd) {
                $result = verify_password($pwd, $user['password_hash']);
                echo "<p>Password '$pwd': " . ($result ? "✅ CORRECT" : "❌ Wrong") . "</p>";
                if ($result) break;
            }
            echo "</div>";
        }
    }
    
    // Test 4: Create a test user
    echo "<h2>👤 Create Test User:</h2>";
    $test_email = 'test_' . time() . '@example.com';
    $test_username = 'testuser_' . time();
    $test_password = 'testpass123';
    $test_hash = hash_password($test_password);
    
    try {
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'customer')");
        $stmt->execute([$test_username, $test_email, $test_hash]);
        
        echo "<p>✅ Test user created successfully!</p>";
        echo "<p><strong>Email:</strong> $test_email</p>";
        echo "<p><strong>Password:</strong> $test_password</p>";
        
        // Test login with new user
        $stmt = $db->prepare("SELECT id, username, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$test_email]);
        $new_user = $stmt->fetch();
        
        if ($new_user && verify_password($test_password, $new_user['password_hash'])) {
            echo "<p>✅ Login test with new user: PASSED</p>";
        } else {
            echo "<p>❌ Login test with new user: FAILED</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Failed to create test user: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>🎯 Summary:</h2>";
    echo "<p>If you see any failures above, there might be authentication issues.</p>";
    echo "<p>Try logging in with the test user credentials shown above.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Authentication Test</title>
</head>
<body>
    <p><a href="auth/login.php" style="background: #DC143C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🔐 Test Login</a></p>
    <p><a href="auth/register.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">📝 Register New User</a></p>
</body>
</html>
