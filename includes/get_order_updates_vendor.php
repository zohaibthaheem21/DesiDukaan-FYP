<?php
/**
 * DesiDukaan - Vendor Order Updates Checker
 * Checks for new orders and updates for the current vendor
 */

require_once '../config/config.php';
require_once 'csrf_protection.php';
require_once 'input_validation.php';

// Initialize CSRF protection
initCSRFProtection();

// Require seller login
require_role('seller');

// Rate limiting
if (!validateRateLimit('vendor_order_updates', 60, 60)) {
    http_response_code(429);
    echo json_encode([
        'success' => false, 
        'message' => 'Rate limit exceeded',
        'error_code' => 'RATE_LIMIT_EXCEEDED'
    ]);
    exit;
}

header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Get vendor's shop ID
    $stmt = $db->prepare("SELECT id FROM shops WHERE seller_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $shop = $stmt->fetch();
    
    if (!$shop) {
        echo json_encode([
            'success' => false,
            'message' => 'Shop not found'
        ]);
        exit;
    }
    
    $shop_id = $shop['id'];
    
    // Check for new orders in the last 5 minutes
    $stmt = $db->prepare("
        SELECT COUNT(*) as new_orders
        FROM orders 
        WHERE shop_id = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        AND status = 'pending'
    ");
    $stmt->execute([$shop_id]);
    $new_orders_result = $stmt->fetch();
    
    // Check for orders updated in the last 5 minutes (status changes, etc.)
    $stmt = $db->prepare("
        SELECT COUNT(*) as updated_orders
        FROM orders 
        WHERE shop_id = ? 
        AND updated_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        AND updated_at > created_at
    ");
    $stmt->execute([$shop_id]);
    $updated_orders_result = $stmt->fetch();
    
    // Check for new notifications
    $stmt = $db->prepare("
        SELECT COUNT(*) as new_notifications
        FROM notifications 
        WHERE user_id = ? 
        AND is_read = 0
        AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications_result = $stmt->fetch();
    
    $has_updates = $new_orders_result['new_orders'] > 0 || 
                   $updated_orders_result['updated_orders'] > 0 || 
                   $notifications_result['new_notifications'] > 0;
    
    echo json_encode([
        'success' => true,
        'has_updates' => $has_updates,
        'new_orders' => $new_orders_result['new_orders'],
        'updated_orders' => $updated_orders_result['updated_orders'],
        'new_notifications' => $notifications_result['new_notifications']
    ]);
    
} catch (Exception $e) {
    error_log("Vendor order updates check error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to check for updates'
    ]);
}
?>
