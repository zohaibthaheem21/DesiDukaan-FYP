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

    // Get total orders (placeholder for now)
    $total_orders = 0; // Will be implemented when order system is ready

    // Get total revenue (placeholder for now)
    $total_revenue = 0; // Will be implemented when order system is ready

} catch (PDOException $e) {
    $total_products = 0;
    $total_orders = 0;
    $total_revenue = 0;
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
    <div class="container seller-dashboard">
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
