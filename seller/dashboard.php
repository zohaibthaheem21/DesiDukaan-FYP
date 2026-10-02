<?php
/**
 * DesiDukaan - Seller Dashboard
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/notifications.php';
require_once '../includes/seller_navbar.php';

// Require seller login
require_role('seller');

// Get seller's shop information
$db = getDB();
$stmt = $db->prepare("SELECT * FROM shops WHERE seller_id = ? AND status = 'approved'");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

if (!$shop) {
    // Check if shop exists but not approved
    $stmt = $db->prepare("SELECT status FROM shops WHERE seller_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $shop_status = $stmt->fetch();
    
    if ($shop_status) {
        if ($shop_status['status'] === 'pending') {
            redirect(SITE_URL . '/seller/shop-pending.php');
        }
    } else {
        redirect(SITE_URL . '/seller/create-shop.php');
    }
}

// Get seller information
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Check if this is first login after approval (show notification once)
$show_approval_notification = false;
if (!isset($_SESSION['approval_notification_shown'])) {
    $show_approval_notification = true;
    $_SESSION['approval_notification_shown'] = true;
}

// Get shop statistics
try {
    // Get total products
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE shop_id = ?");
    $stmt->execute([$shop['id']]);
    $total_products = $stmt->fetch()['count'] ?? 0;

    // Get total orders
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE shop_id = ?");
    $stmt->execute([$shop['id']]);
    $total_orders = $stmt->fetch()['count'] ?? 0;

    // Get total revenue
    $stmt = $db->prepare("SELECT SUM(total_amount) as revenue FROM orders WHERE shop_id = ? AND status IN ('confirmed', 'processing', 'shipped', 'delivered')");
    $stmt->execute([$shop['id']]);
    $total_revenue = $stmt->fetch()['revenue'] ?? 0;

    // Get pending orders count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE shop_id = ? AND status = 'pending'");
    $stmt->execute([$shop['id']]);
    $pending_orders = $stmt->fetch()['count'] ?? 0;

    // Get recent orders (last 5)
    $stmt = $db->prepare("
        SELECT o.*, u.full_name as customer_name
        FROM orders o
        JOIN users u ON o.customer_id = u.id
        WHERE o.shop_id = ?
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$shop['id']]);
    $recent_orders = $stmt->fetchAll();

} catch (PDOException $e) {
    $total_products = 0;
    $total_orders = 0;
    $total_revenue = 0;
    $pending_orders = 0;
    $recent_orders = [];
    error_log("Dashboard stats error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('seller_dashboard'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Sticky Navigation -->
    <?php render_seller_navbar('dashboard'); ?>

    <!-- Main Content -->
    <div class="container seller-dashboard" style="padding:10px">
        <!-- Approval Notification (Show Once) -->
        <?php if ($show_approval_notification): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                <h4>🎉 <?php echo t('shop_approved'); ?>!</h4>
                <p>Your shop has been approved and is now live. You can start adding products and managing your business.</p>
            </div>
        <?php endif; ?>

        <!-- Notifications Section -->
        <?php display_notifications($_SESSION['user_id']); ?>

        <!-- Welcome Section -->
        <div class="welcome-section" style="margin-bottom: 3rem;">
            <h1 style="color: var(--primary-color); margin-bottom: 0.5rem; font-size: 2.5rem; font-weight: 700;">
                <?php echo t('welcome'); ?>, <?php echo htmlspecialchars($user['username']); ?>!
            </h1>
            <p style="color: var(--text-light); margin-bottom: 0; font-size: 1.2rem;">
                <?php echo t('dashboard_description'); ?>
            </p>
        </div>

        <!-- Statistics Section -->
        <div class="dashboard-section">
            <h2 class="section-title"><?php echo t('business_overview'); ?></h2>
            <div class="stats-container">
                <div class="stat-box">
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $total_products; ?></h3>
                        <p class="stat-label"><?php echo t('total_products'); ?></p>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $total_orders; ?></h3>
                        <p class="stat-label"><?php echo t('total_orders'); ?></p>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-content">
                        <h3 class="stat-number">Rs. <?php echo number_format($total_revenue); ?></h3>
                        <p class="stat-label"><?php echo t('total_revenue'); ?></p>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-content">
                        <h3 class="stat-number" style="color: #e74c3c;"><?php echo $pending_orders; ?></h3>
                        <p class="stat-label">Pending Orders</p>
                    </div>
                </div>
            </div>
        </div>

<?php
// Include Analytics Engine
require_once '../includes/analytics_engine.php';
$analyticsEngine = AnalyticsEngine::getInstance();
$vendorAnalytics = $analyticsEngine->getVendorAnalytics($shop['id']);
?>

        <!-- Data Science & Business Intelligence Section -->
        <div class="dashboard-section" style="margin-bottom: 2.5rem; background: #ffffff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border-left: 5px solid #DC143C;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h2 class="section-title" style="margin-bottom: 5px; color: #DC143C; display: flex; align-items: center; gap: 8px;">
                        <span>📊</span> Data Science & Sales Analytics
                    </h2>
                    <p style="color: #666; margin: 0; font-size: 0.95rem;">Machine learning sales forecasting, sentiment scores, and customer analytics</p>
                </div>
                <span style="background: #e8f5e9; color: #2e7d32; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px;">
                    🤖 AI Engine Active
                </span>
            </div>

            <div class="stats-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
                <div class="stat-box" style="background: #fafafa; border: 1px solid #eee; border-radius: 10px; padding: 15px;">
                    <div class="stat-content">
                        <small style="color: #888; font-weight: 600; text-transform: uppercase;">Customer Review Sentiment</small>
                        <h3 class="stat-number" style="color: #27ae60; margin: 5px 0;"><?php echo $vendorAnalytics['positive_ratio']; ?>%</h3>
                        <p class="stat-label" style="font-size: 12px; color: #555;">Positive Rating Polarity (Score: <?php echo $vendorAnalytics['avg_sentiment_score']; ?>)</p>
                    </div>
                </div>

                <div class="stat-box" style="background: #fafafa; border: 1px solid #eee; border-radius: 10px; padding: 15px;">
                    <div class="stat-content">
                        <small style="color: #888; font-weight: 600; text-transform: uppercase;">Sales Trend Forecast</small>
                        <h3 class="stat-number" style="color: #2980b9; margin: 5px 0;">Rs. <?php echo number_format($vendorAnalytics['projected_sales']); ?></h3>
                        <p class="stat-label" style="font-size: 12px; color: #555;">Projected Revenue (Time-Series Linear Trend)</p>
                    </div>
                </div>

                <div class="stat-box" style="background: #fafafa; border: 1px solid #eee; border-radius: 10px; padding: 15px;">
                    <div class="stat-content">
                        <small style="color: #888; font-weight: 600; text-transform: uppercase;">Analyzed Reviews</small>
                        <h3 class="stat-number" style="color: #8e44ad; margin: 5px 0;"><?php echo $vendorAnalytics['total_reviews']; ?></h3>
                        <p class="stat-label" style="font-size: 12px; color: #555;">Processed with NLP TF-IDF Classifier</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="dashboard-section">
            <h2 class="section-title"><?php echo t('quick_actions'); ?></h2>
            <div class="actions-container">
                <div class="action-box">
                    <a href="add_product.php" class="action-link">
                        <h4 class="action-title"><?php echo t('add_product'); ?></h4>
                        <p class="action-description">Add new products to your shop</p>
                    </a>
                </div>

                <div class="action-box">
                    <a href="products.php" class="action-link">
                        <h4 class="action-title"><?php echo t('manage_products'); ?></h4>
                        <p class="action-description">View and edit your products</p>
                    </a>
                </div>

                <div class="action-box">
                    <a href="orders.php" class="action-link">
                        <h4 class="action-title"><?php echo t('view_orders'); ?></h4>
                        <p class="action-description">Manage customer orders</p>
                    </a>
                </div>

                <div class="action-box">
                    <a href="shop-settings.php" class="action-link">
                        <h4 class="action-title"><?php echo t('edit_shop'); ?></h4>
                        <p class="action-description">Update shop information</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Orders Section -->
        <?php if (!empty($recent_orders)): ?>
        <div class="dashboard-section">
            <h2 class="section-title">📦 Recent Orders</h2>
            <div style="background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); overflow: hidden;">
                <?php foreach ($recent_orders as $order): ?>
                <div style="padding: 1rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                        <br>
                        <small style="color: #666;">👤 <?php echo htmlspecialchars($order['customer_name']); ?></small>
                        <br>
                        <small style="color: #666;">📅 <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></small>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: bold; color: #2c3e50;">Rs. <?php echo number_format($order['total_amount'], 2); ?></div>
                        <span style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;
                            background: <?php echo $order['status'] === 'pending' ? '#fff3cd' : ($order['status'] === 'confirmed' ? '#d4edda' : '#e2e3e5'); ?>;
                            color: <?php echo $order['status'] === 'pending' ? '#856404' : ($order['status'] === 'confirmed' ? '#155724' : '#383d41'); ?>;">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="padding: 1rem; text-align: center; background: #f8f9fa;">
                    <a href="orders.php" style="color: #e74c3c; font-weight: bold; text-decoration: none;">
                        📋 View All Orders →
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

     <?php include '../includes/footer.php'; ?> 

    <script src="../assets/js/main.js"></script>

    <style>
        .card:hover {
            transform: translateY(-2px);
        }
    </style>
</body>
</html>
