<?php
/**
 * DesiDukaan - Unauthorized Access Page
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/navbar.php';
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('unauthorized_access'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <?php render_navbar('', '../'); ?>

    <!-- Main Content -->
    <div class="container" style="padding: 4rem 20px; text-align: center;">
        <div class="row" style="justify-content: center;">
            <div class="col" style="max-width: 500px;">
                <div class="card">
                    <div style="font-size: 4rem; color: var(--error-color); margin-bottom: 1rem;">
                        🚫
                    </div>
                    <h2 style="color: var(--error-color); margin-bottom: 1rem;">
                        <?php echo t('unauthorized_access'); ?>
                    </h2>
                    <p style="color: var(--text-light); margin-bottom: 2rem;">
                        You don't have permission to access this page.
                    </p>
                    
                    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                        <a href="../index.php" class="btn btn-primary">
                            Go Home
                        </a>
                        <?php if (is_logged_in()): ?>
                            <a href="logout.php" class="btn btn-secondary">
                                <?php echo t('logout'); ?>
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-secondary">
                                <?php echo t('login'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
