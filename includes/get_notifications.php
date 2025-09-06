<?php
/**
 * DesiDukaan - Get Notifications AJAX Endpoint
 * Returns user notifications for real-time updates with security
 */

require_once '../config/config.php';
require_once 'notifications.php';
require_once 'csrf_protection.php';
require_once 'input_validation.php';

// Initialize CSRF protection
initCSRFProtection();

// Rate limiting
if (!validateRateLimit('get_notifications', 60, 60)) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Rate limit exceeded',
        'error_code' => 'RATE_LIMIT_EXCEEDED'
    ]);
    exit;
}

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $limit = intval($_GET['limit'] ?? 10);
    $type = $_GET['type'] ?? null;
    $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
    
    // Validate limit
    if ($limit < 1 || $limit > 50) {
        $limit = 10;
    }
    
    $notifications = get_user_notifications($user_id, $limit, $type, $unread_only);
    $unread_count = get_unread_notification_count($user_id);
    
    // Format notifications for JSON response
    $formatted_notifications = array_map(function($notification) {
        return [
            'id' => $notification['id'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'type' => $notification['type'],
            'is_read' => (bool)$notification['is_read'],
            'order_id' => $notification['order_id'],
            'order_number' => $notification['order_number'],
            'action_url' => $notification['action_url'],
            'created_at' => $notification['created_at'],
            'time_ago' => time_ago($notification['created_at'])
        ];
    }, $notifications);
    
    echo json_encode([
        'success' => true,
        'notifications' => $formatted_notifications,
        'unread_count' => $unread_count,
        'total_count' => count($notifications)
    ]);
    
} catch (Exception $e) {
    error_log("Get notifications error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching notifications'
    ]);
}

/**
 * Helper function to display time ago
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hr ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>
