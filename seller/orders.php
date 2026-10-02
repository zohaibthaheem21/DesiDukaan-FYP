<?php
/**
 * DesiDukaan - Seller Orders Management
 * Complete order management system for vendors
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session first
session_start();

// Auto-login for testing (remove in production)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2; // Seller ID
    $_SESSION['user_role'] = 'seller';
    $_SESSION['username'] = 'zohaib';
}

// Handle different execution contexts
if (file_exists('../config/config.php')) {
    require_once '../config/config.php';
    require_once '../config/languages.php';
    require_once '../includes/seller_navbar.php';
} else {
    require_once 'config/config.php';
    require_once 'config/languages.php';
    require_once 'includes/seller_navbar.php';
}

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'seller') {
    // Redirect to login page
    header('Location: ../auth/login.php');
    exit;
}

// Handle AJAX requests for order status updates
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'update_order_status') {
        try {
            $order_id = (int)$_POST['order_id'];
            $new_status = $_POST['status'];
            $vendor_notes = $_POST['vendor_notes'] ?? '';
            $rejection_reason = $_POST['rejection_reason'] ?? '';

            // Validate status
            $valid_statuses = ['confirmed', 'rejected', 'processing', 'shipped', 'delivered', 'cancelled'];
            if (!in_array($new_status, $valid_statuses)) {
                throw new Exception('Invalid status');
            }

            $db = getDB();

            // Verify order belongs to this seller's shop
            $stmt = $db->prepare("
                SELECT o.*, s.seller_id
                FROM orders o
                JOIN shops s ON o.shop_id = s.id
                WHERE o.id = ? AND s.seller_id = ?
            ");
            $stmt->execute([$order_id, $_SESSION['user_id']]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception('Order not found or access denied');
            }

            // Update order status
            $update_fields = ['status = ?', 'updated_at = NOW()'];
            $update_params = [$new_status];

            if ($vendor_notes) {
                $update_fields[] = 'vendor_notes = ?';
                $update_params[] = $vendor_notes;
            }

            if ($rejection_reason) {
                $update_fields[] = 'rejection_reason = ?';
                $update_params[] = $rejection_reason;
            }

            // Add timestamp fields based on status
            switch ($new_status) {
                case 'confirmed':
                    $update_fields[] = 'confirmed_at = NOW()';
                    break;
                case 'rejected':
                    $update_fields[] = 'rejected_at = NOW()';
                    break;
                case 'shipped':
                    $update_fields[] = 'shipped_at = NOW()';
                    break;
                case 'delivered':
                    $update_fields[] = 'delivered_at = NOW()';
                    break;
            }

            $update_params[] = $order_id;

            $stmt = $db->prepare("UPDATE orders SET " . implode(', ', $update_fields) . " WHERE id = ?");
            $stmt->execute($update_params);

            // Send success response
            echo json_encode([
                'success' => true,
                'message' => 'Order status updated successfully'
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}

// Get seller's shop ID
$db = getDB();
$stmt = $db->prepare("SELECT id, shop_name FROM shops WHERE seller_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

if (!$shop) {
    $no_shop_error = true;
    $shop_id = 0;
} else {
    $no_shop_error = false;
    $shop_id = $shop['id'];
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';
$search = $_GET['search'] ?? '';

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

// Only execute queries if shop exists
if (!$no_shop_error && $shop_id > 0) {
    try {
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
            $conditions[] = "(o.order_number LIKE ? OR u.full_name LIKE ? OR u.username LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $conditions);

        // Get orders with customer information
        $stmt = $db->prepare("
            SELECT o.*,
                   COALESCE(u.full_name, u.username) as customer_name,
                   u.email as customer_email,
                   COALESCE(u.phone, '') as customer_phone,
                   GROUP_CONCAT(COALESCE(oi.product_name, 'Unknown Product') SEPARATOR ', ') as products,
                   COUNT(oi.id) as item_count,
                   SUM(COALESCE(oi.quantity, 0)) as total_quantity
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE {$whereClause}
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        // Get order statistics
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

    } catch (Exception $e) {
        error_log("Orders query error: " . $e->getMessage());
        $orders = [];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - DesiDukaan</title>
    <meta name="csrf-token" content="<?php echo bin2hex(random_bytes(32)); ?>">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #DC143C;
            --primary-hover: #B91C3C;
            --background-white: #FFFFFF;
            --text-dark: #333333;
            --text-light: #666666;
            --text-white: #FFFFFF;
            --border-light: #E0E0E0;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --shadow-medium: rgba(0, 0, 0, 0.15);
            --success-color: #28a745;
            --error-color: #dc3545;
            --warning-color: #ffc107;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
 
        }

        .orders-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: var(--text-white);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(220, 20, 60, 0.2);
        }

        .page-title {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
            background: var(--background-white);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px var(--shadow-light);
            text-align: center;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-hover));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(220, 20, 60, 0.15);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-light);
            font-weight: 600;
        }

        .filters-section {
            background: var(--background-white);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px var(--shadow-light);
            margin-bottom: 2rem;
            border: 1px solid var(--border-light);
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
            color: var(--text-dark);
        }

        .filter-group select,
        .filter-group input {
            padding: 0.75rem;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--background-white);
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(220, 20, 60, 0.1);
        }

        .filter-btn {
            background: var(--primary-color);
            color: var(--text-white);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(220, 20, 60, 0.2);
        }

        .filter-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 20, 60, 0.3);
        }

        .orders-table {
            background: var(--background-white);
            border-radius: 16px;
            box-shadow: 0 4px 20px var(--shadow-light);
            overflow: hidden;
            border: 1px solid var(--border-light);
        }

        .table-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1rem 1.5rem;
            border-bottom: 2px solid var(--primary-color);
        }

        .table-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-dark);
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
            justify-content: space-between;
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
        .status-rejected { background: #f8d7da; color: #721c24; }

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
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-accept {
            background: var(--success-color);
            color: var(--text-white);
        }
        .btn-reject {
            background: var(--error-color);
            color: var(--text-white);
        }
        .btn-ship {
            background: var(--primary-color);
            color: var(--text-white);
        }
        .btn-deliver {
            background: #17a2b8;
            color: var(--text-white);
        }
        .btn-cancel {
            background: #6c757d;
            color: var(--text-white);
        }
        .btn-contact {
            background: var(--primary-color);
            color: var(--text-white);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-accept:hover { background: #218838; }
        .btn-reject:hover { background: #c82333; }
        .btn-ship:hover { background: var(--primary-hover); }
        .btn-deliver:hover { background: #138496; }
        .btn-cancel:hover { background: #5a6268; }
        .btn-contact:hover { background: var(--primary-hover); }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: center;
        }

        /* Enhanced Mobile Responsiveness */
        @media (max-width: 1200px) {
            .orders-container {
                max-width: 100%;
                padding: 0 1rem;
            }
        }

        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .order-details {
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            body {
                /* padding-top: 70px; */
            }

            .orders-container {
                padding: 0 0.5rem;
            }

            .page-header {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .page-subtitle {
                font-size: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .stat-number {
                font-size: 1.5rem;
            }

            .filters-section {
                padding: 1rem;
            }

            .filters-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .filter-btn {
                width: 100%;
                padding: 1rem;
                font-size: 1rem;
            }

            .order-item {
                padding: 1rem;
            }

            .order-details {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .order-actions {
                justify-content: center;
                flex-direction: column;
                gap: 0.5rem;
            }

            .action-btn {
                width: 100%;
                padding: 0.75rem;
                font-size: 0.9rem;
            }

            .orders-list {
                max-height: none;
            }
        }

        @media (max-width: 480px) {
            .page-header {
                padding: 1rem;
                border-radius: 12px;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .stat-card {
                padding: 0.75rem;
            }

            .stat-number {
                font-size: 1.25rem;
            }

            .stat-label {
                font-size: 0.8rem;
            }

            .filters-section {
                padding: 0.75rem;
            }

            .filter-group label {
                font-size: 0.9rem;
            }

            .filter-group select,
            .filter-group input {
                padding: 0.5rem;
                font-size: 0.9rem;
            }

            .order-item {
                padding: 0.75rem;
            }

            .order-number {
                font-size: 1rem;
            }

            .order-date {
                font-size: 0.8rem;
            }

            .customer-info h4,
            .product-info h4,
            .amount-info h4 {
                font-size: 0.8rem;
            }

            .action-btn {
                padding: 0.5rem;
                font-size: 0.8rem;
            }
        }

        /* Print Styles */
        @media print {
            .navbar,
            .filters-section,
            .order-actions,
            footer {
                display: none !important;
            }

            .page-header {
                background: none !important;
                color: black !important;
            }

            .orders-container {
                max-width: 100% !important;
                padding: 0 !important;
            }

            .order-item {
                break-inside: avoid;
                border: 1px solid #ddd;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body class="logged-in seller-orders">

    <!-- Navigation -->
    <?php render_seller_navbar('orders', '../'); ?>

    <div class="orders-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">📦 Orders Management</h1>
            <p class="page-subtitle">Manage and track all your orders</p>
        </div>

        <?php if ($no_shop_error): ?>
        <!-- No Shop Error -->
        <div class="error-message">
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
                        <span style="color: #dc3545; font-weight: bold;">❌ Order was rejected</span>
                        <?php elseif ($order['status'] === 'delivered'): ?>
                        <span style="color: #28a745; font-weight: bold;">✅ Order completed</span>
                        <?php endif; ?>

                        <?php if ($order['customer_phone'] || $order['customer_email']): ?>
                        <button class="action-btn btn-contact" onclick="contactCustomer('<?php echo htmlspecialchars($order['customer_email']); ?>', '<?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?>')">
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

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

    <script>
        function acceptOrder(orderId) {
            const notes = prompt('Add any notes for the customer (optional):');

            if (confirm('Are you sure you want to accept this order? The customer will be notified.')) {
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

            if (vendorNotes) {
                formData.append('vendor_notes', vendorNotes);
            }

            if (rejectionReason) {
                formData.append('rejection_reason', rejectionReason);
            }

            fetch('orders.php', {
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
    </script>

</body>
</html>
