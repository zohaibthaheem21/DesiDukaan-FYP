<?php
/**
 * DesiDukaan - Create Shop
 */

require_once '../config/config.php';
require_once '../config/languages.php';

// Require seller login
require_role('seller');

// Check if seller already has a shop
$db = getDB();
$stmt = $db->prepare("SELECT id, status FROM shops WHERE seller_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$existing_shop = $stmt->fetch();

if ($existing_shop) {
    if ($existing_shop['status'] === 'pending') {
        redirect(SITE_URL . '/seller/shop-pending.php');
    } else {
        redirect(SITE_URL . '/seller/dashboard.php');
    }
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = sanitize_input($_POST['shop_name'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone_number = sanitize_input($_POST['phone_number'] ?? '');
    $cnic_number = sanitize_input($_POST['cnic_number'] ?? '');
    $shop_description = sanitize_input($_POST['shop_description'] ?? '');
    
    // Validation
    if (empty($shop_name)) {
        $errors['shop_name'] = t('field_required');
    }
    
    if (empty($address)) {
        $errors['address'] = t('field_required');
    }
    
    if (empty($email)) {
        $errors['email'] = t('field_required');
    } elseif (!validate_email($email)) {
        $errors['email'] = t('invalid_email');
    }
    
    if (empty($phone_number)) {
        $errors['phone_number'] = t('field_required');
    } elseif (!validate_mobile($phone_number)) {
        $errors['phone_number'] = t('invalid_mobile');
    }
    
    if (empty($cnic_number)) {
        $errors['cnic_number'] = t('field_required');
    } elseif (!validate_cnic($cnic_number)) {
        $errors['cnic_number'] = t('invalid_cnic');
    }
    
    // File uploads
    $cnic_picture = null;
    $shop_logo = null;
    
    // Handle CNIC picture upload
    if (isset($_FILES['cnic_picture']) && $_FILES['cnic_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['cnic_picture']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['cnic_picture']['name'], PATHINFO_EXTENSION);
            $cnic_picture = 'cnic_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
            $upload_path = UPLOAD_PATH . 'cnic/' . $cnic_picture;
            
            if (!move_uploaded_file($_FILES['cnic_picture']['tmp_name'], $upload_path)) {
                $errors['cnic_picture'] = 'Failed to upload CNIC picture.';
            }
        } else {
            $errors['cnic_picture'] = 'Please upload a valid image file (JPEG, PNG).';
        }
    } else {
        $errors['cnic_picture'] = 'CNIC picture is required.';
    }
    
    // Handle shop logo upload
    if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['shop_logo']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['shop_logo']['name'], PATHINFO_EXTENSION);
            $shop_logo = 'logo_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
            $upload_path = UPLOAD_PATH . 'logos/' . $shop_logo;
            
            if (!move_uploaded_file($_FILES['shop_logo']['tmp_name'], $upload_path)) {
                $errors['shop_logo'] = 'Failed to upload shop logo.';
            }
        } else {
            $errors['shop_logo'] = 'Please upload a valid image file (JPEG, PNG).';
        }
    }
    
    // If no errors, create shop request
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO shops (seller_id, shop_name, address, email, phone_number, cnic_number, cnic_picture, shop_logo, shop_description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $_SESSION['user_id'],
                $shop_name,
                $address,
                $email,
                $phone_number,
                $cnic_number,
                $cnic_picture,
                $shop_logo,
                $shop_description
            ]);
            
            redirect(SITE_URL . '/seller/shop-pending.php');
            
        } catch (PDOException $e) {
            $errors['general'] = 'Failed to submit shop request. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('create_shop'); ?> - <?php echo t('site_name'); ?></title>
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
    <div class="container" style="padding: 2rem 20px;">
        <div class="row" style="justify-content: center;">
            <div class="col" style="max-width: 600px;">
                <div class="card">
                    <div class="card-header" style="text-align: center;">
                        <div style="width: 80px; height: 80px; background: var(--primary-color); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            🏪
                        </div>
                        <h2 class="card-title"><?php echo t('create_shop'); ?></h2>
                        <p style="color: var(--text-light);">
                            Fill in the details below to create your shop. Your request will be reviewed by our admin team.
                        </p>
                    </div>
                    
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-error">
                            <?php echo $errors['general']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" id="createShopForm" data-validate>
                        <div class="form-group">
                            <label for="shop_name" class="form-label"><?php echo t('shop_name'); ?></label>
                            <input type="text" id="shop_name" name="shop_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['shop_name'] ?? ''); ?>" required>
                            <?php if (isset($errors['shop_name'])): ?>
                                <div class="error-message"><?php echo $errors['shop_name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="address" class="form-label"><?php echo t('address'); ?></label>
                            <textarea id="address" name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            <?php if (isset($errors['address'])): ?>
                                <div class="error-message"><?php echo $errors['address']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="email" class="form-label"><?php echo t('email'); ?></label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="error-message"><?php echo $errors['email']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="phone_number" class="form-label"><?php echo t('phone_number'); ?></label>
                                    <input type="text" id="phone_number" name="phone_number" class="form-control" 
                                           value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>" 
                                           placeholder="03XXXXXXXXX" required oninput="formatMobile(this)">
                                    <?php if (isset($errors['phone_number'])): ?>
                                        <div class="error-message"><?php echo $errors['phone_number']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="cnic_number" class="form-label"><?php echo t('cnic_number'); ?></label>
                            <input type="text" id="cnic_number" name="cnic_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['cnic_number'] ?? ''); ?>" 
                                   placeholder="1234567890123" required oninput="formatCNIC(this)">
                            <?php if (isset($errors['cnic_number'])): ?>
                                <div class="error-message"><?php echo $errors['cnic_number']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="cnic_picture" class="form-label"><?php echo t('cnic_picture'); ?></label>
                                    <input type="file" id="cnic_picture" name="cnic_picture" class="form-control" 
                                           accept="image/*" required data-preview="cnicPreview">
                                    <img id="cnicPreview" style="max-width: 100%; height: 150px; object-fit: cover; margin-top: 10px; display: none; border-radius: 6px;">
                                    <?php if (isset($errors['cnic_picture'])): ?>
                                        <div class="error-message"><?php echo $errors['cnic_picture']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="shop_logo" class="form-label"><?php echo t('shop_logo'); ?></label>
                                    <input type="file" id="shop_logo" name="shop_logo" class="form-control" 
                                           accept="image/*" data-preview="logoPreview">
                                    <img id="logoPreview" style="max-width: 100%; height: 150px; object-fit: cover; margin-top: 10px; display: none; border-radius: 6px;">
                                    <?php if (isset($errors['shop_logo'])): ?>
                                        <div class="error-message"><?php echo $errors['shop_logo']; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="shop_description" class="form-label"><?php echo t('shop_description'); ?></label>
                            <textarea id="shop_description" name="shop_description" class="form-control" rows="4" 
                                      placeholder="Tell us about your shop and what you sell..."><?php echo htmlspecialchars($_POST['shop_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <?php echo t('submit'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
