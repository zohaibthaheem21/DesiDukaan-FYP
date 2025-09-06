<?php
/**
 * DesiDukaan - My Orders
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/navbar.php';
require_once '../includes/cart.php';
require_once '../includes/csrf_protection.php';

// Initialize CSRF protection
initCSRFProtection();

// Require customer login
require_role('customer');

// Get customer orders with enhanced information
$db = getDB();
try {
    $stmt = $db->prepare("
        SELECT o.*, COUNT(oi.id) as item_count,
               s.shop_name, s.email as vendor_email, s.phone_number as vendor_phone,
               u_vendor.full_name as vendor_name
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN shops s ON o.shop_id = s.id
        LEFT JOIN users u_vendor ON s.seller_id = u_vendor.id
        WHERE o.customer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
}

// Get order details if viewing specific order
$order_details = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $order_id = intval($_GET['view']);
    try {
        $stmt = $db->prepare("
            SELECT o.*, u.username, u.email,
                   s.shop_name, s.email as vendor_email, s.phone_number as vendor_phone,
                   u_vendor.full_name as vendor_name
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            LEFT JOIN shops s ON o.shop_id = s.id
            LEFT JOIN users u_vendor ON s.seller_id = u_vendor.id
            WHERE o.id = ? AND o.customer_id = ?
        ");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        $order_details = $stmt->fetch();

        if ($order_details) {
            $stmt = $db->prepare("
                SELECT oi.*, p.title, p.images, s.shop_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN shops s ON p.shop_id = s.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order_details['items'] = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        $order_details = null;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo t('my_orders'); ?> - <?php echo t('site_name'); ?></title>
    <?php echo csrfMetaTag(); ?>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <?php render_navbar('customer', '../'); ?>

    <!-- Orders Content -->
    <div class="container" style="padding: 2rem 20px;">
        <?php if ($order_details): ?>
            <!-- Order Details View -->
            <div class="order-details-header">
                <a href="orders.php" class="back-btn">← <?php echo t('back_to_orders'); ?></a>
                <h1 class="page-title">📦 <?php echo t('order_details'); ?></h1>
            </div>
            
            <div class="order-details-card">
                <div class="order-header">
                    <div class="order-info">
                        <h2><?php echo t('order'); ?> #<?php echo htmlspecialchars($order_details['order_number']); ?></h2>
                        <p class="order-date"><?php echo date('F j, Y g:i A', strtotime($order_details['created_at'])); ?></p>
                    </div>
                    <div class="order-status">
                        <span class="status-badge status-<?php echo $order_details['status']; ?>">
                            <?php echo ucfirst($order_details['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="order-items">
                    <h3><?php echo t('order_items'); ?></h3>
                    <?php foreach ($order_details['items'] as $item): ?>
                        <?php
                        $images = $item['images'] ? json_decode($item['images'], true) : [];
                        $first_image = !empty($images) ? $images[0] : null;
                        ?>
                        <div class="order-item">
                            <div class="item-image">
                                <?php if ($first_image && file_exists(UPLOAD_PATH . 'products/' . $first_image)): ?>
                                    <img src="<?php echo UPLOAD_URL . 'products/' . $first_image; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <?php else: ?>
                                    <div class="no-image">🎨</div>
                                <?php endif; ?>
                            </div>
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                <p class="item-shop">by <?php echo htmlspecialchars($item['shop_name']); ?></p>
                                <?php if ($item['size']): ?>
                                    <p class="item-meta"><?php echo t('size'); ?>: <?php echo htmlspecialchars($item['size']); ?></p>
                                <?php endif; ?>
                                <?php if ($item['color']): ?>
                                    <p class="item-meta"><?php echo t('color'); ?>: <?php echo htmlspecialchars($item['color']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="item-quantity">
                                <span><?php echo t('quantity'); ?>: <?php echo $item['quantity']; ?></span>
                            </div>
                            <div class="item-price">
                                <span>Rs. <?php echo number_format($item['price'], 2); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-summary">
                    <div class="summary-row">
                        <span><?php echo t('total_amount'); ?>:</span>
                        <span class="total-amount">Rs. <?php echo number_format($order_details['total_amount'], 2); ?></span>
                    </div>
                </div>
                
                <div class="order-shipping">
                    <h3><?php echo t('shipping_address'); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars($order_details['shipping_address'])); ?></p>
                    <p><strong><?php echo t('phone'); ?>:</strong> <?php echo htmlspecialchars($order_details['phone_number']); ?></p>
                </div>
            </div>
        <?php else: ?>
            <!-- Orders List View -->
            <div class="orders-header">
                <h1 class="orders-title">📦 <?php echo t('my_orders'); ?></h1>
                <p class="orders-subtitle"><?php echo count($orders); ?> <?php echo t('orders_found'); ?></p>
            </div>

            <?php if (empty($orders)): ?>
                <!-- No Orders -->
                <div class="empty-orders">
                    <div class="empty-orders-icon">📦</div>
                    <h2 class="empty-orders-title"><?php echo t('no_orders_yet'); ?></h2>
                    <p class="empty-orders-text"><?php echo t('start_shopping_message'); ?></p>
                    <a href="dashboard.php" class="btn btn-primary"><?php echo t('start_shopping'); ?></a>
                </div>
            <?php else: ?>
                <!-- Orders List -->
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-card-header">
                                <div class="order-number">
                                    <h3><?php echo t('order'); ?> #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                                    <p class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                </div>
                                <div class="order-status">
                                    <?php
                                    $statusInfo = [
                                        'pending' => ['icon' => '⏳', 'color' => '#ffc107', 'text' => 'Pending Confirmation'],
                                        'confirmed' => ['icon' => '✅', 'color' => '#28a745', 'text' => 'Confirmed'],
                                        'processing' => ['icon' => '⚙️', 'color' => '#007bff', 'text' => 'Processing'],
                                        'shipped' => ['icon' => '🚚', 'color' => '#17a2b8', 'text' => 'Shipped'],
                                        'delivered' => ['icon' => '📦', 'color' => '#6f42c1', 'text' => 'Delivered'],
                                        'cancelled' => ['icon' => '❌', 'color' => '#dc3545', 'text' => 'Cancelled'],
                                        'rejected' => ['icon' => '🚫', 'color' => '#dc3545', 'text' => 'Rejected by Seller']
                                    ];
                                    $status = $statusInfo[$order['status']];
                                    ?>
                                    <span class="status-badge status-<?php echo $order['status']; ?>" style="background-color: <?php echo $status['color']; ?>; color: white;">
                                        <?php echo $status['icon']; ?> <?php echo $status['text']; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Status Timeline -->
                            <div class="order-timeline">
                                <?php if ($order['status'] === 'rejected'): ?>
                                    <div class="rejection-notice">
                                        <div class="rejection-icon">🚫</div>
                                        <div class="rejection-text">
                                            <h4>Order Rejected by Seller</h4>
                                            <p>Unfortunately, this order could not be processed by the seller.</p>
                                            <?php if (!empty($order['vendor_notes'])): ?>
                                                <p><strong>Reason:</strong> <?php echo htmlspecialchars($order['vendor_notes']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php
                                    $timelineSteps = [
                                        'pending' => 'Order Placed',
                                        'confirmed' => 'Confirmed by Seller',
                                        'processing' => 'Being Prepared',
                                        'shipped' => 'Shipped',
                                        'delivered' => 'Delivered'
                                    ];

                                    $currentStepIndex = array_search($order['status'], array_keys($timelineSteps));
                                    if ($order['status'] === 'cancelled') $currentStepIndex = -1;
                                    ?>
                                    <div class="timeline-container">
                                        <?php foreach ($timelineSteps as $stepStatus => $stepLabel): ?>
                                            <?php
                                            $stepIndex = array_search($stepStatus, array_keys($timelineSteps));
                                            $isCompleted = $stepIndex <= $currentStepIndex;
                                            $isCurrent = $stepIndex === $currentStepIndex;
                                            ?>
                                            <div class="timeline-step <?php echo $isCompleted ? 'completed' : ''; ?> <?php echo $isCurrent ? 'current' : ''; ?>">
                                                <div class="timeline-dot"></div>
                                                <div class="timeline-label"><?php echo $stepLabel; ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Delivery Estimate and Vendor Contact -->
                            <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered' && $order['status'] !== 'rejected'): ?>
                            <div class="delivery-estimate">
                                <?php
                                $estimateText = '';
                                switch ($order['status']) {
                                    case 'pending':
                                        $estimateText = '⏰ Waiting for seller confirmation';
                                        break;
                                    case 'confirmed':
                                        $estimateText = '📦 Will be processed within 1-2 business days';
                                        break;
                                    case 'processing':
                                        $estimateText = '🚚 Expected to ship within 24 hours';
                                        break;
                                    case 'shipped':
                                        $estimateText = '🏠 Expected delivery within 2-3 business days';
                                        break;
                                }
                                ?>
                                <p class="estimate-text"><?php echo $estimateText; ?></p>
                            </div>
                            <?php endif; ?>

                            <!-- Vendor Contact Information -->
                            <?php if (in_array($order['status'], ['confirmed', 'processing', 'shipped']) && ($order['vendor_email'] || $order['vendor_phone'])): ?>
                            <div class="vendor-contact">
                                <h4>📞 Seller Contact Information</h4>
                                <div class="vendor-info">
                                    <?php if ($order['shop_name']): ?>
                                        <p><strong>Shop:</strong> <?php echo htmlspecialchars($order['shop_name']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($order['vendor_name']): ?>
                                        <p><strong>Seller:</strong> <?php echo htmlspecialchars($order['vendor_name']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($order['vendor_email']): ?>
                                        <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($order['vendor_email']); ?>"><?php echo htmlspecialchars($order['vendor_email']); ?></a></p>
                                    <?php endif; ?>
                                    <?php if ($order['vendor_phone']): ?>
                                        <p><strong>Phone:</strong> <a href="tel:<?php echo htmlspecialchars($order['vendor_phone']); ?>"><?php echo htmlspecialchars($order['vendor_phone']); ?></a></p>
                                    <?php endif; ?>
                                </div>
                                <p class="contact-note">You can contact the seller directly for any questions about your order.</p>
                            </div>
                            <?php endif; ?>

                            <div class="order-card-body">
                                <div class="order-meta">
                                    <span class="order-items-count"><?php echo $order['item_count']; ?> <?php echo t('items'); ?></span>
                                    <span class="order-total">Rs. <?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>

                            <div class="order-card-actions">
                                <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                                    👁️ <?php echo t('view_details'); ?>
                                </a>
                                <?php
                                // Show cancel button only if order is pending and customer can still cancel
                                $canCancel = $order['status'] === 'pending' &&
                                           (!isset($order['customer_can_cancel']) || $order['customer_can_cancel'] == 1);
                                ?>
                                <?php if ($canCancel): ?>
                                    <button class="btn btn-secondary btn-sm" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                        ❌ <?php echo t('cancel_order'); ?>
                                    </button>
                                <?php elseif ($order['status'] === 'rejected'): ?>
                                    <a href="dashboard.php" class="btn btn-info btn-sm">
                                        🛍️ Shop Again
                                    </a>
                                <?php elseif (in_array($order['status'], ['confirmed', 'processing', 'shipped'])): ?>
                                    <span class="order-status-note">
                                        ✅ Order confirmed - Cannot be cancelled
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/customer.js"></script>

    <style>
        /* Enhanced Order Tracking Styles */
        .order-timeline {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .timeline-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin: 10px 0;
        }

        .timeline-container::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }

        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .timeline-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dee2e6;
            border: 3px solid white;
            margin-bottom: 8px;
            transition: all 0.3s ease;
        }

        .timeline-step.completed .timeline-dot {
            background: #28a745;
        }

        .timeline-step.current .timeline-dot {
            background: #007bff;
            transform: scale(1.2);
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.2);
        }

        .timeline-label {
            font-size: 12px;
            text-align: center;
            color: #6c757d;
            font-weight: 500;
            max-width: 80px;
        }

        .timeline-step.completed .timeline-label {
            color: #28a745;
            font-weight: 600;
        }

        .timeline-step.current .timeline-label {
            color: #007bff;
            font-weight: 600;
        }

        .delivery-estimate {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            padding: 12px 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #2196f3;
        }

        .estimate-text {
            margin: 0;
            font-size: 14px;
            color: #1976d2;
            font-weight: 500;
        }

        .vendor-contact {
            background: linear-gradient(135deg, #f0f8ff, #e6f3ff);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }

        .vendor-contact h4 {
            margin: 0 0 10px 0;
            color: #007bff;
            font-size: 16px;
        }

        .vendor-info p {
            margin: 5px 0;
            font-size: 14px;
        }

        .vendor-info a {
            color: #007bff;
            text-decoration: none;
        }

        .vendor-info a:hover {
            text-decoration: underline;
        }

        .contact-note {
            margin: 10px 0 0 0;
            font-size: 12px;
            color: #6c757d;
            font-style: italic;
        }

        .rejection-notice {
            background: linear-gradient(135deg, #fff5f5, #fed7d7);
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #e53e3e;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .rejection-icon {
            font-size: 32px;
            flex-shrink: 0;
        }

        .rejection-text h4 {
            margin: 0 0 8px 0;
            color: #e53e3e;
            font-size: 16px;
        }

        .rejection-text p {
            margin: 5px 0;
            font-size: 14px;
            color: #2d3748;
        }

        .order-status-note {
            font-size: 12px;
            color: #28a745;
            font-weight: 500;
            padding: 4px 8px;
            background: #d4edda;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            margin-bottom: 20px;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }

        .order-card-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-number h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 18px;
            font-weight: 700;
        }

        .order-date {
            margin: 5px 0 0 0;
            color: #6c757d;
            font-size: 14px;
        }

        .order-card-body {
            padding: 15px 20px;
        }

        .order-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-items-count {
            color: #6c757d;
            font-size: 14px;
        }

        .order-total {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
        }

        .order-card-actions {
            padding: 15px 20px;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .timeline-container {
                flex-direction: column;
                gap: 15px;
            }

            .timeline-container::before {
                display: none;
            }

            .timeline-step {
                flex-direction: row;
                width: 100%;
                justify-content: flex-start;
                gap: 15px;
            }

            .timeline-label {
                max-width: none;
                text-align: left;
            }

            .order-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .order-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();

            // Auto-refresh order status every 30 seconds
            setInterval(function() {
                // Only refresh if we're on the orders list page (not viewing details)
                if (!window.location.search.includes('view=')) {
                    checkForOrderUpdates();
                }
            }, 30000);
        });

        function cancelOrder(orderId) {
            if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                return;
            }

            const csrfToken = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

            fetch('../includes/customer_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=cancel_order&order_id=${orderId}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Order cancelled successfully', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            });
        }

        function checkForOrderUpdates() {
            fetch('../includes/get_order_updates.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.has_updates) {
                    // Show notification about order updates
                    showNotification('Your orders have been updated. Refreshing...', 'info');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error checking for updates:', error);
            });
        }

        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;

            // Add styles
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1'};
                color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
                border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#bee5eb'};
                border-radius: 8px;
                padding: 15px;
                max-width: 400px;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease;
            `;

            // Add to page
            document.body.appendChild(notification);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Add CSS for notification animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            .notification-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .notification-close {
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                margin-left: 10px;
                opacity: 0.7;
            }
            .notification-close:hover {
                opacity: 1;
            }
        `;
        document.head.appendChild(style);

        // Initialize CSRF token
        window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    </script>

    <?php echo getCSRFJavaScript(); ?>
</body>
</html>
