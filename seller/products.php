<?php
/**
 * DesiDukaan - Seller Products Management
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

// Handle delete product
if (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);

    try {
        // Get product images to delete files
        $stmt = $db->prepare("SELECT images FROM products WHERE id = ? AND shop_id = ?");
        $stmt->execute([$product_id, $shop['id']]);
        $product = $stmt->fetch();

        if ($product && $product['images']) {
            $images = json_decode($product['images'], true);
            foreach ($images as $image) {
                $file_path = UPLOAD_PATH . 'products/' . $image;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }

        // Delete product from database
        $stmt = $db->prepare("DELETE FROM products WHERE id = ? AND shop_id = ?");
        $stmt->execute([$product_id, $shop['id']]);

        $_SESSION['success'] = t('product_deleted_successfully');
        redirect('products.php');

    } catch (PDOException $e) {
        $_SESSION['error'] = 'Failed to delete product.';
    }
}

// Get all products for this shop
$stmt = $db->prepare("SELECT * FROM products WHERE shop_id = ? ORDER BY created_at DESC");
$stmt->execute([$shop['id']]);
$products = $stmt->fetchAll();

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

$success_message = get_flash_message('success');
$error_message = get_flash_message('error');
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('products'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Sticky Navigation -->
    <?php render_seller_navbar('products'); ?>

    <!-- Main Content -->
    <div class="container seller-dashboard">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div>
                    <h2 class="card-title" style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        <?php echo t('manage_products'); ?>
                    </h2>
                    <p style="color: var(--text-light); margin: 0;"><?php echo t('manage_shop_products'); ?></p>
                </div>
                <a href="add_product.php" class="btn btn-primary">
                    <span style="margin-right: 5px;">➕</span>
                    <?php echo t('add_product'); ?>
                </a>
            </div>

            <?php if (empty($products)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-light);">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📦</div>
                    <h3 style="color: var(--text-dark); margin-bottom: 1rem;"><?php echo t('no_products_yet'); ?></h3>
                    <p style="margin-bottom: 2rem;"><?php echo t('add_product_description'); ?></p>
                    <a href="add_product.php" class="btn btn-primary">
                        <?php echo t('add_product'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="products-grid-modern">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card-modern">
                            <?php
                            $images = $product['images'] ? json_decode($product['images'], true) : [];
                            $first_image = !empty($images) ? $images[0] : null;
                            ?>

                            <!-- Product Image Carousel -->
                            <div class="product-image-wrapper">
                                <?php if (!empty($images)): ?>
                                    <div class="image-carousel-container">
                                        <div class="image-carousel" id="carousel-<?php echo $product['id']; ?>">
                                            <?php foreach ($images as $index => $image): ?>
                                                <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                                    <img src="<?php echo UPLOAD_URL . 'products/' . $image; ?>"
                                                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                                                         class="product-image-modern">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <?php if (count($images) > 1): ?>
                                            <!-- Navigation Arrows -->
                                            <button class="carousel-nav prev" onclick="changeSlide(<?php echo $product['id']; ?>, -1)">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M15 18l-6-6 6-6"/>
                                                </svg>
                                            </button>
                                            <button class="carousel-nav next" onclick="changeSlide(<?php echo $product['id']; ?>, 1)">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M9 18l6-6-6-6"/>
                                                </svg>
                                            </button>

                                            <!-- Image Counter -->
                                            <div class="image-counter">
                                                <span class="current-image">1</span>/<span class="total-images"><?php echo count($images); ?></span>
                                            </div>

                                            <!-- Dots Indicator -->
                                            <div class="carousel-dots">
                                                <?php for ($i = 0; $i < count($images); $i++): ?>
                                                    <button class="dot <?php echo $i === 0 ? 'active' : ''; ?>"
                                                            onclick="goToSlide(<?php echo $product['id']; ?>, <?php echo $i; ?>)"></button>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="product-image-modern no-image-modern">
                                        <div class="no-image-icon">📷</div>
                                        <span><?php echo t('no_image_available'); ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Quick Actions Overlay -->
                                <div class="quick-actions-overlay">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="quick-action-btn edit-btn" title="Edit Product">
                                        ✏️
                                    </a>
                                    <button onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['title']); ?>')"
                                            class="quick-action-btn delete-btn" title="Delete Product">
                                        🗑️
                                    </button>
                                </div>
                            </div>

                            <!-- Product Info -->
                            <div class="product-info-modern">
                                <h3 class="product-title-modern"><?php echo htmlspecialchars(translateProductContent($product['title'], true)); ?></h3>

                                <p class="product-description-modern">
                                    <?php
                                    $description = translateProductContent($product['description']);
                                    echo htmlspecialchars(strlen($description) > 80 ? substr($description, 0, 80) . '...' : $description);
                                    ?>
                                </p>

                                <div class="product-price-modern">
                                    <span class="currency"><?php echo t('currency_symbol'); ?></span>
                                    <span class="amount"><?php echo number_format($product['price'], 0); ?></span>
                                </div>

                                <!-- View Details Button -->
                                <a href="view_product.php?id=<?php echo $product['id']; ?>" class="view-details-btn">
                                    <span><?php echo t('view_details_btn'); ?></span>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 18l6-6-6-6"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 400px; width: 90%; text-align: center;">
            <h3 style="color: var(--text-dark); margin-bottom: 1rem;"><?php echo t('confirm_delete'); ?></h3>
            <p id="deleteMessage" style="color: var(--text-light); margin-bottom: 2rem;"></p>

            <form method="POST" style="display: inline;">
                <input type="hidden" id="deleteProductId" name="product_id">
                <button type="submit" name="delete_product" class="btn btn-danger" style="margin-right: 1rem;">
                    <?php echo t('yes_delete'); ?>
                </button>
                <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">
                    <?php echo t('cancel_action'); ?>
                </button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>

    <style>
        /* Modern Products Grid */
        .products-grid-modern {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
            padding: 0.5rem;
        }

        /* Modern Product Card */
        .product-card-modern {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #f0f0f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            height: fit-content;
        }

        .product-card-modern:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }

        /* Perfect Image Wrapper with Carousel - Daraz Style */
        .product-image-wrapper {
            position: relative;
            height: 200px;
            overflow: hidden;
            background: white;
            border: 1px solid #f0f0f0;
            border-radius: 12px 12px 0 0;
        }

        /* Image Carousel Container */
        .image-carousel-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .image-carousel {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            overflow: hidden;
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .carousel-slide.active {
            opacity: 1;
        }

        .product-image-modern {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            display: block;
            transition: transform 0.3s ease;
            background: white;
        }

        .product-card-modern:hover .product-image-modern {
            transform: scale(1.02);
        }

        /* Carousel Navigation */
        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .carousel-nav.prev {
            left: 8px;
        }

        .carousel-nav.next {
            right: 8px;
        }

        .product-card-modern:hover .carousel-nav {
            opacity: 1;
        }

        .carousel-nav:hover {
            background: rgba(220, 20, 60, 0.9);
            transform: translateY(-50%) scale(1.1);
        }

        /* Image Counter */
        .image-counter {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            backdrop-filter: blur(4px);
        }

        /* Carousel Dots */
        .carousel-dots {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .product-card-modern:hover .carousel-dots {
            opacity: 1;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: #DC143C;
            transform: scale(1.2);
        }

        .dot:hover {
            background: rgba(255, 255, 255, 0.8);
        }

        .no-image-modern {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            width: 100%;
            color: #999;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }

        .no-image-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            opacity: 0.6;
        }

        /* Image Badge */
        .image-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            backdrop-filter: blur(4px);
        }

        /* Quick Actions Overlay */
        .quick-actions-overlay {
            position: absolute;
            top: 12px;
            left: 12px;
            display: flex;
            gap: 8px;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .product-card-modern:hover .quick-actions-overlay {
            opacity: 1;
            transform: translateY(0);
        }

        .quick-action-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            backdrop-filter: blur(8px);
        }

        .edit-btn {
            background: rgba(52, 152, 219, 0.9);
            color: white;
        }

        .delete-btn {
            background: rgba(231, 76, 60, 0.9);
            color: white;
        }

        .quick-action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Product Info */
        .product-info-modern {
            padding: 1.25rem;
        }

        .product-title-modern {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0 0 0.75rem 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-description-modern {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin: 0 0 1rem 0;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price-modern {
            display: flex;
            align-items: baseline;
            margin-bottom: 1.25rem;
        }

        .currency {
            font-size: 0.9rem;
            color: var(--primary-color);
            font-weight: 600;
            margin-right: 2px;
        }

        .amount {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary-color);
        }

        /* View Details Button */
        .view-details-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary-color), #e74c3c);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .view-details-btn:hover {
            background: linear-gradient(135deg, #c0392b, var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 20, 60, 0.3);
        }

        .view-details-btn svg {
            transition: transform 0.3s ease;
        }

        .view-details-btn:hover svg {
            transform: translateX(4px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .products-grid-modern {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1rem;
                padding: 0;
            }

            .product-image-wrapper {
                height: 180px;
            }

            .product-info-modern {
                padding: 1rem;
            }

            .product-title-modern {
                font-size: 1rem;
            }

            .amount {
                font-size: 1.2rem;
            }

            /* Mobile Carousel Adjustments */
            .carousel-nav {
                opacity: 1;
                background: rgba(0, 0, 0, 0.5);
                width: 28px;
                height: 28px;
            }

            .carousel-nav.prev {
                left: 4px;
            }

            .carousel-nav.next {
                right: 4px;
            }

            .image-counter {
                top: 4px;
                right: 4px;
                padding: 2px 6px;
                font-size: 0.7rem;
            }

            .carousel-dots {
                opacity: 1;
                bottom: 4px;
            }

            .dot {
                width: 6px;
                height: 6px;
            }
        }

        @media (max-width: 480px) {
            .products-grid-modern {
                grid-template-columns: 1fr;
            }

            .product-image-wrapper {
                height: 160px;
            }
        }
    </style>

    <script>
        // Product Deletion Functions
        function confirmDelete(productId, productTitle) {
            document.getElementById('deleteProductId').value = productId;
            document.getElementById('deleteMessage').textContent =
                '<?php echo t('confirm_delete_product'); ?>\n\n"' + productTitle + '"';
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Image Carousel Functions
        let currentSlides = {};

        function changeSlide(productId, direction) {
            const carousel = document.getElementById('carousel-' + productId);
            const slides = carousel.querySelectorAll('.carousel-slide');
            const dots = carousel.parentElement.querySelectorAll('.dot');
            const counter = carousel.parentElement.querySelector('.current-image');

            if (!currentSlides[productId]) {
                currentSlides[productId] = 0;
            }

            // Remove active class from current slide
            slides[currentSlides[productId]].classList.remove('active');
            dots[currentSlides[productId]].classList.remove('active');

            // Calculate new slide index
            currentSlides[productId] += direction;

            if (currentSlides[productId] >= slides.length) {
                currentSlides[productId] = 0;
            } else if (currentSlides[productId] < 0) {
                currentSlides[productId] = slides.length - 1;
            }

            // Add active class to new slide
            slides[currentSlides[productId]].classList.add('active');
            dots[currentSlides[productId]].classList.add('active');

            // Update counter
            counter.textContent = currentSlides[productId] + 1;
        }

        function goToSlide(productId, slideIndex) {
            const carousel = document.getElementById('carousel-' + productId);
            const slides = carousel.querySelectorAll('.carousel-slide');
            const dots = carousel.parentElement.querySelectorAll('.dot');
            const counter = carousel.parentElement.querySelector('.current-image');

            if (!currentSlides[productId]) {
                currentSlides[productId] = 0;
            }

            // Remove active class from current slide
            slides[currentSlides[productId]].classList.remove('active');
            dots[currentSlides[productId]].classList.remove('active');

            // Set new slide
            currentSlides[productId] = slideIndex;

            // Add active class to new slide
            slides[currentSlides[productId]].classList.add('active');
            dots[currentSlides[productId]].classList.add('active');

            // Update counter
            counter.textContent = currentSlides[productId] + 1;
        }

        // Initialize carousels
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize current slide tracking for all products
            const carousels = document.querySelectorAll('.image-carousel');
            carousels.forEach(carousel => {
                const productId = carousel.id.replace('carousel-', '');
                currentSlides[productId] = 0;
            });
        });
    </script>
</body>
</html>
