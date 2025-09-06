<?php
/**
 * DesiDukaan - Customer AJAX Actions Handler
 * Handles customer-specific actions with security and validation
 */

require_once '../config/config.php';
require_once 'notifications.php';
require_once 'csrf_protection.php';
require_once 'input_validation.php';

// Initialize CSRF protection
initCSRFProtection();

// Require customer login
require_role('customer');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate CSRF token
csrfMiddleware();

// Rate limiting
if (!validateRateLimit('customer_action', 20, 60)) {
    echo json_encode([
        'success' => false,
        'message' => 'Rate limit exceeded. Please try again later.',
        'error_code' => 'RATE_LIMIT_EXCEEDED'
    ]);
    exit;
}

$action = sanitizeString($_POST['action'] ?? '');

try {
    $db = getDB();
    
    switch ($action) {
        case 'cancel_order':
            // Validate and sanitize input
            $order_id = validateInteger($_POST['order_id'] ?? null, 1);
            if (!$order_id) {
                throw new Exception('Invalid order ID');
            }
            
            // Verify order belongs to current customer and can be cancelled
            $stmt = $db->prepare("
                SELECT o.*, s.seller_id, s.shop_name
                FROM orders o
                LEFT JOIN shops s ON o.shop_id = s.id
                WHERE o.id = ? AND o.customer_id = ?
            ");
            $stmt->execute([$order_id, $_SESSION['user_id']]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            // Check if order can be cancelled
            if ($order['status'] !== 'pending') {
                throw new Exception('Order cannot be cancelled. Current status: ' . $order['status']);
            }
            
            // Check if customer can still cancel (vendor hasn't accepted yet)
            if (isset($order['customer_can_cancel']) && !$order['customer_can_cancel']) {
                throw new Exception('Order has been confirmed by the vendor and cannot be cancelled');
            }
            
            // Update order status to cancelled
            $stmt = $db->prepare("
                UPDATE orders 
                SET status = 'cancelled', 
                    updated_at = NOW(),
                    customer_can_cancel = FALSE
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([$order_id, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Failed to cancel order');
            }
            
            // Create order status history
            try {
                $stmt = $db->prepare("
                    INSERT INTO order_status_history (order_id, old_status, new_status, notes, changed_by, created_at)
                    VALUES (?, ?, 'cancelled', 'Order cancelled by customer', ?, NOW())
                ");
                $stmt->execute([$order_id, $order['status'], $_SESSION['user_id']]);
            } catch (Exception $e) {
                error_log("Order status history error: " . $e->getMessage());
            }
            
            // Create vendor notification if there's a vendor
            if ($order['seller_id']) {
                try {
                    create_notification(
                        $order['seller_id'],
                        'Order Cancelled',
                        "Order #{$order['order_number']} has been cancelled by the customer.",
                        'info',
                        $order_id,
                        "/seller/orders.php?order_id={$order_id}"
                    );
                } catch (Exception $e) {
                    error_log("Vendor notification error: " . $e->getMessage());
                }
            }
            
            // Send email notification to vendor if available
            try {
                if ($order['seller_id']) {
                    // Get vendor email
                    $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
                    $stmt->execute([$order['seller_id']]);
                    $vendor = $stmt->fetch();
                    
                    if ($vendor && $vendor['email']) {
                        include_once __DIR__ . '/email_notifications.php';
                        
                        // Send simple cancellation notification to vendor
                        $subject = "Order Cancelled - " . $order['order_number'] . " - DesiDukaan";
                        $message = "
                        <html>
                        <body style='font-family: Arial, sans-serif;'>
                            <h2>Order Cancellation Notice</h2>
                            <p>Order #{$order['order_number']} has been cancelled by the customer.</p>
                            <p><strong>Order Details:</strong></p>
                            <ul>
                                <li>Order Number: {$order['order_number']}</li>
                                <li>Amount: Rs. " . number_format($order['total_amount'], 2) . "</li>
                                <li>Cancelled At: " . date('F j, Y g:i A') . "</li>
                            </ul>
                            <p>No further action is required from your side.</p>
                            <p>Thank you,<br>DesiDukaan Team</p>
                        </body>
                        </html>
                        ";
                        
                        sendEnhancedEmail($vendor['email'], $subject, $message);
                    }
                }
            } catch (Exception $e) {
                error_log("Vendor email notification error: " . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'order_id' => $order_id
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Customer AJAX Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
