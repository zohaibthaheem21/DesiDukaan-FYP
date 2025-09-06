<?php
/**
 * Fix Product Images - Update products to use existing uploaded images
 */

require_once 'config/config.php';

try {
    $db = getDB();
    
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
    
    echo "<h1>🖼️ Fixing Product Images</h1>";
    echo "<p>Found " . count($existing_images) . " existing images in uploads/products/</p>";
    
    if (empty($existing_images)) {
        echo "<p>❌ No images found. Creating placeholder images...</p>";
        
        // Create some placeholder image files (empty files for now)
        $placeholder_images = [
            'ajrak_placeholder.jpg',
            'topi_placeholder.jpg', 
            'jewelry_placeholder.jpg',
            'kurta_placeholder.jpg',
            'shawl_placeholder.jpg',
            'dress_placeholder.jpg'
        ];
        
        foreach ($placeholder_images as $placeholder) {
            $file_path = $products_dir . $placeholder;
            if (!file_exists($file_path)) {
                // Create a simple text file as placeholder
                file_put_contents($file_path, 'placeholder');
                $existing_images[] = $placeholder;
            }
        }
        
        echo "<p>✅ Created " . count($placeholder_images) . " placeholder images.</p>";
    }
    
    // Get all products that need image updates
    $stmt = $db->prepare("SELECT id, title, images FROM products WHERE shop_id IN (SELECT id FROM shops WHERE status = 'approved')");
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    $updated_count = 0;
    $image_index = 0;
    
    foreach ($products as $product) {
        $current_images = $product['images'] ? json_decode($product['images'], true) : [];
        
        // Check if current images exist
        $valid_images = [];
        foreach ($current_images as $img) {
            if (file_exists($products_dir . $img)) {
                $valid_images[] = $img;
            }
        }
        
        // If no valid images, assign from existing images
        if (empty($valid_images) && !empty($existing_images)) {
            $assigned_image = $existing_images[$image_index % count($existing_images)];
            $valid_images = [$assigned_image];
            $image_index++;
            
            // Update the product with new image
            $stmt = $db->prepare("UPDATE products SET images = ? WHERE id = ?");
            $stmt->execute([json_encode($valid_images), $product['id']]);
            
            echo "<p>✅ Updated '{$product['title']}' with image: {$assigned_image}</p>";
            $updated_count++;
        } else if (!empty($valid_images)) {
            echo "<p>ℹ️ '{$product['title']}' already has valid images.</p>";
        }
    }
    
    echo "<p><strong>🎉 Updated $updated_count products with images!</strong></p>";
    echo "<p><a href='customer/dashboard.php' style='background: #DC143C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 View Customer Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fix Product Images</title>
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
        h1 {
            color: #DC143C;
            text-align: center;
        }
    </style>
</head>
<body>
</body>
</html>
