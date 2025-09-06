<?php
/**
 * DesiDukaan - Mark Notification as Read AJAX Endpoint
 * Marks notifications as read with enhanced security and logging
 */

require_once '../config/config.php';
require_once 'notifications.php';
require_once 'csrf_protection.php';
require_once 'input_validation.php';

// Initialize CSRF protection
initCSRFProtection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate CSRF token
csrfMiddleware();

// Rate limiting
if (!validateRateLimit('mark_notification_read', 30, 60)) {
    echo json_encode([
        'success' => false,
        'message' => 'Rate limit exceeded',
        'error_code' => 'RATE_LIMIT_EXCEEDED'
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$notification_id = intval($_POST['notification_id'] ?? 0);
$mark_all = isset($_POST['mark_all']) && $_POST['mark_all'] === 'true';

try {
    if ($mark_all) {
        // Mark all notifications as read
        $count = mark_all_notifications_read($_SESSION['user_id']);
        echo json_encode([
            'success' => true,
            'message' => "Marked {$count} notifications as read",
            'marked_count' => $count
        ]);
    } else {
        // Mark single notification as read
        if ($notification_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
            exit;
        }

        $success = mark_notification_as_read($notification_id, $_SESSION['user_id']);

        if ($success) {
            $unread_count = get_unread_notification_count($_SESSION['user_id']);
            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read',
                'unread_count' => $unread_count
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Notification not found or already read']);
        }
    }

} catch (Exception $e) {
    error_log("Mark notification read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
