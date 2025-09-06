<?php
/**
 * DesiDukaan - Order Updates Checker
 * Checks for recent order status updates for the current customer
 */

require_once '../config/config.php';

// Require customer login
require_role('customer');

header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Check for orders updated in the last 5 minutes
    $stmt = $db->prepare("
        SELECT COUNT(*) as update_count
        FROM orders 
        WHERE customer_id = ? 
        AND updated_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        AND updated_at > created_at
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    $has_updates = $result['update_count'] > 0;
    
    // Also check for new notifications
    $stmt = $db->prepare("
        SELECT COUNT(*) as notification_count
        FROM notifications 
        WHERE user_id = ? 
        AND is_read = 0
        AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notification_result = $stmt->fetch();
    
    $has_new_notifications = $notification_result['notification_count'] > 0;
    
    echo json_encode([
        'success' => true,
        'has_updates' => $has_updates || $has_new_notifications,
        'order_updates' => $result['update_count'],
        'new_notifications' => $notification_result['notification_count']
    ]);
    
} catch (Exception $e) {
    error_log("Order updates check error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to check for updates'
    ]);
}
?>
