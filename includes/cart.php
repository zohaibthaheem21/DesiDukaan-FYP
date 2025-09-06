<?php
/**
 * Shopping Cart Management System
 */

// Determine correct path for config files
$config_path = file_exists('../config/config.php') ? '../config/' : 'config/';
require_once $config_path . 'config.php';
require_once $config_path . 'languages.php';


// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Note: Cart is now stored in database, not sessions

/**
 * Check if user is authenticated as customer
 */
function isCustomerAuthenticated() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Check both possible session variables for role
    $user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
    return $user_role === 'customer';
}

/**
 * Add item to cart (Database-based)
 */
function addToCart($product_id, $quantity = 1) {
    if (!isCustomerAuthenticated()) {
        return false;
    }

    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'];

        // Check if item already exists in cart
        $stmt = $db->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update existing quantity
            $new_quantity = $existing['quantity'] + $quantity;
            $stmt = $db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$new_quantity, $user_id, $product_id]);
        } else {
            // Insert new cart item
            $stmt = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }

        return true;
    } catch (Exception $e) {
        error_log("Cart Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove item from cart (Database-based)
 */
function removeFromCart($product_id) {
    if (!isCustomerAuthenticated()) {
        return false;
    }

    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'];

        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);

        return true;
    } catch (Exception $e) {
        error_log("Cart Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update item quantity in cart (Database-based)
 */
function updateCartQuantity($product_id, $quantity) {
    if (!isCustomerAuthenticated()) {
        return false;
    }

    if ($quantity <= 0) {
        return removeFromCart($product_id);
    }

    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'];

        $stmt = $db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $user_id, $product_id]);

        return true;
    } catch (Exception $e) {
        error_log("Cart Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get cart items count (Database-based)
 */
function getCartItemsCount() {
    if (!isCustomerAuthenticated()) {
        return 0;
    }

    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'];

        $stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();

        return intval($result['total'] ?? 0);
    } catch (Exception $e) {
        error_log("Cart Error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get cart items with product details (Database-based)
 */
function getCartItems() {
    if (!isCustomerAuthenticated()) {
        return [];
    }

    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'];

        $stmt = $db->prepare("
            SELECT p.*, s.shop_name, c.quantity as cart_quantity, c.id as cart_id
            FROM cart c
            JOIN products p ON c.product_id = p.id
            JOIN shops s ON p.shop_id = s.id
            WHERE c.user_id = ? AND p.is_active = 1
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll();

        // Calculate subtotals
        foreach ($cart_items as &$item) {
            $item['subtotal'] = $item['price'] * $item['cart_quantity'];
        }

        return $cart_items;
    } catch (Exception $e) {
        error_log("Cart Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if product is in cart (Database-based)
 */
function isInCart($product_id) {
    if (!isCustomerAuthenticated()) {
        return false;
    }

    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'];

        $stmt = $db->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);

        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("Cart Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get cart total
 */
function getCartTotal() {
    $items = getCartItems();
    $total = 0;
    foreach ($items as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}

/**
 * Clear cart (Database-based)
 */
function clearCart() {
    if (!isCustomerAuthenticated()) {
        return false;
    }

    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'];

        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);

        return true;
    } catch (Exception $e) {
        error_log("Cart Error: " . $e->getMessage());
        return false;
    }
}



/**
 * Get product quantity in cart (Database-based)
 */
function getCartQuantity($product_id) {
    if (!isCustomerAuthenticated()) {
        return 0;
    }

    try {
        $db = getDB();
        $user_id = $_SESSION['user_id'];

        $stmt = $db->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $result = $stmt->fetch();

        return intval($result['quantity'] ?? 0);
    } catch (Exception $e) {
        error_log("Cart Error: " . $e->getMessage());
        return 0;
    }
}

// Handle AJAX cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Check if user is authenticated as customer
    if (!isCustomerAuthenticated()) {
        echo json_encode(['success' => false, 'message' => 'Please login as customer first']);
        exit;
    }

    switch ($_POST['action']) {
        case 'add_to_cart':
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);
            
            if ($product_id > 0) {
                addToCart($product_id, $quantity);
                echo json_encode([
                    'success' => true,
                    'message' => t('item_added_to_cart'),
                    'cart_count' => getCartItemsCount()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product']);
            }
            break;
            
        case 'remove_from_cart':
            $product_id = intval($_POST['product_id'] ?? 0);
            
            if ($product_id > 0) {
                removeFromCart($product_id);
                echo json_encode([
                    'success' => true,
                    'message' => t('item_removed_from_cart'),
                    'cart_count' => getCartItemsCount()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product']);
            }
            break;
            
        case 'update_quantity':
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 0);

            if ($product_id > 0 && $quantity > 0) {
                updateCartQuantity($product_id, $quantity);

                // Get product price for subtotal calculation
                $conn = getDBConnection();
                $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();

                $subtotal = $product ? number_format($product['price'] * $quantity, 2) : '0.00';

                echo json_encode([
                    'success' => true,
                    'cart_count' => getCartItemsCount(),
                    'cart_total' => number_format(getCartTotal(), 2),
                    'subtotal' => $subtotal,
                    'quantity' => $quantity
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
            }
            break;
            
        case 'get_cart_count':
            echo json_encode([
                'success' => true,
                'cart_count' => getCartItemsCount()
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

?>
