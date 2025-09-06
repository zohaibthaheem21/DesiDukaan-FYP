<?php
/**
 * DesiDukaan - Seller Orders Management
 * Complete order management system for vendors
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/seller_navbar.php';
require_once '../includes/csrf_protection.php';

// Initialize CSRF protection
initCSRFProtection();

// Require seller login
require_role('seller');

// Get seller's shop ID
$db = getDB();
$stmt = $db->prepare("SELECT id FROM shops WHERE seller_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

if (!$shop) {
    // Show a message instead of redirecting
    $no_shop_error = true;
} else {
    $no_shop_error = false;
}

$shop_id = $shop['id'] ?? 0;

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query conditions
$conditions = ["o.shop_id = ?"];
$params = [$shop_id];

if ($status_filter !== 'all') {
    $conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($date_filter !== 'all') {
    switch ($date_filter) {
        case 'today':
            $conditions[] = "DATE(o.created_at) = CURDATE()";
            break;
        case 'week':
            $conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

if (!empty($search)) {
    $conditions[] = "(o.order_number LIKE ? OR u.full_name LIKE ? OR oi.product_name LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = implode(' AND ', $conditions);

// Get orders with customer and product information
try {
    $stmt = $db->prepare("
        SELECT o.*,
               COALESCE(u.full_name, u.username) as customer_name,
               u.email as customer_email,
               COALESCE(u.phone, '') as customer_phone,
               COALESCE(sa.customer_name, '') as shipping_name,
               COALESCE(sa.phone_number, '') as phone_number,
               COALESCE(sa.address_line_1, '') as address_line_1,
               COALESCE(sa.city, '') as city,
               COALESCE(sa.state, '') as state,
               COALESCE(sa.postal_code, '') as postal_code,
               GROUP_CONCAT(COALESCE(oi.product_name, 'Unknown Product') SEPARATOR ', ') as products,
               COUNT(oi.id) as item_count,
               SUM(COALESCE(oi.quantity, 0)) as total_quantity
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        LEFT JOIN shipping_addresses sa ON o.id = sa.order_id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE {$whereClause}
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px;'>Query preparation error: " . $e->getMessage() . "</div>";
    $stmt = null;
}
// Initialize default values
$orders = [];
$stats = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'confirmed_orders' => 0,
    'shipped_orders' => 0,
    'delivered_orders' => 0,
    'total_revenue' => 0
];

// Only execute queries if shop exists and query was prepared successfully
if (!$no_shop_error && $shop_id > 0 && $stmt) {
    try {
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        echo "<!-- Debug: Found " . count($orders) . " orders for shop $shop_id -->";
    } catch (Exception $e) {
        echo "<div style='color: red; padding: 20px;'>Query execution error: " . $e->getMessage() . "</div>";
        $orders = [];
    }

    // Get order statistics
    try {
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(total_amount) as total_revenue
            FROM orders
            WHERE shop_id = ?
        ");
        $stmt->execute([$shop_id]);
        $stats = $stmt->fetch();
        echo "<!-- Debug: Stats query executed successfully -->";
    } catch (Exception $e) {
        echo "<div style='color: red; padding: 20px;'>Stats query error: " . $e->getMessage() . "</div>";
        $stats = [
            'total_orders' => 0,
            'pending_orders' => 0,
            'confirmed_orders' => 0,
            'shipped_orders' => 0,
            'delivered_orders' => 0,
            'total_revenue' => 0
        ];
    }
} else {
    echo "<!-- Debug: No shop error: " . ($no_shop_error ? 'true' : 'false') . ", Shop ID: $shop_id, Stmt: " . ($stmt ? 'prepared' : 'null') . " -->";
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo t('orders'); ?> - <?php echo t('site_name'); ?></title>
    <?php echo csrfMetaTag(); ?>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/seller.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <style>
        .orders-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .page-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #e74c3c;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #7f8c8d;
            font-weight: 600;
        }

        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .filter-group select,
        .filter-group input {
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        .filter-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .orders-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #ddd;
        }

        .table-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .orders-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .order-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            transition: background 0.3s ease;
        }

        .order-item:hover {
            background: #f8f9fa;
        }

        .order-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .order-number {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .order-date {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .order-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-processing { background: #cce7ff; color: #004085; }
        .status-shipped { background: #e2e3e5; color: #383d41; }
        .status-delivered { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .order-details {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .customer-info h4,
        .product-info h4,
        .amount-info h4 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .order-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-accept { background: #28a745; color: white; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-ship { background: #007bff; color: white; }
        .btn-deliver { background: #17a2b8; color: white; }
        .btn-cancel { background: #6c757d; color: white; }
        .btn-view { background: #6c757d; color: white; }

        .status-info {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }

        /* Enhanced Mobile Responsiveness */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .orders-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .order-card {
                padding: 15px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .order-number {
                font-size: 16px;
            }

            .order-status {
                font-size: 12px;
                padding: 4px 8px;
            }

            .order-details {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .order-actions {
                flex-direction: column;
                gap: 8px;
            }

            .action-btn {
                width: 100%;
                text-align: center;
                padding: 10px;
                font-size: 14px;
            }

            .order-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .order-amount {
                font-size: 18px;
            }

            .order-date {
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 5px;
            }

            .order-card {
                padding: 12px;
                margin-bottom: 10px;
            }

            .order-number {
                font-size: 14px;
            }

            .order-amount {
                font-size: 16px;
            }

            .action-btn {
                padding: 8px;
                font-size: 12px;
            }

            .order-status {
                font-size: 11px;
                padding: 3px 6px;
            }
        }

        /* Touch-friendly improvements */
        @media (hover: none) and (pointer: coarse) {
            .action-btn {
                min-height: 44px;
                padding: 12px 16px;
            }

            .order-card {
                padding: 16px;
            }
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .orders-container {
                margin: 1rem auto;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .order-details {
                grid-template-columns: 1fr;
            }

            .order-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body class="logged-in seller-orders">


    <!-- Navigation -->
    <?php render_seller_navbar('../'); ?>

    <div class="orders-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">📦 Orders Management</h1>
            <p class="page-subtitle">Manage and track all your orders</p>

        </div>

        <?php if ($no_shop_error): ?>
        <!-- No Shop Error -->
        <div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;">
            <h2>🏪 Shop Required</h2>
            <p>You need to create and set up your shop before you can manage orders.</p>
            <div style="margin-top: 20px;">
                <a href="create-shop.php" style="background: #e74c3c; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    🏪 Create Your Shop
                </a>
                <a href="dashboard.php" style="background: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-left: 10px;">
                    📊 Go to Dashboard
                </a>
            </div>
        </div>
        <?php else: ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_orders']; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['confirmed_orders']; ?></div>
                <div class="stat-label">Confirmed Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['shipped_orders']; ?></div>
                <div class="stat-label">Shipped Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['delivered_orders']; ?></div>
                <div class="stat-label">Delivered Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">Rs. <?php echo number_format($stats['total_revenue'], 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="status">Order Status</label>
                        <select name="status" id="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="date">Date Range</label>
                        <select name="date" id="date">
                            <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="search">Search</label>
                        <input type="text" name="search" id="search" placeholder="Order number, customer, product..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="filter-btn">🔍 Filter Orders</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="orders-table">
            <div class="table-header">
                <h3 class="table-title">Orders List (<?php echo count($orders); ?> orders)</h3>
            </div>

            <div class="orders-list">
                <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h3>No Orders Found</h3>
                    <p>You haven't received any orders yet.</p>
                    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <h4>💡 Tips to get your first order:</h4>
                        <ul style="text-align: left; margin: 10px 0;">
                            <li>✅ Make sure your shop is approved and active</li>
                            <li>📦 Add attractive products with good descriptions</li>
                            <li>💰 Set competitive prices</li>
                            <li>📸 Upload high-quality product images</li>
                            <li>📢 Share your shop with friends and family</li>
                        </ul>
                        <p><a href="products.php" style="color: #e74c3c; font-weight: bold;">➕ Add Products</a> |
                           <a href="dashboard.php" style="color: #e74c3c; font-weight: bold;">📊 View Dashboard</a></p>
                    </div>

                </div>
                <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="order-item" data-order-id="<?php echo $order['id']; ?>">
                    <div class="order-header">
                        <div>
                            <div class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                            <div class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <span class="order-status status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>

                    <div class="order-details">
                        <div class="customer-info">
                            <h4>👤 Customer</h4>
                            <p><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                            <p>📧 <?php echo htmlspecialchars($order['customer_email']); ?></p>
                            <?php if ($order['customer_phone']): ?>
                            <p>📞 <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                            <?php endif; ?>
                            <?php if ($order['phone_number']): ?>
                            <p>📱 <?php echo htmlspecialchars($order['phone_number']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <h4>🛍️ Products (<?php echo $order['item_count']; ?> items)</h4>
                            <p><?php echo htmlspecialchars($order['products']); ?></p>
                            <p>Qty: <?php echo $order['total_quantity']; ?></p>
                        </div>

                        <div class="amount-info">
                            <h4>💰 Amount</h4>
                            <p><strong>Rs. <?php echo number_format($order['total_amount'], 2); ?></strong></p>
                            <p>💳 <?php echo ucfirst($order['payment_method'] ?? 'COD'); ?></p>
                            <p>📊 <?php echo ucfirst($order['payment_status'] ?? 'Pending'); ?></p>
                        </div>

                        <?php if ($order['shipping_name'] || $order['address_line_1']): ?>
                        <div class="shipping-info">
                            <h4>🚚 Shipping Address</h4>
                            <?php if ($order['shipping_name']): ?>
                            <p><strong><?php echo htmlspecialchars($order['shipping_name']); ?></strong></p>
                            <?php endif; ?>
                            <?php if ($order['address_line_1']): ?>
                            <p>📍 <?php echo htmlspecialchars($order['address_line_1']); ?></p>
                            <?php if ($order['city'] && $order['state']): ?>
                            <p>🏙️ <?php echo htmlspecialchars($order['city']) . ', ' . htmlspecialchars($order['state']); ?></p>
                            <?php endif; ?>
                            <?php if ($order['postal_code']): ?>
                            <p>📮 <?php echo htmlspecialchars($order['postal_code']); ?></p>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="order-actions">
                        <?php if ($order['status'] === 'pending'): ?>
                        <button class="action-btn btn-accept" onclick="acceptOrder(<?php echo $order['id']; ?>)">
                            ✅ Accept Order
                        </button>
                        <button class="action-btn btn-reject" onclick="rejectOrder(<?php echo $order['id']; ?>)">
                            ❌ Reject Order
                        </button>
                        <?php elseif ($order['status'] === 'confirmed'): ?>
                        <button class="action-btn btn-ship" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'shipped')">
                            🚚 Mark as Shipped
                        </button>
                        <button class="action-btn btn-cancel" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled')">
                            ❌ Cancel Order
                        </button>
                        <?php elseif ($order['status'] === 'shipped'): ?>
                        <button class="action-btn btn-deliver" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')">
                            📦 Mark as Delivered
                        </button>
                        <?php elseif ($order['status'] === 'rejected'): ?>
                        <span class="status-info">❌ Order was rejected</span>
                        <?php elseif ($order['status'] === 'delivered'): ?>
                        <span class="status-info">✅ Order completed</span>
                        <?php endif; ?>

                        <button class="action-btn btn-view" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                            👁️ View Details
                        </button>

                        <?php if ($order['customer_phone'] || $order['phone_number']): ?>
                        <button class="action-btn btn-contact" onclick="contactCustomer('<?php echo htmlspecialchars($order['customer_email']); ?>', '<?php echo htmlspecialchars($order['customer_phone'] ?? $order['phone_number'] ?? ''); ?>')">
                            📞 Contact Customer
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php endif; // End of shop check ?>

    <script src="../assets/js/vendor-ajax.js"></script>
    <script src="../assets/js/main.js"></script>

    <script>
        function acceptOrder(orderId) {
            const notes = prompt('Add any notes for the customer (optional):');

            if (confirm('Are you sure you want to accept this order? The customer will be notified and will no longer be able to cancel.')) {
                updateOrderStatusWithNotes(orderId, 'confirmed', notes);
            }
        }

        function rejectOrder(orderId) {
            const reason = prompt('Please provide a reason for rejection (this will be sent to the customer):');

            if (!reason || reason.trim() === '') {
                alert('Please provide a reason for rejection.');
                return;
            }

            if (confirm('Are you sure you want to reject this order? The customer will be notified.')) {
                updateOrderStatusWithNotes(orderId, 'rejected', null, reason);
            }
        }

        function updateOrderStatus(orderId, newStatus) {
            if (!confirm(`Are you sure you want to mark this order as ${newStatus}?`)) {
                return;
            }

            updateOrderStatusWithNotes(orderId, newStatus);
        }

        function updateOrderStatusWithNotes(orderId, newStatus, vendorNotes = null, rejectionReason = null) {
            const formData = new FormData();
            formData.append('action', 'update_order_status');
            formData.append('order_id', orderId);
            formData.append('status', newStatus);
            formData.append('csrf_token', window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '');

            if (vendorNotes) {
                formData.append('vendor_notes', vendorNotes);
            }

            if (rejectionReason) {
                formData.append('rejection_reason', rejectionReason);
            }

            fetch('../includes/vendor_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    // Reload page to show updated status
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

        function viewOrderDetails(orderId) {
            // Open order details in a new window or modal
            window.open(`order_details.php?id=${orderId}`, '_blank', 'width=800,height=600');
        }

        function contactCustomer(email, phone) {
            const options = [];

            if (email) {
                options.push(`📧 Email: ${email}`);
            }
            if (phone) {
                options.push(`📞 Phone: ${phone}`);
            }

            if (options.length === 0) {
                alert('No contact information available');
                return;
            }

            const message = `Customer Contact Information:\n\n${options.join('\n')}\n\nContact information copied to clipboard!`;

            // Copy email to clipboard
            if (email) {
                navigator.clipboard.writeText(email).then(() => {
                    alert(message);
                }).catch(() => {
                    alert(`Customer Contact:\n\n${options.join('\n')}`);
                });
            } else {
                alert(`Customer Contact:\n\n${options.join('\n')}`);
            }
        }

        // Initialize CSRF token
        window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        // Auto-refresh orders every 30 seconds for real-time updates
        setInterval(function() {
            // Only refresh if no modals are open and user is active
            if (document.visibilityState === 'visible') {
                checkForOrderUpdates();
            }
        }, 30000);

        function checkForOrderUpdates() {
            fetch('../includes/get_order_updates_vendor.php', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.has_updates) {
                    showNotification('New orders or updates available. Refreshing...', 'info');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error checking for updates:', error);
            });
        }
    </script>

    <?php echo getCSRFJavaScript(); ?>
</body>
</html>
