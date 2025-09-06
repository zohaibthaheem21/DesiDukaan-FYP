<?php
/**
 * Get Product Details for Modal
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/cart.php';
require_once '../includes/wishlist.php';

// Require customer login
require_role('customer');

$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    echo '<div style="padding: 2rem; text-align: center;"><h3>Invalid product ID</h3></div>';
    exit;
}

// Get product details
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.*, s.shop_name, s.address as shop_address, s.phone_number as shop_phone
        FROM products p
        JOIN shops s ON p.shop_id = s.id
        WHERE p.id = ? AND s.status = 'approved' AND p.is_active = 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo '<div style="padding: 2rem; text-align: center;"><h3>Product not found</h3></div>';
        exit;
    }
    
    $images = $product['images'] ? json_decode($product['images'], true) : [];
    $sizes = $product['sizes'] ? json_decode($product['sizes'], true) : [];
    $colors = $product['colors'] ? json_decode($product['colors'], true) : [];
    $in_cart = isInCart($product_id);
    $in_wishlist = isInWishlist($_SESSION['user_id'], $product_id);
    
} catch (Exception $e) {
    echo '<div style="padding: 2rem; text-align: center;"><h3>Error loading product</h3></div>';
    exit;
}
?>

<div class="product-modal-content">
    <!-- Product Images -->
    <div class="modal-image-section">
        <?php if (!empty($images)): ?>
            <div class="modal-image-gallery">
                <div class="main-image-container">
                    <img src="<?php echo UPLOAD_URL . 'products/' . $images[0]; ?>" 
                         alt="<?php echo htmlspecialchars($product['title']); ?>"
                         class="modal-main-image" id="modalMainImage">
                </div>
                <?php if (count($images) > 1): ?>
                    <div class="image-thumbnails">
                        <?php foreach ($images as $index => $image): ?>
                            <img src="<?php echo UPLOAD_URL . 'products/' . $image; ?>"
                                 alt="<?php echo htmlspecialchars($product['title']); ?>"
                                 class="thumbnail-image <?php echo $index === 0 ? 'active' : ''; ?>"
                                 onclick="changeModalImage('<?php echo UPLOAD_URL . 'products/' . $image; ?>', this)">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="modal-no-image">
                <span>🎨</span>
                <p><?php echo t('no_image_available'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Product Details -->
    <div class="modal-details-section">
        <div class="modal-product-header">
            <h2 class="modal-product-title"><?php echo htmlspecialchars($product['title']); ?></h2>
            <div class="modal-product-meta">
                <span class="modal-shop-name">by <?php echo htmlspecialchars($product['shop_name']); ?></span>
                <span class="modal-product-price">Rs. <?php echo number_format($product['price'], 2); ?></span>
            </div>
        </div>
        
        <!-- Product Description -->
        <div class="modal-product-description">
            <h4><?php echo t('description'); ?></h4>
            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
        </div>
        
        <!-- Product Options -->
        <div class="modal-product-options">
            <?php if (!empty($sizes)): ?>
                <div class="option-group">
                    <h4><?php echo t('available_sizes'); ?></h4>
                    <div class="size-options-modal">
                        <?php foreach ($sizes as $size): ?>
                            <span class="size-option"><?php echo htmlspecialchars($size); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($colors)): ?>
                <div class="option-group">
                    <h4><?php echo t('available_colors'); ?></h4>
                    <div class="color-options-modal">
                        <?php foreach ($colors as $color): ?>
                            <span class="color-option" style="background-color: <?php echo htmlspecialchars($color); ?>" title="<?php echo htmlspecialchars($color); ?>"></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Stock Status -->
        <div class="modal-stock-info">
            <?php if ($product['quantity'] > 0): ?>
                <span class="stock-available">✅ <?php echo $product['quantity']; ?> <?php echo t('items_in_stock'); ?></span>
            <?php else: ?>
                <span class="stock-unavailable">❌ <?php echo t('out_of_stock'); ?></span>
            <?php endif; ?>
        </div>
        
        <!-- Quantity Selector -->
        <?php if ($product['quantity'] > 0): ?>
            <div class="quantity-selector">
                <label for="quantity"><?php echo t('quantity'); ?>:</label>
                <div class="quantity-controls">
                    <button type="button" onclick="changeQuantity(-1)">-</button>
                    <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>">
                    <button type="button" onclick="changeQuantity(1)">+</button>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="modal-actions">
            <?php if ($product['quantity'] > 0): ?>
                <?php if ($in_cart): ?>
                    <!-- <button class="btn-modal btn-in-cart" onclick="ajaxManager.removeFromCart(<?php echo $product_id; ?>)" data-product-id="<?php echo $product_id; ?>">
                        Remove from Cart
                    </button> -->
                <?php else: ?>
                    <!-- <button class="btn-modal btn-add-cart" onclick="ajaxManager.addToCartWithQuantity(<?php echo $product_id; ?>)" data-product-id="<?php echo $product_id; ?>">
                        Add to Cart
                    </button> -->
                <?php endif; ?>
                <button class="btn-modal btn-buy-now" onclick="buyNowProduct(<?php echo $product_id; ?>)">
                    <span>⚡</span> <?php echo t('buy_now'); ?>
                </button>
            <?php else: ?>
                <button class="btn-modal btn-disabled" disabled>
                    <span>❌</span> <?php echo t('out_of_stock'); ?>
                </button>
            <?php endif; ?>
            
            <button class="btn-modal btn-wishlist" onclick="ajaxManager.toggleWishlist(<?php echo $product_id; ?>)" data-product-id="<?php echo $product_id; ?>">
                <span class="heart-icon"><?php echo $in_wishlist ? '❤️' : '🤍'; ?></span>
                <span class="wishlist-text"><?php echo $in_wishlist ? t('remove_from_wishlist') : t('add_to_wishlist'); ?></span>
            </button>
        </div>
        
        <!-- Shop Information -->
        <div class="modal-shop-info">
            <h4><?php echo t('shop_information'); ?></h4>
            <div class="shop-details">
                <p><strong><?php echo t('shop_name'); ?>:</strong> <?php echo htmlspecialchars($product['shop_name']); ?></p>
                <p><strong><?php echo t('address'); ?>:</strong> <?php echo htmlspecialchars($product['shop_address']); ?></p>
                <p><strong><?php echo t('phone'); ?>:</strong> <?php echo htmlspecialchars($product['shop_phone']); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.product-modal-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    padding: 2rem;
    max-width: 800px;
}

.modal-image-section {
    position: sticky;
    top: 2rem;
}

.modal-image-gallery {
    background: #f8f9fa;
    border-radius: 12px;
    overflow: hidden;
}

.main-image-container {
    aspect-ratio: 1;
    overflow: hidden;
}

.modal-main-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.image-thumbnails {
    display: flex;
    gap: 0.5rem;
    padding: 1rem;
    overflow-x: auto;
}

.thumbnail-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
    opacity: 0.7;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.thumbnail-image:hover,
.thumbnail-image.active {
    opacity: 1;
    border-color: var(--primary-color);
}

.modal-no-image {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 12px;
    color: var(--text-light);
}

.modal-no-image span {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.modal-details-section {
    padding: 1rem 0;
}

.modal-product-header {
    margin-bottom: 2rem;
}

.modal-product-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.modal-product-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.modal-shop-name {
    color: var(--text-light);
    font-size: 1rem;
}

.modal-product-price {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary-color);
}

.modal-product-description {
    margin-bottom: 2rem;
}

.modal-product-description h4 {
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.modal-product-description p {
    color: var(--text-light);
    line-height: 1.6;
}

.modal-product-options {
    margin-bottom: 2rem;
}

.option-group {
    margin-bottom: 1.5rem;
}

.option-group h4 {
    color: var(--text-dark);
    margin-bottom: 0.75rem;
    font-size: 1rem;
}

.size-options-modal {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.size-option {
    background: var(--primary-color);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
}

.color-options-modal {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.color-option {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 1px var(--border-light);
    cursor: pointer;
}

.modal-stock-info {
    margin-bottom: 1.5rem;
}

.stock-available {
    color: #28a745;
    font-weight: 600;
}

.stock-unavailable {
    color: #dc3545;
    font-weight: 600;
}

.quantity-selector {
    margin-bottom: 2rem;
}

.quantity-selector label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-dark);
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-controls button {
    background: linear-gradient(135deg, var(--primary-color), #B91C3C);
    color: white;
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 1.3rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.2);
}

.quantity-controls button:hover {
    background: linear-gradient(135deg, #B91C3C, var(--primary-color));
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.quantity-controls button:active {
    transform: translateY(0) scale(0.95);
    box-shadow: 0 2px 6px rgba(220, 38, 38, 0.2);
}

.quantity-controls button:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.quantity-controls input {
    width: 80px;
    padding: 0.75rem;
    border: 2px solid var(--border-light);
    border-radius: 6px;
    text-align: center;
    font-weight: 600;
}

.modal-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
}

.btn-modal {
    padding: 12px 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    background: #fff;
    color: #333;
    text-align: center;
    min-width: 120px;
    min-height: 40px;
    font-family: Arial, sans-serif;
    flex: 1;
}

.btn-add-cart {
    background: #dc3545;
    color: white;
    border: 1px solid #dc3545;
}

.btn-in-cart {
    background: #28a745;
    color: white;
    border: 1px solid #28a745;
}

.btn-buy-now {
    background: #ff6b35;
    color: white;
    border: 1px solid #ff6b35;
}

.btn-wishlist {
    background: #fff;
    color: #dc3545;
    border: 1px solid #dc3545;
}

.btn-disabled {
    background: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
    border: 1px solid #e9ecef;
}


.modal-shop-info {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
}

.modal-shop-info h4 {
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.shop-details p {
    margin-bottom: 0.5rem;
    color: var(--text-light);
}

@media (max-width: 768px) {
    .product-modal-content {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        padding: 1.5rem;
    }
    
    .modal-image-section {
        position: static;
    }
    
    .modal-product-meta {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .modal-actions {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 1rem 0;
        margin: 0 -1.5rem -1.5rem;
        border-top: 1px solid var(--border-light);
    }
}
</style>

<script>
function addToCartWithQuantity(productId) {
    const quantityInput = document.getElementById('quantity');
    const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : 1;

    // Debug log
    console.log('Adding to cart:', productId, 'quantity:', quantity);

    // Use the existing addToCart function with quantity
    addToCart(productId, quantity);
}



</script>
