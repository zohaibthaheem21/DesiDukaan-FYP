<?php
/**
 * Wishlist Management System
 */

// Determine correct path for config files
$config_path = file_exists('../config/config.php') ? '../config/' : 'config/';
require_once $config_path . 'config.php';
require_once $config_path . 'languages.php';

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated as customer
 */
if (!function_exists('isCustomerAuthenticated')) {
    function isCustomerAuthenticated() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Check both possible session variables for role
        $user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
        return $user_role === 'customer';
    }
}

/**
 * Add item to wishlist
 */
function addToWishlist($customer_id, $product_id) {
    try {
        $db = getDB();

        // Validate inputs
        if (!$customer_id || !$product_id) {
            return ['success' => false, 'message' => 'Invalid customer or product ID'];
        }

        // Check if wishlist table exists and create/fix it
        $stmt = $db->query("SHOW TABLES LIKE 'wishlist'");
        if ($stmt->rowCount() == 0) {
            // Create new wishlist table
            $db->exec("CREATE TABLE wishlist (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_wishlist (user_id, product_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )");
        } else {
            // Check if table has correct columns
            $stmt = $db->query("SHOW COLUMNS FROM wishlist LIKE 'customer_id'");
            if ($stmt->rowCount() > 0) {
                // Rename customer_id to user_id for consistency
                $db->exec("ALTER TABLE wishlist CHANGE customer_id user_id INT NOT NULL");
            }

            // Ensure user_id column exists
            $stmt = $db->query("SHOW COLUMNS FROM wishlist LIKE 'user_id'");
            if ($stmt->rowCount() == 0) {
                $db->exec("ALTER TABLE wishlist ADD COLUMN user_id INT NOT NULL AFTER id");
            }
        }

        // Check if product exists and is active
        $stmt = $db->prepare("
            SELECT p.id FROM products p
            JOIN shops s ON p.shop_id = s.id
            WHERE p.id = ? AND p.is_active = 1 AND s.status = 'approved'
        ");
        $stmt->execute([$product_id]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Product not found or not available'];
        }

        // Check if already in wishlist
        $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$customer_id, $product_id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Item already in wishlist'];
        }

        // Add to wishlist
        $stmt = $db->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
        $result = $stmt->execute([$customer_id, $product_id]);

        if ($result) {
            return ['success' => true, 'message' => 'Added to wishlist successfully! ❤️'];
        } else {
            return ['success' => false, 'message' => 'Failed to add to wishlist'];
        }
    } catch (Exception $e) {
        error_log("Wishlist Add Error: " . $e->getMessage() . " - Customer: $customer_id, Product: $product_id");
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Remove item from wishlist
 */
function removeFromWishlist($customer_id, $product_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$customer_id, $product_id]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Removed from wishlist successfully! 💔'];
        } else {
            return ['success' => false, 'message' => 'Item not found in wishlist'];
        }
    } catch (Exception $e) {
        error_log("Wishlist Remove Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Unable to remove from wishlist. Please try again.'];
    }
}

/**
 * Check if product is in wishlist
 */
function isInWishlist($customer_id, $product_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$customer_id, $product_id]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get wishlist items count
 */
function getWishlistItemsCount($customer_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM wishlist w
            JOIN products p ON w.product_id = p.id
            JOIN shops s ON p.shop_id = s.id
            WHERE w.user_id = ? AND p.is_active = 1 AND s.status = 'approved'
        ");
        $stmt->execute([$customer_id]);
        $result = $stmt->fetch();
        return $result['count'];
    } catch (Exception $e) {
        return 0;
    }
}

// Handle AJAX wishlist operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Check if user is authenticated as customer
    if (!isCustomerAuthenticated()) {
        echo json_encode(['success' => false, 'message' => 'Please login as customer first']);
        exit;
    }

    switch ($_POST['action']) {
        case 'add_to_wishlist':
            $product_id = intval($_POST['product_id'] ?? 0);
            
            if ($product_id > 0) {
                $result = addToWishlist($_SESSION['user_id'], $product_id);
                if ($result['success']) {
                    $result['wishlist_count'] = getWishlistItemsCount($_SESSION['user_id']);
                }
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product']);
            }
            break;
            
        case 'remove_from_wishlist':
            $product_id = intval($_POST['product_id'] ?? 0);
            
            if ($product_id > 0) {
                $result = removeFromWishlist($_SESSION['user_id'], $product_id);
                if ($result['success']) {
                    $result['wishlist_count'] = getWishlistItemsCount($_SESSION['user_id']);
                }
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product']);
            }
            break;
            
        case 'toggle_wishlist':
            $product_id = intval($_POST['product_id'] ?? 0);
            
            if ($product_id > 0) {
                if (isInWishlist($_SESSION['user_id'], $product_id)) {
                    $result = removeFromWishlist($_SESSION['user_id'], $product_id);
                    $result['action'] = 'removed';
                } else {
                    $result = addToWishlist($_SESSION['user_id'], $product_id);
                    $result['action'] = 'added';
                }
                
                if ($result['success']) {
                    $result['wishlist_count'] = getWishlistItemsCount($_SESSION['user_id']);
                    $result['in_wishlist'] = isInWishlist($_SESSION['user_id'], $product_id);
                }
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product']);
            }
            break;
            
        case 'get_wishlist_count':
            echo json_encode([
                'success' => true,
                'wishlist_count' => getWishlistItemsCount($_SESSION['user_id'])
            ]);
            break;

        case 'check_wishlist':
            $product_id = intval($_POST['product_id'] ?? 0);

            if ($product_id > 0) {
                $in_wishlist = isInWishlist($_SESSION['user_id'], $product_id);
                echo json_encode([
                    'success' => true,
                    'in_wishlist' => $in_wishlist,
                    'product_id' => $product_id
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}
?>
