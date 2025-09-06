<?php
/**
 * DesiDukaan - Login Page
 */

require_once '../config/config.php';
require_once '../config/languages.php';
require_once '../includes/navbar.php';

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

$errors = [];
$success_message = get_flash_message('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email)) {
        $errors['email'] = t('field_required');
    } elseif (!validate_email($email)) {
        $errors['email'] = t('invalid_email');
    }
    
    if (empty($password)) {
        $errors['password'] = t('field_required');
    }
    
    // If no validation errors, attempt login
    if (empty($errors)) {
        try {
            $db = getDB();
            
            $stmt = $db->prepare("SELECT id, username, password_hash, role FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && verify_password($password, $user['password_hash'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                
                // Create session record
                $session_id = generate_session_id();
                $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
                
                $stmt = $db->prepare("INSERT INTO user_sessions (id, user_id, expires_at, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $session_id,
                    $user['id'],
                    $expires_at,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                $_SESSION['session_id'] = $session_id;
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'customer':
                        redirect(SITE_URL . '/customer/dashboard.php');
                        break;
                    case 'seller':
                        // Check if seller has a shop
                        $stmt = $db->prepare("SELECT id, status FROM shops WHERE seller_id = ?");
                        $stmt->execute([$user['id']]);
                        $shop = $stmt->fetch();
                        
                        if (!$shop) {
                            redirect(SITE_URL . '/seller/create-shop.php');
                        } elseif ($shop['status'] === 'pending') {
                            redirect(SITE_URL . '/seller/shop-pending.php');
                        } else {
                            redirect(SITE_URL . '/seller/dashboard.php');
                        }
                        break;
                    case 'admin':
                        redirect(SITE_URL . '/admin/dashboard.php');
                        break;
                }
                
            } else {
                $errors['general'] = t('invalid_credentials');
            }
            
        } catch (PDOException $e) {
            $errors['general'] = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_current_language(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('login'); ?> - <?php echo t('site_name'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
</head>
<body>
    <!-- Navigation -->
    <?php render_navbar('', '../'); ?>

    <!-- Main Content -->
    <div class="container" style="padding: 2rem 20px;">
        <div class="row" style="justify-content: center;">
            <div class="col" style="max-width: 450px;">
                <div class="card">
                    <div class="card-header" style="text-align: center;">
                        <div style="width: 80px; height: 80px; background: var(--primary-color); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                            🔐
                        </div>
                        <h2 class="card-title"><?php echo t('login'); ?></h2>
                    </div>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-error">
                            <?php echo $errors['general']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="loginForm" data-validate>
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
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <?php echo t('login'); ?>
                        </button>
                    </form>
                    
                    <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-light);">
                        <p style="color: var(--text-light);">
                            Don't have an account?
                            <a href="../user-type-selection.php" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                                <?php echo t('register'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>
