<?php
/**
 * DesiDukaan - Buy Now Order Processing
 * Handles the creation of buy now orders
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Disable error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/config.php';

// Clean any output that might have been generated
ob_clean();

// Require customer login
require_role('customer');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Get and validate input data
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $customer_name = trim($_POST['customer_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address_line_1 = trim($_POST['address_line_1'] ?? '');
    $address_line_2 = trim($_POST['address_line_2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'India');
    $landmark = trim($_POST['landmark'] ?? '');
    
    // Validate required fields
    if (empty($product_id) || empty($quantity) || empty($customer_name) || 
        empty($phone_number) || empty($address_line_1) || empty($city) || 
        empty($state) || empty($postal_code)) {
        throw new Exception('All required fields must be filled');
    }
    
    if ($quantity <= 0) {
        throw new Exception('Quantity must be greater than 0');
    }
    
    // Get product details and verify availability
    $stmt = $db->prepare("
        SELECT p.*, s.id as shop_id, s.seller_id, s.shop_name, s.email as shop_email,
               u.email as seller_email, u.username as seller_name
        FROM products p
        JOIN shops s ON p.shop_id = s.id
        JOIN users u ON s.seller_id = u.id
        WHERE p.id = ? AND p.is_active = 1 AND s.status = 'approved'
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Product not found or not available');
    }
    
    // Check stock using both possible column names
    $available_stock = $product['stock_quantity'] ?? $product['quantity'] ?? 0;
    if ($available_stock < $quantity) {
        throw new Exception('Insufficient stock. Available: ' . $available_stock);
    }
    
    // Calculate order totals
    $unit_price = $product['price'];
    $total_amount = $unit_price * $quantity;
    
    // Generate unique order number
    $order_number = 'DN' . date('Ymd') . sprintf('%06d', rand(1, 999999));
    
    // Check if order number already exists
    $stmt = $db->prepare("SELECT id FROM orders WHERE order_number = ?");
    $stmt->execute([$order_number]);
    if ($stmt->fetch()) {
        // Generate new order number if exists
        $order_number = 'DN' . date('YmdHis') . sprintf('%04d', rand(1, 9999));
    }
    
    // Create order record with all required columns
    $shipping_address_text = $address_line_1 . ', ' . $address_line_2 . ', ' . $city . ', ' . $state . ' - ' . $postal_code . ', ' . $country;

    $stmt = $db->prepare("
        INSERT INTO orders (
            order_number, customer_id, shop_id, total_amount, status,
            payment_status, payment_method, order_type, customer_name,
            shipping_address, phone_number, customer_can_cancel, created_at, updated_at
        ) VALUES (?, ?, ?, ?, 'pending', 'pending', 'cod', 'buy_now', ?, ?, ?, 1, NOW(), NOW())
    ");
    $stmt->execute([
        $order_number,
        $_SESSION['user_id'],
        $product['shop_id'],
        $total_amount,
        $customer_name,
        $shipping_address_text,
        $phone_number
    ]);
    
    $order_id = $db->lastInsertId();
    
    // Create order item record
    $stmt = $db->prepare("
        INSERT INTO order_items (
            order_id, product_id, quantity, unit_price, total_price, 
            product_name, product_image
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    // Get first image from images JSON
    $images = $product['images'] ? json_decode($product['images'], true) : [];
    $first_image = !empty($images) ? $images[0] : null;

    $stmt->execute([
        $order_id,
        $product_id,
        $quantity,
        $unit_price,
        $total_amount,
        $product['title'],
        $first_image
    ]);
    
    // Create shipping address record
    $stmt = $db->prepare("
        INSERT INTO shipping_addresses (
            order_id, customer_name, phone_number, address_line_1, address_line_2,
            city, state, postal_code, country, landmark, address_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'home')
    ");
    $stmt->execute([
        $order_id,
        $customer_name,
        $phone_number,
        $address_line_1,
        $address_line_2,
        $city,
        $state,
        $postal_code,
        $country,
        $landmark
    ]);
    
    // Update product stock - determine which column to use
    $stock_updated = false;

    // First, check which stock column exists
    $stmt = $db->query("DESCRIBE products");
    $product_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (in_array('stock_quantity', $product_columns)) {
        // Use stock_quantity column
        try {
            $stmt = $db->prepare("
                UPDATE products
                SET stock_quantity = GREATEST(0, stock_quantity - ?)
                WHERE id = ? AND stock_quantity >= ?
            ");
            $result = $stmt->execute([$quantity, $product_id, $quantity]);

            if ($stmt->rowCount() > 0) {
                $stock_updated = true;
                error_log("Stock updated using stock_quantity column for product $product_id");
            } else {
                // Check current stock
                $stmt = $db->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $currentStock = $stmt->fetchColumn();
                error_log("Stock update failed - current stock: $currentStock, requested: $quantity");
            }
        } catch (Exception $e) {
            error_log("Stock update error with stock_quantity: " . $e->getMessage());
        }
    } elseif (in_array('quantity', $product_columns)) {
        // Use quantity column
        try {
            $stmt = $db->prepare("
                UPDATE products
                SET quantity = GREATEST(0, quantity - ?)
                WHERE id = ? AND quantity >= ?
            ");
            $result = $stmt->execute([$quantity, $product_id, $quantity]);

            if ($stmt->rowCount() > 0) {
                $stock_updated = true;
                error_log("Stock updated using quantity column for product $product_id");
            } else {
                // Check current stock
                $stmt = $db->prepare("SELECT quantity FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $currentStock = $stmt->fetchColumn();
                error_log("Stock update failed - current stock: $currentStock, requested: $quantity");
            }
        } catch (Exception $e) {
            error_log("Stock update error with quantity: " . $e->getMessage());
        }
    } else {
        error_log("No stock column found in products table");
    }

    if (!$stock_updated) {
        error_log("Warning: Stock was not updated for product $product_id");
        // Don't fail the order, just log the issue
    }

    // Log inventory change
    try {
        $stmt = $db->prepare("
            INSERT INTO inventory_logs (product_id, change_type, quantity_change, reason, order_id, created_at)
            VALUES (?, 'decrease', ?, 'Order placed via Buy Now', ?, NOW())
        ");
        $stmt->execute([$product_id, $quantity, $order_id]);
    } catch (Exception $e) {
        // Inventory logs table might not exist, continue without error
        error_log("Inventory log error: " . $e->getMessage());
    }
    
    // Create order status history (check if table exists and has required columns)
    try {
        $stmt = $db->prepare("
            INSERT INTO order_status_history (order_id, new_status, changed_by, notes)
            VALUES (?, 'pending', ?, 'Order placed via Buy Now')
        ");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
    } catch (Exception $e) {
        // If change_reason column doesn't exist, try with notes column
        try {
            $stmt = $db->prepare("
                INSERT INTO order_status_history (order_id, new_status, changed_by)
                VALUES (?, 'pending', ?)
            ");
            $stmt->execute([$order_id, $_SESSION['user_id']]);
        } catch (Exception $e2) {
            // Log error but don't fail the order
            error_log("Order status history error: " . $e2->getMessage());
        }
    }
    
    // Get customer email for notifications
    $stmt = $db->prepare("SELECT email, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $customer = $stmt->fetch();
    
    $db->commit();
    
    // Send email notifications (async)
    try {
        // Capture any output from email functions
        ob_start();
        include_once __DIR__ . '/email_notifications.php';
        
        // Send customer confirmation email
        sendOrderConfirmationEmail([
            'order_id' => $order_id,
            'order_number' => $order_number,
            'customer_email' => $customer['email'],
            'customer_name' => $customer['username'],
            'product_name' => $product['title'],
            'quantity' => $quantity,
            'total_amount' => $total_amount,
            'shipping_address' => [
                'name' => $customer_name,
                'phone' => $phone_number,
                'address' => $address_line_1 . ', ' . $city . ', ' . $state . ' - ' . $postal_code
            ]
        ]);
        
        // Send vendor notification email
        $vendor_email_data = [
            'order_id' => $order_id,
            'order_number' => $order_number,
            'vendor_email' => $product['seller_email'],
            'vendor_name' => $product['seller_name'],
            'shop_name' => $product['shop_name'],
            'customer_name' => $customer['username'],
            'product_name' => $product['title'],
            'quantity' => $quantity,
            'total_amount' => $total_amount,
            'shipping_address' => [
                'name' => $customer_name,
                'phone' => $phone_number,
                'address' => $address_line_1 . ', ' . $city . ', ' . $state . ' - ' . $postal_code
            ]
        ];

        // Debug log for email data
        error_log("Sending vendor email to: " . $product['seller_email'] . " for order: " . $order_number);

        $email_result = sendVendorOrderNotification($vendor_email_data);
        if (!$email_result) {
            error_log("Failed to send vendor notification email for order: " . $order_number);
        } else {
            error_log("Vendor notification email sent successfully for order: " . $order_number);
        }

        // Clean any output from email functions
        ob_end_clean();

    } catch (Exception $e) {
        // Clean any output from email functions
        ob_end_clean();
        // Log email error but don't fail the order
        error_log("Email notification error for order {$order_id}: " . $e->getMessage());
    }
    
    // Clean output buffer and send JSON response
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_id' => $order_id,
        'order_number' => $order_number
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    error_log("Buy Now Error: " . $e->getMessage());

    // Clean output buffer and send error response
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
?>
