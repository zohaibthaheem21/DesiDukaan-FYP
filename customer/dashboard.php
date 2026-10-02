<?php
/**
 * DesiDukaan - Customer Dashboard
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/navbar.php';
require_once '../includes/cart.php';


// Include wishlist functions
function isInWishlist($customer_id, $product_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM wishlist WHERE customer_id = ? AND product_id = ?");
        $stmt->execute([$customer_id, $product_id]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

// Require customer login
require_role('customer');

// Get customer information
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get search and filter parameters
$search = sanitize_input($_GET['search'] ?? '');
$category = sanitize_input($_GET['category'] ?? '');
$sort = sanitize_input($_GET['sort'] ?? 'newest');

// Build query based on filters
$where_conditions = ["s.status = 'approved'", "p.is_active = 1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.description LIKE ? OR s.shop_name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

// Sort options
$order_by = "p.created_at DESC";
switch ($sort) {
    case 'price_low':
        $order_by = "p.price ASC";
        break;
    case 'price_high':
        $order_by = "p.price DESC";
        break;
    case 'oldest':
        $order_by = "p.created_at ASC";
        break;
    default:
        $order_by = "p.created_at DESC";
}

// Get products from all approved shops
try {
    $where_clause = implode(' AND ', $where_conditions);
    $stmt = $db->prepare("
        SELECT p.*, s.shop_name
        FROM products p
        JOIN shops s ON p.shop_id = s.id
        WHERE $where_clause
        ORDER BY $order_by
        LIMIT 24
    ");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

// Get featured products (latest 6)
try {
    $stmt = $db->prepare("
        SELECT p.*, s.shop_name
        FROM products p
        JOIN shops s ON p.shop_id = s.id
        WHERE s.status = 'approved' AND p.is_active = 1
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
} catch (PDOException $e) {
    $featured_products = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('customer_dashboard'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body class="logged-in customer-dashboard">
    <!-- Navigation -->
    <?php render_navbar('customer', '../'); ?>

    <!-- Hero Section -->
    <div class="hero-section-simple">
        <div class="container">
            <div class="hero-content-simple">
                <h1 class="hero-title-simple">
                    <?php echo t('welcome'); ?>, <?php echo htmlspecialchars($user['username']); ?>! 👋
                </h1>
                <p class="hero-subtitle-simple">
                    <?php echo t('discover_amazing_products'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="search-filter-section">
        <div class="container">
            <div class="search-filter-container">
                <div class="search-box">
                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="<?php echo t('search_products'); ?>"
                               value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                        <button type="submit" class="search-btn">🔍</button>
                    </form>
                </div>
                <div class="filter-controls">
                    <select name="sort" onchange="updateFilters()" class="filter-select">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>><?php echo t('newest_first'); ?></option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>><?php echo t('oldest_first'); ?></option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>><?php echo t('price_low_to_high'); ?></option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>><?php echo t('price_high_to_low'); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>

<?php
// Include Analytics Engine for AI Product Recommendations
require_once '../includes/analytics_engine.php';
$analytics = AnalyticsEngine::getInstance();
$ai_recommendations = $analytics->getRecommendedProducts($_SESSION['user_id'], 4);
?>

    <!-- Main Content -->
    <div class="container main-content">
        <?php if (!empty($ai_recommendations) && empty($search)): ?>
            <!-- AI Recommended Products Banner -->
            <section class="products-section" style="margin-bottom: 40px; background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%); padding: 25px; border-radius: 12px; border: 1px solid #ffcdd2;">
                <div class="section-header">
                    <h2 class="section-title" style="color: #DC143C; display: flex; align-items: center; gap: 8px;">
                        <span>🤖</span> AI-Powered Recommended for You
                        <span style="font-size: 11px; background: #DC143C; color: #fff; padding: 2px 8px; border-radius: 12px; font-weight: 600;">Collaborative Filtering</span>
                    </h2>
                    <p class="section-subtitle">Products personalized based on your browsing & purchasing patterns</p>
                </div>
                <div class="products-grid-modern">
                    <?php foreach ($ai_recommendations as $rec_product): ?>
                        <div class="product-card-ecommerce" style="border: 1px solid #ffcdd2;">
                            <div class="product-content-modern" style="padding: 15px;">
                                <span class="badge" style="background: #e8f5e9; color: #2e7d32; font-size: 11px; margin-bottom: 5px; display: inline-block;">
                                    Match Score: <?php echo round($rec_product['recommendation_score'] * 100); ?>%
                                </span>
                                <h3 class="product-title-modern"><?php echo htmlspecialchars($rec_product['title']); ?></h3>
                                <p class="product-shop">by <?php echo htmlspecialchars($rec_product['shop_name']); ?></p>
                                <div class="product-pricing" style="margin-top: 10px;">
                                    <span class="product-price-modern">Rs. <?php echo number_format($rec_product['price'], 2); ?></span>
                                </div>
                                <button class="btn-modern btn-add-cart" onclick="ajaxManager.addToCart(<?php echo $rec_product['id']; ?>)" style="margin-top: 10px; width: 100%;">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Products Section -->
        <section class="products-section">
            <div class="section-header">
                <h2 class="section-title">
                    <?php if (!empty($search)): ?>
                        🔍 <?php echo t('search_results'); ?> "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                        🛍️ <?php echo t('all_products'); ?>
                    <?php endif; ?>
                </h2>
                <p class="section-subtitle"><?php echo count($products); ?> <?php echo t('products_found'); ?></p>
            </div>

            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🛍️</div>
                    <h3 class="empty-title"><?php echo t('no_products_found'); ?></h3>
                    <p class="empty-text"><?php echo t('try_different_search'); ?></p>
                    <?php if (!empty($search)): ?>
                        <a href="dashboard.php" class="btn btn-primary"><?php echo t('view_all_products'); ?></a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="products-grid-modern">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $images = $product['images'] ? json_decode($product['images'], true) : [];
                        $first_image = !empty($images) ? $images[0] : null;
                        $sizes = $product['sizes'] ? json_decode($product['sizes'], true) : [];
                        $colors = $product['colors'] ? json_decode($product['colors'], true) : [];
                        $in_cart = isInCart($product['id']);
                        $in_wishlist = isInWishlist($_SESSION['user_id'], $product['id']);
                        ?>
                        <div class="product-card-ecommerce" data-product-id="<?php echo $product['id']; ?>">
                            <!-- Product Image -->
                            <div class="product-image-container">
                                <?php if ($first_image && file_exists(UPLOAD_PATH . 'products/' . $first_image)): ?>
                                    <img src="<?php echo UPLOAD_URL . 'products/' . $first_image; ?>"
                                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                                         class="product-image-modern"
                                         onclick="openProductModal(<?php echo $product['id']; ?>)"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="product-image-modern no-image" onclick="openProductModal(<?php echo $product['id']; ?>)" style="display: none;">
                                        <span>🎨</span>
                                        <p><?php echo t('no_image'); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="product-image-modern no-image" onclick="openProductModal(<?php echo $product['id']; ?>)">
                                        <span>🎨</span>
                                        <p><?php echo t('no_image'); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Product Badges -->
                                <div class="product-badges">
                                    <?php if (count($images) > 1): ?>
                                        <span class="badge badge-images"><?php echo count($images); ?> <?php echo t('photos'); ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Wishlist Button -->
                                <!-- <button class="wishlist-btn" data-product-id="<?php echo $product['id']; ?>" onclick="ajaxManager.toggleWishlist(<?php echo $product['id']; ?>)">
                                    <span class="heart-icon"><?php echo $in_wishlist ? '❤️' : '🤍'; ?></span>
                                </button> -->

                                <!-- Quick Actions -->
                                <div class="quick-actions">
                                    <button class="quick-action-btn" onclick="openProductModal(<?php echo $product['id']; ?>)" title="<?php echo t('quick_view'); ?>">
                                        👁️
                                    </button>
                                </div>
                            </div>

                            <!-- Product Content -->
                            <div class="product-content-modern">
                                <div class="product-header">
                                    <h3 class="product-title-modern" onclick="openProductModal(<?php echo $product['id']; ?>)">
                                        <?php echo htmlspecialchars($product['title']); ?>
                                    </h3>
                                    <p class="product-shop">by <?php echo htmlspecialchars($product['shop_name']); ?></p>
                                </div>

                                <!-- Product Meta -->
                                <div class="product-meta-modern">
                                    <?php if (!empty($sizes)): ?>
                                        <div class="meta-item">
                                            <span class="meta-label"><?php echo t('sizes'); ?>:</span>
                                            <div class="size-options">
                                                <?php foreach (array_slice($sizes, 0, 3) as $size): ?>
                                                    <span class="size-tag"><?php echo htmlspecialchars($size); ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($sizes) > 3): ?>
                                                    <span class="size-tag more">+<?php echo count($sizes) - 3; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($colors)): ?>
                                        <div class="meta-item">
                                            <span class="meta-label"><?php echo t('colors'); ?>:</span>
                                            <div class="color-options">
                                                <?php foreach (array_slice($colors, 0, 4) as $color): ?>
                                                    <span class="color-dot" style="background-color: <?php echo htmlspecialchars($color); ?>" title="<?php echo htmlspecialchars($color); ?>"></span>
                                                <?php endforeach; ?>
                                                <?php if (count($colors) > 4): ?>
                                                    <span class="color-dot more">+<?php echo count($colors) - 4; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Product Price -->
                                <div class="product-pricing">
                                    <span class="product-price-modern">Rs. <?php echo number_format($product['price'], 2); ?></span>
                                    <?php if ($product['quantity'] > 0): ?>
                                        <span class="stock-status in-stock">✅ <?php echo t('in_stock'); ?></span>
                                    <?php else: ?>
                                        <span class="stock-status out-of-stock">❌ <?php echo t('out_of_stock'); ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Product Actions -->
                                <div class="product-actions-modern">
                                    <?php if ($product['quantity'] > 0): ?>
                                        <?php if ($in_cart): ?>
                                            <button class="btn-modern btn-in-cart" onclick="ajaxManager.removeFromCart(<?php echo $product['id']; ?>)" data-product-id="<?php echo $product['id']; ?>">
                                                Remove from Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-modern btn-add-cart" onclick="ajaxManager.addToCart(<?php echo $product['id']; ?>)" data-product-id="<?php echo $product['id']; ?>">
                                                Add to Cart
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn-modern btn-disabled" disabled>
                                            <span class="btn-icon">❌</span>
                                            <span class="btn-text"><?php echo t('out_of_stock'); ?></span>
                                        </button>
                                    <?php endif; ?>

                                    <button class="btn-modern btn-view" onclick="openProductModal(<?php echo $product['id']; ?>)">
                                        <span class="btn-icon">👁️</span>
                                        <span class="btn-text"><?php echo t('view_details'); ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="modal-overlay" onclick="closeProductModal()">
        <div class="modal-content-modern" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeProductModal()">×</button>
            <div id="modalContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/ajax-utils.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/customer.js"></script>

    <script>
        // Initialize cart count and wishlist states on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            initializeWishlistStates();
        });
    </script>
</body>
</html>
