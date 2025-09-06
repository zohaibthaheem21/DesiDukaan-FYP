<?php
/**
 * DesiDukaan - Order Audit and Logging System
 * Comprehensive audit trail for order events and status changes
 */

require_once 'config/config.php';

/**
 * Log detailed order event with context
 */
function logOrderAuditEvent($order_id, $event_type, $description, $user_id = null, $additional_data = []) {
    try {
        $db = getDB();
        
        // Get current order state for context
        $stmt = $db->prepare("SELECT status, total_amount, customer_id FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order_context = $stmt->fetch();
        
        // Prepare audit data
        $audit_data = [
            'order_status' => $order_context['status'] ?? null,
            'order_amount' => $order_context['total_amount'] ?? null,
            'customer_id' => $order_context['customer_id'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'session_id' => session_id(),
            'timestamp' => date('Y-m-d H:i:s'),
            'additional_data' => $additional_data
        ];
        
        // Insert audit record
        $stmt = $db->prepare("
            INSERT INTO order_events (
                order_id, event_type, description, user_id, 
                additional_data, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $order_id,
            $event_type,
            $description,
            $user_id,
            json_encode($audit_data)
        ]);
        
        // Also log to system log for critical events
        if (in_array($event_type, ['order_placed', 'order_confirmed', 'order_rejected', 'payment_processed'])) {
            error_log("ORDER_AUDIT: {$event_type} - Order #{$order_id} - User #{$user_id} - {$description}");
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Order audit logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get comprehensive audit trail for an order
 */
function getOrderAuditTrail($order_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT oe.*, u.username, u.full_name,
                   CASE 
                       WHEN oe.user_id = o.customer_id THEN 'Customer'
                       WHEN oe.user_id IN (SELECT seller_id FROM shops WHERE id = o.shop_id) THEN 'Vendor'
                       ELSE 'System'
                   END as user_role
            FROM order_events oe
            LEFT JOIN users u ON oe.user_id = u.id
            LEFT JOIN orders o ON oe.order_id = o.id
            WHERE oe.order_id = ?
            ORDER BY oe.created_at ASC
        ");
        
        $stmt->execute([$order_id]);
        $events = $stmt->fetchAll();
        
        // Parse additional data for each event
        foreach ($events as &$event) {
            if ($event['additional_data']) {
                $event['parsed_data'] = json_decode($event['additional_data'], true);
            }
        }
        
        return $events;
        
    } catch (Exception $e) {
        error_log("Failed to get audit trail: " . $e->getMessage());
        return [];
    }
}

/**
 * Generate audit report for date range
 */
function generateOrderAuditReport($date_from, $date_to, $event_types = null) {
    try {
        $db = getDB();
        
        $where_conditions = ["oe.created_at BETWEEN ? AND ?"];
        $params = [$date_from, $date_to];
        
        if ($event_types && is_array($event_types)) {
            $placeholders = str_repeat('?,', count($event_types) - 1) . '?';
            $where_conditions[] = "oe.event_type IN ({$placeholders})";
            $params = array_merge($params, $event_types);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $stmt = $db->prepare("
            SELECT 
                oe.event_type,
                COUNT(*) as event_count,
                COUNT(DISTINCT oe.order_id) as unique_orders,
                COUNT(DISTINCT oe.user_id) as unique_users,
                MIN(oe.created_at) as first_occurrence,
                MAX(oe.created_at) as last_occurrence
            FROM order_events oe
            WHERE {$where_clause}
            GROUP BY oe.event_type
            ORDER BY event_count DESC
        ");
        
        $stmt->execute($params);
        $summary = $stmt->fetchAll();
        
        // Get detailed events
        $stmt = $db->prepare("
            SELECT oe.*, o.order_number, o.total_amount, 
                   u.username, u.full_name
            FROM order_events oe
            LEFT JOIN orders o ON oe.order_id = o.id
            LEFT JOIN users u ON oe.user_id = u.id
            WHERE {$where_clause}
            ORDER BY oe.created_at DESC
            LIMIT 1000
        ");
        
        $stmt->execute($params);
        $detailed_events = $stmt->fetchAll();
        
        return [
            'summary' => $summary,
            'detailed_events' => $detailed_events,
            'date_range' => ['from' => $date_from, 'to' => $date_to],
            'total_events' => array_sum(array_column($summary, 'event_count'))
        ];
        
    } catch (Exception $e) {
        error_log("Audit report generation failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Monitor suspicious order activities
 */
function detectSuspiciousOrderActivity($hours_back = 24) {
    try {
        $db = getDB();
        
        $suspicious_activities = [];
        
        // Check for rapid order cancellations by same user
        $stmt = $db->prepare("
            SELECT user_id, COUNT(*) as cancellation_count
            FROM order_events 
            WHERE event_type = 'order_cancelled' 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
            AND user_id IS NOT NULL
            GROUP BY user_id
            HAVING cancellation_count > 5
        ");
        $stmt->execute([$hours_back]);
        $rapid_cancellations = $stmt->fetchAll();
        
        if ($rapid_cancellations) {
            $suspicious_activities['rapid_cancellations'] = $rapid_cancellations;
        }
        
        // Check for orders with unusual amounts
        $stmt = $db->prepare("
            SELECT o.id, o.order_number, o.total_amount, o.customer_id
            FROM orders o
            WHERE o.created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
            AND (o.total_amount > 50000 OR o.total_amount < 1)
        ");
        $stmt->execute([$hours_back]);
        $unusual_amounts = $stmt->fetchAll();
        
        if ($unusual_amounts) {
            $suspicious_activities['unusual_amounts'] = $unusual_amounts;
        }
        
        // Check for multiple failed order attempts
        $stmt = $db->prepare("
            SELECT user_id, COUNT(*) as failed_attempts
            FROM order_events 
            WHERE event_type = 'order_placement_failed' 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
            AND user_id IS NOT NULL
            GROUP BY user_id
            HAVING failed_attempts > 10
        ");
        $stmt->execute([$hours_back]);
        $failed_attempts = $stmt->fetchAll();
        
        if ($failed_attempts) {
            $suspicious_activities['excessive_failed_attempts'] = $failed_attempts;
        }
        
        return $suspicious_activities;
        
    } catch (Exception $e) {
        error_log("Suspicious activity detection failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Export audit data for compliance
 */
function exportOrderAuditData($order_id, $format = 'json') {
    try {
        $audit_trail = getOrderAuditTrail($order_id);
        
        if (empty($audit_trail)) {
            return null;
        }
        
        $export_data = [
            'order_id' => $order_id,
            'export_timestamp' => date('Y-m-d H:i:s'),
            'total_events' => count($audit_trail),
            'events' => $audit_trail
        ];
        
        switch ($format) {
            case 'json':
                return json_encode($export_data, JSON_PRETTY_PRINT);
                
            case 'csv':
                $csv_data = "Event Type,Description,User ID,Username,Timestamp,Additional Data\n";
                foreach ($audit_trail as $event) {
                    $csv_data .= sprintf(
                        "%s,%s,%s,%s,%s,%s\n",
                        $event['event_type'],
                        str_replace('"', '""', $event['description']),
                        $event['user_id'] ?? '',
                        $event['username'] ?? '',
                        $event['created_at'],
                        str_replace('"', '""', $event['additional_data'] ?? '')
                    );
                }
                return $csv_data;
                
            default:
                return $export_data;
        }
        
    } catch (Exception $e) {
        error_log("Audit data export failed: " . $e->getMessage());
        return null;
    }
}

/**
 * Archive old audit records
 */
function archiveOldAuditRecords($days_to_keep = 365) {
    try {
        $db = getDB();
        
        // Create archive table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS order_events_archive LIKE order_events
        ");
        
        // Move old records to archive
        $stmt = $db->prepare("
            INSERT INTO order_events_archive 
            SELECT * FROM order_events 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$days_to_keep]);
        $archived_count = $stmt->rowCount();
        
        // Delete old records from main table
        $stmt = $db->prepare("
            DELETE FROM order_events 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$days_to_keep]);
        
        return [
            'success' => true,
            'archived_records' => $archived_count
        ];
        
    } catch (Exception $e) {
        error_log("Audit archiving failed: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}
?>
