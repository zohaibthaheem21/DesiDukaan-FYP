<?php
/**
 * Cart Checkout Page
 */

require_once '../config/config.php';
require_once '../includes/cart.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated as customer
if (!isCustomerAuthenticated()) {
    header('Location: ../auth/login.php');
    exit;
}

// Get cart items
$cart_items = getCartItems();
$cart_total = getCartTotal();

if (empty($cart_items)) {
    header('Location: cart.php?error=empty_cart');
    exit;
}

// Get user details
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - DesiDukaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-shipping-fast"></i> Shipping Information</h4>
                    </div>
                    <div class="card-body">
                        <form id="checkoutForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="customer_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                           value="<?= htmlspecialchars($user['full_name'] ?? $user['username']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone_number" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address_line_1" class="form-label">Address Line 1 *</label>
                                <input type="text" class="form-control" id="address_line_1" name="address_line_1" 
                                       placeholder="House/Flat No., Street Name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address_line_2" class="form-label">Address Line 2</label>
                                <input type="text" class="form-control" id="address_line_2" name="address_line_2" 
                                       placeholder="Landmark, Area">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">State *</label>
                                    <input type="text" class="form-control" id="state" name="state" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="postal_code" class="form-label">Postal Code *</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value="cod">Cash on Delivery (COD)</option>
                                    <option value="online">Online Payment</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-shopping-cart"></i> Order Summary</h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <small><?= htmlspecialchars($item['title']) ?></small>
                                    <br>
                                    <small class="text-muted">Qty: <?= $item['cart_quantity'] ?> × ₹<?= number_format($item['price'], 2) ?></small>
                                </div>
                                <div>
                                    <strong>₹<?= number_format($item['subtotal'], 2) ?></strong>
                                </div>
                            </div>
                            <hr>
                        <?php endforeach; ?>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <h5>Total:</h5>
                            <h5 class="text-primary">₹<?= number_format($cart_total, 2) ?></h5>
                        </div>
                        
                        <button type="button" class="btn btn-primary w-100 mt-3" onclick="placeOrder()">
                            <i class="fas fa-credit-card"></i> Place Order
                        </button>
                        
                        <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fas fa-arrow-left"></i> Back to Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function placeOrder() {
            const form = document.getElementById('checkoutForm');
            const formData = new FormData(form);
            
            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Disable button to prevent double submission
            const button = event.target;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            fetch('../includes/process_cart_checkout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Orders placed successfully! You will receive email confirmations shortly.');
                    window.location.href = 'orders.php';
                } else {
                    alert('Error: ' + data.message);
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-credit-card"></i> Place Order';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while placing the order. Please try again.');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-credit-card"></i> Place Order';
            });
        }
    </script>
</body>
</html>
