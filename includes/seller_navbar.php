<?php
/**
 * Seller Sticky Navbar Component
 */

function render_seller_navbar($current_page = '', $base_path = '../') {
    $logo_path = $base_path . 'assets/images/logo.png';
    $has_logo = file_exists($logo_path);

    // Get seller's shop information
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM shops WHERE seller_id = ? AND status = 'approved'");
    $stmt->execute([$_SESSION['user_id']]);
    $shop = $stmt->fetch();
    ?>
    <nav class="navbar navbar-sticky">
        <div class="container">
            <a href="<?php echo $base_path; ?>seller/dashboard.php" class="navbar-brand">
                <div class="brand-container">
                    <?php if ($has_logo): ?>
                        <div class="logo-wrapper">
                            <img src="<?php echo $logo_path; ?>" alt="DesiDukaan Logo" class="brand-logo">
                        </div>
                    <?php else: ?>
                        <div class="logo-wrapper">
                            <span class="brand-icon">🏪</span>
                        </div>
                    <?php endif; ?>
                    <span class="brand-text">DesiDukaan</span>
                </div>
            </a>

            <button class="hamburger" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <ul class="navbar-nav" id="mobileMenu">
                <li>
                    <a href="dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                        <?php echo t('seller_dashboard'); ?>
                    </a>
                </li>
                <li>
                    <a href="add_product.php" class="<?php echo $current_page === 'add_product' ? 'active' : ''; ?>">
                        <?php echo t('add_product'); ?>
                    </a>
                </li>
                <li>
                    <a href="products.php" class="<?php echo $current_page === 'products' ? 'active' : ''; ?>">
                        <?php echo t('products'); ?>
                    </a>
                </li>
                <li>
                    <a href="orders.php" class="<?php echo $current_page === 'orders' ? 'active' : ''; ?>">
                        <?php echo t('orders'); ?>
                    </a>
                </li>

                <!-- Notification Bell -->
                <li class="notification-nav-item">
                    <?php
                    // Temporarily disabled notification bell to fix orders page
                    // TODO: Fix notification bell path issues
                    /*
                    require_once $base_path . 'includes/notification_bell.php';
                    render_notification_bell($_SESSION['user_id'], $base_path);
                    */
                    ?>
                    <!-- <div style="padding: 8px;">
                        <span style="font-size: 20px; color: #2c3e50;">🔔</span>
                    </div> -->
                </li>

                <!-- Shop Profile Link -->
                <li>
                    <a href="shop-profile.php" class="<?php echo $current_page === 'profile' ? 'active' : ''; ?>">
                        <?php if ($shop && $shop['shop_logo']): ?>
                            <img src="<?php echo UPLOAD_URL . 'logos/' . $shop['shop_logo']; ?>"
                                 style="width: 24px; height: 24px; border-radius: 50%; margin-right: 8px; object-fit: cover;">
                        <?php endif; ?>
                        <?php echo t('profile'); ?>
                    </a>
                </li>

                <li>
                    <div class="language-toggle">
                        <button type="button" data-lang="en" class="<?php echo get_current_language() === 'en' ? 'active' : ''; ?>">
                            <?php echo t('english'); ?>
                        </button>
                        <button type="button" data-lang="sd" class="<?php echo get_current_language() === 'sd' ? 'active' : ''; ?>">
                            <?php echo t('sindhi'); ?>
                        </button>
                    </div>
                </li>

                <li>
                    <a href="<?php echo $base_path; ?>auth/logout.php" style="color: rgba(255,255,255,0.9);">
                        <?php echo t('logout'); ?>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    
    <style>
        .navbar-sticky .navbar-nav a {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .notification-nav-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
        }

        .notification-nav-item .notification-bell .bell-icon {
            color: rgba(255, 255, 255, 0.9);
        }

        .notification-nav-item .notification-bell:hover {
            background-color: rgba(255, 255, 255, 0.1);
            font-weight: 500;
        }

        .navbar-sticky .navbar-nav a:hover,
        .navbar-sticky .navbar-nav a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateY(-1px);
        }

        .navbar-sticky .navbar-nav {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .navbar-sticky .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 20px;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .brand-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-wrapper {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            flex-shrink: 0;
        }

        .brand-logo {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .brand-icon {
            font-size: 1.3rem;
            color: var(--primary-color);
        }

        .brand-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            white-space: nowrap;
        }



        @media (max-width: 768px) {
            .navbar-sticky .container {
                flex-direction: row;
                gap: 1rem;
            }

            .navbar-sticky .navbar-nav {
                justify-content: center;
                width: 100%;
            }

            .navbar-sticky .navbar-nav a {
                font-size: 0.9rem;
                padding: 0.5rem 0.75rem;
            }


        }
    </style>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const hamburger = document.querySelector('.hamburger');

            menu.classList.toggle('active');
            hamburger.classList.toggle('active');
        }

        // Close menu when clicking on a link
        document.addEventListener('DOMContentLoaded', function() {
            const menuLinks = document.querySelectorAll('.navbar-nav a');
            menuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const menu = document.getElementById('mobileMenu');
                    const hamburger = document.querySelector('.hamburger');
                    menu.classList.remove('active');
                    hamburger.classList.remove('active');
                });
            });
        });
    </script>

    <?php
}
?>
