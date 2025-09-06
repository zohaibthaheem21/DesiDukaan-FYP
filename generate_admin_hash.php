<?php
/**
 * Generate Admin Password Hash
 * Run this to get the correct password hash for admin
 */

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Admin Password Hash Generator</h2>";
echo "<p><strong>Password:</strong> $password</p>";
echo "<p><strong>Hash:</strong> $hash</p>";
echo "<p>Copy this hash and update the database schema.</p>";

// Test the hash
if (password_verify($password, $hash)) {
    echo "<p style='color: green;'>✅ Hash verification successful!</p>";
} else {
    echo "<p style='color: red;'>❌ Hash verification failed!</p>";
}
?>
