<?php
/**
 * DesiDukaan - Admin Dashboard
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/navbar.php';

// Require admin login
require_role('admin');

// Get pending shop requests
$db = getDB();
$stmt = $db->prepare("
    SELECT s.*, u.username, u.email as user_email 
    FROM shops s 
    JOIN users u ON s.seller_id = u.id 
    WHERE s.status = 'pending' 
    ORDER BY s.created_at ASC
");
$stmt->execute();
$pending_shops = $stmt->fetchAll();

// Get statistics
$stmt = $db->prepare("SELECT COUNT(*) as total FROM shops WHERE status = 'pending'");
$stmt->execute();
$pending_count = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM shops WHERE status = 'approved'");
$stmt->execute();
$approved_count = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$stmt->execute();
$customer_count = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'seller'");
$stmt->execute();
$seller_count = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('admin_panel'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <?php render_navbar('admin', '../'); ?>

    <!-- Main Content -->
    <div class="container" style="padding: 2rem 20px;">
        <!-- Welcome Section -->
        <div class="row">
            <div class="col">
                <h2 style="color: var(--primary-color); margin-bottom: 2rem;">
                    <?php echo t('admin_panel'); ?>
                </h2>
            </div>
        </div>

        <!-- Statistics Cards -->
<div class="row stats-row" style="margin-bottom: 3rem;">

    <div class="col-3">
        <div class="stat-card">
            <h3><?php echo $pending_count; ?></h3>
            <p>Pending Shops</p>
        </div>
    </div>

    <div class="col-3">
        <div class="stat-card">
            <h3><?php echo $approved_count; ?></h3>
            <p>Approved Shops</p>
        </div>
    </div>

    <div class="col-3">
        <div class="stat-card">
            <h3><?php echo $customer_count; ?></h3>
            <p>Customers</p>
        </div>
    </div>

    <div class="col-3">
        <div class="stat-card">
            <h3><?php echo $seller_count; ?></h3>
            <p>Sellers</p>
        </div>
    </div>

</div>

<style>
    /* Dashboard Cards */
    .stats-row {
        display: flex;
        justify-content: space-between;
        gap: 25px;  /* space between cards */
        margin-bottom: 40px;
        flex-wrap: wrap;
    }

    .stat-card {
        flex: 1;
        background: var(--primary-color); /* Navbar-related color */
        border-radius: 14px;
        padding: 30px 20px;
        text-align: center;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        color: white;
        min-width: 200px;
    }

    .stat-card h3 {
        font-size: 36px;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .stat-card p {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }

    .stat-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 22px rgba(0,0,0,0.18);
        background: #B91C3C; /* slightly darker shade on hover */
    }

    /* Quick Action Cards */
    .quick-action a {
        display: block;
        padding: 1.5rem;
        background: var(--primary-color);
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        text-decoration: none;
        color: white;
        transition: all 0.3s ease;
        border: 1px solid #a52a2a;
    }

    .quick-action a:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.15);
        background: #B91C3C;
    }

    .quick-action div {
        font-size: 2rem;
        margin-bottom: 1rem;
    }

    .quick-action h4 {
        margin-bottom: 0.5rem;
    }

    .quick-action p {
        margin: 0;
    }
</style>


<?php
require_once '../includes/analytics_engine.php';
$platformAnalytics = AnalyticsEngine::getInstance()->getPlatformDataScienceOverview();
?>
        <!-- Data Science Platform Metrics -->
        <div class="row" style="margin-bottom: 3rem;">
            <div class="col" style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-left: 5px solid #8e44ad;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div>
                        <h3 style="color: #8e44ad; margin-bottom: 5px; display: flex; align-items: center; gap: 8px;">
                            <span>🤖</span> Platform Data Science & Analytics Summary
                        </h3>
                        <p style="color: #666; margin: 0; font-size: 0.95rem;">Machine learning models, customer RFM segmentation, and dataset exports</p>
                    </div>
                    <a href="../setup_data_science_features.php" target="_blank" style="background: #8e44ad; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">
                        ⚡ Re-run Data Science Pipeline
                    </a>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div style="background: #fdfafb; border: 1px solid #f3e5f5; padding: 15px; border-radius: 10px;">
                        <span style="font-size: 11px; text-transform: uppercase; color: #888; font-weight: 600;">Analyzed Product Reviews</span>
                        <h4 style="font-size: 24px; color: #8e44ad; margin: 5px 0;"><?php echo $platformAnalytics['total_reviews_analyzed']; ?></h4>
                        <small style="color: #666;">Categorized by NLP Polarity</small>
                    </div>

                    <div style="background: #fdfafb; border: 1px solid #f3e5f5; padding: 15px; border-radius: 10px;">
                        <span style="font-size: 11px; text-transform: uppercase; color: #888; font-weight: 600;">Behavioral Event Logs</span>
                        <h4 style="font-size: 24px; color: #2980b9; margin: 5px 0;"><?php echo $platformAnalytics['total_behavioral_interactions']; ?></h4>
                        <small style="color: #666;">Views, Cart & Wishlist Events</small>
                    </div>

                    <div style="background: #fdfafb; border: 1px solid #f3e5f5; padding: 15px; border-radius: 10px;">
                        <span style="font-size: 11px; text-transform: uppercase; color: #888; font-weight: 600;">RFM Customer Segments</span>
                        <h4 style="font-size: 18px; color: #27ae60; margin: 5px 0;">
                            VIP: <?php echo $platformAnalytics['rfm_segments']['VIP'] ?? 1; ?> | Loyal: <?php echo $platformAnalytics['rfm_segments']['Loyal Customer'] ?? 2; ?>
                        </h4>
                        <small style="color: #666;">RFM Model Classification</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="row" style="margin-bottom: 3rem;">
            <div class="col">
                <h3 style="color: var(--text-dark); margin-bottom: 1.5rem;">🚀 Quick Actions</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <a href="customers.php" style="display: block; padding: 1.5rem; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-decoration: none; color: inherit; transition: all 0.3s ease; border: 1px solid #e9ecef;">
                        <div style="font-size: 2rem; margin-bottom: 1rem;">👥</div>
                        <h4 style="color: var(--text-dark); margin-bottom: 0.5rem;">Customer Management</h4>
                        <p style="color: var(--text-light); margin: 0;"><?php echo $customer_count; ?> customers</p>
                    </a>
                    <a href="shops.php" style="display: block; padding: 1.5rem; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-decoration: none; color: inherit; transition: all 0.3s ease; border: 1px solid #e9ecef;">
                        <div style="font-size: 2rem; margin-bottom: 1rem;">🏪</div>
                        <h4 style="color: var(--text-dark); margin-bottom: 0.5rem;">Shop Management</h4>
                        <p style="color: var(--text-light); margin: 0;"><?php echo $pending_count; ?> pending approval</p>
                    </a>
                </div>
            </div>
        </div>

        <!-- Pending Shop Requests -->
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo t('pending_requests'); ?></h3>
                    </div>
                    
                    <?php if (empty($pending_shops)): ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-light);">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">✅</div>
                            <p><?php echo t('no_pending_requests'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Shop Name</th>
                                        <th>Seller</th>
                                        <th>Email</th>
                                        <th>Submitted</th>
                                        <th style="text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_shops as $shop): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($shop['shop_name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($shop['username']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($shop['email']); ?>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($shop['created_at'])); ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <a href="shop-details.php?id=<?php echo $shop['id']; ?>"
                                                   class="btn btn-primary btn-sm">
                                                    <?php echo t('view_details'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>
