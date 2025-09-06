<?php
/**
 * DesiDukaan - User Type Selection Page
 */

require_once 'config/config.php';
require_once 'config/languages.php';
require_once 'includes/navbar.php';

// If user is already logged in, redirect to appropriate dashboard
if (is_logged_in()) {
    switch ($_SESSION['user_role']) {
        case 'customer':
            redirect(SITE_URL . '/customer/dashboard.php');
            break;
        case 'seller':
            redirect(SITE_URL . '/seller/dashboard.php');
            break;
        case 'admin':
            redirect(SITE_URL . '/admin/dashboard.php');
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('choose_user_type'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <?php render_navbar('', ''); ?>

    <!-- Main Content -->
    <div class="container" style="padding: 4rem 20px;">
        <div class="row">
            <div class="col" style="text-align: center; margin-bottom: 3rem;">
                <h1><?php echo t('choose_user_type'); ?></h1>
                <p style="color: var(--text-light); font-size: 1.1rem;">
                    <?php echo t('slogan'); ?>
                </p>
            </div>
        </div>
        
        <div class="row" style="justify-content: center; gap: 2rem;">
            <!-- Customer Registration Card -->
            <div class="col-6" style="max-width: 400px;">
                <div class="card" style="text-align: center; height: 100%;">
                    <div class="card-header">
                        <div style="width: 80px; height: 80px; background: var(--primary-color); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            🛍️
                        </div>
                        <h3 class="card-title"><?php echo t('register_as_customer'); ?></h3>
                    </div>
                    
                    <p style="color: var(--text-light); margin-bottom: 2rem;">
                        <?php echo t('customer_desc'); ?>
                    </p>
                    
                    <a href="auth/register-customer.php" class="btn btn-primary" style="width: 100%;">
                        <?php echo t('register_as_customer'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Seller Registration Card -->
            <div class="col-6" style="max-width: 400px;">
                <div class="card" style="text-align: center; height: 100%;">
                    <div class="card-header">
                        <div style="width: 80px; height: 80px; background: var(--primary-color); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            🏪
                        </div>
                        <h3 class="card-title"><?php echo t('register_as_seller'); ?></h3>
                    </div>
                    
                    <p style="color: var(--text-light); margin-bottom: 2rem;">
                        <?php echo t('seller_desc'); ?>
                    </p>
                    
                    <a href="auth/register-seller.php" class="btn btn-primary" style="width: 100%;">
                        <?php echo t('register_as_seller'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row" style="margin-top: 3rem;">
            <div class="col" style="text-align: center;">
                <p style="color: var(--text-light);">
                    <?php echo t('already_have_account'); ?>
                    <a href="auth/login.php" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                        <?php echo t('login'); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
</body>
</html>
