<?php
/**
 * DesiDukaan - Shop Pending Approval
 */

require_once '../config/config.php';
require_once '../config/languages.php';

// Require seller login
require_role('seller');

// Get shop information
$db = getDB();
$stmt = $db->prepare("SELECT * FROM shops WHERE seller_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

if (!$shop) {
    redirect(SITE_URL . '/seller/create-shop.php');
}

if ($shop['status'] === 'approved') {
    redirect(SITE_URL . '/seller/dashboard.php');
}

if ($shop['status'] === 'rejected') {
    // Show rejection reason and allow resubmission
    $rejection_reason = $shop['rejection_reason'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Status - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="navbar-brand"><?php echo t('site_name'); ?></a>
            
            <ul class="navbar-nav">
                <li>
                    <div class="language-toggle">
                        <button type="button" data-lang="en" class="<?php echo get_current_language() === 'en' ? 'active' : ''; ?>">
                            English
                        </button>
                        <button type="button" data-lang="sd" class="<?php echo get_current_language() === 'sd' ? 'active' : ''; ?>">
                            سنڌي
                        </button>
                    </div>
                </li>
                <li><a href="../auth/logout.php"><?php echo t('logout'); ?></a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container" style="padding: 4rem 20px;">
        <div class="row" style="justify-content: center;">
            <div class="col" style="max-width: 600px;">
                <div class="card" style="text-align: center;">
                    <?php if ($shop['status'] === 'pending'): ?>
                        <div style="font-size: 4rem; color: var(--warning-color); margin-bottom: 1rem;">
                            ⏳
                        </div>
                        <h2 style="color: var(--warning-color); margin-bottom: 1rem;">
                            Shop Under Review
                        </h2>
                        <p style="color: var(--text-light); margin-bottom: 2rem; font-size: 1.1rem;">
                            <?php echo t('shop_request_submitted'); ?>
                        </p>
                        
                        <!-- Shop Details -->
                        <div style="text-align: left; background: #f8f9fa; padding: 1.5rem; border-radius: 6px; margin-bottom: 2rem;">
                            <h4 style="margin-bottom: 1rem; color: var(--text-dark);">Submitted Details:</h4>
                            <p><strong>Shop Name:</strong> <?php echo htmlspecialchars($shop['shop_name']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($shop['address']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($shop['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($shop['phone_number']); ?></p>
                            <p><strong>Submitted:</strong> <?php echo date('F j, Y g:i A', strtotime($shop['created_at'])); ?></p>
                        </div>
                        
                    <?php elseif ($shop['status'] === 'rejected'): ?>
                        <div style="font-size: 4rem; color: var(--error-color); margin-bottom: 1rem;">
                            ❌
                        </div>
                        <h2 style="color: var(--error-color); margin-bottom: 1rem;">
                            Shop Request Rejected
                        </h2>
                        <p style="color: var(--text-light); margin-bottom: 1rem;">
                            <?php echo t('shop_rejected_notification'); ?>
                        </p>
                        
                        <?php if ($rejection_reason): ?>
                            <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; text-align: left;">
                                <strong>Reason for rejection:</strong><br>
                                <?php echo htmlspecialchars($rejection_reason); ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="create-shop.php" class="btn btn-primary">
                            Submit New Request
                        </a>
                    <?php endif; ?>
                    
                    <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-light);">
                        <p style="color: var(--text-light); margin-bottom: 1rem;">
                            Need help? Contact our support team.
                        </p>
                        <a href="../auth/logout.php" class="btn btn-secondary">
                            <?php echo t('logout'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    
    <!-- Auto-refresh every 30 seconds to check for status updates -->
    <script>
        <?php if ($shop['status'] === 'pending'): ?>
        setTimeout(function() {
            location.reload();
        }, 30000); // 30 seconds
        <?php endif; ?>
    </script>
</body>
</html>
