<?php
/**
 * DesiDukaan - My Wishlist
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/navbar.php';
require_once '../includes/cart.php';

// Require customer login
require_role('customer');

// Get customer wishlist
$db = getDB();
try {
    $stmt = $db->prepare("
        SELECT w.*, p.title, p.description, p.price, p.quantity, p.images, p.sizes, p.colors, s.shop_name
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        JOIN shops s ON p.shop_id = s.id
        WHERE w.user_id = ? AND p.is_active = 1 AND s.status = 'approved'
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $wishlist_items = $stmt->fetchAll();
} catch (Exception $e) {
    $wishlist_items = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('my_wishlist'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body class="logged-in customer-wishlist">
    <!-- Navigation -->
    <?php render_navbar('customer', '../'); ?>

    <!-- Wishlist Content -->
    <div class="container wishlist-container">
        <div class="wishlist-hero">
            <div class="wishlist-hero-content">
                <h1 class="wishlist-title">
                    <span class="title-icon">❤️</span>
                    <span class="title-text"><?php echo t('my_wishlist'); ?></span>
                </h1>
                <p class="wishlist-subtitle">
                    <span class="item-count"><?php echo count($wishlist_items); ?></span>
                    <span class="item-text"><?php echo count($wishlist_items) === 1 ? 'item saved' : 'items saved'; ?></span>
                </p>
                <div class="wishlist-stats">
                    <div class="stat-badge">
                        <span class="stat-icon">💝</span>
                        <span class="stat-text">Favorites Collection</span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($wishlist_items)): ?>
            <!-- Empty Wishlist -->
            <div class="empty-wishlist">
                <div class="empty-wishlist-icon">💔</div>
                <h2 class="empty-wishlist-title"><?php echo t('wishlist_empty'); ?></h2>
                <p class="empty-wishlist-text"><?php echo t('add_items_to_wishlist'); ?></p>
                <a href="dashboard.php" class="btn btn-primary"><?php echo t('browse_products'); ?></a>
            </div>
        <?php else: ?>
            <!-- Wishlist Items -->
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <?php
                    $images = $item['images'] ? json_decode($item['images'], true) : [];
                    $first_image = !empty($images) ? $images[0] : null;
                    $sizes = $item['sizes'] ? json_decode($item['sizes'], true) : [];
                    $colors = $item['colors'] ? json_decode($item['colors'], true) : [];
                    $in_cart = isInCart($item['product_id']);
                    ?>
                    <div class="wishlist-item-card" data-product-id="<?php echo $item['product_id']; ?>">
                        <!-- Product Image -->
                        <div class="wishlist-image-container">
                            <?php if ($first_image && file_exists(UPLOAD_PATH . 'products/' . $first_image)): ?>
                                <img src="<?php echo UPLOAD_URL . 'products/' . $first_image; ?>"
                                     alt="<?php echo htmlspecialchars($item['title']); ?>"
                                     class="wishlist-image"
                                     onclick="openProductModal(<?php echo $item['product_id']; ?>)">
                            <?php else: ?>
                                <div class="wishlist-image no-image" onclick="openProductModal(<?php echo $item['product_id']; ?>)">
                                    <span>🎨</span>
                                    <p><?php echo t('no_image'); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Remove from Wishlist Button -->
                            <button class="remove-wishlist-btn" onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)" title="<?php echo t('remove_from_wishlist'); ?>">
                                <span>💔</span>
                            </button>
                        </div>
                        
                        <!-- Product Content -->
                        <div class="wishlist-content">
                            <div class="wishlist-header">
                                <h3 class="wishlist-product-title" onclick="openProductModal(<?php echo $item['product_id']; ?>)">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </h3>
                                <p class="wishlist-shop">by <?php echo htmlspecialchars($item['shop_name']); ?></p>
                            </div>
                            
                            <!-- Product Meta -->
                            <div class="wishlist-meta">
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
                            <div class="wishlist-pricing">
                                <span class="wishlist-price">Rs. <?php echo number_format($item['price'], 2); ?></span>
                                <?php if ($item['quantity'] > 0): ?>
                                    <span class="stock-status in-stock">✅ <?php echo t('in_stock'); ?></span>
                                <?php else: ?>
                                    <span class="stock-status out-of-stock">❌ <?php echo t('out_of_stock'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Product Actions -->
                            <div class="wishlist-actions">
                                <?php if ($item['quantity'] > 0): ?>
                                    <?php if ($in_cart): ?>
                                        <button class="btn-wishlist btn-in-cart" onclick="removeFromCart(<?php echo $item['product_id']; ?>)">
                                            <span class="btn-icon">✅</span>
                                            <span class="btn-text"><?php echo t('in_cart'); ?></span>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-wishlist btn-add-cart" onclick="addToCart(<?php echo $item['product_id']; ?>)">
                                            <span class="btn-icon">🛒</span>
                                            <span class="btn-text"><?php echo t('add_to_cart'); ?></span>
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn-wishlist btn-disabled" disabled>
                                        <span class="btn-icon">❌</span>
                                        <span class="btn-text"><?php echo t('out_of_stock'); ?></span>
                                    </button>
                                <?php endif; ?>
                                
                                <button class="btn-wishlist btn-view" onclick="openProductModal(<?php echo $item['product_id']; ?>)">
                                    <span class="btn-icon">👁️</span>
                                    <span class="btn-text"><?php echo t('view_details'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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

    <style>
        /* Enhanced Wishlist Specific Styles */
        .wishlist-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .wishlist-hero {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24, #ff9ff3, #54a0ff);
            border-radius: 24px;
            padding: 3rem 2rem;
            margin-bottom: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(255, 107, 107, 0.3);
        }

        .wishlist-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .wishlist-hero-content {
            position: relative;
            z-index: 2;
        }

        .wishlist-title {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .title-icon {
            font-size: 3.5rem;
            animation: heartBeat 2s infinite;
        }

        .title-text {
            background: linear-gradient(45deg, #fff, #f8f9fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .wishlist-subtitle {
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
        }

        .item-count {
            font-weight: 700;
            font-size: 1.5rem;
            color: #fff;
        }

        .wishlist-stats {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .stat-badge {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-weight: 600;
        }

        .stat-icon {
            font-size: 1.2rem;
        }

        /* Enhanced Wishlist Items */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .wishlist-item {
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .wishlist-item:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 60px rgba(255, 107, 107, 0.15);
        }

        .wishlist-image-container {
            position: relative;
            height: 250px;
            overflow: hidden;
        }

        .wishlist-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .wishlist-item:hover .wishlist-image {
            transform: scale(1.1);
        }

        .remove-wishlist-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .remove-wishlist-btn:hover {
            background: #ff6b6b;
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.3);
        }

        .wishlist-content {
            padding: 1.5rem;
        }

        .wishlist-product-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .wishlist-product-title:hover {
            color: var(--primary-color);
        }

        .wishlist-shop {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .wishlist-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stock-status {
            font-size: 0.9rem;
            font-weight: 600;
            margin-left: 1rem;
        }

        .stock-status.in-stock {
            color: #27ae60;
        }

        .stock-status.out-of-stock {
            color: #e74c3c;
        }

        .wishlist-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .btn-wishlist {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            min-height: 44px;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, var(--primary-color), #B91C3C);
            color: white;
        }

        .btn-add-cart:hover {
            background: linear-gradient(135deg, #B91C3C, #991B1B);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(220, 38, 38, 0.3);
        }

        .btn-in-cart {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }

        .btn-view {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-view:hover {
            background: linear-gradient(135deg, #2980b9, #1f4e79);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(52, 152, 219, 0.3);
        }

        .btn-disabled {
            background: #bdc3c7;
            color: #7f8c8d;
            cursor: not-allowed;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .wishlist-container {
                padding: 1rem;
            }

            .wishlist-hero {
                padding: 2rem 1rem;
            }

            .wishlist-title {
                font-size: 2rem;
            }

            .title-icon {
                font-size: 2.5rem;
            }

            .wishlist-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .wishlist-actions {
                flex-direction: column;
            }
        }
    </style>
    
    <script>
        // Initialize wishlist states when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeWishlistStates();
        });

        function removeFromWishlist(productId) {
            showLoading();
            
            fetch('../includes/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_from_wishlist&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification(data.message, 'success');
                    // Remove the item from the page
                    const itemCard = document.querySelector(`[data-product-id="${productId}"]`);
                    if (itemCard) {
                        itemCard.style.animation = 'fadeOut 0.3s ease-out';
                        setTimeout(() => {
                            itemCard.remove();
                            // Update count
                            const countElement = document.querySelector('.wishlist-subtitle');
                            if (countElement) {
                                const currentCount = parseInt(countElement.textContent);
                                countElement.textContent = `${currentCount - 1} <?php echo t('items_in_wishlist'); ?>`;
                            }
                            // Check if wishlist is empty
                            if (document.querySelectorAll('.wishlist-item-card').length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                } else {
                    showNotification(data.message || 'Failed to remove from wishlist', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Something went wrong. Please try again.', 'error');
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });
    </script>
    
    <style>
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.8); }
        }
    </style>
</body>
</html>
