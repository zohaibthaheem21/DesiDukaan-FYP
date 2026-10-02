<?php
/**
 * Reusable Navbar Component
 */

function render_navbar($current_page = '', $base_path = '') {
    $logo_path = $base_path . 'assets/images/logo.png';
    $has_logo = file_exists($logo_path);
    ?>
    <nav class="navbar">
        <div class="container">
            <a href="<?php echo $base_path; ?>index.php" class="navbar-brand">
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
                    <span class="brand-text">
                        <?php if ($current_page === 'admin'): ?>
                            DesiDukaan - Admin
                        <?php else: ?>
                            DesiDukaan
                        <?php endif; ?>
                    </span>
                </div>
            </a>

            <!-- Hamburger Menu Button -->
            <button class="hamburger" id="hamburger-menu" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <ul class="navbar-nav" id="navbar-nav">
                <?php if ($current_page === 'customer'): ?>
                    <li><a href="dashboard.php"><?php echo t('home'); ?></a></li>
                    <li><a href="profile.php"><?php echo t('profile'); ?></a></li>
                    <li class="cart-nav-item">
                        <a href="cart.php" class="cart-link">
                            <span class="cart-icon">🛒</span>
                            <span class="cart-text"><?php echo t('shopping_cart'); ?></span>
                            <span class="cart-count" id="cart-count">0</span>
                        </a>
                    </li>
                <?php elseif ($current_page === 'seller'): ?>
                    <li><a href="dashboard.php"><?php echo t('home'); ?></a></li>
                    <li><a href="products.php"><?php echo t('products'); ?></a></li>
                    <li><a href="orders.php"><?php echo t('orders'); ?></a></li>
                    <li><a href="shop-profile.php"><?php echo t('shop_settings'); ?></a></li>
                <?php elseif ($current_page === 'admin'): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="shop-details.php"><?php echo t('shop_requests'); ?></a></li>
                <?php else: ?>
                    <li><a href="<?php echo $base_path; ?>auth/login.php"><?php echo t('login'); ?></a></li>
                <?php endif; ?>
                <li><a href="<?php echo $base_path; ?>tutorials.php">Tutorials</a></li>
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

                <?php if (is_logged_in()): ?>
                    <li><a href="<?php echo $base_path; ?>auth/logout.php"><?php echo t('logout'); ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <style>
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

        /* Cart Navigation Styles */
        .cart-nav-item {
            position: relative;
        }

        .cart-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .cart-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .cart-icon {
            font-size: 1.2rem;
        }

        .cart-text {
            font-weight: 500;
        }

        .cart-count {
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            position: absolute;
            top: -5px;
            right: 5px;
            animation: pulse 2s infinite;
        }

        .cart-count.empty {
            display: none;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @keyframes cartBounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        .cart-link:hover .cart-icon {
            animation: cartBounce 0.6s ease-in-out;
        }

        @media (max-width: 768px) {
            .cart-text {
                display: none;
            }

            .cart-count {
                position: static;
                margin-left: 0.5rem;
            }
        }
    </style>

    <?php
}
?>
