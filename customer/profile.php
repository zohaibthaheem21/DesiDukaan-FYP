<?php
/**
 * DesiDukaan - Customer Profile
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/navbar.php';
require_once '../includes/cart.php';

// Require customer login
require_role('customer');

// Get customer information
$db = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get customer statistics
try {
    // Get order count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $order_count = $result['count'];

    // Get cart items count
    $cart_count = getCartItemsCount();

    // Get wishlist count
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM wishlist w
        JOIN products p ON w.product_id = p.id
        JOIN shops s ON p.shop_id = s.id
        WHERE w.user_id = ? AND p.is_active = 1 AND s.status = 'approved'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $wishlist_count = $result['count'];

} catch (Exception $e) {
    $order_count = 0;
    $cart_count = 0;
    $wishlist_count = 0;
}

// Handle profile update and password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        // Handle password change
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = t('all_fields_required');
        } elseif ($new_password !== $confirm_password) {
            $error = t('passwords_do_not_match');
        } elseif (strlen($new_password) < 6) {
            $error = t('password_too_short');
        } else {
            // Verify current password
            if (verify_password($current_password, $user['password_hash'])) {
                try {
                    $new_password_hash = hash_password($new_password);
                    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$new_password_hash, $_SESSION['user_id']]);

                    $success = t('password_changed_successfully');
                } catch (PDOException $e) {
                    $error = t('database_error');
                }
            } else {
                $error = t('current_password_incorrect');
            }
        }
    } else {
        // Handle profile update
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $phone = sanitize_input($_POST['phone'] ?? '');
        $address = sanitize_input($_POST['address'] ?? '');
        $full_name = sanitize_input($_POST['full_name'] ?? '');

        try {
            // Check if username or email already exists (excluding current user)
            $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $_SESSION['user_id']]);

            if ($stmt->fetch()) {
                $error = t('username_or_email_exists');
            } else {
                // Update user profile
                $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ?, full_name = ? WHERE id = ?");
                $stmt->execute([$username, $email, $phone, $address, $full_name, $_SESSION['user_id']]);

                $success = t('profile_updated_successfully');

                // Refresh user data
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = t('database_error');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('my_profile'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <?php render_navbar('customer', '../'); ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="profile-hero">
                <div class="profile-avatar">
                    <span class="avatar-icon">👤</span>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['name'] ?? $user['username'] ?? 'Customer'); ?></h1>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $order_count; ?></span>
                            <span class="stat-label"><?php echo t('orders'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $cart_count; ?></span>
                            <span class="stat-label"><?php echo t('cart_items'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $wishlist_count; ?></span>
                            <span class="stat-label"><?php echo t('wishlist'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Profile Content -->
    <div class="container profile-content">
        <!-- Alert Messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <span class="alert-icon">❌</span>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Profile Form -->
            <div class="profile-form-section">
                <div class="profile-card">
                    <div class="card-header">
                        <h2 class="card-title">👤 <?php echo t('personal_information'); ?></h2>
                        <p class="card-subtitle"><?php echo t('update_your_profile'); ?></p>
                    </div>

                    <form method="POST" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name" class="form-label">
                                    <span class="label-icon">📝</span>
                                    <?php echo t('full_name'); ?>
                                </label>
                                <input type="text" id="full_name" name="full_name"
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                                       class="form-control-modern" placeholder="<?php echo t('enter_full_name'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="username" class="form-label">
                                    <span class="label-icon">👤</span>
                                    <?php echo t('username'); ?> *
                                </label>
                                <input type="text" id="username" name="username"
                                       value="<?php echo htmlspecialchars($user['username']); ?>"
                                       class="form-control-modern" required placeholder="<?php echo t('enter_username'); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email" class="form-label">
                                    <span class="label-icon">📧</span>
                                    <?php echo t('email'); ?> *
                                </label>
                                <input type="email" id="email" name="email"
                                       value="<?php echo htmlspecialchars($user['email']); ?>"
                                       class="form-control-modern" required placeholder="<?php echo t('enter_email'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">
                                    <span class="label-icon">📱</span>
                                    <?php echo t('phone'); ?>
                                </label>
                                <input type="tel" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                       class="form-control-modern" placeholder="<?php echo t('enter_phone'); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">
                                <span class="label-icon">📍</span>
                                <?php echo t('address'); ?>
                            </label>
                            <textarea id="address" name="address" rows="3"
                                      class="form-control-modern" placeholder="<?php echo t('enter_address'); ?>"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-profile btn-save">
                                <span class="btn-icon">💾</span>
                                <?php echo t('save_changes'); ?>
                            </button>
                            <a href="dashboard.php" class="btn-profile btn-cancel">
                                <span class="btn-icon">↩️</span>
                                <?php echo t('back_to_dashboard'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Profile Sidebar -->
            <div class="profile-sidebar">
                <!-- Account Overview -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">📊 <?php echo t('account_overview'); ?></h3>
                    <div class="overview-stats">
                        <div class="overview-stat">
                            <span class="overview-icon">📦</span>
                            <div class="overview-info">
                                <span class="overview-number"><?php echo $order_count; ?></span>
                                <span class="overview-label"><?php echo t('total_orders'); ?></span>
                            </div>
                        </div>

                        <div class="overview-stat">
                            <span class="overview-icon">🛒</span>
                            <div class="overview-info">
                                <span class="overview-number"><?php echo $cart_count; ?></span>
                                <span class="overview-label"><?php echo t('cart_items'); ?></span>
                            </div>
                        </div>

                        <div class="overview-stat">
                            <span class="overview-icon">❤️</span>
                            <div class="overview-info">
                                <span class="overview-number"><?php echo $wishlist_count; ?></span>
                                <span class="overview-label"><?php echo t('wishlist_items'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">⚡ <?php echo t('quick_actions'); ?></h3>
                    <div class="quick-actions-list">
                        <a href="dashboard.php" class="quick-action">
                            <span class="action-icon">🏠</span>
                            <span class="action-text"><?php echo t('browse_products'); ?></span>
                        </a>

                        <a href="cart.php" class="quick-action">
                            <span class="action-icon">🛒</span>
                            <span class="action-text"><?php echo t('view_cart'); ?></span>
                            <?php if ($cart_count > 0): ?>
                                <span class="action-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="orders.php" class="quick-action">
                            <span class="action-icon">📦</span>
                            <span class="action-text"><?php echo t('my_orders'); ?></span>
                            <?php if ($order_count > 0): ?>
                                <span class="action-badge"><?php echo $order_count; ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="wishlist.php" class="quick-action">
                            <span class="action-icon">❤️</span>
                            <span class="action-text"><?php echo t('my_wishlist'); ?></span>
                            <?php if ($wishlist_count > 0): ?>
                                <span class="action-badge"><?php echo $wishlist_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <!-- Account Security -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">🔒 <?php echo t('account_security'); ?></h3>
                    <div class="security-info">
                        <div class="security-item">
                            <span class="security-icon">📅</span>
                            <div class="security-details">
                                <span class="security-label"><?php echo t('member_since'); ?></span>
                                <span class="security-value"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                            </div>
                        </div>

                        <div class="security-item">
                            <span class="security-icon">🔐</span>
                            <div class="security-details">
                                <span class="security-label"><?php echo t('password'); ?></span>
                                <button class="change-password-btn" onclick="showPasswordModal()">
                                    <?php echo t('change_password'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal-overlay" onclick="closePasswordModal()">
        <div class="modal-content-modern" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closePasswordModal()">×</button>
            <div class="password-modal-content">
                <h2 class="modal-title">🔐 <?php echo t('change_password'); ?></h2>
                <form method="POST" class="password-form">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label for="current_password" class="form-label">
                            <span class="label-icon">🔒</span>
                            <?php echo t('current_password'); ?>
                        </label>
                        <input type="password" id="current_password" name="current_password"
                               class="form-control-modern" required placeholder="<?php echo t('enter_current_password'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">
                            <span class="label-icon">🔑</span>
                            <?php echo t('new_password'); ?>
                        </label>
                        <input type="password" id="new_password" name="new_password"
                               class="form-control-modern" required placeholder="<?php echo t('enter_new_password'); ?>" minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            <span class="label-icon">🔑</span>
                            <?php echo t('confirm_password'); ?>
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="form-control-modern" required placeholder="<?php echo t('confirm_new_password'); ?>" minlength="6">
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn-modal btn-save">
                            <span>💾</span> <?php echo t('change_password'); ?>
                        </button>
                        <button type="button" class="btn-modal btn-cancel" onclick="closePasswordModal()">
                            <span>❌</span> <?php echo t('cancel'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/customer.js"></script>

    <script>
        // Update profile stats in real-time
        function updateProfileStats() {
            fetch('../includes/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_cart_count'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCountElement = document.querySelector('.stat-item:nth-child(2) .stat-number');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cart_count;
                        cartCountElement.classList.add('stat-update-animation');
                        setTimeout(() => {
                            cartCountElement.classList.remove('stat-update-animation');
                        }, 600);
                    }
                }
            })
            .catch(error => console.error('Error updating cart count:', error));
        }

        // Update stats on page load and periodically
        document.addEventListener('DOMContentLoaded', function() {
            updateProfileStats();
        });

    <script>
        function showPasswordModal() {
            document.getElementById('passwordModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').classList.remove('active');
            document.body.style.overflow = '';
            // Clear form
            document.querySelector('.password-form').reset();
        }

        // Initialize cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();

            // Add keyboard navigation for modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closePasswordModal();
                }
            });
        });
    </script>

    <style>
        .password-modal-content {
            padding: 2rem;
            max-width: 500px;
        }

        .modal-title {
            text-align: center;
            color: var(--text-dark);
            margin-bottom: 2rem;
        }

        .password-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-modal {
            flex: 1;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-save {
            background: var(--primary-color);
            color: white;
        }

        .btn-save:hover {
            background: #B91C3C;
        }

        .btn-cancel {
            background: #f8f9fa;
            color: var(--text-dark);
            border: 2px solid var(--border-light);
        }

        .btn-cancel:hover {
            background: #e9ecef;
        }

        .stat-update-animation {
            animation: statPulse 0.6s ease;
            color: var(--primary-color) !important;
        }

        @keyframes statPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</body>
</html>
