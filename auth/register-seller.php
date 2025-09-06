<?php
/**
 * DesiDukaan - Seller Registration
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/navbar.php';

// If user is already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect(SITE_URL . '/seller/dashboard.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $cnic = sanitize_input($_POST['cnic'] ?? '');
    $mobile_number = sanitize_input($_POST['mobile_number'] ?? '');
    
    // Validation
    if (empty($username)) {
        $errors['username'] = t('field_required');
    }
    
    if (empty($email)) {
        $errors['email'] = t('field_required');
    } elseif (!validate_email($email)) {
        $errors['email'] = t('invalid_email');
    }
    
    if (empty($password)) {
        $errors['password'] = t('field_required');
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors['password'] = t('password_too_short');
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = t('passwords_dont_match');
    }
    
    if (empty($cnic)) {
        $errors['cnic'] = t('field_required');
    } elseif (!validate_cnic($cnic)) {
        $errors['cnic'] = t('invalid_cnic');
    }
    
    if (empty($mobile_number)) {
        $errors['mobile_number'] = t('field_required');
    } elseif (!validate_mobile($mobile_number)) {
        $errors['mobile_number'] = t('invalid_mobile');
    }
    
    // Check if username, email, or CNIC already exists
    if (empty($errors)) {
        try {
            $db = getDB();
            
            // Check username
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors['username'] = t('username_taken');
            }
            
            // Check email
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors['email'] = t('email_taken');
            }
            
            // Check CNIC
            $stmt = $db->prepare("SELECT id FROM users WHERE cnic = ?");
            $stmt->execute([$cnic]);
            if ($stmt->fetch()) {
                $errors['cnic'] = 'CNIC is already registered.';
            }
            
        } catch (PDOException $e) {
            $errors['general'] = 'Database error occurred.';
        }
    }
    
    // If no errors, create user
    if (empty($errors)) {
        try {
            $db = getDB();
            $password_hash = hash_password($password);

            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, role, cnic, mobile_number) VALUES (?, ?, ?, 'seller', ?, ?)");
            $stmt->execute([$username, $email, $password_hash, $cnic, $mobile_number]);

            // Log in the user
            $user_id = $db->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_role'] = 'seller';
            $_SESSION['username'] = $username;

            // Set success flag for display
            $success = true;

        } catch (PDOException $e) {
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('register_seller'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <?php render_navbar('', '../'); ?>

    <!-- Main Content -->
    <div class="container" style="padding: 2rem 20px;">
        <div class="row" style="justify-content: center;">
            <div class="col" style="max-width: 500px;">
                <div class="card">
                    <div class="card-header" style="text-align: center;">
                        <div style="width: 80px; height: 80px; background: var(--primary-color); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            🏪
                        </div>
                        <h2 class="card-title"><?php echo t('register_seller'); ?></h2>
                    </div>
                    
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-error">
                            <?php echo $errors['general']; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <h4>🎉 Registration Successful!</h4>
                            <p>Welcome to DesiDukaan, <?php echo htmlspecialchars($username); ?>!</p>
                            <p>Now let's create your shop to start selling your amazing products.</p>
                            <div style="margin-top: 1rem;">
                                <a href="../seller/create-shop.php" class="btn btn-primary">
                                    Create Your Shop →
                                </a>
                            </div>
                        </div>

                        <script>
                            // Auto-redirect after 5 seconds
                            setTimeout(function() {
                                window.location.href = '../seller/create-shop.php';
                            }, 5000);
                        </script>
                    <?php endif; ?>
                    
                    <?php if (!$success): ?>
                    <form method="POST" id="sellerRegisterForm" data-validate>
                        <div class="form-group">
                            <label for="username" class="form-label"><?php echo t('username'); ?></label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="error-message"><?php echo $errors['username']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="cnic" class="form-label"><?php echo t('cnic'); ?></label>
                            <input type="text" id="cnic" name="cnic" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['cnic'] ?? ''); ?>" 
                                   placeholder="1234567890123" maxlength="15" required
                                   oninput="formatCNIC(this)">
                            <?php if (isset($errors['cnic'])): ?>
                                <div class="error-message"><?php echo $errors['cnic']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="mobile_number" class="form-label"><?php echo t('mobile_number'); ?></label>
                            <input type="text" id="mobile_number" name="mobile_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['mobile_number'] ?? ''); ?>" 
                                   placeholder="03XXXXXXXXX" maxlength="13" required
                                   oninput="formatMobile(this)">
                            <?php if (isset($errors['mobile_number'])): ?>
                                <div class="error-message"><?php echo $errors['mobile_number']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label"><?php echo t('email'); ?></label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="error-message"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label"><?php echo t('password'); ?></label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="error-message"><?php echo $errors['password']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label"><?php echo t('confirm_password'); ?></label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="error-message"><?php echo $errors['confirm_password']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <?php echo t('register_seller'); ?>
                        </button>
                    </form>

                    <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-light);">
                        <p style="color: var(--text-light);">
                            <?php echo t('already_have_account'); ?>
                            <a href="login.php" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                                <?php echo t('login'); ?>
                            </a>
                        </p>
                        <p style="color: var(--text-light); margin-top: 1rem;">
                            <a href="../user-type-selection.php" style="color: var(--primary-color); text-decoration: none;">
                                ← <?php echo t('choose_user_type'); ?>
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>
