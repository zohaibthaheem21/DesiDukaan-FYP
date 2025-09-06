<?php
/**
 * DesiDukaan - Shopping Cart
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/navbar.php';
require_once '../includes/cart.php';

// Require customer login
require_role('customer');

// Get cart items
$cart_items = getCartItems();
$cart_total = getCartTotal();
$cart_count = getCartItemsCount();
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('shopping_cart'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body class="logged-in customer-cart">
    <!-- Navigation -->
    <?php render_navbar('customer', '../'); ?>

    <!-- Cart Content -->
    <div class="container" style="padding: 2rem 20px;">
        <div class="cart-header">
            <h1 class="cart-title">🛒 <?php echo t('shopping_cart'); ?></h1>
            <p class="cart-subtitle"><?php echo $cart_count; ?> <?php echo t('items_in_cart'); ?></p>
        </div>

        <?php if (empty($cart_items)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h2 class="empty-cart-title"><?php echo t('cart_empty'); ?></h2>
                <p class="empty-cart-text"><?php echo t('add_items_to_cart'); ?></p>
                <a href="dashboard.php" class="btn btn-primary"><?php echo t('continue_shopping'); ?></a>
            </div>
        <?php else: ?>
            <!-- Cart Items -->
            <div class="cart-content">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <?php
                        $images = $item['images'] ? json_decode($item['images'], true) : [];
                        $first_image = !empty($images) ? $images[0] : null;
                        ?>
                        <div class="cart-item" data-product-id="<?php echo $item['id']; ?>">
                            <div class="cart-item-image">
                                <?php if ($first_image): ?>
                                    <img src="<?php echo UPLOAD_URL . 'products/' . $first_image; ?>"
                                         alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <?php else: ?>
                                    <div class="no-image">🎨</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="cart-item-details">
                                <h3 class="cart-item-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p class="cart-item-shop">by <?php echo htmlspecialchars($item['shop_name']); ?></p>
                                <p class="cart-item-price">Rs. <?php echo number_format($item['price'], 2); ?></p>
                            </div>
                            
                            <div class="cart-item-quantity">
                                <label><?php echo t('quantity'); ?>:</label>
                                <div class="quantity-controls">
                                    <button onclick="ajaxManager.updateCartQuantity(<?php echo $item['id']; ?>, <?php echo $item['cart_quantity'] - 1; ?>)">-</button>
                                    <span class="quantity-display"><?php echo $item['cart_quantity']; ?></span>
                                    <button onclick="ajaxManager.updateCartQuantity(<?php echo $item['id']; ?>, <?php echo $item['cart_quantity'] + 1; ?>)">+</button>
                                </div>
                            </div>
                            
                            <div class="cart-item-subtotal">
                                <p class="subtotal-label"><?php echo t('subtotal'); ?>:</p>
                                <p class="subtotal-amount">Rs. <?php echo number_format($item['subtotal'], 2); ?></p>
                            </div>
                            
                            <div class="cart-item-actions">
                                <button class="view-details-btn" onclick="openProductModal(<?php echo $item['id']; ?>)">
                                    👁️ <?php echo t('view_details'); ?>
                                </button>
                                <button class="remove-btn" onclick="ajaxManager.removeFromCart(<?php echo $item['id']; ?>)">
                                    🗑️ <?php echo t('remove'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Cart Summary -->
                <div class="cart-summary">
                    <div class="summary-card">
                        <h3 class="summary-title"><?php echo t('order_summary'); ?></h3>
                        
                        <div class="summary-row">
                            <span><?php echo t('subtotal'); ?> (<?php echo $cart_count; ?> <?php echo t('items'); ?>):</span>
                            <span>Rs. <?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span><?php echo t('shipping'); ?>:</span>
                            <span><?php echo t('free'); ?></span>
                        </div>
                        
                        <div class="summary-row total-row">
                            <span><?php echo t('total'); ?>:</span>
                            <span>Rs. <?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        
                        <div class="summary-actions">
                            <button class="btn btn-primary btn-checkout">
                                ⚡ <?php echo t('checkout'); ?>
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                🛍️ <?php echo t('continue_shopping'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="modal" style="display: none;">
        <div class="modal-content-modern">
            <span class="close" onclick="closeProductModal()">&times;</span>
            <div id="modalContent">
                <!-- Modal content will be loaded here -->
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/ajax-utils.js"></script>
    <script src="../assets/js/main.js"></script>
        <script src="../assets/js/customer.js"></script>

    <script>
        function updateCartItemQuantity(productId, newQuantity) {
            if (newQuantity <= 0) {
                removeCartItemFromDOM(productId);
                return;
            }

            showLoading();

            fetch('../includes/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_quantity&product_id=${productId}&quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    // Update quantity display without full page reload
                    const quantityDisplay = document.querySelector(`[data-product-id="${productId}"] .quantity-display`);
                    if (quantityDisplay) {
                        quantityDisplay.textContent = newQuantity;
                        quantityDisplay.classList.add('quantity-update-animation');
                        setTimeout(() => {
                            quantityDisplay.classList.remove('quantity-update-animation');
                        }, 600);
                    }

                    // Update cart count
                    updateCartCount(data.cart_count);

                    // Update subtotal if element exists
                    const subtotalElement = document.querySelector(`[data-product-id="${productId}"] .subtotal-amount`);
                    if (subtotalElement) {
                        const priceElement = document.querySelector(`[data-product-id="${productId}"] .cart-item-price`);
                        if (priceElement) {
                            const price = parseFloat(priceElement.textContent.replace(/[^\d.]/g, ''));
                            const newSubtotal = price * newQuantity;
                            subtotalElement.textContent = `Rs. ${newSubtotal.toFixed(2)}`;
                        }
                    }

                    showNotification('Quantity updated successfully! 📦', 'success');
                } else {
                    showNotification('Failed to update quantity', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Something went wrong', 'error');
            });
        }

        function removeCartItemFromDOM(productId) {
            showLoading();

            fetch('../includes/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=remove_from_cart&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    // Remove item from DOM with animation
                    const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
                    if (cartItem) {
                        cartItem.style.transform = 'translateX(-100%)';
                        cartItem.style.opacity = '0';
                        cartItem.style.transition = 'all 0.5s ease';

                        setTimeout(() => {
                            cartItem.remove();

                            // Check if cart is empty
                            const remainingItems = document.querySelectorAll('.cart-item');
                            if (remainingItems.length === 0) {
                                location.reload(); // Reload to show empty cart message
                            }
                        }, 500);
                    }

                    // Update cart count
                    updateCartCount(data.cart_count);
                    showNotification('Item removed from cart! 🗑️', 'success');
                } else {
                    showNotification('Failed to remove item', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showNotification('Something went wrong', 'error');
            });
        }
        
        // Initialize cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            
            // Handle checkout button
            const checkoutBtn = document.querySelector('.btn-checkout');
            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function() {
                    // Check if cart has items
                    const cartItems = document.querySelectorAll('.cart-item');
                    if (cartItems.length === 0) {
                        showNotification('Your cart is empty!', 'error');
                        return;
                    }

                    // Redirect to checkout page
                    window.location.href = 'checkout.php';
                });
            }
        });
    </script>
    
    <style>
        .cart-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .cart-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .cart-subtitle {
            color: var(--text-light);
            font-size: 1.1rem;
        }
        
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .empty-cart-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-cart-title {
            font-size: 1.5rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .empty-cart-text {
            color: var(--text-light);
            margin-bottom: 2rem;
        }
        
        .cart-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
        }
        
        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .cart-item {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: grid;
            grid-template-columns: 100px 1fr auto auto auto;
            gap: 1.5rem;
            align-items: center;
        }
        
        .cart-item-image {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
        }
        
        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cart-item-image .no-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--text-light);
        }
        
        .cart-item-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }
        
        .cart-item-shop {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .cart-item-price {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        .cart-item-quantity {
            text-align: center;
        }
        
        .cart-item-quantity label {
            display: block;
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.8);
            padding: 0.5rem;
            border-radius: 12px;
            border: 2px solid var(--border-light);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .quantity-controls button {
            background: linear-gradient(135deg, var(--primary-color), #B91C3C);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1.2rem;
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

        .quantity-display {
            min-width: 50px;
            text-align: center;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-dark);
            background: white;
            padding: 0.5rem;
            border-radius: 8px;
            border: 2px solid var(--border-light);
        }
        
        .cart-item-subtotal {
            text-align: right;
        }
        
        .subtotal-label {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }
        
        .subtotal-amount {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
        }
        
        .remove-btn {
            background: none;
            border: 1px solid #dc3545;
            color: #dc3545;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .remove-btn:hover {
            background: #dc3545;
            color: white;
        }
        
        .cart-summary {
            position: sticky;
            top: 2rem;
            height: fit-content;
        }
        
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .summary-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-light);
        }
        
        .summary-row:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .total-row {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            border-top: 2px solid var(--primary-color);
            padding-top: 1rem;
            margin-top: 1rem;
        }
        
        .summary-actions {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .btn-checkout {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .cart-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .cart-item {
                grid-template-columns: 80px 1fr;
                gap: 1rem;
            }
            
            .cart-item-image {
                width: 80px;
                height: 80px;
            }
            
            .cart-item-quantity,
            .cart-item-subtotal,
            .cart-item-actions {
                grid-column: 1 / -1;
                justify-self: stretch;
            }
            
            .cart-item-quantity {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .cart-item-subtotal {
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .cart-summary {
                position: static;
            }
        }

        /* Enhanced Cart Item Actions */
        .cart-item-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .view-details-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
            flex: 1;
            min-width: 120px;
        }

        .view-details-btn:hover {
            background: linear-gradient(135deg, #2980b9, #1f4e79);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(52, 152, 219, 0.3);
        }

        .remove-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
            flex: 1;
            min-width: 120px;
        }

        .remove-btn:hover {
            background: linear-gradient(135deg, #c82333, #a71e2a);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(220, 53, 69, 0.3);
        }

        /* Product Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content-modern {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 16px;
            max-width: 90vw;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 2rem;
            cursor: pointer;
            color: #999;
            z-index: 1001;
            background: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .modal .close:hover {
            color: #e74c3c;
            background: #f8f9fa;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #e74c3c;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .modal-content-modern {
                max-width: 95vw;
                max-height: 95vh;
                margin: 2.5vh auto;
            }
        }
    </style>
</body>
</html>
