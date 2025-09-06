<?php
/**
 * DesiDukaan - Enhanced Notification System
 * Real-time notifications for vendors and customers
 */

/**
 * Get user notifications with enhanced filtering
 */
function get_user_notifications($user_id, $limit = 5, $type = null, $unread_only = false) {
    try {
        $db = getDB();

        $conditions = ["user_id = ?"];
        $params = [$user_id];

        if ($type) {
            $conditions[] = "type = ?";
            $params[] = $type;
        }

        if ($unread_only) {
            $conditions[] = "is_read = FALSE";
        }

        $whereClause = implode(' AND ', $conditions);

        $stmt = $db->prepare("
            SELECT n.*, o.order_number, o.total_amount
            FROM notifications n
            LEFT JOIN orders o ON n.order_id = o.id
            WHERE {$whereClause}
            ORDER BY n.created_at DESC
            LIMIT ?
        ");

        $params[] = $limit;
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Notification fetch error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unread notification count
 */
function get_unread_notification_count($user_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        error_log("Notification count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark notification as read
 */
function mark_notification_as_read($notification_id, $user_id = null) {
    try {
        $db = getDB();

        $sql = "UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE id = ?";
        $params = [$notification_id];

        // Add user verification for security
        if ($user_id) {
            $sql .= " AND user_id = ?";
            $params[] = $user_id;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Mark notification read error: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for a user
 */
function mark_all_notifications_read($user_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$user_id]);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Mark all notifications read error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Create a new notification
 */
function create_notification($user_id, $title, $message, $type = 'info', $order_id = null, $action_url = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, order_id, action_url, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $title, $message, $type, $order_id, $action_url]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Create notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create vendor order notification
 */
function create_vendor_order_notification($vendor_id, $order_id, $order_number) {
    $title = "New Order Received! 🎉";
    $message = "You have received a new order #{$order_number}. Please accept or reject.";
    $action_url = "/seller/orders.php?order_id={$order_id}";

    return create_notification($vendor_id, $title, $message, 'order', $order_id, $action_url);
}

/**
 * Create customer order status notification
 */
function create_customer_status_notification($customer_id, $order_id, $order_number, $status) {
    $statusMessages = [
        'confirmed' => ['title' => 'Order Confirmed! ✅', 'message' => "Your order #{$order_number} has been confirmed by the seller."],
        'rejected' => ['title' => 'Order Update ❌', 'message' => "Your order #{$order_number} has been declined by the seller."],
        'shipped' => ['title' => 'Order Shipped! 🚚', 'message' => "Your order #{$order_number} has been shipped and is on its way."],
        'delivered' => ['title' => 'Order Delivered! 📦', 'message' => "Your order #{$order_number} has been delivered successfully."]
    ];

    if (!isset($statusMessages[$status])) {
        return false;
    }

    $notification = $statusMessages[$status];
    $action_url = "/customer/orders.php?view={$order_id}";

    return create_notification($customer_id, $notification['title'], $notification['message'], 'order', $order_id, $action_url);
}

/**
 * Display notifications with enhanced UI
 */
function display_notifications($user_id, $show_all = false) {
    $notifications = get_user_notifications($user_id, $show_all ? 50 : 5);

    if (empty($notifications)) {
        echo '<div class="no-notifications">
            <div class="no-notifications-icon">🔔</div>
            <p>No notifications yet</p>
        </div>';
        return;
    }

    echo '<div class="notifications-container">';

    foreach ($notifications as $notification) {
        $type_icons = [
            'order' => '📦',
            'success' => '✅',
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️'
        ];

        $icon = $type_icons[$notification['type']] ?? '📢';
        $read_class = $notification['is_read'] ? 'read' : 'unread';
        $order_info = '';

        if ($notification['order_number']) {
            $order_info = " (Order #{$notification['order_number']})";
        }

        echo '<div class="notification-item ' . $read_class . '" data-id="' . $notification['id'] . '">';
        echo '<div class="notification-icon">' . $icon . '</div>';
        echo '<div class="notification-content">';
        echo '<h4 class="notification-title">' . htmlspecialchars($notification['title']) . $order_info . '</h4>';
        echo '<p class="notification-message">' . htmlspecialchars($notification['message']) . '</p>';
        echo '<div class="notification-meta">';
        echo '<span class="notification-time">' . time_ago($notification['created_at']) . '</span>';

        if ($notification['action_url']) {
            echo '<a href="' . htmlspecialchars($notification['action_url']) . '" class="notification-action">View Details</a>';
        }

        if (!$notification['is_read']) {
            echo '<button onclick="markAsRead(' . $notification['id'] . ')" class="mark-read-btn">Mark as Read</button>';
        }

        echo '</div></div></div>';
    }

    echo '</div>';

    // Add notification styles and JavaScript
    echo '<style>
    .notifications-container {
        max-height: 400px;
        overflow-y: auto;
        border-radius: 8px;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .notification-item {
        display: flex;
        padding: 15px;
        border-bottom: 1px solid #eee;
        transition: background 0.2s ease;
        cursor: pointer;
    }

    .notification-item:hover {
        background: #f8f9fa;
    }

    .notification-item.unread {
        background: #f0f8ff;
        border-left: 4px solid #007bff;
    }

    .notification-icon {
        font-size: 24px;
        margin-right: 15px;
        flex-shrink: 0;
    }

    .notification-content {
        flex: 1;
    }

    .notification-title {
        margin: 0 0 5px 0;
        font-size: 14px;
        font-weight: 600;
        color: #2c3e50;
    }

    .notification-message {
        margin: 0 0 10px 0;
        font-size: 13px;
        color: #6c757d;
        line-height: 1.4;
    }

    .notification-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12px;
    }

    .notification-time {
        color: #999;
    }

    .notification-action {
        color: #007bff;
        text-decoration: none;
        font-weight: 500;
    }

    .notification-action:hover {
        text-decoration: underline;
    }

    .mark-read-btn {
        background: none;
        border: none;
        color: #007bff;
        cursor: pointer;
        font-size: 12px;
        padding: 0;
    }

    .mark-read-btn:hover {
        text-decoration: underline;
    }

    .no-notifications {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .no-notifications-icon {
        font-size: 48px;
        margin-bottom: 10px;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .notification-item {
            padding: 12px;
        }

        .notification-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
    }
    </style>';

    // Add JavaScript for real-time functionality
    echo '<script>
    function markAsRead(notificationId) {
        fetch("../includes/mark_notification_read.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "notification_id=" + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
                if (notificationElement) {
                    notificationElement.classList.remove("unread");
                    notificationElement.classList.add("read");
                    const markReadBtn = notificationElement.querySelector(".mark-read-btn");
                    if (markReadBtn) {
                        markReadBtn.remove();
                    }
                }
                updateNotificationCount();
            }
        })
        .catch(error => {
            console.error("Error:", error);
        });
    }

    function updateNotificationCount() {
        fetch("../includes/get_notification_count.php")
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector(".notification-badge");
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = "block";
                } else {
                    badge.style.display = "none";
                }
            }
        })
        .catch(error => {
            console.error("Error updating notification count:", error);
        });
    }
    </script>';
}

/**
 * Helper function to display time ago
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);

    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hr ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';

    return date('M j, Y', strtotime($datetime));
}
?>
