<?php
/**
 * DesiDukaan - Input Validation and Sanitization System
 * Comprehensive input validation, sanitization, and XSS protection
 */

/**
 * Sanitize string input
 */
function sanitizeString($input, $max_length = null) {
    if (!is_string($input)) {
        return '';
    }
    
    // Remove null bytes
    $input = str_replace("\0", '', $input);
    
    // Trim whitespace
    $input = trim($input);
    
    // Limit length if specified
    if ($max_length && strlen($input) > $max_length) {
        $input = substr($input, 0, $max_length);
    }
    
    return $input;
}

/**
 * Sanitize HTML input (remove dangerous tags)
 */
function sanitizeHTML($input) {
    $input = sanitizeString($input);
    
    // Remove dangerous tags and attributes
    $input = strip_tags($input, '<p><br><strong><em><u><ol><ul><li>');
    
    // Remove javascript: and data: URLs
    $input = preg_replace('/javascript:/i', '', $input);
    $input = preg_replace('/data:/i', '', $input);
    
    return $input;
}

/**
 * Validate and sanitize email
 */
function validateEmail($email) {
    $email = sanitizeString($email, 254);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Additional checks
    if (strlen($email) > 254) {
        return false;
    }
    
    return $email;
}

/**
 * Validate and sanitize phone number
 */
function validatePhoneNumber($phone) {
    $phone = sanitizeString($phone);
    
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check length (Indian phone numbers)
    if (strlen($phone) < 10 || strlen($phone) > 12) {
        return false;
    }
    
    return $phone;
}

/**
 * Validate integer input
 */
function validateInteger($input, $min = null, $max = null) {
    if (!is_numeric($input)) {
        return false;
    }
    
    $value = intval($input);
    
    if ($min !== null && $value < $min) {
        return false;
    }
    
    if ($max !== null && $value > $max) {
        return false;
    }
    
    return $value;
}

/**
 * Validate float/decimal input
 */
function validateFloat($input, $min = null, $max = null, $decimals = 2) {
    if (!is_numeric($input)) {
        return false;
    }
    
    $value = floatval($input);
    
    if ($min !== null && $value < $min) {
        return false;
    }
    
    if ($max !== null && $value > $max) {
        return false;
    }
    
    return round($value, $decimals);
}

/**
 * Validate URL
 */
function validateURL($url) {
    $url = sanitizeString($url, 2048);
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // Only allow http and https
    $parsed = parse_url($url);
    if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
        return false;
    }
    
    return $url;
}

/**
 * Validate order status
 */
function validateOrderStatus($status) {
    $valid_statuses = [
        'pending', 'confirmed', 'processing', 
        'shipped', 'delivered', 'cancelled', 'rejected'
    ];
    
    $status = sanitizeString($status);
    
    if (!in_array($status, $valid_statuses)) {
        return false;
    }
    
    return $status;
}

/**
 * Validate user role
 */
function validateUserRole($role) {
    $valid_roles = ['customer', 'seller', 'admin'];
    
    $role = sanitizeString($role);
    
    if (!in_array($role, $valid_roles)) {
        return false;
    }
    
    return $role;
}

/**
 * Validate and sanitize address
 */
function validateAddress($address_data) {
    $validated = [];
    $errors = [];
    
    // Customer name
    $name = sanitizeString($address_data['customer_name'] ?? '', 100);
    if (empty($name)) {
        $errors[] = 'Customer name is required';
    } else {
        $validated['customer_name'] = $name;
    }
    
    // Phone number
    $phone = validatePhoneNumber($address_data['phone_number'] ?? '');
    if (!$phone) {
        $errors[] = 'Valid phone number is required';
    } else {
        $validated['phone_number'] = $phone;
    }
    
    // Address line 1
    $address1 = sanitizeString($address_data['address_line_1'] ?? '', 255);
    if (empty($address1)) {
        $errors[] = 'Address line 1 is required';
    } else {
        $validated['address_line_1'] = $address1;
    }
    
    // Address line 2 (optional)
    $validated['address_line_2'] = sanitizeString($address_data['address_line_2'] ?? '', 255);
    
    // City
    $city = sanitizeString($address_data['city'] ?? '', 100);
    if (empty($city)) {
        $errors[] = 'City is required';
    } else {
        $validated['city'] = $city;
    }
    
    // State
    $state = sanitizeString($address_data['state'] ?? '', 100);
    if (empty($state)) {
        $errors[] = 'State is required';
    } else {
        $validated['state'] = $state;
    }
    
    // Postal code
    $postal_code = sanitizeString($address_data['postal_code'] ?? '');
    if (!preg_match('/^[0-9]{6}$/', $postal_code)) {
        $errors[] = 'Valid 6-digit postal code is required';
    } else {
        $validated['postal_code'] = $postal_code;
    }
    
    // Country (default to India)
    $validated['country'] = sanitizeString($address_data['country'] ?? 'India', 100);
    
    return [
        'valid' => empty($errors),
        'data' => $validated,
        'errors' => $errors
    ];
}

/**
 * Validate order item data
 */
function validateOrderItem($item_data) {
    $validated = [];
    $errors = [];
    
    // Product ID
    $product_id = validateInteger($item_data['product_id'] ?? null, 1);
    if (!$product_id) {
        $errors[] = 'Valid product ID is required';
    } else {
        $validated['product_id'] = $product_id;
    }
    
    // Product name
    $product_name = sanitizeString($item_data['product_name'] ?? '', 255);
    if (empty($product_name)) {
        $errors[] = 'Product name is required';
    } else {
        $validated['product_name'] = $product_name;
    }
    
    // Quantity
    $quantity = validateInteger($item_data['quantity'] ?? null, 1, 999);
    if (!$quantity) {
        $errors[] = 'Valid quantity (1-999) is required';
    } else {
        $validated['quantity'] = $quantity;
    }
    
    // Price
    $price = validateFloat($item_data['price'] ?? null, 0.01, 999999.99);
    if ($price === false) {
        $errors[] = 'Valid price is required';
    } else {
        $validated['price'] = $price;
    }
    
    // Calculate total price
    if (isset($validated['quantity']) && isset($validated['price'])) {
        $validated['total_price'] = $validated['quantity'] * $validated['price'];
    }
    
    return [
        'valid' => empty($errors),
        'data' => $validated,
        'errors' => $errors
    ];
}

/**
 * Prevent XSS attacks
 */
function preventXSS($input) {
    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) {
    $errors = [];
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'No file uploaded or upload failed';
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = 'File size exceeds maximum allowed size';
    }
    
    // Check file type
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        $errors[] = 'File type not allowed';
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    if (!in_array($mime_type, $allowed_mimes)) {
        $errors[] = 'Invalid file type detected';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'file_info' => [
            'name' => sanitizeString($file['name']),
            'size' => $file['size'],
            'type' => $mime_type,
            'extension' => $file_extension
        ]
    ];
}

/**
 * Rate limiting validation
 */
function validateRateLimit($action, $limit = 10, $window = 60) {
    $key = 'rate_limit_' . $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'timestamp' => time()];
    }
    
    $current_time = time();
    $rate_data = $_SESSION[$key];
    
    // Reset counter if window has passed
    if (($current_time - $rate_data['timestamp']) > $window) {
        $_SESSION[$key] = ['count' => 1, 'timestamp' => $current_time];
        return true;
    }
    
    // Check if limit exceeded
    if ($rate_data['count'] >= $limit) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}
?>
