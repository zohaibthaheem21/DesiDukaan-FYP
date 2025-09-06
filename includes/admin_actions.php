<?php
/**
 * Admin AJAX Actions Handler
 */

require_once '../config/config.php';

// Require admin login
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    $db = getDB();
    
    switch ($action) {
        case 'approve_shop':
            $shop_id = intval($_POST['shop_id'] ?? 0);
            
            if ($shop_id > 0) {
                $stmt = $db->prepare("UPDATE shops SET status = 'approved' WHERE id = ?");
                $success = $stmt->execute([$shop_id]);
                
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Shop approved successfully'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to approve shop']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid shop ID']);
            }
            break;
            
        case 'reject_shop':
            $shop_id = intval($_POST['shop_id'] ?? 0);
            
            if ($shop_id > 0) {
                $stmt = $db->prepare("UPDATE shops SET status = 'rejected' WHERE id = ?");
                $success = $stmt->execute([$shop_id]);
                
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Shop rejected successfully'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to reject shop']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid shop ID']);
            }
            break;
            
        case 'suspend_shop':
            $shop_id = intval($_POST['shop_id'] ?? 0);
            
            if ($shop_id > 0) {
                $stmt = $db->prepare("UPDATE shops SET status = 'suspended' WHERE id = ?");
                $success = $stmt->execute([$shop_id]);
                
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Shop suspended successfully'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to suspend shop']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid shop ID']);
            }
            break;
            
        case 'toggle_user_status':
            $user_id = intval($_POST['user_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            if ($user_id > 0 && in_array($status, ['active', 'inactive'])) {
                $is_active = $status === 'active' ? 1 : 0;
                $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
                $success = $stmt->execute([$is_active, $user_id]);
                
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => "User {$status} successfully",
                        'status' => $status
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update user status']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            break;
            
        case 'delete_user':
            $user_id = intval($_POST['user_id'] ?? 0);
            
            if ($user_id > 0) {
                // Don't allow deleting admin users
                $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if ($user && $user['role'] !== 'admin') {
                    $deleteStmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $success = $deleteStmt->execute([$user_id]);
                    
                    if ($success) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'User deleted successfully'
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete admin users']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            }
            break;
            
        case 'toggle_product_status':
            $product_id = intval($_POST['product_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            if ($product_id > 0 && in_array($status, ['active', 'inactive'])) {
                $is_active = $status === 'active' ? 1 : 0;
                $stmt = $db->prepare("UPDATE products SET is_active = ? WHERE id = ?");
                $success = $stmt->execute([$is_active, $product_id]);
                
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => "Product {$status} successfully",
                        'status' => $status
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update product status']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            }
            break;
            
        case 'delete_product':
            $product_id = intval($_POST['product_id'] ?? 0);
            
            if ($product_id > 0) {
                $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
                $success = $stmt->execute([$product_id]);
                
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Product deleted successfully'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            }
            break;
            
        case 'update_setting':
            $setting = $_POST['setting'] ?? '';
            $value = $_POST['value'] ?? '';
            
            if (!empty($setting)) {
                // Check if setting exists
                $stmt = $db->prepare("SELECT id FROM settings WHERE setting_key = ?");
                $stmt->execute([$setting]);
                
                if ($stmt->fetch()) {
                    // Update existing setting
                    $updateStmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                    $success = $updateStmt->execute([$value, $setting]);
                } else {
                    // Insert new setting
                    $insertStmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                    $success = $insertStmt->execute([$setting, $value]);
                }
                
                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Setting updated successfully'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update setting']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid setting name']);
            }
            break;
            
        case 'bulk_update_shops':
            $shop_ids = explode(',', $_POST['shop_ids'] ?? '');
            $bulk_action = $_POST['bulk_action'] ?? '';
            
            $shop_ids = array_filter(array_map('intval', $shop_ids));
            
            if (empty($shop_ids)) {
                echo json_encode(['success' => false, 'message' => 'No shops selected']);
                break;
            }
            
            $success = false;
            $message = '';
            $placeholders = str_repeat('?,', count($shop_ids) - 1) . '?';
            
            switch ($bulk_action) {
                case 'approve':
                    $stmt = $db->prepare("UPDATE shops SET status = 'approved' WHERE id IN ($placeholders)");
                    $success = $stmt->execute($shop_ids);
                    $message = 'Shops approved successfully';
                    break;
                    
                case 'suspend':
                    $stmt = $db->prepare("UPDATE shops SET status = 'suspended' WHERE id IN ($placeholders)");
                    $success = $stmt->execute($shop_ids);
                    $message = 'Shops suspended successfully';
                    break;
                    
                case 'reject':
                    $stmt = $db->prepare("UPDATE shops SET status = 'rejected' WHERE id IN ($placeholders)");
                    $success = $stmt->execute($shop_ids);
                    $message = 'Shops rejected successfully';
                    break;
            }
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'affected_count' => count($shop_ids)
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
    error_log("Admin AJAX Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
