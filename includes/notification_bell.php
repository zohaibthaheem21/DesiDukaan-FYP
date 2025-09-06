<?php
/**
 * DesiDukaan - Notification Bell Component
 * Real-time notification bell with dropdown for vendors and customers
 */

require_once 'notifications.php';

function render_notification_bell($user_id, $base_path = '') {
    $unread_count = get_unread_notification_count($user_id);
    $recent_notifications = get_user_notifications($user_id, 5, null, true); // Get 5 recent unread notifications
    
    echo '<div class="notification-bell-container">';
    echo '<div class="notification-bell" onclick="toggleNotificationDropdown()">';
    echo '<span class="bell-icon">🔔</span>';
    
    if ($unread_count > 0) {
        echo '<span class="notification-badge" id="notification-badge">' . $unread_count . '</span>';
    } else {
        echo '<span class="notification-badge" id="notification-badge" style="display: none;"></span>';
    }
    
    echo '</div>';
    
    // Notification dropdown
    echo '<div class="notification-dropdown" id="notification-dropdown">';
    echo '<div class="notification-dropdown-header">';
    echo '<h4>Notifications</h4>';
    
    if ($unread_count > 0) {
        echo '<button class="mark-all-read-btn" onclick="markAllAsRead()">Mark all as read</button>';
    }
    
    echo '</div>';
    
    echo '<div class="notification-dropdown-content" id="notification-dropdown-content">';
    
    if (empty($recent_notifications)) {
        echo '<div class="no-notifications">';
        echo '<div class="no-notifications-icon">🔔</div>';
        echo '<p>No new notifications</p>';
        echo '</div>';
    } else {
        foreach ($recent_notifications as $notification) {
            render_notification_item($notification);
        }
    }
    
    echo '</div>';
    
    echo '<div class="notification-dropdown-footer">';
    echo '<a href="' . $base_path . 'notifications.php" class="view-all-link">View All Notifications</a>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
    
    // Add CSS styles
    echo '<style>
    .notification-bell-container {
        position: relative;
        display: inline-block;
    }
    
    .notification-bell {
        position: relative;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: background-color 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .notification-bell:hover {
        background-color: rgba(0, 0, 0, 0.1);
    }
    
    .bell-icon {
        font-size: 20px;
        color: #2c3e50;
    }
    
    .notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        background: #e74c3c;
        color: white;
        border-radius: 50%;
        min-width: 18px;
        height: 18px;
        font-size: 11px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .notification-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        width: 350px;
        max-height: 400px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        display: none;
        overflow: hidden;
    }
    
    .notification-dropdown.show {
        display: block;
        animation: slideDown 0.2s ease;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .notification-dropdown-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
    }
    
    .notification-dropdown-header h4 {
        margin: 0;
        font-size: 16px;
        color: #2c3e50;
    }
    
    .mark-all-read-btn {
        background: none;
        border: none;
        color: #007bff;
        cursor: pointer;
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 4px;
        transition: background-color 0.2s ease;
    }
    
    .mark-all-read-btn:hover {
        background-color: rgba(0, 123, 255, 0.1);
    }
    
    .notification-dropdown-content {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .notification-dropdown-footer {
        padding: 10px 15px;
        border-top: 1px solid #eee;
        text-align: center;
        background: #f8f9fa;
    }
    
    .view-all-link {
        color: #007bff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
    }
    
    .view-all-link:hover {
        text-decoration: underline;
    }
    
    .notification-item-mini {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background-color 0.2s ease;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    
    .notification-item-mini:hover {
        background-color: #f8f9fa;
    }
    
    .notification-item-mini.unread {
        background-color: #f0f8ff;
        border-left: 3px solid #007bff;
    }
    
    .notification-icon-mini {
        font-size: 16px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .notification-content-mini {
        flex: 1;
        min-width: 0;
    }
    
    .notification-title-mini {
        font-size: 13px;
        font-weight: 600;
        color: #2c3e50;
        margin: 0 0 4px 0;
        line-height: 1.3;
    }
    
    .notification-message-mini {
        font-size: 12px;
        color: #6c757d;
        margin: 0 0 4px 0;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .notification-time-mini {
        font-size: 11px;
        color: #999;
    }
    
    .no-notifications {
        text-align: center;
        padding: 30px 20px;
        color: #6c757d;
    }
    
    .no-notifications-icon {
        font-size: 32px;
        margin-bottom: 8px;
        opacity: 0.5;
    }
    
    .no-notifications p {
        margin: 0;
        font-size: 14px;
    }
    
    @media (max-width: 768px) {
        .notification-dropdown {
            width: 300px;
            right: -50px;
        }
    }
    </style>';
    
    // Add JavaScript for functionality
    echo '<script>
    let notificationDropdownOpen = false;
    let notificationPollingInterval;
    
    function toggleNotificationDropdown() {
        const dropdown = document.getElementById("notification-dropdown");
        notificationDropdownOpen = !notificationDropdownOpen;
        
        if (notificationDropdownOpen) {
            dropdown.classList.add("show");
            refreshNotifications();
            startNotificationPolling();
        } else {
            dropdown.classList.remove("show");
            stopNotificationPolling();
        }
    }
    
    function refreshNotifications() {
        fetch("' . $base_path . 'includes/get_notifications.php?limit=5&unread_only=true")
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationDropdown(data.notifications);
                updateNotificationBadge(data.unread_count);
            }
        })
        .catch(error => {
            console.error("Error refreshing notifications:", error);
        });
    }
    
    function updateNotificationDropdown(notifications) {
        const content = document.getElementById("notification-dropdown-content");
        
        if (notifications.length === 0) {
            content.innerHTML = `
                <div class="no-notifications">
                    <div class="no-notifications-icon">🔔</div>
                    <p>No new notifications</p>
                </div>
            `;
        } else {
            content.innerHTML = notifications.map(notification => `
                <div class="notification-item-mini ${notification.is_read ? "" : "unread"}" onclick="handleNotificationClick(${notification.id}, \'${notification.action_url || ""}\')">
                    <div class="notification-icon-mini">${getNotificationIcon(notification.type)}</div>
                    <div class="notification-content-mini">
                        <div class="notification-title-mini">${notification.title}</div>
                        <div class="notification-message-mini">${notification.message}</div>
                        <div class="notification-time-mini">${notification.time_ago}</div>
                    </div>
                </div>
            `).join("");
        }
    }
    
    function updateNotificationBadge(count) {
        const badge = document.getElementById("notification-badge");
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = "flex";
        } else {
            badge.style.display = "none";
        }
    }
    
    function getNotificationIcon(type) {
        const icons = {
            "order": "📦",
            "success": "✅",
            "error": "❌",
            "warning": "⚠️",
            "info": "ℹ️"
        };
        return icons[type] || "📢";
    }
    
    function handleNotificationClick(notificationId, actionUrl) {
        // Mark as read
        markAsRead(notificationId);
        
        // Navigate to action URL if provided
        if (actionUrl) {
            window.location.href = actionUrl;
        }
    }
    
    function markAsRead(notificationId) {
        fetch("' . $base_path . 'includes/mark_notification_read.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "notification_id=" + notificationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshNotifications();
            }
        })
        .catch(error => {
            console.error("Error marking notification as read:", error);
        });
    }
    
    function markAllAsRead() {
        fetch("' . $base_path . 'includes/mark_notification_read.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "mark_all=true"
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshNotifications();
            }
        })
        .catch(error => {
            console.error("Error marking all notifications as read:", error);
        });
    }
    
    function startNotificationPolling() {
        notificationPollingInterval = setInterval(refreshNotifications, 30000); // Poll every 30 seconds
    }
    
    function stopNotificationPolling() {
        if (notificationPollingInterval) {
            clearInterval(notificationPollingInterval);
        }
    }
    
    // Close dropdown when clicking outside
    document.addEventListener("click", function(event) {
        const container = document.querySelector(".notification-bell-container");
        if (container && !container.contains(event.target) && notificationDropdownOpen) {
            toggleNotificationDropdown();
        }
    });
    
    // Start background polling for notification count
    setInterval(function() {
        if (!notificationDropdownOpen) {
            fetch("' . $base_path . 'includes/get_notification_count.php")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge(data.count);
                }
            })
            .catch(error => {
                console.error("Error polling notification count:", error);
            });
        }
    }, 60000); // Poll every minute when dropdown is closed
    </script>';
}

function render_notification_item($notification) {
    $type_icons = [
        'order' => '📦',
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    
    $icon = $type_icons[$notification['type']] ?? '📢';
    $read_class = $notification['is_read'] ? '' : 'unread';
    $action_url = $notification['action_url'] ?? '';
    
    echo '<div class="notification-item-mini ' . $read_class . '" onclick="handleNotificationClick(' . $notification['id'] . ', \'' . htmlspecialchars($action_url) . '\')">';
    echo '<div class="notification-icon-mini">' . $icon . '</div>';
    echo '<div class="notification-content-mini">';
    echo '<div class="notification-title-mini">' . htmlspecialchars($notification['title']) . '</div>';
    echo '<div class="notification-message-mini">' . htmlspecialchars($notification['message']) . '</div>';
    echo '<div class="notification-time-mini">' . time_ago($notification['created_at']) . '</div>';
    echo '</div>';
    echo '</div>';
}
?>
