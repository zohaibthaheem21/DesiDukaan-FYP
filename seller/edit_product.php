<?php
/**
 * DesiDukaan - Edit Product
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/seller_navbar.php';

// Require seller login
require_role('seller');

// Get seller's shop
$db = getDB();
$stmt = $db->prepare("SELECT * FROM shops WHERE seller_id = ? AND status = 'approved'");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

if (!$shop) {
    redirect(SITE_URL . '/seller/dashboard.php');
}

// Get product ID
$product_id = intval($_GET['id'] ?? 0);

// Get product details
$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND shop_id = ?");
$stmt->execute([$product_id, $shop['id']]);
$product = $stmt->fetch();

if (!$product) {
    redirect(SITE_URL . '/seller/products.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $sizes = $_POST['sizes'] ?? [];
    $colors = $_POST['colors'] ?? [];
    
    // Validation
    if (empty($title)) {
        $errors['title'] = t('field_required');
    }
    
    if (empty($description)) {
        $errors['description'] = t('field_required');
    }
    
    if ($price <= 0) {
        $errors['price'] = 'Price must be greater than 0';
    }
    
    if ($quantity < 0) {
        $errors['quantity'] = 'Quantity cannot be negative';
    }
    
    // Handle new image uploads
    $current_images = $product['images'] ? json_decode($product['images'], true) : [];
    $uploaded_images = $current_images; // Keep existing images

    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $max_total_images = 5;

        $new_image_count = count($_FILES['images']['name']);
        $total_images = count($current_images) + $new_image_count;

        if ($total_images > $max_total_images) {
            $errors['images'] = "Maximum $max_total_images images allowed in total. You currently have " . count($current_images) . " images.";
        } else {
            for ($i = 0; $i < $new_image_count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file_type = $_FILES['images']['type'][$i];
                    $file_size = $_FILES['images']['size'][$i];

                    if (!in_array($file_type, $allowed_types)) {
                        $errors['images'] = 'Only JPEG, PNG images are allowed';
                        break;
                    }

                    if ($file_size > $max_size) {
                        $errors['images'] = 'Image size must be less than 5MB';
                        break;
                    }

                    $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                    $filename = 'product_' . $shop['id'] . '_' . time() . '_' . (count($uploaded_images) + $i) . '.' . $file_extension;
                    $upload_path = UPLOAD_PATH . 'products/' . $filename;

                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_path)) {
                        $uploaded_images[] = $filename;
                    } else {
                        $errors['images'] = 'Failed to upload image ' . ($i + 1);
                        break;
                    }
                }
            }
        }
    }
    
    // If no errors, update product
    if (empty($errors)) {
        try {
            $sizes_json = !empty($sizes) ? json_encode($sizes) : null;
            $colors_json = !empty($colors) ? json_encode($colors) : null;
            $images_json = !empty($uploaded_images) ? json_encode($uploaded_images) : null;

            $stmt = $db->prepare("UPDATE products SET title = ?, description = ?, price = ?, quantity = ?, sizes = ?, colors = ?, images = ? WHERE id = ? AND shop_id = ?");
            $stmt->execute([$title, $description, $price, $quantity, $sizes_json, $colors_json, $images_json, $product_id, $shop['id']]);
            
            $_SESSION['success'] = t('product_updated_successfully');
            redirect('products.php');
            
        } catch (PDOException $e) {
            $errors['general'] = 'Failed to update product. Please try again.';
        }
    }
}

// Prepare form data
$current_sizes = $product['sizes'] ? json_decode($product['sizes'], true) : [];
$current_colors = $product['colors'] ? json_decode($product['colors'], true) : [];
$current_images = $product['images'] ? json_decode($product['images'], true) : [];
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('edit_product'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Sticky Navigation -->
    <?php render_seller_navbar('products'); ?>

    <!-- Main Content -->
    <div class="container seller-dashboard">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title" style="color: var(--primary-color); margin-bottom: 0.5rem;">
                    <?php echo t('edit_product'); ?>
                </h2>
                <p style="color: var(--text-light); margin: 0;">Update product information</p>
            </div>
            
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error">
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="editProductForm" data-validate>
                <div class="form-grid">
                    <div class="form-card">
                        <h3 style="color: var(--text-dark); margin-bottom: 1.5rem;">Basic Information</h3>
                        
                        <div class="form-group">
                            <label for="title" class="form-label"><?php echo t('product_title'); ?></label>
                            <input type="text" id="title" name="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($product['title']); ?>" required>
                            <?php if (isset($errors['title'])): ?>
                                <div class="error-message"><?php echo $errors['title']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label"><?php echo t('product_description'); ?></label>
                            <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="error-message"><?php echo $errors['description']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="price" class="form-label"><?php echo t('product_price'); ?> (Rs.)</label>
                                    <input type="number" id="price" name="price" class="form-control" 
                                           value="<?php echo $product['price']; ?>" 
                                           min="0" step="0.01" required>
                                    <?php if (isset($errors['price'])): ?>
                                        <div class="error-message"><?php echo $errors['price']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="quantity" class="form-label"><?php echo t('product_quantity'); ?></label>
                                    <input type="number" id="quantity" name="quantity" class="form-control" 
                                           value="<?php echo $product['quantity']; ?>" 
                                           min="0" required>
                                    <?php if (isset($errors['quantity'])): ?>
                                        <div class="error-message"><?php echo $errors['quantity']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-card">
                        <h3 style="color: var(--text-dark); margin-bottom: 1.5rem;"><?php echo t('product_details'); ?></h3>
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo t('product_sizes'); ?></label>
                            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" name="sizes[]" value="small" 
                                           <?php echo in_array('small', $current_sizes) ? 'checked' : ''; ?>>
                                    <?php echo t('size_small'); ?>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" name="sizes[]" value="medium"
                                           <?php echo in_array('medium', $current_sizes) ? 'checked' : ''; ?>>
                                    <?php echo t('size_medium'); ?>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" name="sizes[]" value="large"
                                           <?php echo in_array('large', $current_sizes) ? 'checked' : ''; ?>>
                                    <?php echo t('size_large'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <?php if (!empty($current_images)): ?>
                            <div class="form-group">
                                <label class="form-label">Current Images (<?php echo count($current_images); ?>)</label>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                    <?php foreach ($current_images as $index => $image): ?>
                                        <div style="position: relative; border: 2px solid var(--border-light); border-radius: 8px; overflow: hidden; background: white;">
                                            <img src="<?php echo UPLOAD_URL . 'products/' . $image; ?>"
                                                 style="width: 120px; height: 120px; object-fit: contain; display: block;"
                                                 alt="Product Image <?php echo $index + 1; ?>">
                                            <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.8); color: white; padding: 4px; font-size: 11px; text-align: center;">
                                                Image <?php echo $index + 1; ?>
                                            </div>
                                            <div style="position: absolute; top: 4px; left: 4px; background: var(--primary-color); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">
                                                <?php echo $index + 1; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <p style="color: var(--text-light); font-size: 0.9rem; margin: 0;">
                                    You can add more images below (maximum <?php echo 5 - count($current_images); ?> additional images allowed)
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="images" class="form-label">Add More Images</label>
                            <div class="file-upload-area" onclick="document.getElementById('images').click()">
                                <div style="font-size: 2rem; color: var(--text-light); margin-bottom: 1rem;">📷</div>
                                <p style="color: var(--text-dark); margin-bottom: 0.5rem;">Click to upload additional images</p>
                                <p style="color: var(--text-light); font-size: 0.9rem; margin: 0;">JPEG, PNG up to 5MB each</p>
                            </div>
                            <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;">
                            <div id="imagePreview" style="margin-top: 1rem; display: none;"></div>
                            <?php if (isset($errors['images'])): ?>
                                <div class="error-message"><?php echo $errors['images']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="min-width: 200px;">
                        <?php echo t('edit_product'); ?>
                    </button>
                    <a href="products.php" class="btn btn-secondary" style="margin-left: 1rem;">
                        <?php echo t('cancel'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script>
        // Enhanced Image preview functionality
        document.getElementById('images').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const files = Array.from(e.target.files);

            console.log('New files selected:', files.length);

            preview.innerHTML = '';

            if (files.length > 0) {
                preview.style.display = 'block';

                // Add file count indicator
                const countDiv = document.createElement('div');
                countDiv.style.cssText = 'margin-bottom: 1rem; color: var(--primary-color); font-weight: 600; padding: 0.5rem; background: rgba(220, 20, 60, 0.1); border-radius: 8px; text-align: center;';
                countDiv.textContent = `${files.length} new image(s) selected for upload`;
                preview.appendChild(countDiv);

                files.forEach((file, index) => {
                    console.log(`Processing new file ${index + 1}:`, file.name, file.size, file.type);

                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const imgContainer = document.createElement('div');
                        imgContainer.style.cssText = 'display: inline-block; margin: 5px; position: relative; border: 2px solid var(--primary-color); border-radius: 8px; overflow: hidden; background: white;';

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.cssText = 'width: 120px; height: 120px; object-fit: contain; display: block; background: white;';

                        const fileName = document.createElement('div');
                        fileName.style.cssText = 'position: absolute; bottom: 0; left: 0; right: 0; background: rgba(220, 20, 60, 0.9); color: white; padding: 4px; font-size: 11px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;';
                        fileName.textContent = file.name;

                        const newLabel = document.createElement('div');
                        newLabel.style.cssText = 'position: absolute; top: 4px; left: 4px; background: var(--success-color); color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold;';
                        newLabel.textContent = 'NEW';

                        imgContainer.appendChild(img);
                        imgContainer.appendChild(fileName);
                        imgContainer.appendChild(newLabel);
                        preview.appendChild(imgContainer);
                    };

                    reader.readAsDataURL(file);
                });
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html>
