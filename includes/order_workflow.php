<?php
/**
 * DesiDukaan - Automated Order Workflow System
 * Handles automated order confirmation, status transitions, and notifications
 */

require_once 'config/config.php';
require_once 'notifications.php';
require_once 'email_notifications.php';

/**
 * Process a new order through the automated workflow
 */
function processNewOrder($order_id) {
    try {
        $db = getDB();
        
        // Get complete order information
        $stmt = $db->prepare("
            SELECT o.*, u.email as customer_email, u.full_name as customer_name,
                   s.shop_name, s.seller_id, s.email as vendor_email,
                   GROUP_CONCAT(oi.product_name SEPARATOR ', ') as products
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            LEFT JOIN shops s ON o.shop_id = s.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.id = ?
            GROUP BY o.id
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            throw new Exception("Order not found: {$order_id}");
        }
        
        // Log order event
        logOrderEvent($order_id, 'order_placed', 'Order placed by customer', $order['customer_id']);
        
        // Send order confirmation email to customer
        sendOrderConfirmationEmail($order);
        
        // Send vendor notification email if vendor exists
        if ($order['seller_id'] && $order['vendor_email']) {
            sendVendorOrderNotification($order);
            
            // Create vendor notification
            create_notification(
                $order['seller_id'],
                'New Order Received',
                "You have received a new order. Please accept or reject.",
                'order',
                $order_id,
                "/seller/orders.php?order_id={$order_id}"
            );
        }
        
        // Log workflow completion
        logOrderEvent($order_id, 'workflow_initiated', 'Automated order workflow completed', null);
        
        return ['success' => true, 'message' => 'Order workflow processed successfully'];
        
    } catch (Exception $e) {
        error_log("Order workflow error: " . $e->getMessage());
        logOrderEvent($order_id, 'workflow_error', 'Error in order workflow: ' . $e->getMessage(), null);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Handle order status transitions with automated actions
 */
function processOrderStatusChange($order_id, $old_status, $new_status, $changed_by, $notes = null) {
    try {
        $db = getDB();
        
        // Get order information
        $stmt = $db->prepare("
            SELECT o.*, u.email as customer_email, u.full_name as customer_name,
                   s.shop_name, s.seller_id, s.email as vendor_email
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            LEFT JOIN shops s ON o.shop_id = s.id
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            throw new Exception("Order not found: {$order_id}");
        }
        
        // Log status change
        logOrderEvent($order_id, 'status_changed', "Status changed from {$old_status} to {$new_status}", $changed_by, $notes);
        
        // Process status-specific actions
        switch ($new_status) {
            case 'confirmed':
                handleOrderConfirmation($order, $changed_by);
                break;
                
            case 'rejected':
                handleOrderRejection($order, $changed_by, $notes);
                break;
                
            case 'processing':
                handleOrderProcessing($order, $changed_by);
                break;
                
            case 'shipped':
                handleOrderShipped($order, $changed_by);
                break;
                
            case 'delivered':
                handleOrderDelivered($order, $changed_by);
                break;
                
            case 'cancelled':
                handleOrderCancellation($order, $changed_by);
                break;
        }
        
        return ['success' => true, 'message' => 'Status change processed successfully'];
        
    } catch (Exception $e) {
        error_log("Order status change error: " . $e->getMessage());
        logOrderEvent($order_id, 'status_change_error', 'Error processing status change: ' . $e->getMessage(), $changed_by);
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Handle order confirmation workflow
 */
function handleOrderConfirmation($order, $changed_by) {
    // Send acceptance email to customer
    sendOrderAcceptanceEmail($order);
    
    // Create customer notification
    create_customer_status_notification(
        $order['customer_id'],
        $order['id'],
        $order['order_number'],
        'confirmed'
    );
    
    // Log confirmation
    logOrderEvent($order['id'], 'order_confirmed', 'Order confirmed by vendor', $changed_by);
}

/**
 * Handle order rejection workflow
 */
function handleOrderRejection($order, $changed_by, $rejection_reason = null) {
    // Send rejection email to customer
    sendOrderRejectionEmail($order, $rejection_reason);
    
    // Create customer notification
    create_customer_status_notification(
        $order['customer_id'],
        $order['id'],
        $order['order_number'],
        'rejected'
    );
    
    // Log rejection
    logOrderEvent($order['id'], 'order_rejected', 'Order rejected by vendor' . ($rejection_reason ? ": {$rejection_reason}" : ''), $changed_by);
}

/**
 * Handle order processing workflow
 */
function handleOrderProcessing($order, $changed_by) {
    // Send processing email to customer
    sendOrderStatusUpdateEmail($order, 'processing');
    
    // Create customer notification
    create_customer_status_notification(
        $order['customer_id'],
        $order['id'],
        $order['order_number'],
        'processing'
    );
    
    // Log processing
    logOrderEvent($order['id'], 'order_processing', 'Order marked as processing', $changed_by);
}

/**
 * Handle order shipped workflow
 */
function handleOrderShipped($order, $changed_by) {
    // Send shipped email to customer
    sendOrderStatusUpdateEmail($order, 'shipped');
    
    // Create customer notification
    create_customer_status_notification(
        $order['customer_id'],
        $order['id'],
        $order['order_number'],
        'shipped'
    );
    
    // Log shipping
    logOrderEvent($order['id'], 'order_shipped', 'Order marked as shipped', $changed_by);
}

/**
 * Handle order delivered workflow
 */
function handleOrderDelivered($order, $changed_by) {
    // Send delivery confirmation email to customer
    sendOrderStatusUpdateEmail($order, 'delivered');
    
    // Create customer notification
    create_customer_status_notification(
        $order['customer_id'],
        $order['id'],
        $order['order_number'],
        'delivered'
    );
    
    // Log delivery
    logOrderEvent($order['id'], 'order_delivered', 'Order marked as delivered', $changed_by);
}

/**
 * Handle order cancellation workflow
 */
function handleOrderCancellation($order, $changed_by) {
    // Send cancellation email to customer
    sendOrderStatusUpdateEmail($order, 'cancelled');
    
    // Create customer notification if cancelled by vendor
    if ($changed_by != $order['customer_id']) {
        create_customer_status_notification(
            $order['customer_id'],
            $order['id'],
            $order['order_number'],
            'cancelled'
        );
    }
    
    // Log cancellation
    logOrderEvent($order['id'], 'order_cancelled', 'Order cancelled', $changed_by);
}

/**
 * Log order events for audit trail
 */
function logOrderEvent($order_id, $event_type, $description, $user_id = null, $additional_data = null) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            INSERT INTO order_events (order_id, event_type, description, user_id, additional_data, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $order_id,
            $event_type,
            $description,
            $user_id,
            $additional_data ? json_encode($additional_data) : null
        ]);
        
    } catch (Exception $e) {
        error_log("Order event logging error: " . $e->getMessage());
    }
}

/**
 * Create customer status notification
 */
function create_customer_status_notification($customer_id, $order_id, $order_number, $status) {
    $status_messages = [
        'confirmed' => 'Your order has been confirmed by the seller',
        'rejected' => 'Your order has been rejected by the seller',
        'processing' => 'Your order is being prepared',
        'shipped' => 'Your order has been shipped',
        'delivered' => 'Your order has been delivered',
        'cancelled' => 'Your order has been cancelled'
    ];
    
    $message = $status_messages[$status] ?? "Your order status has been updated to {$status}";
    
    create_notification(
        $customer_id,
        "Order Update - #{$order_number}",
        $message,
        'order',
        $order_id,
        "/customer/orders.php?view={$order_id}"
    );
}
?>
