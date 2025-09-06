<?php
/**
 * DesiDukaan - Add Product
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
    
    // Handle image uploads (1-5 images, 1 required)
    $uploaded_images = [];

    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $max_images = 5;

        $image_count = count($_FILES['images']['name']);

        if ($image_count > $max_images) {
            $errors['images'] = "Maximum $max_images images allowed";
        } else {
            for ($i = 0; $i < $image_count; $i++) {
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
                    $filename = 'product_' . $shop['id'] . '_' . time() . '_' . $i . '.' . $file_extension;
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
    } else {
        $errors['images'] = 'At least one product image is required';
    }
    
    // If no errors, save product
    if (empty($errors)) {
        try {
            $sizes_json = !empty($sizes) ? json_encode($sizes) : null;
            $colors_json = !empty($colors) ? json_encode($colors) : null;
            $images_json = !empty($uploaded_images) ? json_encode($uploaded_images) : null;

            $stmt = $db->prepare("INSERT INTO products (shop_id, title, description, price, quantity, sizes, colors, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$shop['id'], $title, $description, $price, $quantity, $sizes_json, $colors_json, $images_json]);
            
            $success = true;
            
        } catch (PDOException $e) {
            $errors['general'] = 'Failed to add product. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('add_product'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Sticky Navigation -->
    <?php render_seller_navbar('add_product'); ?>

    <!-- Main Content -->
    <div class="container seller-dashboard">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <h4>🎉 <?php echo t('product_added_successfully'); ?></h4>
                <p>Your product has been added to your shop successfully!</p>
                <div style="margin-top: 1rem;">
                    <a href="add_product.php" class="btn btn-primary">Add Another Product</a>
                    <a href="products.php" class="btn btn-secondary">View All Products</a>
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title" style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        <?php echo t('add_product'); ?>
                    </h2>
                    <p style="color: var(--text-light); margin: 0;"><?php echo t('add_product_description'); ?></p>
                </div>
                
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-error">
                        <?php echo $errors['general']; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="addProductForm" data-validate>
                    <div class="form-grid">
                        <div class="form-card">
                            <h3 style="color: var(--text-dark); margin-bottom: 1.5rem;"><?php echo t('basic_information'); ?></h3>
                            
                            <div class="form-group">
                                <label for="title" class="form-label"><?php echo t('product_title'); ?></label>
                                <input type="text" id="title" name="title" class="form-control" 
                                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                                <?php if (isset($errors['title'])): ?>
                                    <div class="error-message"><?php echo $errors['title']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="description" class="form-label"><?php echo t('product_description'); ?></label>
                                <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                <?php if (isset($errors['description'])): ?>
                                    <div class="error-message"><?php echo $errors['description']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="price" class="form-label"><?php echo t('product_price'); ?> (Rs.)</label>
                                        <input type="number" id="price" name="price" class="form-control" 
                                               value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
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
                                               value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>" 
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
                                <div class="size-options">
                                    <label class="size-option">
                                        <input type="checkbox" name="sizes[]" value="xs"
                                               <?php echo (isset($_POST['sizes']) && in_array('xs', $_POST['sizes'])) ? 'checked' : ''; ?>>
                                        <span><?php echo t('size_xs'); ?></span>
                                    </label>
                                    <label class="size-option">
                                        <input type="checkbox" name="sizes[]" value="s"
                                               <?php echo (isset($_POST['sizes']) && in_array('s', $_POST['sizes'])) ? 'checked' : ''; ?>>
                                        <span><?php echo t('size_s'); ?></span>
                                    </label>
                                    <label class="size-option">
                                        <input type="checkbox" name="sizes[]" value="m"
                                               <?php echo (isset($_POST['sizes']) && in_array('m', $_POST['sizes'])) ? 'checked' : ''; ?>>
                                        <span><?php echo t('size_m'); ?></span>
                                    </label>
                                    <label class="size-option">
                                        <input type="checkbox" name="sizes[]" value="l"
                                               <?php echo (isset($_POST['sizes']) && in_array('l', $_POST['sizes'])) ? 'checked' : ''; ?>>
                                        <span><?php echo t('size_l'); ?></span>
                                    </label>
                                    <label class="size-option">
                                        <input type="checkbox" name="sizes[]" value="xl"
                                               <?php echo (isset($_POST['sizes']) && in_array('xl', $_POST['sizes'])) ? 'checked' : ''; ?>>
                                        <span><?php echo t('size_xl'); ?></span>
                                    </label>
                                    <label class="size-option">
                                        <input type="checkbox" name="sizes[]" value="xxl"
                                               <?php echo (isset($_POST['sizes']) && in_array('xxl', $_POST['sizes'])) ? 'checked' : ''; ?>>
                                        <span><?php echo t('size_xxl'); ?></span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><?php echo t('product_colors'); ?></label>
                                <div class="color-options">
                                    <label class="color-option">
                                        <input type="checkbox" name="colors[]" value="red"
                                               <?php echo (isset($_POST['colors']) && in_array('red', $_POST['colors'])) ? 'checked' : ''; ?>>
                                        <span class="color-swatch" style="background: #dc2626;"></span>
                                        <span>Red</span>
                                    </label>
                                    <label class="color-option">
                                        <input type="checkbox" name="colors[]" value="blue"
                                               <?php echo (isset($_POST['colors']) && in_array('blue', $_POST['colors'])) ? 'checked' : ''; ?>>
                                        <span class="color-swatch" style="background: #2563eb;"></span>
                                        <span>Blue</span>
                                    </label>
                                    <label class="color-option">
                                        <input type="checkbox" name="colors[]" value="green"
                                               <?php echo (isset($_POST['colors']) && in_array('green', $_POST['colors'])) ? 'checked' : ''; ?>>
                                        <span class="color-swatch" style="background: #16a34a;"></span>
                                        <span>Green</span>
                                    </label>
                                    <label class="color-option">
                                        <input type="checkbox" name="colors[]" value="black"
                                               <?php echo (isset($_POST['colors']) && in_array('black', $_POST['colors'])) ? 'checked' : ''; ?>>
                                        <span class="color-swatch" style="background: #000000;"></span>
                                        <span>Black</span>
                                    </label>
                                    <label class="color-option">
                                        <input type="checkbox" name="colors[]" value="white"
                                               <?php echo (isset($_POST['colors']) && in_array('white', $_POST['colors'])) ? 'checked' : ''; ?>>
                                        <span class="color-swatch" style="background: #ffffff; border: 1px solid #ccc;"></span>
                                        <span>White</span>
                                    </label>
                                    <label class="color-option">
                                        <input type="checkbox" name="colors[]" value="yellow"
                                               <?php echo (isset($_POST['colors']) && in_array('yellow', $_POST['colors'])) ? 'checked' : ''; ?>>
                                        <span class="color-swatch" style="background: #eab308;"></span>
                                        <span>Yellow</span>
                                    </label>
                                    <label class="color-option">
                                        <input type="checkbox" name="colors[]" value="purple"
                                               <?php echo (isset($_POST['colors']) && in_array('purple', $_POST['colors'])) ? 'checked' : ''; ?>>
                                        <span class="color-swatch" style="background: #9333ea;"></span>
                                        <span>Purple</span>
                                    </label>
                                    <label class="color-option">
                                        <input type="checkbox" name="colors[]" value="pink"
                                               <?php echo (isset($_POST['colors']) && in_array('pink', $_POST['colors'])) ? 'checked' : ''; ?>>
                                        <span class="color-swatch" style="background: #ec4899;"></span>
                                        <span>Pink</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="images" class="form-label"><?php echo t('product_images'); ?> <span style="color: var(--error-color);">*</span></label>
                                <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 1rem;">Upload 1-5 images (at least 1 required)</p>
                                <div class="file-upload-area" onclick="document.getElementById('images').click()">
                                    <div style="font-size: 2rem; color: var(--text-light); margin-bottom: 1rem;">📷</div>
                                    <p style="color: var(--text-dark); margin-bottom: 0.5rem;">Click to upload images</p>
                                    <p style="color: var(--text-light); font-size: 0.9rem; margin: 0;">JPEG, PNG up to 5MB each (Max 5 images)</p>
                                </div>
                                <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;" required>
                                <div id="imagePreview" style="margin-top: 1rem; display: none;"></div>
                                <?php if (isset($errors['images'])): ?>
                                    <div class="error-message"><?php echo $errors['images']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary" style="min-width: 200px;">
                            <?php echo t('add_product'); ?>
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary" style="margin-left: 1rem;">
                            <?php echo t('cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>

    <style>
        .size-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .size-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .size-option:hover {
            border-color: var(--primary-color);
            background: rgba(220, 20, 60, 0.05);
        }

        .size-option input[type="checkbox"] {
            display: none;
        }

        .size-option input[type="checkbox"]:checked + span {
            color: var(--primary-color);
            font-weight: 600;
        }

        .size-option:has(input[type="checkbox"]:checked) {
            border-color: var(--primary-color);
            background: rgba(220, 20, 60, 0.1);
        }

        .color-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .color-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .color-option:hover {
            border-color: var(--primary-color);
            background: rgba(220, 20, 60, 0.05);
        }

        .color-option input[type="checkbox"] {
            display: none;
        }

        .color-swatch {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
        }

        .color-option input[type="checkbox"]:checked + .color-swatch {
            box-shadow: 0 0 0 3px var(--primary-color);
        }

        .color-option:has(input[type="checkbox"]:checked) {
            border-color: var(--primary-color);
            background: rgba(220, 20, 60, 0.1);
        }

        .file-upload-area {
            border: 2px dashed var(--border-light);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(220, 20, 60, 0.05);
        }

        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(220, 20, 60, 0.1);
        }
    </style>

    <script>
        // Enhanced Image preview functionality
        let selectedFiles = [];

        document.getElementById('images').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const files = Array.from(e.target.files);

            console.log('Files selected:', files.length);
            console.log('File details:', files.map(f => f.name));

            if (files.length > 5) {
                alert('Maximum 5 images allowed. Please select up to 5 images.');
                e.target.value = '';
                return;
            }

            selectedFiles = files;
            preview.innerHTML = '';

            if (files.length > 0) {
                preview.style.display = 'block';

                // Add file count indicator
                const countDiv = document.createElement('div');
                countDiv.style.cssText = 'margin-bottom: 1rem; color: var(--primary-color); font-weight: 600; padding: 0.5rem; background: rgba(220, 20, 60, 0.1); border-radius: 8px; text-align: center;';
                countDiv.textContent = `${files.length} image(s) selected for upload`;
                preview.appendChild(countDiv);

                files.forEach((file, index) => {
                    console.log(`Processing file ${index + 1}:`, file.name, file.size, file.type);

                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const imgContainer = document.createElement('div');
                        imgContainer.style.cssText = 'display: inline-block; margin: 5px; position: relative; border: 2px solid var(--border-light); border-radius: 8px; overflow: hidden; background: white;';

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.cssText = 'width: 120px; height: 120px; object-fit: contain; display: block; background: white;';

                        const fileName = document.createElement('div');
                        fileName.style.cssText = 'position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.8); color: white; padding: 4px; font-size: 11px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;';
                        fileName.textContent = file.name;

                        const indexLabel = document.createElement('div');
                        indexLabel.style.cssText = 'position: absolute; top: 4px; left: 4px; background: var(--primary-color); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;';
                        indexLabel.textContent = index + 1;

                        imgContainer.appendChild(img);
                        imgContainer.appendChild(fileName);
                        imgContainer.appendChild(indexLabel);
                        preview.appendChild(imgContainer);
                    };

                    reader.readAsDataURL(file);
                });
            } else {
                preview.style.display = 'none';
            }
        });
        
        // Enhanced Drag and drop functionality
        const uploadArea = document.querySelector('.file-upload-area');

        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');

            const files = Array.from(e.dataTransfer.files);
            console.log('Files dropped:', files.length);

            // Filter only image files
            const imageFiles = files.filter(file => file.type.startsWith('image/'));
            console.log('Image files:', imageFiles.length);

            if (imageFiles.length !== files.length) {
                alert('Only image files are allowed. Non-image files have been filtered out.');
            }

            if (imageFiles.length > 5) {
                alert('Maximum 5 images allowed. Only the first 5 images will be selected.');
                imageFiles.splice(5);
            }

            // Create a new FileList
            const dt = new DataTransfer();
            imageFiles.forEach(file => dt.items.add(file));

            document.getElementById('images').files = dt.files;
            document.getElementById('images').dispatchEvent(new Event('change'));
        });

        // Form submission handler
        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('images');
            console.log('Form submitting with files:', fileInput.files.length);

            if (fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select at least one product image.');
                return false;
            }

            // Log file details for debugging
            for (let i = 0; i < fileInput.files.length; i++) {
                console.log(`File ${i + 1}:`, fileInput.files[i].name, fileInput.files[i].size, fileInput.files[i].type);
            }

            // Show loading indicator
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Uploading...';
            }
        });
    </script>
</body>
</html>
