<?php
/**
 * Process Cart Checkout - Complete Order Placement from Cart
 */

require_once '../config/config.php';
require_once 'cart.php';
require_once 'order_placement_handler.php';
require_once 'email_notifications.php';
require_once 'order_workflow.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

// Check if user is authenticated as customer
if (!isCustomerAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Please login as customer first']);
    exit;
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $db = getDB();
    
    // Get cart items
    $cart_items = getCartItems();
    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
        exit;
    }
    
    // Get form data
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address_line_1 = trim($_POST['address_line_1'] ?? '');
    $address_line_2 = trim($_POST['address_line_2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cod';
    
    // Validate required fields
    if (empty($customer_name) || empty($phone_number) || empty($address_line_1) || 
        empty($city) || empty($state) || empty($postal_code)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    // Group cart items by shop (since each shop needs a separate order)
    $orders_by_shop = [];
    foreach ($cart_items as $item) {
        $shop_id = $item['shop_id'];
        if (!isset($orders_by_shop[$shop_id])) {
            $orders_by_shop[$shop_id] = [
                'shop_id' => $shop_id,
                'shop_name' => $item['shop_name'],
                'items' => [],
                'total_amount' => 0
            ];
        }
        
        $orders_by_shop[$shop_id]['items'][] = [
            'product_id' => $item['id'],
            'product_name' => $item['title'],
            'quantity' => $item['cart_quantity'],
            'unit_price' => $item['price'],
            'total_price' => $item['subtotal'],
            'product_image' => !empty($item['images']) ? json_decode($item['images'], true)[0] ?? null : null
        ];
        
        $orders_by_shop[$shop_id]['total_amount'] += $item['subtotal'];
    }
    
    $db->beginTransaction();
    $created_orders = [];
    
    try {
        // Create separate orders for each shop
        foreach ($orders_by_shop as $shop_order) {
            // Prepare order data
            $order_data = [
                'customer_id' => $_SESSION['user_id'],
                'shop_id' => $shop_order['shop_id'],
                'total_amount' => $shop_order['total_amount'],
                'payment_method' => $payment_method,
                'order_type' => 'cart',
                'customer_name' => $customer_name,
                'items' => $shop_order['items'],
                'shipping_address' => [
                    'customer_name' => $customer_name,
                    'phone_number' => $phone_number,
                    'address_line_1' => $address_line_1,
                    'address_line_2' => $address_line_2,
                    'city' => $city,
                    'state' => $state,
                    'postal_code' => $postal_code,
                    'country' => 'India'
                ]
            ];
            
            // Create order using the order placement handler
            $result = handleNewOrderPlacement($order_data);
            
            if (!$result['success']) {
                throw new Exception('Failed to create order for shop: ' . $shop_order['shop_name'] . '. ' . $result['message']);
            }
            
            $created_orders[] = [
                'order_id' => $result['order_id'],
                'order_number' => $result['order_number'],
                'shop_name' => $shop_order['shop_name'],
                'total_amount' => $shop_order['total_amount']
            ];
        }
        
        // Clear cart after successful order creation
        clearCart();
        
        // Commit transaction
        $db->commit();
        
        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Orders placed successfully!',
            'orders' => $created_orders,
            'total_orders' => count($created_orders)
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Cart checkout error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process checkout: ' . $e->getMessage()
    ]);
}
?>
