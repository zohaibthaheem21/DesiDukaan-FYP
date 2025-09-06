<?php
/**
 * DesiDukaan - Splash Screen
 */

require_once 'config/config.php';
require_once 'config/languages.php';

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
    <title><?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
</head>
<body>
    <div class="splash-screen">
        <div class="logo-container">
            <?php if (file_exists('assets/images/logo.png')): ?>
                <img src="assets/images/logo.png" alt="DesiDukaan" class="logo-image">
            <?php else: ?>
                <div class="logo">
                    DesiDukaan
                </div>
            <?php endif; ?>
        </div>

        <div class="slogan">
            <?php echo t('slogan'); ?>
        </div>

        <div class="slogan-english">
            <?php echo t('slogan_english'); ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/ajax-utils.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Start splash screen transition after page loads
        window.addEventListener('load', function() {
            startSplashTransition();
        });
    </script>
</body>
</html>
