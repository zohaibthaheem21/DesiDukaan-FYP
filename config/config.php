<?php
/**
 * Main Configuration File for DesiDukaan
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Site configuration
define('SITE_NAME', 'DesiDukaan');
define('SITE_URL', 'http://localhost/DesiDukaan');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Create upload directories if they don't exist
$upload_dirs = [
    UPLOAD_PATH . 'cnic/',
    UPLOAD_PATH . 'logos/',
    UPLOAD_PATH . 'products/'
];

foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Language settings
define('DEFAULT_LANGUAGE', 'en');
$supported_languages = ['en', 'sd'];

// Helper functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_cnic($cnic) {
    // Remove any non-numeric characters
    $cnic = preg_replace('/[^0-9]/', '', $cnic);
    // Check if it's 13 digits
    return strlen($cnic) === 13 && is_numeric($cnic);
}

function validate_mobile($mobile) {
    // Remove any non-numeric characters
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    // Check if it's 11 digits starting with 03
    return strlen($mobile) === 11 && substr($mobile, 0, 2) === '03';
}

function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function generate_session_id() {
    return bin2hex(random_bytes(32));
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . '/auth/login.php');
        exit;
    }
}

function require_role($required_role) {
    require_login();
    if ($_SESSION['user_role'] !== $required_role) {
        header('Location: ' . SITE_URL . '/auth/unauthorized.php');
        exit;
    }
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function get_current_language() {
    return $_SESSION['language'] ?? DEFAULT_LANGUAGE;
}

function set_language($lang) {
    global $supported_languages;
    if (in_array($lang, $supported_languages)) {
        $_SESSION['language'] = $lang;
    }
}

// Error handling
function handle_error($message, $redirect_url = null) {
    $_SESSION['error'] = $message;
    if ($redirect_url) {
        redirect($redirect_url);
    }
}

function handle_success($message, $redirect_url = null) {
    $_SESSION['success'] = $message;
    if ($redirect_url) {
        redirect($redirect_url);
    }
}

function get_flash_message($type) {
    if (isset($_SESSION[$type])) {
        $message = $_SESSION[$type];
        unset($_SESSION[$type]);
        return $message;
    }
    return null;
}
?>
