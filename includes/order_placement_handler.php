<?php
/**
 * DesiDukaan - Order Placement Handler
 * Handles new order placement and triggers automated workflow
 */

require_once 'config/config.php';
require_once 'order_workflow.php';

/**
 * Handle new order placement with automated workflow
 */
function handleNewOrderPlacement($order_data) {
    try {
        $db = getDB();
        
        // Start transaction
        $db->beginTransaction();
        
        // Generate unique order number
        $order_number = generateOrderNumber();
        
        // Insert main order record
        $stmt = $db->prepare("
            INSERT INTO orders (
                order_number, customer_id, shop_id, total_amount, 
                payment_method, status, created_at, updated_at,
                customer_can_cancel
            ) VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW(), TRUE)
        ");
        
        $stmt->execute([
            $order_number,
            $order_data['customer_id'],
            $order_data['shop_id'],
            $order_data['total_amount'],
            $order_data['payment_method'] ?? 'cod'
        ]);
        
        $order_id = $db->lastInsertId();
        
        // Insert order items
        if (isset($order_data['items']) && is_array($order_data['items'])) {
            $stmt = $db->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, quantity,
                    unit_price, total_price, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");

            foreach ($order_data['items'] as $item) {
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['product_name'],
                    $item['quantity'],
                    $item['unit_price'] ?? $item['price'], // Support both old and new format
                    $item['total_price']
                ]);
            }
        }
        
        // Insert shipping address if provided
        if (isset($order_data['shipping_address'])) {
            $addr = $order_data['shipping_address'];
            $stmt = $db->prepare("
                INSERT INTO shipping_addresses (
                    order_id, customer_name, phone_number, 
                    address_line_1, address_line_2, city, 
                    state, postal_code, country, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $order_id,
                $addr['customer_name'] ?? '',
                $addr['phone_number'] ?? '',
                $addr['address_line_1'] ?? '',
                $addr['address_line_2'] ?? '',
                $addr['city'] ?? '',
                $addr['state'] ?? '',
                $addr['postal_code'] ?? '',
                $addr['country'] ?? 'India'
            ]);
        }

        // Create shipping address record if provided
        if (isset($order_data['shipping_address']) && is_array($order_data['shipping_address'])) {
            $shipping = $order_data['shipping_address'];
            $stmt = $db->prepare("
                INSERT INTO shipping_addresses (
                    order_id, customer_name, phone_number, address_line_1,
                    address_line_2, city, state, postal_code, country, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $order_id,
                $shipping['customer_name'] ?? $order_data['customer_name'] ?? '',
                $shipping['phone_number'] ?? '',
                $shipping['address_line_1'] ?? '',
                $shipping['address_line_2'] ?? '',
                $shipping['city'] ?? '',
                $shipping['state'] ?? '',
                $shipping['postal_code'] ?? '',
                $shipping['country'] ?? 'India'
            ]);
        }

        // Commit transaction
        $db->commit();
        
        // Trigger automated workflow
        $workflow_result = processNewOrder($order_id);
        
        if (!$workflow_result['success']) {
            error_log("Workflow failed for order {$order_id}: " . $workflow_result['message']);
        }
        
        return [
            'success' => true,
            'order_id' => $order_id,
            'order_number' => $order_number,
            'message' => 'Order placed successfully'
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollback();
        }
        
        error_log("Order placement error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to place order: ' . $e->getMessage()
        ];
    }
}

/**
 * Generate unique order number
 */
function generateOrderNumber() {
    $prefix = 'DD';
    $timestamp = date('ymd');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . $timestamp . $random;
}

/**
 * Validate order data before placement
 */
function validateOrderData($order_data) {
    $errors = [];
    
    // Required fields
    if (empty($order_data['customer_id'])) {
        $errors[] = 'Customer ID is required';
    }
    
    if (empty($order_data['shop_id'])) {
        $errors[] = 'Shop ID is required';
    }
    
    if (empty($order_data['total_amount']) || $order_data['total_amount'] <= 0) {
        $errors[] = 'Valid total amount is required';
    }
    
    if (empty($order_data['items']) || !is_array($order_data['items'])) {
        $errors[] = 'Order items are required';
    }
    
    // Validate items
    if (isset($order_data['items']) && is_array($order_data['items'])) {
        foreach ($order_data['items'] as $index => $item) {
            if (empty($item['product_id'])) {
                $errors[] = "Product ID is required for item " . ($index + 1);
            }
            
            if (empty($item['quantity']) || $item['quantity'] <= 0) {
                $errors[] = "Valid quantity is required for item " . ($index + 1);
            }
            
            if (empty($item['price']) || $item['price'] <= 0) {
                $errors[] = "Valid price is required for item " . ($index + 1);
            }
        }
    }
    
    return $errors;
}

/**
 * Process order placement with validation
 */
function processOrderPlacement($order_data) {
    // Validate order data
    $validation_errors = validateOrderData($order_data);
    
    if (!empty($validation_errors)) {
        return [
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validation_errors
        ];
    }
    
    // Handle order placement
    return handleNewOrderPlacement($order_data);
}

/**
 * Get order placement statistics for monitoring
 */
function getOrderPlacementStats($date_from = null, $date_to = null) {
    try {
        $db = getDB();
        
        $date_condition = '';
        $params = [];
        
        if ($date_from && $date_to) {
            $date_condition = 'WHERE created_at BETWEEN ? AND ?';
            $params = [$date_from, $date_to];
        } elseif ($date_from) {
            $date_condition = 'WHERE created_at >= ?';
            $params = [$date_from];
        }
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_order_value,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_orders,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_orders,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders
            FROM orders 
            {$date_condition}
        ");
        
        $stmt->execute($params);
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("Order stats error: " . $e->getMessage());
        return null;
    }
}

/**
 * Clean up old order events (for maintenance)
 */
function cleanupOldOrderEvents($days_to_keep = 90) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            DELETE FROM order_events 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            AND event_type NOT IN ('order_placed', 'order_confirmed', 'order_delivered')
        ");
        
        $stmt->execute([$days_to_keep]);
        
        return [
            'success' => true,
            'deleted_records' => $stmt->rowCount()
        ];
        
    } catch (Exception $e) {
        error_log("Order events cleanup error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
?>
