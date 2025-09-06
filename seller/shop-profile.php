<?php
/**
 * DesiDukaan - Shop Profile
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/seller_navbar.php';

// Require seller login
require_role('seller');

// Get seller's shop
$db = getDB();
$stmt = $db->prepare("SELECT * FROM shops WHERE seller_id = ? AND status = 'approved'");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

if (!$shop) {
    redirect(SITE_URL . '/seller/dashboard.php');
}

// Get seller information
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Profile - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Sticky Navigation -->
    <?php render_seller_navbar('profile'); ?>

    <!-- Main Content -->
    <div class="container seller-dashboard">
        <!-- Profile Header -->
        <div class="dashboard-section">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php if ($shop['shop_logo']): ?>
                        <img src="<?php echo UPLOAD_URL . 'logos/' . $shop['shop_logo']; ?>" 
                             alt="Shop Logo" class="shop-logo-large">
                    <?php else: ?>
                        <div class="default-avatar">🏪</div>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h1 class="shop-name"><?php echo htmlspecialchars($shop['shop_name']); ?></h1>
                    <p class="shop-owner">Owned by <?php echo htmlspecialchars($user['username']); ?></p>
                    <span class="shop-status approved">✅ Approved Shop</span>
                </div>
            </div>
        </div>

        <!-- Shop Information -->
        <div class="dashboard-section">
            <h2 class="section-title">Shop Information</h2>
            <div class="info-grid">
                <div class="info-card">
                    <h3 class="info-title">Contact Details</h3>
                    <div class="info-item">
                        <strong>Email:</strong>
                        <span><?php echo htmlspecialchars($shop['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Phone:</strong>
                        <span><?php echo htmlspecialchars($shop['phone_number']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>CNIC:</strong>
                        <span><?php echo htmlspecialchars($shop['cnic_number']); ?></span>
                    </div>
                </div>

                <div class="info-card">
                    <h3 class="info-title">Address</h3>
                    <div class="info-item">
                        <p><?php echo nl2br(htmlspecialchars($shop['address'])); ?></p>
                    </div>
                </div>

                <?php if ($shop['shop_description']): ?>
                <div class="info-card full-width">
                    <h3 class="info-title">About Our Shop</h3>
                    <div class="info-item">
                        <p><?php echo nl2br(htmlspecialchars($shop['shop_description'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Account Information -->
        <div class="dashboard-section">
            <h2 class="section-title">Account Information</h2>
            <div class="info-grid">
                <div class="info-card">
                    <h3 class="info-title">Personal Details</h3>
                    <div class="info-item">
                        <strong>Username:</strong>
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Email:</strong>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>CNIC:</strong>
                        <span><?php echo htmlspecialchars($user['cnic']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Mobile:</strong>
                        <span><?php echo htmlspecialchars($user['mobile_number']); ?></span>
                    </div>
                </div>

                <div class="info-card">
                    <h3 class="info-title">Account Status</h3>
                    <div class="info-item">
                        <strong>Member Since:</strong>
                        <span><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Shop Approved:</strong>
                        <span><?php echo date('F j, Y', strtotime($shop['approved_at'])); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Account Status:</strong>
                        <span class="status-active">✅ Active</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="dashboard-section">
            <div class="profile-actions">
                <a href="shop-settings.php" class="btn btn-primary">Edit Shop Information</a>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>

<style>
.profile-header {
    display: flex;
    align-items: center;
    gap: 2rem;
    background: var(--background-white);
    padding: 3rem;
    border-radius: 20px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.profile-avatar {
    flex-shrink: 0;
}

.shop-logo-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--primary-color);
}

.default-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: var(--ajrak-gradient-1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: white;
}

.profile-info {
    flex: 1;
}

.shop-name {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.shop-owner {
    font-size: 1.2rem;
    color: var(--text-light);
    margin-bottom: 1rem;
}

.shop-status {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.shop-status.approved {
    background: rgba(40, 167, 69, 0.1);
    color: var(--success-color);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
}

.info-card {
    background: var(--background-white);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    border-left: 4px solid var(--primary-color);
}

.info-card.full-width {
    grid-column: 1 / -1;
}

.info-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1.5rem;
    border-bottom: 2px solid var(--border-light);
    padding-bottom: 0.5rem;
}

.info-item {
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.info-item:last-child {
    margin-bottom: 0;
}

.info-item strong {
    color: var(--text-dark);
    font-weight: 600;
    min-width: 100px;
}

.info-item span,
.info-item p {
    color: var(--text-light);
    margin: 0;
    flex: 1;
    text-align: right;
}

.status-active {
    color: var(--success-color);
    font-weight: 600;
}

.profile-actions {
    text-align: center;
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
        padding: 2rem;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .info-item span,
    .info-item p {
        text-align: left;
    }
    
    .profile-actions {
        flex-direction: column;
        align-items: center;
    }
}
</style>
