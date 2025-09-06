<?php
/**
 * DesiDukaan - Order Confirmation Page
 * Shows order confirmation details after successful purchase
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/navbar.php';

// Require customer login
require_role('customer');

// Get order ID from URL
$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Get order details
$db = getDB();
$stmt = $db->prepare("
    SELECT o.*, s.shop_name, s.seller_id, u.full_name as seller_name,
           sa.customer_name, sa.phone_number, sa.address_line_1, sa.address_line_2,
           sa.city, sa.state, sa.postal_code, sa.landmark
    FROM orders o
    JOIN shops s ON o.shop_id = s.id
    JOIN users u ON s.seller_id = u.id
    LEFT JOIN shipping_addresses sa ON o.id = sa.order_id
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: dashboard.php');
    exit;
}

// Get order items
$stmt = $db->prepare("
    SELECT oi.*, p.image as current_image
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('order_confirmation'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .success-header {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 3rem 2rem;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(39, 174, 96, 0.3);
        }

        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .success-title {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .success-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .order-details-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #e74c3c;
        }

        .info-label {
            font-weight: 600;
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 1.1rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .item-price {
            color: #e74c3c;
            font-weight: 600;
        }

        .shipping-address {
            background: #e8f5e8;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #27ae60;
        }

        .next-steps {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
        }

        .steps-list {
            list-style: none;
            padding: 0;
        }

        .steps-list li {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
        }

        .step-number {
            background: rgba(255, 255, 255, 0.2);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .confirmation-container {
                margin: 1rem auto;
            }

            .success-header {
                padding: 2rem 1rem;
            }

            .success-title {
                font-size: 2rem;
            }

            .order-details-card {
                padding: 1.5rem;
            }

            .order-info-grid {
                grid-template-columns: 1fr;
            }

            .order-item {
                flex-direction: column;
                text-align: center;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="logged-in customer-confirmation">
    <!-- Navigation -->
    <?php render_navbar('customer', '../'); ?>

    <div class="confirmation-container">
        <!-- Success Header -->
        <div class="success-header">
            <div class="success-icon">🎉</div>
            <h1 class="success-title">Order Placed Successfully!</h1>
            <p class="success-subtitle">Thank you for your purchase. Your order is being processed.</p>
        </div>

        <!-- Order Details -->
        <div class="order-details-card">
            <h2 class="section-title">📦 Order Details</h2>
            
            <div class="order-info-grid">
                <div class="info-item">
                    <div class="info-label">Order Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['order_number']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Order Date</div>
                    <div class="info-value"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total Amount</div>
                    <div class="info-value">Rs. <?php echo number_format($order['total_amount'], 2); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">Cash on Delivery</div>
                </div>
            </div>

            <!-- Order Items -->
            <h3 class="section-title">🛍️ Items Ordered</h3>
            <?php foreach ($order_items as $item): ?>
            <div class="order-item">
                <img src="../<?php echo htmlspecialchars($item['product_image'] ?? $item['current_image'] ?? 'assets/images/no-image.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                     class="item-image">
                <div class="item-details">
                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                    <div class="item-price">
                        Rs. <?php echo number_format($item['unit_price'], 2); ?> × <?php echo $item['quantity']; ?> = 
                        Rs. <?php echo number_format($item['total_price'], 2); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Shipping Address -->
            <h3 class="section-title">🚚 Shipping Address</h3>
            <div class="shipping-address">
                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                Phone: <?php echo htmlspecialchars($order['phone_number']); ?><br>
                <?php echo htmlspecialchars($order['address_line_1']); ?>
                <?php if ($order['address_line_2']): ?>, <?php echo htmlspecialchars($order['address_line_2']); ?><?php endif; ?><br>
                <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> - <?php echo htmlspecialchars($order['postal_code']); ?>
                <?php if ($order['landmark']): ?><br>Landmark: <?php echo htmlspecialchars($order['landmark']); ?><?php endif; ?>
            </div>

            <!-- Shop Information -->
            <h3 class="section-title">🏪 Shop Information</h3>
            <div class="info-item">
                <div class="info-label">Shop Name</div>
                <div class="info-value"><?php echo htmlspecialchars($order['shop_name']); ?></div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="next-steps">
            <h2 class="section-title">📋 What Happens Next?</h2>
            <ul class="steps-list">
                <li>
                    <span class="step-number">1</span>
                    <span>Your order is being processed by the seller</span>
                </li>
                <li>
                    <span class="step-number">2</span>
                    <span>You'll receive email and SMS updates on order status</span>
                </li>
                <li>
                    <span class="step-number">3</span>
                    <span>Your order will be shipped within 1-2 business days</span>
                </li>
                <li>
                    <span class="step-number">4</span>
                    <span>Expected delivery: 3-7 business days</span>
                </li>
                <li>
                    <span class="step-number">5</span>
                    <span>Payment will be collected upon delivery (COD)</span>
                </li>
            </ul>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="orders.php" class="btn btn-primary">
                📦 Track Your Orders
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                🏠 Continue Shopping
            </a>
        </div>
    </div>

    <script src="../assets/js/ajax-utils.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/customer.js"></script>
</body>
</html>
