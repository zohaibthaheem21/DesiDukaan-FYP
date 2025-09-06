<?php
/**
 * DesiDukaan - Enhanced Email Notification System
 * Handles sending email notifications for orders with improved reliability
 */

// Config is already included by the calling script

/**
 * Enhanced email sending function with better error handling
 */
function sendEnhancedEmail($to, $subject, $message, $headers = null) {
    // Default headers if none provided
    if (!$headers) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: DesiDukaan <noreply@desidukaan.com>" . "\r\n";
        $headers .= "Reply-To: support@desidukaan.com" . "\r\n";
        $headers .= "X-Mailer: DesiDukaan Email System" . "\r\n";
    }

    // Validate email address
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: $to");
        return false;
    }

    // Try to send email
    $sent = false;
    try {
        $sent = mail($to, $subject, $message, $headers);

        if (!$sent) {
            error_log("Failed to send email to: $to, Subject: $subject");
        }
    } catch (Exception $e) {
        error_log("Email sending exception: " . $e->getMessage());
    }

    return $sent;
}

/**
 * Send order confirmation email to customer
 */
function sendOrderConfirmationEmail($orderData) {
    $to = $orderData['customer_email'];
    $subject = "Order Confirmation - " . $orderData['order_number'] . " - DesiDukaan";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #e74c3c; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .order-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .footer { background: #34495e; color: white; padding: 15px; text-align: center; }
            .btn { background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🛒 Order Confirmation</h1>
                <h2>DesiDukaan</h2>
            </div>
            
            <div class='content'>
                <h3>Dear " . htmlspecialchars($orderData['customer_name']) . ",</h3>
                
                <p>Thank you for your order! We're excited to confirm that your order has been successfully placed.</p>
                
                <div class='order-details'>
                    <h4>📦 Order Details</h4>
                    <p><strong>Order Number:</strong> " . htmlspecialchars($orderData['order_number']) . "</p>
                    <p><strong>Product:</strong> " . htmlspecialchars($orderData['product_name']) . "</p>
                    <p><strong>Quantity:</strong> " . $orderData['quantity'] . "</p>
                    <p><strong>Total Amount:</strong> Rs. " . number_format($orderData['total_amount'], 2) . "</p>
                    <p><strong>Payment Method:</strong> Cash on Delivery (COD)</p>
                </div>
                
                <div class='order-details'>
                    <h4>🚚 Shipping Address</h4>
                    <p><strong>Name:</strong> " . htmlspecialchars($orderData['shipping_address']['name']) . "</p>
                    <p><strong>Phone:</strong> " . htmlspecialchars($orderData['shipping_address']['phone']) . "</p>
                    <p><strong>Address:</strong> " . htmlspecialchars($orderData['shipping_address']['address']) . "</p>
                </div>
                
                <div class='order-details'>
                    <h4>📋 What's Next?</h4>
                    <ul>
                        <li>Your order is being processed by the seller</li>
                        <li>You'll receive updates via email and SMS</li>
                        <li>Expected delivery: 3-7 business days</li>
                        <li>Payment will be collected upon delivery</li>
                    </ul>
                </div>
                
                <p style='text-align: center; margin: 20px 0;'>
                    <a href='" . getBaseUrl() . "customer/orders.php' class='btn'>Track Your Order</a>
                </p>
                
                <p>If you have any questions, please contact our customer support.</p>
                
                <p>Thank you for shopping with DesiDukaan!</p>
            </div>
            
            <div class='footer'>
                <p>&copy; 2024 DesiDukaan. All rights reserved.</p>
                <p>This is an automated email. Please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: DesiDukaan <noreply@desidukaan.com>" . "\r\n";
    $headers .= "Reply-To: support@desidukaan.com" . "\r\n";

    $sent = sendEnhancedEmail($to, $subject, $message, $headers);
    
    // Log email notification
    logEmailNotification([
        'order_id' => $orderData['order_id'],
        'recipient_email' => $to,
        'recipient_type' => 'customer',
        'email_type' => 'order_confirmation',
        'subject' => $subject,
        'status' => $sent ? 'sent' : 'failed'
    ]);
    
    return $sent;
}

/**
 * Send new order notification to vendor
 */
function sendVendorOrderNotification($orderData) {
    $to = $orderData['vendor_email'];
    $subject = "🔔 NEW ORDER ALERT - " . $orderData['order_number'] . " - DesiDukaan";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #27ae60; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .order-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .footer { background: #34495e; color: white; padding: 15px; text-align: center; }
            .btn { background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
            .urgent { background: #e74c3c; color: white; padding: 10px; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 New Order Received!</h1>
                <h2>DesiDukaan Vendor Portal</h2>
            </div>
            
            <div class='content'>
                <h3>Dear " . htmlspecialchars($orderData['vendor_name']) . ",</h3>
                
                <div class='urgent'>
                    <strong>⚡ Action Required:</strong> You have received a new order that needs your attention!
                </div>
                
                <p>Congratulations! A new order has been placed for your shop <strong>" . htmlspecialchars($orderData['shop_name']) . "</strong>.</p>
                
                <div class='order-details'>
                    <h4>📦 Order Information</h4>
                    <p><strong>Order Number:</strong> " . htmlspecialchars($orderData['order_number']) . "</p>
                    <p><strong>Customer:</strong> " . htmlspecialchars($orderData['customer_name']) . "</p>
                    <p><strong>Product:</strong> " . htmlspecialchars($orderData['product_name']) . "</p>
                    <p><strong>Quantity:</strong> " . $orderData['quantity'] . "</p>
                    <p><strong>Order Value:</strong> Rs. " . number_format($orderData['total_amount'], 2) . "</p>
                    <p><strong>Payment Method:</strong> Cash on Delivery (COD)</p>
                </div>
                
                <div class='order-details'>
                    <h4>🚚 Delivery Address</h4>
                    <p><strong>Name:</strong> " . htmlspecialchars($orderData['shipping_address']['name']) . "</p>
                    <p><strong>Phone:</strong> " . htmlspecialchars($orderData['shipping_address']['phone']) . "</p>
                    <p><strong>Address:</strong> " . htmlspecialchars($orderData['shipping_address']['address']) . "</p>
                </div>
                
                <div class='order-details'>
                    <h4>📋 Next Steps</h4>
                    <ol>
                        <li><strong>Confirm the order</strong> in your vendor dashboard</li>
                        <li><strong>Prepare the product</strong> for shipping</li>
                        <li><strong>Update order status</strong> as you process it</li>
                        <li><strong>Arrange pickup/delivery</strong> within 24-48 hours</li>
                    </ol>
                </div>
                
                <p style='text-align: center; margin: 20px 0;'>
                    <a href='" . getBaseUrl() . "vendor/orders.php' class='btn'>Manage Order</a>
                </p>
                
                <p><strong>Important:</strong> Please confirm this order within 24 hours to maintain good customer experience.</p>
                
                <p>Thank you for being a valued partner with DesiDukaan!</p>
            </div>
            
            <div class='footer'>
                <p>&copy; 2024 DesiDukaan. All rights reserved.</p>
                <p>This is an automated email. Please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: DesiDukaan Orders <orders@desidukaan.com>" . "\r\n";
    $headers .= "Reply-To: support@desidukaan.com" . "\r\n";
    $headers .= "X-Priority: 1" . "\r\n"; // High priority for vendor notifications

    $sent = sendEnhancedEmail($to, $subject, $message, $headers);
    
    // Log email notification
    logEmailNotification([
        'order_id' => $orderData['order_id'],
        'recipient_email' => $to,
        'recipient_type' => 'vendor',
        'email_type' => 'order_confirmation',
        'subject' => $subject,
        'status' => $sent ? 'sent' : 'failed'
    ]);
    
    return $sent;
}

/**
 * Send order acceptance email to customer
 */
function sendOrderAcceptanceEmail($orderData) {
    $to = $orderData['customer_email'];
    $subject = "✅ Order Accepted - " . $orderData['order_number'] . " - DesiDukaan";

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 30px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; font-weight: 700; }
            .header h2 { margin: 10px 0 0 0; font-size: 18px; opacity: 0.9; font-weight: 400; }
            .content { padding: 30px 20px; background: #f8f9fa; }
            .success-banner { background: white; padding: 25px; border-radius: 12px; margin: 20px 0; border-left: 5px solid #28a745; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .success-icon { font-size: 48px; text-align: center; margin-bottom: 15px; }
            .success-title { font-size: 24px; font-weight: 700; color: #28a745; margin-bottom: 10px; text-align: center; }
            .success-message { font-size: 16px; margin-bottom: 15px; text-align: center; }
            .delivery-info { background: #e9ecef; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; font-weight: 600; color: #495057; }
            .order-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .order-details h4 { margin: 0 0 15px 0; color: #2c3e50; font-size: 18px; }
            .detail-row { display: flex; justify-content: space-between; margin: 8px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
            .detail-label { font-weight: 600; color: #6c757d; }
            .detail-value { color: #2c3e50; }
            .vendor-contact { background: #f0f8ff; padding: 15px; border-radius: 8px; margin: 15px 0; }
            .track-button { display: inline-block; background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: 600; margin: 20px 0; text-align: center; }
            .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 14px; background: #2c3e50; color: white; }
            .footer p { margin: 5px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✅ Order Accepted!</h1>
                <h2>Great News from DesiDukaan</h2>
            </div>

            <div class='content'>
                <h3>Dear " . htmlspecialchars($orderData['customer_name']) . ",</h3>

                <div class='success-banner'>
                    <div class='success-icon'>🎉</div>
                    <div class='success-title'>Your Order Has Been Accepted!</div>
                    <div class='success-message'>The seller has confirmed your order and it will be delivered within 2-3 working days.</div>
                    <div class='delivery-info'>Expected Delivery: 2-3 Working Days</div>
                </div>

                <div class='order-details'>
                    <h4>📋 Order Summary</h4>
                    <div class='detail-row'>
                        <span class='detail-label'>Order Number:</span>
                        <span class='detail-value'>#" . htmlspecialchars($orderData['order_number']) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Products:</span>
                        <span class='detail-value'>" . htmlspecialchars($orderData['products'] ?? $orderData['product_name'] ?? 'N/A') . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Total Amount:</span>
                        <span class='detail-value'>Rs. " . number_format($orderData['total_amount'], 2) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Shop:</span>
                        <span class='detail-value'>" . htmlspecialchars($orderData['shop_name'] ?? 'DesiDukaan Shop') . "</span>
                    </div>
                </div>";

    if (isset($orderData['vendor_contact']) && $orderData['vendor_contact']) {
        $message .= "
                <div class='vendor-contact'>
                    <h4>📞 Vendor Contact Information</h4>
                    <p><strong>Contact:</strong> " . htmlspecialchars($orderData['vendor_contact']) . "</p>
                    <p><em>You can contact the seller directly for any questions about your order.</em></p>
                </div>";
    }

    $message .= "
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . getBaseUrl() . "customer/orders.php' class='track-button'>🔍 Track Your Order</a>
                </div>

                <p style='text-align: center; color: #6c757d; font-size: 14px;'>
                    Your order is now being prepared for delivery. You'll receive updates as it progresses.
                </p>

                <p style='text-align: center; font-weight: 600; color: #2c3e50;'>
                    Thank you for choosing DesiDukaan! 🛍️
                </p>
            </div>

            <div class='footer'>
                <p>&copy; 2024 DesiDukaan. All rights reserved.</p>
                <p>This is an automated email. Please do not reply directly to this message.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: DesiDukaan Order Updates <orders@desidukaan.com>" . "\r\n";
    $headers .= "Reply-To: support@desidukaan.com" . "\r\n";

    $sent = sendEnhancedEmail($to, $subject, $message, $headers);

    // Log email notification
    logEmailNotification([
        'order_id' => $orderData['order_id'] ?? $orderData['id'],
        'recipient_email' => $to,
        'recipient_type' => 'customer',
        'email_type' => 'order_accepted',
        'subject' => $subject,
        'status' => $sent ? 'sent' : 'failed'
    ]);

    return $sent;
}

/**
 * Send order rejection email to customer
 */
function sendOrderRejectionEmail($orderData, $rejectionReason = null) {
    $to = $orderData['customer_email'];
    $subject = "❌ Order Update - " . $orderData['order_number'] . " - DesiDukaan";

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 30px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; font-weight: 700; }
            .header h2 { margin: 10px 0 0 0; font-size: 18px; opacity: 0.9; font-weight: 400; }
            .content { padding: 30px 20px; background: #f8f9fa; }
            .rejection-banner { background: white; padding: 25px; border-radius: 12px; margin: 20px 0; border-left: 5px solid #dc3545; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .rejection-icon { font-size: 48px; text-align: center; margin-bottom: 15px; }
            .rejection-title { font-size: 24px; font-weight: 700; color: #dc3545; margin-bottom: 10px; text-align: center; }
            .rejection-message { font-size: 16px; margin-bottom: 15px; text-align: center; }
            .reason-box { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107; }
            .order-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .order-details h4 { margin: 0 0 15px 0; color: #2c3e50; font-size: 18px; }
            .detail-row { display: flex; justify-content: space-between; margin: 8px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
            .detail-label { font-weight: 600; color: #6c757d; }
            .detail-value { color: #2c3e50; }
            .next-steps { background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 15px 0; }
            .action-button { display: inline-block; background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: 600; margin: 10px 5px; text-align: center; }
            .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 14px; background: #2c3e50; color: white; }
            .footer p { margin: 5px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>❌ Order Update</h1>
                <h2>DesiDukaan Order Status</h2>
            </div>

            <div class='content'>
                <h3>Dear " . htmlspecialchars($orderData['customer_name']) . ",</h3>

                <div class='rejection-banner'>
                    <div class='rejection-icon'>😔</div>
                    <div class='rejection-title'>Order Could Not Be Processed</div>
                    <div class='rejection-message'>We regret to inform you that your order could not be accepted by the seller.</div>
                </div>";

    if ($rejectionReason) {
        $message .= "
                <div class='reason-box'>
                    <h4>📝 Reason for Rejection:</h4>
                    <p>" . htmlspecialchars($rejectionReason) . "</p>
                </div>";
    }

    $message .= "
                <div class='order-details'>
                    <h4>📋 Order Details</h4>
                    <div class='detail-row'>
                        <span class='detail-label'>Order Number:</span>
                        <span class='detail-value'>#" . htmlspecialchars($orderData['order_number']) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Products:</span>
                        <span class='detail-value'>" . htmlspecialchars($orderData['products'] ?? $orderData['product_name'] ?? 'N/A') . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Amount:</span>
                        <span class='detail-value'>Rs. " . number_format($orderData['total_amount'], 2) . "</span>
                    </div>
                </div>

                <div class='next-steps'>
                    <h4>🔄 What's Next?</h4>
                    <ul>
                        <li>No payment has been charged (COD orders)</li>
                        <li>You can browse similar products from other sellers</li>
                        <li>Contact our support team if you need assistance</li>
                        <li>Consider placing a new order with different specifications</li>
                    </ul>
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . getBaseUrl() . "customer/dashboard.php' class='action-button'>🛍️ Continue Shopping</a>
                    <a href='" . getBaseUrl() . "customer/orders.php' class='action-button'>📋 View Orders</a>
                </div>

                <p style='text-align: center; color: #6c757d; font-size: 14px;'>
                    We apologize for any inconvenience. Our customer support team is here to help you find what you need.
                </p>

                <p style='text-align: center; font-weight: 600; color: #2c3e50;'>
                    Thank you for choosing DesiDukaan! 🛍️
                </p>
            </div>

            <div class='footer'>
                <p>&copy; 2024 DesiDukaan. All rights reserved.</p>
                <p>Need help? Contact our support team anytime.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: DesiDukaan Order Updates <orders@desidukaan.com>" . "\r\n";
    $headers .= "Reply-To: support@desidukaan.com" . "\r\n";

    $sent = sendEnhancedEmail($to, $subject, $message, $headers);

    // Log email notification
    logEmailNotification([
        'order_id' => $orderData['order_id'] ?? $orderData['id'],
        'recipient_email' => $to,
        'recipient_type' => 'customer',
        'email_type' => 'order_rejected',
        'subject' => $subject,
        'status' => $sent ? 'sent' : 'failed'
    ]);

    return $sent;
}

/**
 * Log email notification to database
 */
function logEmailNotification($data) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO email_notifications (
                order_id, recipient_email, recipient_type, email_type, 
                subject, status, sent_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['order_id'],
            $data['recipient_email'],
            $data['recipient_type'],
            $data['email_type'],
            $data['subject'],
            $data['status']
        ]);
    } catch (Exception $e) {
        error_log("Email logging error: " . $e->getMessage());
    }
}

/**
 * Get base URL for email links
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $path . '/';
}

/**
 * Send order status update email
 */
function sendOrderStatusUpdateEmail($orderData, $newStatus) {
    $statusMessages = [
        'confirmed' => [
            'title' => 'Order Confirmed! 🎉',
            'message' => 'Great news! Your order has been confirmed by the seller and is now being prepared.',
            'timeline' => 'Your order will be processed and shipped within 1-2 business days.',
            'icon' => '✅',
            'color' => '#28a745'
        ],
        'processing' => [
            'title' => 'Order Being Processed 📦',
            'message' => 'Your order is currently being prepared for shipment.',
            'timeline' => 'Expected to ship within 24 hours.',
            'icon' => '⚙️',
            'color' => '#007bff'
        ],
        'shipped' => [
            'title' => 'Order Shipped! 🚚',
            'message' => 'Excellent! Your order has been dispatched and is on its way to you.',
            'timeline' => 'You will receive your order within 2-3 business days.',
            'icon' => '🚚',
            'color' => '#17a2b8'
        ],
        'delivered' => [
            'title' => 'Order Delivered! 🎊',
            'message' => 'Your order has been successfully delivered. We hope you love your purchase!',
            'timeline' => 'Thank you for shopping with DesiDukaan.',
            'icon' => '📦',
            'color' => '#6f42c1'
        ],
        'cancelled' => [
            'title' => 'Order Cancelled ❌',
            'message' => 'Your order has been cancelled. If you have any questions, please contact our support.',
            'timeline' => 'Any payment made will be refunded within 3-5 business days.',
            'icon' => '❌',
            'color' => '#dc3545'
        ]
    ];

    $statusInfo = $statusMessages[$newStatus];
    $to = $orderData['customer_email'];
    $subject = $statusInfo['title'] . " - Order #" . $orderData['order_number'] . " - DesiDukaan";

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, " . $statusInfo['color'] . ", #2c3e50); color: white; padding: 30px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; font-weight: 700; }
            .header h2 { margin: 10px 0 0 0; font-size: 18px; opacity: 0.9; font-weight: 400; }
            .content { padding: 30px 20px; background: #f8f9fa; }
            .status-banner { background: white; padding: 25px; border-radius: 12px; margin: 20px 0; border-left: 5px solid " . $statusInfo['color'] . "; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .status-icon { font-size: 48px; text-align: center; margin-bottom: 15px; }
            .status-title { font-size: 24px; font-weight: 700; color: " . $statusInfo['color'] . "; margin-bottom: 10px; text-align: center; }
            .status-message { font-size: 16px; margin-bottom: 15px; text-align: center; }
            .timeline { background: #e9ecef; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; font-weight: 600; color: #495057; }
            .order-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .order-details h4 { margin: 0 0 15px 0; color: #2c3e50; font-size: 18px; }
            .detail-row { display: flex; justify-content: space-between; margin: 8px 0; padding: 8px 0; border-bottom: 1px solid #eee; }
            .detail-label { font-weight: 600; color: #6c757d; }
            .detail-value { color: #2c3e50; }
            .track-button { display: inline-block; background: " . $statusInfo['color'] . "; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: 600; margin: 20px 0; text-align: center; }
            .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 14px; background: #2c3e50; color: white; }
            .footer p { margin: 5px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . $statusInfo['icon'] . " " . $statusInfo['title'] . "</h1>
                <h2>DesiDukaan Order Update</h2>
            </div>

            <div class='content'>
                <h3>Dear " . htmlspecialchars($orderData['customer_name']) . ",</h3>

                <div class='status-banner'>
                    <div class='status-icon'>" . $statusInfo['icon'] . "</div>
                    <div class='status-title'>" . $statusInfo['title'] . "</div>
                    <div class='status-message'>" . $statusInfo['message'] . "</div>
                    <div class='timeline'>" . $statusInfo['timeline'] . "</div>
                </div>

                <div class='order-details'>
                    <h4>📋 Order Summary</h4>
                    <div class='detail-row'>
                        <span class='detail-label'>Order Number:</span>
                        <span class='detail-value'>#" . htmlspecialchars($orderData['order_number']) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Products:</span>
                        <span class='detail-value'>" . htmlspecialchars($orderData['products']) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Total Amount:</span>
                        <span class='detail-value'>Rs. " . number_format($orderData['total_amount'], 2) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Shop:</span>
                        <span class='detail-value'>" . htmlspecialchars($orderData['shop_name']) . "</span>
                    </div>";

    if (isset($orderData['shipping_name']) && $orderData['shipping_name']) {
        $message .= "
                    <div class='detail-row'>
                        <span class='detail-label'>Delivery Address:</span>
                        <span class='detail-value'>" . htmlspecialchars($orderData['shipping_name']) . "<br>" .
                        htmlspecialchars($orderData['address_line_1']) . "<br>" .
                        htmlspecialchars($orderData['city']) . ", " . htmlspecialchars($orderData['state']) . " - " . htmlspecialchars($orderData['postal_code']) . "</span>
                    </div>";
    }

    $message .= "
                </div>

                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . getBaseUrl() . "customer/orders.php' class='track-button'>🔍 Track Your Order</a>
                </div>

                <p style='text-align: center; color: #6c757d; font-size: 14px;'>
                    Need help? Contact our customer support team anytime.
                </p>

                <p style='text-align: center; font-weight: 600; color: #2c3e50;'>
                    Thank you for choosing DesiDukaan! 🛍️
                </p>
            </div>

            <div class='footer'>
                <p>&copy; 2024 DesiDukaan. All rights reserved.</p>
                <p>This is an automated email. Please do not reply directly to this message.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: DesiDukaan Order Updates <orders@desidukaan.com>" . "\r\n";
    $headers .= "Reply-To: support@desidukaan.com" . "\r\n";

    $sent = sendEnhancedEmail($to, $subject, $message, $headers);

    // Log email notification
    logEmailNotification([
        'order_id' => $orderData['id'],
        'recipient_email' => $to,
        'recipient_type' => 'customer',
        'email_type' => 'status_update',
        'subject' => $subject,
        'status' => $sent ? 'sent' : 'failed'
    ]);

    return $sent;
}
?>
