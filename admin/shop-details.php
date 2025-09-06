<?php
/**
 * DesiDukaan - Admin Shop Details & Approval
 */

require_once '../config/config.php';
require_once '../config/languages.php';

// Require admin login
require_role('admin');

$shop_id = $_GET['id'] ?? 0;

// Get shop details
$db = getDB();
$stmt = $db->prepare("
    SELECT s.*, u.username, u.email as user_email, u.cnic as user_cnic, u.mobile_number 
    FROM shops s 
    JOIN users u ON s.seller_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$shop_id]);
$shop = $stmt->fetch();

if (!$shop) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$errors = [];
$success_message = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rejection_reason = sanitize_input($_POST['rejection_reason'] ?? '');
    
    if ($action === 'approve') {
        try {
            $stmt = $db->prepare("UPDATE shops SET status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $shop_id]);

            // Log admin action
            $stmt = $db->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?, 'approve_shop', 'shop', ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $shop_id, "Approved shop: " . $shop['shop_name']]);

            // Send notification to seller
            $notification_title = "Shop Approved! 🎉";
            $notification_message = "Congratulations! Your shop '{$shop['shop_name']}' has been approved and is now live on DesiDukaan. You can start adding products and managing your shop.";

            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')");
            $stmt->execute([$shop['seller_id'], $notification_title, $notification_message]);

            $success_message = "Shop approved successfully! Seller has been notified.";
            $shop['status'] = 'approved';

        } catch (PDOException $e) {
            $errors['general'] = 'Failed to approve shop. Please try again.';
        }
        
    } elseif ($action === 'reject') {
        if (empty($rejection_reason)) {
            $errors['rejection_reason'] = 'Please provide a reason for rejection.';
        } else {
            try {
                $stmt = $db->prepare("UPDATE shops SET status = 'rejected', rejection_reason = ? WHERE id = ?");
                $stmt->execute([$rejection_reason, $shop_id]);

                // Log admin action
                $stmt = $db->prepare("INSERT INTO admin_logs (admin_id, action, target_type, target_id, details) VALUES (?, 'reject_shop', 'shop', ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $shop_id, "Rejected shop: " . $shop['shop_name'] . " - Reason: " . $rejection_reason]);

                // Send notification to seller
                $notification_title = "Shop Request Rejected";
                $notification_message = "Unfortunately, your shop '{$shop['shop_name']}' request has been rejected. Reason: {$rejection_reason}. You can submit a new request with the required corrections.";

                $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'error')");
                $stmt->execute([$shop['seller_id'], $notification_title, $notification_message]);

                $success_message = "Shop rejected successfully! Seller has been notified with the reason.";
                $shop['status'] = 'rejected';

            } catch (PDOException $e) {
                $errors['general'] = 'Failed to reject shop. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Details - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand">
                <div class="brand-container">
                    <?php
                    $logo_path = '../assets/images/logo.png';
                    if (file_exists($logo_path)): ?>
                        <div class="logo-wrapper">
                            <img src="<?php echo $logo_path; ?>" alt="DesiDukaan Logo" class="brand-logo">
                        </div>
                    <?php else: ?>
                        <div class="logo-wrapper">
                            <span class="brand-icon">🏪</span>
                        </div>
                    <?php endif; ?>
                    <span class="brand-text"><?php echo t('site_name'); ?> - Admin</span>
                </div>
            </a>
            
            <ul class="navbar-nav">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="shop-requests.php"><?php echo t('shop_requests'); ?></a></li>
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
    <div class="container" style="padding: 2rem 20px;">
        <div class="row">
            <div class="col">
                <a href="dashboard.php" style="color: var(--primary-color); text-decoration: none; margin-bottom: 1rem; display: inline-block;">
                    ← Back to Dashboard
                </a>
                <h2 style="color: var(--primary-color); margin-bottom: 2rem;">
                    Shop Details & Review
                </h2>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="row">
                <div class="col">
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($errors['general'])): ?>
            <div class="row">
                <div class="col">
                    <div class="alert alert-error">
                        <?php echo $errors['general']; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Shop Information -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Shop Information</h3>
                        <span class="badge" style="background: <?php 
                            echo $shop['status'] === 'pending' ? 'var(--warning-color)' : 
                                ($shop['status'] === 'approved' ? 'var(--success-color)' : 'var(--error-color)'); 
                        ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem;">
                            <?php echo ucfirst($shop['status']); ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Shop Name:</strong><br>
                        <?php echo htmlspecialchars($shop['shop_name']); ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Address:</strong><br>
                        <?php echo nl2br(htmlspecialchars($shop['address'])); ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Contact Email:</strong><br>
                        <?php echo htmlspecialchars($shop['email']); ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Phone Number:</strong><br>
                        <?php echo htmlspecialchars($shop['phone_number']); ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>CNIC Number:</strong><br>
                        <?php echo htmlspecialchars($shop['cnic_number']); ?>
                    </div>
                    
                    <?php if ($shop['shop_description']): ?>
                        <div style="margin-bottom: 1rem;">
                            <strong>Description:</strong><br>
                            <?php echo nl2br(htmlspecialchars($shop['shop_description'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Submitted:</strong><br>
                        <?php echo date('F j, Y g:i A', strtotime($shop['created_at'])); ?>
                    </div>
                </div>
            </div>
            
            <!-- Seller Information -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Seller Information</h3>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Username:</strong><br>
                        <?php echo htmlspecialchars($shop['username']); ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Email:</strong><br>
                        <?php echo htmlspecialchars($shop['user_email']); ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>CNIC:</strong><br>
                        <?php echo htmlspecialchars($shop['user_cnic']); ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Mobile Number:</strong><br>
                        <?php echo htmlspecialchars($shop['mobile_number']); ?>
                    </div>
                </div>
                
                <!-- Documents -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Uploaded Documents</h3>
                    </div>
                    
                    <?php if ($shop['cnic_picture']): ?>
                        <div style="margin-bottom: 1rem;">
                            <strong>CNIC Picture:</strong><br>
                            <img src="<?php echo UPLOAD_URL . 'cnic/' . $shop['cnic_picture']; ?>" 
                                 style="max-width: 100%; height: 200px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-light);"
                                 alt="CNIC Picture">
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($shop['shop_logo']): ?>
                        <div style="margin-bottom: 1rem;">
                            <strong>Shop Logo:</strong><br>
                            <img src="<?php echo UPLOAD_URL . 'logos/' . $shop['shop_logo']; ?>" 
                                 style="max-width: 100%; height: 150px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-light);"
                                 alt="Shop Logo">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <?php if ($shop['status'] === 'pending'): ?>
            <div class="row" style="margin-top: 2rem;">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo t('approval_actions'); ?></h3>
                        </div>
                        
                        <form method="POST" style="display: flex; gap: 1rem; align-items: flex-end;">
                            <div style="flex: 1;">
                                <label for="rejection_reason" class="form-label">Rejection Reason (if rejecting):</label>
                                <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="3" 
                                          placeholder="Provide a clear reason for rejection..."><?php echo htmlspecialchars($_POST['rejection_reason'] ?? ''); ?></textarea>
                                <?php if (isset($errors['rejection_reason'])): ?>
                                    <div class="error-message"><?php echo $errors['rejection_reason']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" name="action" value="approve" class="btn btn-success">
                                    ✅ <?php echo t('approve'); ?>
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger">
                                    ❌ <?php echo t('reject'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
