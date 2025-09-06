<?php
/**
 * DesiDukaan - Get Notification Count AJAX Endpoint
 * Returns unread notification count for real-time updates
 */

require_once '../config/config.php';
require_once 'notifications.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $count = get_unread_notification_count($user_id);
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    
} catch (Exception $e) {
    error_log("Get notification count error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching notification count'
    ]);
}
?>
