<?php
/**
 * Add Sample Products for Testing
 */

require_once 'config/config.php';

try {
    $db = getDB();
    
    // First, create a sample seller and shop
    $seller_email = 'sample_seller@example.com';
    $seller_username = 'sample_seller';
    $password_hash = hash_password('password123');
    
    // Check if seller already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$seller_email]);
    $existing_seller = $stmt->fetch();
    
    if (!$existing_seller) {
        // Create seller
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'seller')");
        $stmt->execute([$seller_username, $seller_email, $password_hash]);
        $seller_id = $db->lastInsertId();
        
        // Create shop
        $stmt = $db->prepare("INSERT INTO shops (seller_id, shop_name, address, email, phone_number, cnic_number, status) VALUES (?, ?, ?, ?, ?, ?, 'approved')");
        $stmt->execute([$seller_id, 'Sindhi Crafts Emporium', 'Hyderabad, Sindh', $seller_email, '03001234567', '1234567890123']);
        $shop_id = $db->lastInsertId();
        
        echo "<p>✅ Sample seller and shop created.</p>";
    } else {
        $seller_id = $existing_seller['id'];
        $stmt = $db->prepare("SELECT id FROM shops WHERE seller_id = ?");
        $stmt->execute([$seller_id]);
        $shop = $stmt->fetch();
        $shop_id = $shop['id'];
        echo "<p>ℹ️ Using existing seller and shop.</p>";
    }
    
    // Get existing product images
    $existing_images = [];
    $products_dir = UPLOAD_PATH . 'products/';
    if (is_dir($products_dir)) {
        $files = scandir($products_dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && is_file($products_dir . $file)) {
                $existing_images[] = $file;
            }
        }
    }

    // Sample products data
    $sample_products = [
        [
            'title' => 'Traditional Sindhi Ajrak',
            'description' => 'Authentic handwoven Ajrak with traditional block printing. Made by skilled artisans using natural dyes and centuries-old techniques.',
            'price' => 2500.00,
            'quantity' => 15,
            'sizes' => json_encode(['Small', 'Medium', 'Large', 'XL']),
            'colors' => json_encode(['#8B0000', '#000080', '#006400', '#8B4513']),
            'images' => !empty($existing_images) ? json_encode([array_shift($existing_images)]) : json_encode([])
        ],
        [
            'title' => 'Sindhi Topi (Traditional Cap)',
            'description' => 'Beautiful traditional Sindhi cap with intricate mirror work and embroidery. Perfect for cultural events and daily wear.',
            'price' => 800.00,
            'quantity' => 25,
            'sizes' => json_encode(['S', 'M', 'L']),
            'colors' => json_encode(['#FF0000', '#0000FF', '#008000', '#FFA500']),
            'images' => !empty($existing_images) ? json_encode([array_shift($existing_images)]) : json_encode([])
        ],
        [
            'title' => 'Handcrafted Sindhi Jewelry Set',
            'description' => 'Exquisite jewelry set featuring traditional Sindhi designs with gold plating and colorful stones.',
            'price' => 3500.00,
            'quantity' => 8,
            'sizes' => json_encode(['One Size']),
            'colors' => json_encode(['#FFD700', '#C0C0C0']),
            'images' => !empty($existing_images) ? json_encode([array_shift($existing_images)]) : json_encode([])
        ],
        [
            'title' => 'Sindhi Embroidered Kurta',
            'description' => 'Premium quality kurta with traditional Sindhi embroidery work. Comfortable cotton fabric perfect for any occasion.',
            'price' => 1800.00,
            'quantity' => 20,
            'sizes' => json_encode(['S', 'M', 'L', 'XL', 'XXL']),
            'colors' => json_encode(['#FFFFFF', '#000000', '#0000FF', '#FF0000']),
            'images' => !empty($existing_images) ? json_encode([array_shift($existing_images)]) : json_encode([])
        ],
        [
            'title' => 'Traditional Sindhi Shawl',
            'description' => 'Luxurious wool shawl with traditional Sindhi patterns. Hand-woven by master craftsmen.',
            'price' => 4200.00,
            'quantity' => 12,
            'sizes' => json_encode(['Regular', 'Large']),
            'colors' => json_encode(['#8B0000', '#000080', '#8B4513']),
            'images' => !empty($existing_images) ? json_encode([array_shift($existing_images)]) : json_encode([])
        ],
        [
            'title' => 'Sindhi Cultural Dress Set',
            'description' => 'Complete traditional Sindhi dress set for women including dupatta with beautiful mirror work.',
            'price' => 5500.00,
            'quantity' => 6,
            'sizes' => json_encode(['S', 'M', 'L', 'XL']),
            'colors' => json_encode(['#FF1493', '#4169E1', '#32CD32']),
            'images' => !empty($existing_images) ? json_encode([array_shift($existing_images)]) : json_encode([])
        ]
    ];
    
    // Insert sample products
    $stmt = $db->prepare("
        INSERT INTO products (shop_id, title, description, price, quantity, sizes, colors, images, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $products_added = 0;
    foreach ($sample_products as $product) {
        // Check if product already exists
        $check_stmt = $db->prepare("SELECT id FROM products WHERE title = ? AND shop_id = ?");
        $check_stmt->execute([$product['title'], $shop_id]);
        
        if (!$check_stmt->fetch()) {
            $stmt->execute([
                $shop_id,
                $product['title'],
                $product['description'],
                $product['price'],
                $product['quantity'],
                $product['sizes'],
                $product['colors'],
                $product['images']
            ]);
            $products_added++;
        }
    }
    
    echo "<p>✅ $products_added sample products added.</p>";
    echo "<p>🎉 Sample data setup complete!</p>";
    echo "<p><a href='customer/dashboard.php' style='background: #DC143C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 View Customer Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Sample Products</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        p {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <h1>🛍️ DesiDukaan - Sample Products Setup</h1>
</body>
</html>
