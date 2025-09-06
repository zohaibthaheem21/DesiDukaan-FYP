<?php
/**
 * DesiDukaan - Buy Now Page
 * Allows customers to purchase a single product directly
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/navbar.php';

// Require customer login
require_role('customer');
// Get product ID from URL
$product_id = intval($_GET['product_id'] ?? 0);
$quantity = intval($_GET['quantity'] ?? 1);

if ($product_id <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Get product details with shop and seller information
$db = getDB();
$stmt = $db->prepare("
    SELECT p.*, s.shop_name, s.id as shop_id, s.seller_id, s.email as shop_email, s.phone_number as shop_phone,
           u.email as seller_email, u.username as seller_name
    FROM products p
    JOIN shops s ON p.shop_id = s.id
    JOIN users u ON s.seller_id = u.id
    WHERE p.id = ? AND p.is_active = 1 AND s.status = 'approved'
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: dashboard.php');
    exit;
}

// Process product images
$images = $product['images'] ? json_decode($product['images'], true) : [];
$first_image = !empty($images) ? $images[0] : null;

// Calculate total price
$unit_price = $product['price'];
$total_price = $unit_price * $quantity;

// Get product reviews for display
$stmt = $db->prepare("
    SELECT pr.*, u.username as customer_name
    FROM product_reviews pr
    JOIN users u ON pr.customer_id = u.id
    WHERE pr.product_id = ? AND pr.is_approved = 1
    ORDER BY pr.created_at DESC
    LIMIT 10
");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$stmt = $db->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
    FROM product_reviews 
    WHERE product_id = ? AND is_approved = 1
");
$stmt->execute([$product_id]);
$rating_data = $stmt->fetch();
$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_reviews = $rating_data['total_reviews'] ?? 0;
?>

<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('buy_now'); ?> - <?php echo htmlspecialchars($product['title']); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <style>
        /* Buy Now Page Styling */

body.customer-buy-now {
  background: #f9f9fb;
  font-family: "Segoe UI", Arial, sans-serif;
  color: #333;
  line-height: 1.6;
}

.container.buy-now-container {
  max-width: 1100px;
  margin: 30px auto;
  padding: 20px;
}

/* Header */
.buy-now-header {
  text-align: center;
  margin-bottom: 25px;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: #222;
}

.page-subtitle {
  color: #666;
  margin-top: 5px;
}

/* Form Card Layout */
.buy-now-form {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 25px;
}

.section-header {
  margin-bottom: 12px;
}

.section-title {
  font-size: 1.3rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #333;
}

/* Product Card */
.product-card-buy-now {
  display: flex;
  gap: 20px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  padding: 20px;
}

.product-image-container {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.product-image {
  width: 200px;
  height: 200px;
  object-fit: contain;
  border-radius: 8px;
  border: 1px solid #eee;
}

.no-image-placeholder {
  width: 200px;
  height: 200px;
  background: #f1f1f1;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #888;
}

.product-info {
  flex: 2;
}

.product-name {
  font-size: 1.4rem;
  font-weight: 700;
  margin-bottom: 8px;
}

.product-shop {
  color: #444;
  margin-bottom: 8px;
}

.product-description {
  color: #555;
  margin-bottom: 12px;
}

/* Rating */
.product-rating {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
}

.rating-stars .star {
  font-size: 1.2rem;
  color: #ccc;
}

.rating-stars .filled {
  color: #f5a623;
}

.rating-text {
  font-size: 0.9rem;
  color: #666;
}

/* Price & Quantity */
.price-section {
  margin-top: 10px;
}

.price-row, .quantity-section, .total-price-section {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.unit-price, .total-price {
  font-weight: 700;
  color: #222;
}

.quantity-controls {
  display: flex;
  align-items: center;
  gap: 5px;
}

.quantity-btn {
  width: 28px;
  height: 28px;
  background: #eee;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1.2rem;
  transition: background 0.2s;
}

.quantity-btn:hover {
  background: #ddd;
}

#quantity {
  width: 50px;
  text-align: center;
  border: 1px solid #ccc;
  border-radius: 6px;
  padding: 5px;
}

/* Shipping Form */
.shipping-section {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  padding: 20px;
}

.form-row {
  display: flex;
  gap: 15px;
  margin-bottom: 15px;
}

.form-group {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.form-label {
  font-size: 0.9rem;
  margin-bottom: 5px;
  color: #444;
}

.form-input, .form-select {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.95rem;
  transition: border 0.2s;
}

.form-input:focus, .form-select:focus {
  border-color: #0077cc;
  outline: none;
}

/* Order Summary */
.order-summary {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  padding: 20px;
  height: fit-content;
}

.summary-title {
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 15px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  font-size: 0.95rem;
}

.total-row {
  font-weight: 700;
  font-size: 1.05rem;
  color: #222;
}

.confirm-order-btn {
  margin-top: 15px;
  width: 100%;
  padding: 12px;
  background: #0077cc;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.3s;
}

.confirm-order-btn:hover {
  background: #005fa3;
}

/* Reviews */
.reviews-section {
  margin-top: 40px;
  background: #fff;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.reviews-container {
  display: grid;
  gap: 15px;
}

.review-card {
  padding: 15px;
  border: 1px solid #eee;
  border-radius: 10px;
  background: #fafafa;
}

.review-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.reviewer-name {
  font-weight: 600;
  color: #333;
}

.review-date {
  font-size: 0.85rem;
  color: #888;
}

.review-rating .star {
  color: #ccc;
}

.review-rating .filled {
  color: #f5a623;
}

.review-title {
  font-weight: 600;
  margin-bottom: 5px;
}

.review-text {
  font-size: 0.95rem;
  color: #444;
  margin-bottom: 8px;
}

.verified-purchase {
  font-size: 0.85rem;
  color: #2e7d32;
  font-weight: 600;
}

/* Success Overlay */
.success-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.65);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.success-modal {
  background: #fff;
  padding: 30px;
  border-radius: 12px;
  text-align: center;
  width: 400px;
  max-width: 90%;
  box-shadow: 0 6px 20px rgba(0,0,0,0.25);
  animation: fadeInUp 0.3s ease-out;
}

.success-icon {
  font-size: 3rem;
  margin-bottom: 15px;
  color: #28a745;
}

.success-actions {
  margin-top: 20px;
  display: flex;
  gap: 10px;
  justify-content: center;
}

.success-actions .btn {
  padding: 10px 15px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
}

.btn-primary {
  background: #0077cc;
  color: #fff;
}

.btn-secondary {
  background: #eee;
  color: #333;
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 900px) {
  .buy-now-form {
    grid-template-columns: 1fr;
  }
}

       
    </style>
</head>
<body class="logged-in customer-buy-now">
   

    <!-- Navigation -->
    <?php render_navbar('customer', '../'); ?>

    <div class="container buy-now-container">
        <div class="buy-now-header">
            <h1 class="page-title">
                <span class="title-icon">🛒</span>
                <?php echo t('buy_now'); ?>
            </h1>
            <p class="page-subtitle"><?php echo t('complete_your_purchase'); ?></p>
        </div>

        <form id="buyNowForm" class="buy-now-form" method="POST" action="../includes/process_buy_now.php">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            
            <div class="buy-now-content">
                <!-- Product Details Section -->
                <div class="product-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <span class="section-icon">📦</span>
                            <?php echo t('product_details'); ?>
                        </h2>
                    </div>
                    
                    <div class="product-card-buy-now">
                        <div class="product-image-container">
                            <?php if ($first_image && file_exists('../uploads/products/' . $first_image)): ?>
                                <img src="../uploads/products/<?php echo htmlspecialchars($first_image); ?>"
                                     alt="<?php echo htmlspecialchars($product['title']); ?>"
                                     class="product-image">
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <span class="no-image-icon">📷</span>
                                    <p><?php echo t('no_image'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['title']); ?></h3>
                            <p class="product-shop">
                                <span class="shop-icon">🏪</span>
                                <?php echo htmlspecialchars($product['shop_name']); ?>
                            </p>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <div class="product-rating">
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $avg_rating ? 'filled' : ''; ?>">⭐</span>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-text"><?php echo $avg_rating; ?> (<?php echo $total_reviews; ?> <?php echo t('reviews'); ?>)</span>
                            </div>
                            
                            <div class="price-section">
                                <div class="price-row">
                                    <span class="price-label"><?php echo t('unit_price'); ?>:</span>
                                    <span class="unit-price">Rs. <?php echo number_format($unit_price, 2); ?></span>
                                </div>
                                
                                <div class="quantity-section">
                                    <label for="quantity" class="quantity-label"><?php echo t('quantity'); ?>:</label>
                                    <div class="quantity-controls">
                                        <button type="button" class="quantity-btn" onclick="decreaseQuantity()">-</button>
                                        <input type="number" id="quantity" name="quantity" value="<?php echo $quantity; ?>" min="1" max="<?php echo $product['quantity']; ?>" readonly>
                                        <button type="button" class="quantity-btn" onclick="increaseQuantity()">+</button>
                                    </div>
                                    <span class="stock-info"><?php echo $product['quantity']; ?> <?php echo t('in_stock'); ?></span>
                                </div>
                                
                                <div class="total-price-section">
                                    <span class="total-label"><?php echo t('total_price'); ?>:</span>
                                    <span class="total-price" id="totalPrice">Rs. <?php echo number_format($total_price, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Details Section -->
                <div class="shipping-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <span class="section-icon">🚚</span>
                            <?php echo t('shipping_details'); ?>
                        </h2>
                    </div>
                    
                    <div class="shipping-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="customer_name" class="form-label"><?php echo t('full_name'); ?> *</label>
                                <input type="text" id="customer_name" name="customer_name" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="phone_number" class="form-label"><?php echo t('phone_number'); ?> *</label>
                                <input type="tel" id="phone_number" name="phone_number" class="form-input" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address_line_1" class="form-label"><?php echo t('address_line_1'); ?> *</label>
                            <input type="text" id="address_line_1" name="address_line_1" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address_line_2" class="form-label"><?php echo t('address_line_2'); ?></label>
                            <input type="text" id="address_line_2" name="address_line_2" class="form-input">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city" class="form-label"><?php echo t('city'); ?> *</label>
                                <input type="text" id="city" name="city" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="state" class="form-label"><?php echo t('state'); ?> *</label>
                                <input type="text" id="state" name="state" class="form-input" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="postal_code" class="form-label"><?php echo t('postal_code'); ?> *</label>
                                <input type="text" id="postal_code" name="postal_code" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label for="landmark" class="form-label"><?php echo t('landmark'); ?></label>
                                <input type="text" id="landmark" name="landmark" class="form-input">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address_type" class="form-label"><?php echo t('address_type'); ?></label>
                            <select id="address_type" name="address_type" class="form-select">
                                <option value="home"><?php echo t('home'); ?></option>
                                <option value="office"><?php echo t('office'); ?></option>
                                <option value="other"><?php echo t('other'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h3 class="summary-title"><?php echo t('order_summary'); ?></h3>
                <div class="summary-row">
                    <span><?php echo t('subtotal'); ?>:</span>
                    <span id="summarySubtotal">Rs. <?php echo number_format($total_price, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span><?php echo t('shipping'); ?>:</span>
                    <span class="free-shipping"><?php echo t('free'); ?></span>
                </div>
                <div class="summary-row total-row">
                    <span><?php echo t('total'); ?>:</span>
                    <span id="summaryTotal">Rs. <?php echo number_format($total_price, 2); ?></span>
                </div>
                
                <button type="submit" class="confirm-order-btn">
                    <span class="btn-icon">✅</span>
                    <?php echo t('confirm_order'); ?>
                </button>
            </div>
        </form>

        <!-- Product Reviews Section -->
        <?php if (!empty($reviews)): ?>
        <div class="reviews-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">⭐</span>
                    <?php echo t('customer_reviews'); ?> (<?php echo $total_reviews; ?>)
                </h2>
            </div>
            
            <div class="reviews-container">
                <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <span class="reviewer-name"><?php echo htmlspecialchars($review['customer_name']); ?></span>
                            <span class="review-date"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                        </div>
                        <div class="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">⭐</span>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($review['review_title'])): ?>
                    <h4 class="review-title"><?php echo htmlspecialchars($review['review_title']); ?></h4>
                    <?php endif; ?>
                    
                    <?php if (!empty($review['review_text'])): ?>
                    <p class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                    <?php endif; ?>
                    
                    <div class="review-footer">
                        <span class="verified-purchase">✅ <?php echo t('verified_purchase'); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/ajax-utils.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/customer.js"></script>
    
    <script>
        const unitPrice = <?php echo $unit_price; ?>;
        const maxQuantity = <?php echo $product['quantity']; ?>;
        
        function updateTotalPrice() {
            const quantity = parseInt(document.getElementById('quantity').value);
            const totalPrice = unitPrice * quantity;
            
            document.getElementById('totalPrice').textContent = 'Rs. ' + totalPrice.toLocaleString('en-IN', {minimumFractionDigits: 2});
            document.getElementById('summarySubtotal').textContent = 'Rs. ' + totalPrice.toLocaleString('en-IN', {minimumFractionDigits: 2});
            document.getElementById('summaryTotal').textContent = 'Rs. ' + totalPrice.toLocaleString('en-IN', {minimumFractionDigits: 2});
        }
        
        function increaseQuantity() {
            const quantityInput = document.getElementById('quantity');
            const currentQuantity = parseInt(quantityInput.value);
            
            if (currentQuantity < maxQuantity) {
                quantityInput.value = currentQuantity + 1;
                updateTotalPrice();
            }
        }
        
        function decreaseQuantity() {
            const quantityInput = document.getElementById('quantity');
            const currentQuantity = parseInt(quantityInput.value);
            
            if (currentQuantity > 1) {
                quantityInput.value = currentQuantity - 1;
                updateTotalPrice();
            }
        }
        
        // Form submission handling
        document.getElementById('buyNowForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show processing message
            const submitBtn = document.querySelector('.confirm-order-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="btn-icon">⏳</span> Processing Order...';
            submitBtn.disabled = true;
            
            // Submit form
            const formData = new FormData(this);
            
            fetch('../includes/process_buy_now.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showSuccessMessage(data.message, data.order_number);
                } else {
                    // Show error message
                    alert('Error: ' + data.message);
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your order. Please try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        function showSuccessMessage(message, orderNumber) {
            // Create success overlay
            const overlay = document.createElement('div');
            overlay.className = 'success-overlay';
            overlay.innerHTML = `
                <div class="success-modal">
                    <div class="success-icon">✅</div>
                    <h2>Order Placed Successfully!</h2>
                    <p>${message}</p>
                    <p><strong>Order Number: ${orderNumber}</strong></p>
                    <div class="success-actions">
                        <button onclick="window.location.href='orders.php'" class="btn btn-primary">View Orders</button>
                        <button onclick="window.location.href='dashboard.php'" class="btn btn-secondary">Continue Shopping</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
        }
    </script>
</body>
</html>
