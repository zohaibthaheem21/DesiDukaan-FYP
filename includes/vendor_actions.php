<?php
/**
 * DesiDukaan - Vendor AJAX Actions Handler
 * Handles vendor-specific actions with security and validation
 */

require_once '../config/config.php';
require_once 'csrf_protection.php';
require_once 'input_validation.php';

// Initialize CSRF protection
initCSRFProtection();

// Require seller login (vendor is same as seller)
require_role('seller');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate CSRF token
csrfMiddleware();

// Rate limiting
if (!validateRateLimit('vendor_action', 30, 60)) {
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
        case 'toggle_product_status':
            $product_id = intval($_POST['product_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            if ($product_id > 0 && in_array($status, ['active', 'inactive'])) {
                // Verify product belongs to current vendor
                $stmt = $db->prepare("SELECT id FROM products WHERE id = ? AND shop_id = ?");
                $stmt->execute([$product_id, $_SESSION['shop_id']]);
                
                if ($stmt->fetch()) {
                    $is_active = $status === 'active' ? 1 : 0;
                    $updateStmt = $db->prepare("UPDATE products SET is_active = ? WHERE id = ?");
                    $updateStmt->execute([$is_active, $product_id]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => "Product {$status} successfully",
                        'status' => $status
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            break;
            
        case 'update_order_status':
            // Validate and sanitize inputs
            $order_id = validateInteger($_POST['order_id'] ?? null, 1);
            if (!$order_id) {
                throw new Exception('Invalid order ID');
            }

            $new_status = validateOrderStatus($_POST['status'] ?? '');
            if (!$new_status) {
                throw new Exception('Invalid order status');
            }

            $vendor_notes = sanitizeString($_POST['vendor_notes'] ?? '', 1000);
            $rejection_reason = sanitizeString($_POST['rejection_reason'] ?? '', 500);

            // Additional validation for rejection
            if ($new_status === 'rejected' && empty($rejection_reason)) {
                throw new Exception('Rejection reason is required when rejecting an order');
            }

            // Verify order belongs to current seller's shop
            $stmt = $db->prepare("
                SELECT o.*, s.seller_id, u.email as customer_email,
                       COALESCE(u.full_name, u.username) as customer_name
                FROM orders o
                JOIN shops s ON o.shop_id = s.id
                JOIN users u ON o.customer_id = u.id
                WHERE o.id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception('Order not found');
            }

            if ($order['seller_id'] != $_SESSION['user_id']) {
                throw new Exception('Unauthorized access');
            }

            $old_status = $order['status'];

            // Validate status transition
            $valid_transitions = [
                'pending' => ['confirmed', 'rejected', 'cancelled'],
                'confirmed' => ['processing', 'shipped', 'cancelled'],
                'processing' => ['shipped', 'cancelled'],
                'shipped' => ['delivered'],
                'delivered' => [],
                'cancelled' => [],
                'rejected' => []
            ];

            if (!in_array($new_status, $valid_transitions[$old_status])) {
                throw new Exception("Cannot change status from {$old_status} to {$new_status}");
            }

            // Update order status
            $update_fields = ['status = ?', 'updated_at = NOW()'];
            $update_params = [$new_status];

            // Set timestamp fields and additional data based on status
            switch ($new_status) {
                case 'confirmed':
                    $update_fields[] = 'confirmed_at = NOW()';
                    $update_fields[] = 'customer_can_cancel = FALSE';
                    break;
                case 'rejected':
                    $update_fields[] = 'rejected_at = NOW()';
                    $update_fields[] = 'customer_can_cancel = FALSE';
                    break;
                case 'shipped':
                    $update_fields[] = 'shipped_at = NOW()';
                    break;
                case 'delivered':
                    $update_fields[] = 'delivered_at = NOW()';
                    break;
            }

            // Add vendor notes if provided
            if (!empty($vendor_notes)) {
                $update_fields[] = 'vendor_notes = ?';
                $update_params[] = $vendor_notes;
            }

            $update_params[] = $order_id;

            $stmt = $db->prepare("UPDATE orders SET " . implode(', ', $update_fields) . " WHERE id = ?");
            $stmt->execute($update_params);

            // Handle inventory restoration for cancelled orders
            if ($new_status === 'cancelled' && $old_status !== 'cancelled') {
                // Restore inventory for cancelled orders
                $stmt = $db->prepare("
                    SELECT oi.product_id, oi.quantity
                    FROM order_items oi
                    WHERE oi.order_id = ?
                ");
                $stmt->execute([$order_id]);
                $orderItems = $stmt->fetchAll();

                foreach ($orderItems as $item) {
                    // Restore stock quantity
                    $stmt = $db->prepare("
                        UPDATE products
                        SET stock_quantity = stock_quantity + ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$item['quantity'], $item['product_id']]);

                    // Log inventory restoration
                    try {
                        $stmt = $db->prepare("
                            INSERT INTO inventory_logs (product_id, change_type, quantity_change, reason, order_id, changed_by, created_at)
                            VALUES (?, 'increase', ?, 'Order cancelled - inventory restored', ?, ?, NOW())
                        ");
                        $stmt->execute([$item['product_id'], $item['quantity'], $order_id, $_SESSION['user_id']]);
                    } catch (Exception $e) {
                        // Continue if inventory logs table doesn't exist
                        error_log("Inventory log error: " . $e->getMessage());
                    }
                }
            }

            // Create status history record
            try {
                $stmt = $db->prepare("
                    INSERT INTO order_status_history (order_id, old_status, new_status, notes, changed_by, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $notes = "Status updated by vendor from {$old_status} to {$new_status}";
                if (!empty($vendor_notes)) {
                    $notes .= ". Vendor notes: " . $vendor_notes;
                }
                if (!empty($rejection_reason)) {
                    $notes .= ". Rejection reason: " . $rejection_reason;
                }

                $stmt->execute([
                    $order_id,
                    $old_status,
                    $new_status,
                    $notes,
                    $_SESSION['user_id']
                ]);
            } catch (Exception $e) {
                error_log("Order status history error: " . $e->getMessage());
            }

            // Process through automated workflow system
            try {
                include_once __DIR__ . '/order_workflow.php';

                $workflow_notes = $notes;
                if (!empty($rejection_reason)) {
                    $workflow_notes = $rejection_reason;
                }

                $workflow_result = processOrderStatusChange(
                    $order_id,
                    $old_status,
                    $new_status,
                    $_SESSION['user_id'],
                    $workflow_notes
                );

                if (!$workflow_result['success']) {
                    error_log("Workflow processing failed: " . $workflow_result['message']);
                }
            } catch (Exception $e) {
                error_log("Workflow integration error: " . $e->getMessage());
            }

            // Note: Customer notifications and emails are now handled by the workflow system above

            $status_messages = [
                'confirmed' => 'Order accepted successfully! Customer has been notified and can no longer cancel.',
                'rejected' => 'Order rejected. Customer has been notified with the reason.',
                'processing' => 'Order marked as processing. Customer has been notified.',
                'shipped' => 'Order marked as shipped. Customer has been notified.',
                'delivered' => 'Order marked as delivered. Customer has been notified.',
                'cancelled' => 'Order cancelled. Customer has been notified.'
            ];

            echo json_encode([
                'success' => true,
                'message' => $status_messages[$new_status] ?? "Order status updated to {$new_status} successfully.",
                'new_status' => $new_status,
                'old_status' => $old_status,
                'customer_can_cancel' => !in_array($new_status, ['confirmed', 'rejected', 'processing', 'shipped', 'delivered'])
            ]);
            break;

        case 'delete_product':
            $product_id = intval($_POST['product_id'] ?? 0);

            if ($product_id > 0) {
                // Verify product belongs to current vendor
                $stmt = $db->prepare("SELECT id FROM products WHERE id = ? AND shop_id = ?");
                $stmt->execute([$product_id, $_SESSION['shop_id']]);

                if ($stmt->fetch()) {
                    $deleteStmt = $db->prepare("DELETE FROM products WHERE id = ?");
                    $deleteStmt->execute([$product_id]);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Product deleted successfully'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            }
            break;
            
        case 'update_stock':
            $product_id = intval($_POST['product_id'] ?? 0);
            $stock = intval($_POST['stock'] ?? 0);
            
            if ($product_id > 0 && $stock >= 0) {
                // Verify product belongs to current vendor
                $stmt = $db->prepare("SELECT id FROM products WHERE id = ? AND shop_id = ?");
                $stmt->execute([$product_id, $_SESSION['shop_id']]);
                
                if ($stmt->fetch()) {
                    $updateStmt = $db->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                    $updateStmt->execute([$stock, $product_id]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Stock updated successfully',
                        'stock' => $stock
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Product not found or access denied']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            break;
            
        case 'update_order_status':
            $order_id = intval($_POST['order_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            $valid_statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
            
            if ($order_id > 0 && in_array($status, $valid_statuses)) {
                // Verify order belongs to current vendor's shop
                $stmt = $db->prepare("
                    SELECT oi.id 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ? AND p.shop_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$order_id, $_SESSION['shop_id']]);
                
                if ($stmt->fetch()) {
                    $updateStmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $updateStmt->execute([$status, $order_id]);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => "Order status updated to {$status}",
                        'status' => $status
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            break;
            
        case 'bulk_update':
            $product_ids = explode(',', $_POST['product_ids'] ?? '');
            $bulk_action = $_POST['bulk_action'] ?? '';
            $value = $_POST['value'] ?? '';
            
            $product_ids = array_filter(array_map('intval', $product_ids));
            
            if (empty($product_ids)) {
                echo json_encode(['success' => false, 'message' => 'No products selected']);
                break;
            }
            
            // Verify all products belong to current vendor
            $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
            $stmt = $db->prepare("SELECT id FROM products WHERE id IN ($placeholders) AND shop_id = ?");
            $stmt->execute([...$product_ids, $_SESSION['shop_id']]);
            $verified_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($verified_ids) !== count($product_ids)) {
                echo json_encode(['success' => false, 'message' => 'Some products not found or access denied']);
                break;
            }
            
            $success = false;
            $message = '';
            
            switch ($bulk_action) {
                case 'activate':
                    $stmt = $db->prepare("UPDATE products SET is_active = 1 WHERE id IN ($placeholders)");
                    $success = $stmt->execute($verified_ids);
                    $message = 'Products activated successfully';
                    break;
                    
                case 'deactivate':
                    $stmt = $db->prepare("UPDATE products SET is_active = 0 WHERE id IN ($placeholders)");
                    $success = $stmt->execute($verified_ids);
                    $message = 'Products deactivated successfully';
                    break;
                    
                case 'delete':
                    $stmt = $db->prepare("DELETE FROM products WHERE id IN ($placeholders)");
                    $success = $stmt->execute($verified_ids);
                    $message = 'Products deleted successfully';
                    break;
                    
                case 'update_stock':
                    $stock = intval($value);
                    if ($stock >= 0) {
                        $stmt = $db->prepare("UPDATE products SET quantity = ? WHERE id IN ($placeholders)");
                        $success = $stmt->execute([$stock, ...$verified_ids]);
                        $message = 'Stock updated successfully';
                    }
                    break;
            }
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'affected_count' => count($verified_ids)
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to perform bulk update']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Vendor AJAX Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
