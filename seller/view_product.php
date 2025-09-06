<?php
/**
 * DesiDukaan - View Product Details
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

$images = $product['images'] ? json_decode($product['images'], true) : [];
$sizes = $product['sizes'] ? json_decode($product['sizes'], true) : [];
$colors = $product['colors'] ? json_decode($product['colors'], true) : [];

// Function to translate product content based on current language
function translateProductContent($text, $isTitle = false) {
    $currentLang = get_current_language();

    // Simple translation mapping for common product terms
    $translations = [
        'en' => [
            // Keep English as is, but ensure proper formatting
        ],
        'sd' => [
            // Common product terms in Sindhi
            'shirt' => 'قميص',
            'dress' => 'لباس',
            'shoes' => 'بوٽ',
            'bag' => 'ٿيلهو',
            'watch' => 'گھڙي',
            'phone' => 'فون',
            'book' => 'ڪتاب',
            'pen' => 'قلم',
            'beautiful' => 'خوبصورت',
            'handmade' => 'هٿ سان ٺهيل',
            'traditional' => 'روايتي',
            'modern' => 'جديد',
            'quality' => 'معيار',
            'premium' => 'اعليٰ',
            'cotton' => 'ڪپهه',
            'silk' => 'ريشم',
            'leather' => 'چمڙو'
        ]
    ];

    if ($currentLang === 'sd' && isset($translations['sd'])) {
        foreach ($translations['sd'] as $en => $sd) {
            $text = str_ireplace($en, $sd, $text);
        }
    }

    return $text;
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Sticky Navigation -->
    <?php render_seller_navbar('products'); ?>

    <!-- Main Content -->
    <div class="container seller-dashboard">
        <div class="card">
            <div class="product-detail-header">
                <div class="header-content">
                    <div class="breadcrumb">
                        <a href="products.php"><?php echo t('products'); ?></a>
                        <span>›</span>
                        <span><?php echo htmlspecialchars(translateProductContent($product['title'], true)); ?></span>
                    </div>
                    <h1 class="product-detail-title">
                        <?php echo htmlspecialchars(translateProductContent($product['title'], true)); ?>
                    </h1>
                    <p class="product-detail-subtitle"><?php echo t('complete_product_info'); ?></p>
                </div>
                <div class="header-actions">
                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="action-btn edit-action">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        <?php echo t('edit_product_btn'); ?>
                    </a>
                    <a href="products.php" class="action-btn back-action">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5"/>
                            <path d="M12 19l-7-7 7-7"/>
                        </svg>
                        <?php echo t('back_to_products'); ?>
                    </a>
                </div>
            </div>
            
            <div class="row">
                <!-- Product Images Gallery -->
                <div class="col-6">
                    <?php if (!empty($images)): ?>
                        <div class="images-section">
                            <div class="section-header">
                                <h3 class="section-title"><?php echo t('product_images'); ?></h3>
                                <span class="image-count-badge"><?php echo count($images); ?> <?php echo count($images) === 1 ? 'Image' : 'Images'; ?></span>
                            </div>

                            <!-- Main Image Display -->
                            <div class="main-image-container">
                                <div class="main-image-wrapper">
                                    <img id="mainImage"
                                         src="<?php echo UPLOAD_URL . 'products/' . $images[0]; ?>"
                                         alt="<?php echo htmlspecialchars(translateProductContent($product['title'])); ?>"
                                         class="main-product-image"
                                         onclick="openImageModal(this.src)">

                                    <?php if (count($images) > 1): ?>
                                        <!-- Navigation Arrows -->
                                        <button class="main-nav-btn prev-btn" onclick="changeMainImage(-1)">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M15 18l-6-6 6-6"/>
                                            </svg>
                                        </button>
                                        <button class="main-nav-btn next-btn" onclick="changeMainImage(1)">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M9 18l6-6-6-6"/>
                                            </svg>
                                        </button>

                                        <!-- Image Counter -->
                                        <div class="main-image-counter">
                                            <span id="currentImageIndex">1</span> / <?php echo count($images); ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Zoom Icon -->
                                    <div class="zoom-icon" onclick="openImageModal(document.getElementById('mainImage').src)">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="11" cy="11" r="8"/>
                                            <path d="M21 21l-4.35-4.35"/>
                                            <path d="M11 8v6"/>
                                            <path d="M8 11h6"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Thumbnail Strip -->
                            <?php if (count($images) > 1): ?>
                                <div class="thumbnail-strip">
                                    <div class="thumbnail-container">
                                        <?php foreach ($images as $index => $image): ?>
                                            <div class="thumbnail-wrapper <?php echo $index === 0 ? 'active' : ''; ?>"
                                                 onclick="setMainImage(<?php echo $index; ?>, '<?php echo UPLOAD_URL . 'products/' . $image; ?>')">
                                                <img src="<?php echo UPLOAD_URL . 'products/' . $image; ?>"
                                                     alt="<?php echo htmlspecialchars(translateProductContent($product['title'])); ?> - Image <?php echo $index + 1; ?>"
                                                     class="thumbnail-image">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-images">
                            <div class="no-images-icon">📷</div>
                            <p><?php echo t('no_products_yet'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Information -->
                <div class="col-6">
                    <div class="product-info-card">
                        <h3 class="info-section-title"><?php echo t('product_details'); ?></h3>

                        <div class="info-item">
                            <label class="info-label">
                                <?php echo t('product_title'); ?>:
                            </label>
                            <p class="info-value">
                                <?php echo htmlspecialchars(translateProductContent($product['title'], true)); ?>
                            </p>
                        </div>

                        <div class="info-item">
                            <label class="info-label">
                                <?php echo t('product_description'); ?>:
                            </label>
                            <p class="info-value description">
                                <?php echo nl2br(htmlspecialchars(translateProductContent($product['description']))); ?>
                            </p>
                        </div>
                        
                        <div class="row" style="margin-bottom: 1.5rem;">
                            <div class="col-6">
                                <label style="font-weight: 600; color: var(--text-dark); display: block; margin-bottom: 0.5rem;">
                                    <?php echo t('product_price'); ?>:
                                </label>
                                <p style="color: var(--primary-color); margin: 0; font-size: 1.5rem; font-weight: 700;">
                                    <?php echo t('currency_symbol'); ?> <?php echo number_format($product['price'], 2); ?>
                                </p>
                            </div>
                            <div class="col-6">
                                <label style="font-weight: 600; color: var(--text-dark); display: block; margin-bottom: 0.5rem;">
                                    <?php echo t('product_quantity'); ?>:
                                </label>
                                <p style="color: var(--text-light); margin: 0; font-size: 1.2rem;">
                                    <?php echo $product['quantity']; ?> <?php echo t('units'); ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if (!empty($sizes)): ?>
                            <div style="margin-bottom: 1.5rem;">
                                <label style="font-weight: 600; color: var(--text-dark); display: block; margin-bottom: 0.5rem;">
                                    <?php echo t('product_sizes'); ?>:
                                </label>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <?php foreach ($sizes as $size): ?>
                                        <span style="background: var(--ajrak-gradient-1); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">
                                            <?php echo ucfirst($size); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin-bottom: 1.5rem;">
                            <label style="font-weight: 600; color: var(--text-dark); display: block; margin-bottom: 0.5rem;">
                                <?php echo t('status'); ?>:
                            </label>
                            <span style="background: <?php echo $product['is_active'] ? 'var(--success-color)' : 'var(--error-color)'; ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">
                                <?php echo $product['is_active'] ? t('approved') : t('pending'); ?>
                            </span>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <label style="font-weight: 600; color: var(--text-dark); display: block; margin-bottom: 0.5rem;">
                                    <?php echo t('created_date'); ?>:
                                </label>
                                <p style="color: var(--text-light); margin: 0; font-size: 0.9rem;">
                                    <?php echo date('F j, Y g:i A', strtotime($product['created_at'])); ?>
                                </p>
                            </div>
                            <div class="col-6">
                                <label style="font-weight: 600; color: var(--text-dark); display: block; margin-bottom: 0.5rem;">
                                    <?php echo t('last_updated'); ?>:
                                </label>
                                <p style="color: var(--text-light); margin: 0; font-size: 0.9rem;">
                                    <?php echo date('F j, Y g:i A', strtotime($product['updated_at'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>

    <!-- Image Modal -->
    <div id="imageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 2000; align-items: center; justify-content: center;">
        <div style="position: relative; max-width: 90%; max-height: 90%;">
            <img id="modalImage" style="width: 100%; height: auto; border-radius: 12px;">
            <button onclick="closeImageModal()" style="position: absolute; top: -40px; right: 0; background: white; border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; font-size: 1.5rem;">×</button>
        </div>
    </div>

    <script>
        // Image Gallery Variables
        let currentMainImageIndex = 0;
        const imageUrls = [
            <?php if (!empty($images)): ?>
                <?php foreach ($images as $index => $image): ?>
                    '<?php echo UPLOAD_URL . 'products/' . $image; ?>'<?php echo $index < count($images) - 1 ? ',' : ''; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        ];

        // Modal Functions
        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModal').style.display = 'flex';
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Main Image Gallery Functions
        function changeMainImage(direction) {
            if (imageUrls.length <= 1) return;

            currentMainImageIndex += direction;

            if (currentMainImageIndex >= imageUrls.length) {
                currentMainImageIndex = 0;
            } else if (currentMainImageIndex < 0) {
                currentMainImageIndex = imageUrls.length - 1;
            }

            updateMainImage();
        }

        function setMainImage(index, imageUrl) {
            currentMainImageIndex = index;
            updateMainImage();
        }

        function updateMainImage() {
            const mainImage = document.getElementById('mainImage');
            const counter = document.getElementById('currentImageIndex');
            const thumbnails = document.querySelectorAll('.thumbnail-wrapper');

            if (mainImage && imageUrls[currentMainImageIndex]) {
                mainImage.src = imageUrls[currentMainImageIndex];
            }

            if (counter) {
                counter.textContent = currentMainImageIndex + 1;
            }

            // Update thumbnail active state
            thumbnails.forEach((thumb, index) => {
                if (index === currentMainImageIndex) {
                    thumb.classList.add('active');
                } else {
                    thumb.classList.remove('active');
                }
            });
        }

        // Keyboard Navigation
        document.addEventListener('keydown', function(e) {
            if (imageUrls.length <= 1) return;

            if (e.key === 'ArrowLeft') {
                changeMainImage(-1);
            } else if (e.key === 'ArrowRight') {
                changeMainImage(1);
            }
        });

        // Close modal when clicking outside
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });

        // Initialize gallery
        document.addEventListener('DOMContentLoaded', function() {
            currentMainImageIndex = 0;
            updateMainImage();
        });
    </script>
</body>
</html>

<style>
/* Product Detail Header */
.product-detail-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.header-content {
    flex: 1;
    min-width: 300px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    opacity: 0.9;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
    transition: opacity 0.2s ease;
}

.breadcrumb a:hover {
    opacity: 0.8;
}

.product-detail-title {
    font-size: 2.2rem;
    font-weight: 800;
    margin: 0 0 0.5rem 0;
    line-height: 1.2;
}

.product-detail-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(10px);
}

.edit-action {
    background: rgba(52, 152, 219, 0.2);
    color: white;
}

.back-action {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
}

/* Image Gallery Section */
.images-section {
    margin-bottom: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-title {
    color: var(--primary-color);
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 0.5rem;
}

.image-count-badge {
    background: linear-gradient(135deg, #DC143C, #8B0000);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Main Image Display */
.main-image-container {
    margin-bottom: 1rem;
}

.main-image-wrapper {
    position: relative;
    width: 100%;
    height: 400px;
    background: white;
    border: 1px solid #f0f0f0;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    justify-content: center;
    align-items: center;
}

.main-product-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    cursor: zoom-in;
    transition: transform 0.3s ease;
}

.main-product-image:hover {
    transform: scale(1.02);
}

/* Main Image Navigation */
.main-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 2;
}

.main-nav-btn.prev-btn {
    left: 12px;
}

.main-nav-btn.next-btn {
    right: 12px;
}

.main-image-wrapper:hover .main-nav-btn {
    opacity: 1;
}

.main-nav-btn:hover {
    background: rgba(220, 20, 60, 0.9);
    transform: translateY(-50%) scale(1.1);
}

/* Image Counter */
.main-image-counter {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 0.85rem;
    font-weight: 600;
    backdrop-filter: blur(4px);
}

/* Zoom Icon */
.zoom-icon {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: all 0.3s ease;
}

.main-image-wrapper:hover .zoom-icon {
    opacity: 1;
}

.zoom-icon:hover {
    background: rgba(220, 20, 60, 0.9);
    transform: scale(1.1);
}

/* Thumbnail Strip */
.thumbnail-strip {
    margin-top: 1rem;
}

.thumbnail-container {
    display: flex;
    gap: 0.75rem;
    overflow-x: auto;
    padding: 0.5rem 0;
    scroll-behavior: smooth;
}

.thumbnail-container::-webkit-scrollbar {
    height: 4px;
}

.thumbnail-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

.thumbnail-container::-webkit-scrollbar-thumb {
    background: #DC143C;
    border-radius: 2px;
}

.thumbnail-wrapper {
    flex-shrink: 0;
    width: 80px;
    height: 80px;
    border: 2px solid transparent;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    display: flex;
    justify-content: center;
    align-items: center;
}

.thumbnail-wrapper.active {
    border-color: #DC143C;
    box-shadow: 0 0 0 2px rgba(220, 20, 60, 0.2);
}

.thumbnail-wrapper:hover {
    border-color: #DC143C;
    transform: scale(1.05);
}

.thumbnail-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    transition: transform 0.3s ease;
}

.thumbnail-wrapper:hover .thumbnail-image {
    transform: scale(1.1);
}

.images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.image-container {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
    background: white;
    height: 200px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.image-container:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(220, 20, 60, 0.15);
    border-color: #DC143C;
}

.product-detail-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    display: block;
    transition: transform 0.3s ease;
    background: white;
}

.image-container:hover .product-detail-image {
    transform: scale(1.02);
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    color: white;
    font-weight: 600;
}

.image-container:hover .image-overlay {
    opacity: 1;
}

.no-images {
    text-align: center;
    padding: 3rem;
    background: #f8f9fa;
    border-radius: 12px;
    border: 2px dashed var(--border-light);
    margin-bottom: 2rem;
}

.no-images-icon {
    font-size: 4rem;
    color: var(--text-light);
    margin-bottom: 1rem;
}

.product-info-card {
    background: linear-gradient(135deg, #f8f9fa, #ffffff);
    padding: 2rem;
    border-radius: 16px;
    border: 2px solid var(--border-light);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
}

.info-section-title {
    color: var(--primary-color);
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 0.5rem;
}

.info-item {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    border-left: 4px solid var(--primary-color);
}

.info-label {
    font-weight: 600;
    color: var(--text-dark);
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    color: var(--text-light);
    margin: 0;
    font-size: 1.1rem;
    line-height: 1.6;
}

.info-value.description {
    font-size: 1rem;
    line-height: 1.7;
}

@media (max-width: 768px) {
    .product-detail-header {
        padding: 1.5rem;
        flex-direction: column;
        text-align: center;
    }

    .product-detail-title {
        font-size: 1.8rem;
    }

    .header-actions {
        justify-content: center;
        width: 100%;
    }

    .action-btn {
        flex: 1;
        justify-content: center;
        min-width: 120px;
    }

    /* Mobile Image Gallery */
    .main-image-wrapper {
        height: 300px;
    }

    .main-nav-btn {
        width: 36px;
        height: 36px;
    }

    .main-nav-btn.prev-btn {
        left: 8px;
    }

    .main-nav-btn.next-btn {
        right: 8px;
    }

    .thumbnail-wrapper {
        width: 60px;
        height: 60px;
    }

    .thumbnail-container {
        gap: 0.5rem;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .product-info-card {
        padding: 1.5rem;
    }

    /* Touch-friendly navigation */
    .main-nav-btn {
        opacity: 1;
        background: rgba(0, 0, 0, 0.5);
    }
}
</style>
