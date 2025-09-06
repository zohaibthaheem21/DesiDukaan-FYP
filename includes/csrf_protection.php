<?php
/**
 * DesiDukaan - CSRF Protection System
 * Comprehensive Cross-Site Request Forgery protection
 */

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    
    $token = bin2hex(random_bytes(32));
    $timestamp = time();
    
    // Store token with timestamp
    $_SESSION['csrf_tokens'][$token] = $timestamp;
    
    // Clean up old tokens (older than 1 hour)
    cleanupOldCSRFTokens();
    
    return $token;
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
        return false;
    }
    
    if (!isset($_SESSION['csrf_tokens'][$token])) {
        return false;
    }
    
    $timestamp = $_SESSION['csrf_tokens'][$token];
    $current_time = time();
    
    // Token expires after 1 hour
    if (($current_time - $timestamp) > 3600) {
        unset($_SESSION['csrf_tokens'][$token]);
        return false;
    }
    
    return true;
}

/**
 * Clean up old CSRF tokens
 */
function cleanupOldCSRFTokens() {
    if (!isset($_SESSION['csrf_tokens'])) {
        return;
    }
    
    $current_time = time();
    
    foreach ($_SESSION['csrf_tokens'] as $token => $timestamp) {
        if (($current_time - $timestamp) > 3600) {
            unset($_SESSION['csrf_tokens'][$token]);
        }
    }
}

/**
 * Get CSRF token for forms
 */
function getCSRFToken() {
    return generateCSRFToken();
}

/**
 * Verify CSRF token from request
 */
function verifyCSRFToken() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
    
    if (!$token) {
        return false;
    }
    
    return validateCSRFToken($token);
}

/**
 * Require CSRF token validation
 */
function requireCSRFToken() {
    if (!verifyCSRFToken()) {
        http_response_code(403);
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'CSRF token validation failed',
                'error_code' => 'CSRF_INVALID'
            ]);
        } else {
            echo "CSRF token validation failed. Please refresh the page and try again.";
        }
        exit;
    }
}

/**
 * Generate CSRF hidden input field
 */
function csrfField() {
    $token = getCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Generate CSRF meta tag for AJAX requests
 */
function csrfMetaTag() {
    $token = getCSRFToken();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Rate limiting for CSRF token generation
 */
function rateLimitCSRFGeneration() {
    $key = 'csrf_generation_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'timestamp' => time()];
    }
    
    $current_time = time();
    $rate_limit_data = $_SESSION[$key];
    
    // Reset counter every minute
    if (($current_time - $rate_limit_data['timestamp']) > 60) {
        $_SESSION[$key] = ['count' => 1, 'timestamp' => $current_time];
        return true;
    }
    
    // Allow max 30 token generations per minute
    if ($rate_limit_data['count'] >= 30) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Enhanced CSRF protection with rate limiting
 */
function generateCSRFTokenWithRateLimit() {
    if (!rateLimitCSRFGeneration()) {
        throw new Exception('Rate limit exceeded for CSRF token generation');
    }
    
    return generateCSRFToken();
}

/**
 * Validate request origin
 */
function validateRequestOrigin() {
    $allowed_origins = [
        $_SERVER['HTTP_HOST'] ?? '',
        'localhost',
        '127.0.0.1'
    ];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    
    if (empty($origin)) {
        return true; // Allow requests without origin (direct access)
    }
    
    $parsed_origin = parse_url($origin);
    $origin_host = $parsed_origin['host'] ?? '';
    
    return in_array($origin_host, $allowed_origins);
}

/**
 * Comprehensive request validation
 */
function validateRequest() {
    // Check request origin
    if (!validateRequestOrigin()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request origin',
            'error_code' => 'INVALID_ORIGIN'
        ]);
        exit;
    }
    
    // Check CSRF token for POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCSRFToken();
    }
}

/**
 * Secure headers for CSRF protection
 */
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (basic)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
}

/**
 * Initialize CSRF protection
 */
function initCSRFProtection() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set security headers
    setSecurityHeaders();
    
    // Clean up old tokens
    cleanupOldCSRFTokens();
}

/**
 * CSRF protection middleware for AJAX endpoints
 */
function csrfMiddleware() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRFToken()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'CSRF token validation failed. Please refresh the page.',
                'error_code' => 'CSRF_INVALID',
                'reload_required' => true
            ]);
            exit;
        }
    }
}

/**
 * Generate JavaScript code for CSRF token handling
 */
function getCSRFJavaScript() {
    $token = getCSRFToken();
    return "
    <script>
        // CSRF token for AJAX requests
        window.csrfToken = '" . htmlspecialchars($token) . "';
        
        // Add CSRF token to all AJAX requests
        (function() {
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                if (options.method && options.method.toUpperCase() === 'POST') {
                    if (options.body instanceof FormData) {
                        options.body.append('csrf_token', window.csrfToken);
                    } else if (typeof options.body === 'string') {
                        const params = new URLSearchParams(options.body);
                        params.append('csrf_token', window.csrfToken);
                        options.body = params.toString();
                    }
                }
                return originalFetch(url, options);
            };
            
            // Add CSRF token to jQuery AJAX requests
            if (window.jQuery) {
                jQuery.ajaxSetup({
                    beforeSend: function(xhr, settings) {
                        if (settings.type === 'POST') {
                            if (settings.data) {
                                settings.data += '&csrf_token=' + encodeURIComponent(window.csrfToken);
                            } else {
                                settings.data = 'csrf_token=' + encodeURIComponent(window.csrfToken);
                            }
                        }
                    }
                });
            }
        })();
    </script>
    ";
}
?>
