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
        <div class="row" style="margin-bottom: 3rem;">
            <div class="col-3">
                <div class="card" style="text-align: center; background: linear-gradient(135deg, var(--warning-color), #e0a800);">
                    <h3 style="color: white; margin-bottom: 0.5rem;"><?php echo $pending_count; ?></h3>
                    <p style="color: white; margin: 0; font-weight: 500;">Pending Shops</p>
                </div>
            </div>
            <div class="col-3">
                <div class="card" style="text-align: center; background: linear-gradient(135deg, var(--success-color), #1e7e34);">
                    <h3 style="color: white; margin-bottom: 0.5rem;"><?php echo $approved_count; ?></h3>
                    <p style="color: white; margin: 0; font-weight: 500;">Approved Shops</p>
                </div>
            </div>
            <div class="col-3">
                <div class="card" style="text-align: center; background: linear-gradient(135deg, var(--primary-color), #B91C3C);">
                    <h3 style="color: white; margin-bottom: 0.5rem;"><?php echo $customer_count; ?></h3>
                    <p style="color: white; margin: 0; font-weight: 500;">Customers</p>
                </div>
            </div>
            <div class="col-3">
                <div class="card" style="text-align: center; background: linear-gradient(135deg, #6f42c1, #5a32a3);">
                    <h3 style="color: white; margin-bottom: 0.5rem;"><?php echo $seller_count; ?></h3>
                    <p style="color: white; margin: 0; font-weight: 500;">Sellers</p>
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
