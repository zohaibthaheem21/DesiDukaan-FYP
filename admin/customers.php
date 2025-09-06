<?php
/**
 * DesiDukaan - Admin Customer Management
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/navbar.php';

// Require admin login
require_role('admin');

$db = getDB();

// Handle customer actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'reset_password':
                $customer_id = intval($_POST['customer_id']);
                $new_password = $_POST['new_password'];
                
                if ($customer_id > 0 && !empty($new_password)) {
                    try {
                        $password_hash = hash_password($new_password);
                        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'customer'");
                        $stmt->execute([$password_hash, $customer_id]);
                        
                        $success = "Password reset successfully for customer ID: $customer_id";
                    } catch (Exception $e) {
                        $error = "Failed to reset password: " . $e->getMessage();
                    }
                }
                break;
                
            case 'toggle_status':
                $customer_id = intval($_POST['customer_id']);
                
                if ($customer_id > 0) {
                    try {
                        $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role = 'customer'");
                        $stmt->execute([$customer_id]);
                        
                        $success = "Customer status updated successfully";
                    } catch (Exception $e) {
                        $error = "Failed to update status: " . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get all customers with their statistics
try {
    $stmt = $db->prepare("
        SELECT u.*, 
               COUNT(DISTINCT s.id) as shop_count,
               COUNT(DISTINCT o.id) as order_count,
               COUNT(DISTINCT w.id) as wishlist_count
        FROM users u
        LEFT JOIN shops s ON u.id = s.seller_id
        LEFT JOIN orders o ON u.id = o.customer_id
        LEFT JOIN wishlist w ON u.id = w.customer_id
        WHERE u.role = 'customer'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $customers = $stmt->fetchAll();
} catch (Exception $e) {
    $customers = [];
    $error = "Failed to load customers: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('customer_management'); ?> - <?php echo t('admin_panel'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <?php render_navbar('admin', '../'); ?>

    <!-- Main Content -->
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">👥 <?php echo t('customer_management'); ?></h1>
            <p class="admin-subtitle"><?php echo count($customers); ?> <?php echo t('customers_registered'); ?></p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <span class="alert-message"><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <span class="alert-icon">❌</span>
                <span class="alert-message"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Customers Table -->
        <div class="admin-card">
            <div class="card-header">
                <h2 class="card-title">📋 <?php echo t('all_customers'); ?></h2>
            </div>
            
            <?php if (empty($customers)): ?>
                <div class="empty-state">
                    <div class="empty-icon">👥</div>
                    <h3><?php echo t('no_customers_yet'); ?></h3>
                    <p><?php echo t('customers_will_appear_here'); ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><?php echo t('customer_info'); ?></th>
                                <th><?php echo t('contact'); ?></th>
                                <th><?php echo t('statistics'); ?></th>
                                <th><?php echo t('status'); ?></th>
                                <th><?php echo t('joined'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <div class="customer-info">
                                            <div class="customer-avatar">
                                                <span class="avatar-text"><?php echo strtoupper(substr($customer['username'], 0, 2)); ?></span>
                                            </div>
                                            <div class="customer-details">
                                                <h4 class="customer-name"><?php echo htmlspecialchars($customer['username']); ?></h4>
                                                <p class="customer-id">ID: <?php echo $customer['id']; ?></p>
                                                <?php if ($customer['full_name']): ?>
                                                    <p class="customer-fullname"><?php echo htmlspecialchars($customer['full_name']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <p class="contact-email">📧 <?php echo htmlspecialchars($customer['email']); ?></p>
                                            <?php if ($customer['phone']): ?>
                                                <p class="contact-phone">📱 <?php echo htmlspecialchars($customer['phone']); ?></p>
                                            <?php endif; ?>
                                            <?php if ($customer['address']): ?>
                                                <p class="contact-address">📍 <?php echo htmlspecialchars(substr($customer['address'], 0, 50)) . (strlen($customer['address']) > 50 ? '...' : ''); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="customer-stats">
                                            <div class="stat-item">
                                                <span class="stat-icon">🏪</span>
                                                <span class="stat-value"><?php echo $customer['shop_count']; ?></span>
                                                <span class="stat-label"><?php echo t('shops'); ?></span>
                                            </div>
                                            <div class="stat-item">
                                                <span class="stat-icon">📦</span>
                                                <span class="stat-value"><?php echo $customer['order_count']; ?></span>
                                                <span class="stat-label"><?php echo t('orders'); ?></span>
                                            </div>
                                            <div class="stat-item">
                                                <span class="stat-icon">❤️</span>
                                                <span class="stat-value"><?php echo $customer['wishlist_count']; ?></span>
                                                <span class="stat-label"><?php echo t('wishlist'); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $customer['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $customer['is_active'] ? '✅ ' . t('active') : '❌ ' . t('inactive'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="date-info">
                                            <p class="join-date"><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></p>
                                            <p class="join-time"><?php echo date('g:i A', strtotime($customer['created_at'])); ?></p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-reset" onclick="showResetPasswordModal(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['username']); ?>')">
                                                🔑 <?php echo t('reset_password'); ?>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                <button type="submit" class="btn-action btn-toggle" onclick="return confirm('<?php echo t('confirm_status_change'); ?>')">
                                                    <?php echo $customer['is_active'] ? '🚫 ' . t('deactivate') : '✅ ' . t('activate'); ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔑 <?php echo t('reset_customer_password'); ?></h3>
                <button class="modal-close" onclick="closeResetPasswordModal()">×</button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="customer_id" id="resetCustomerId">
                
                <div class="form-group">
                    <label><?php echo t('customer'); ?>:</label>
                    <p id="resetCustomerName" class="customer-display"></p>
                </div>
                
                <div class="form-group">
                    <label for="new_password"><?php echo t('new_password'); ?>:</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6" 
                           placeholder="<?php echo t('enter_new_password'); ?>" class="form-control">
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-modal btn-primary">
                        🔑 <?php echo t('reset_password'); ?>
                    </button>
                    <button type="button" class="btn-modal btn-secondary" onclick="closeResetPasswordModal()">
                        ❌ <?php echo t('cancel'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function showResetPasswordModal(customerId, customerName) {
            document.getElementById('resetCustomerId').value = customerId;
            document.getElementById('resetCustomerName').textContent = customerName;
            document.getElementById('resetPasswordModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeResetPasswordModal() {
            document.getElementById('resetPasswordModal').classList.remove('active');
            document.body.style.overflow = '';
            document.querySelector('.modal-form').reset();
        }
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeResetPasswordModal();
            }
        });
    </script>
</body>
</html>
