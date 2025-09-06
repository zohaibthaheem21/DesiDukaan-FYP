/**
 * DesiDukaan - Admin AJAX System
 * Handles admin-specific AJAX operations
 */

class AdminAjaxManager extends AjaxManager {
    constructor() {
        super();
    }

    // Shop Management
    async approveShop(shopId) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const data = await this.request('includes/admin_actions.php', {
                body: `action=approve_shop&shop_id=${shopId}`
            });

            if (data.success) {
                this.updateShopStatusUI(shopId, 'approved');
                this.showNotification(data.message || 'Shop approved successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to approve shop', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    async rejectShop(shopId) {
        if (this.isProcessing) return;
        if (!confirm('Are you sure you want to reject this shop?')) return;
        
        this.isProcessing = true;

        try {
            const data = await this.request('includes/admin_actions.php', {
                body: `action=reject_shop&shop_id=${shopId}`
            });

            if (data.success) {
                this.updateShopStatusUI(shopId, 'rejected');
                this.showNotification(data.message || 'Shop rejected successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to reject shop', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    async suspendShop(shopId) {
        if (this.isProcessing) return;
        if (!confirm('Are you sure you want to suspend this shop?')) return;
        
        this.isProcessing = true;

        try {
            const data = await this.request('includes/admin_actions.php', {
                body: `action=suspend_shop&shop_id=${shopId}`
            });

            if (data.success) {
                this.updateShopStatusUI(shopId, 'suspended');
                this.showNotification(data.message || 'Shop suspended successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to suspend shop', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    // User Management
    async toggleUserStatus(userId, currentStatus) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const data = await this.request('includes/admin_actions.php', {
                body: `action=toggle_user_status&user_id=${userId}&status=${newStatus}`
            });

            if (data.success) {
                this.updateUserStatusUI(userId, newStatus);
                this.showNotification(data.message || 'User status updated successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to update user status', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    async deleteUser(userId) {
        if (this.isProcessing) return;
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return;
        
        this.isProcessing = true;

        try {
            const data = await this.request('includes/admin_actions.php', {
                body: `action=delete_user&user_id=${userId}`
            });

            if (data.success) {
                this.removeUserFromUI(userId);
                this.showNotification(data.message || 'User deleted successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to delete user', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    // Product Management
    async toggleProductStatus(productId, currentStatus) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const data = await this.request('includes/admin_actions.php', {
                body: `action=toggle_product_status&product_id=${productId}&status=${newStatus}`
            });

            if (data.success) {
                this.updateProductStatusUI(productId, newStatus);
                this.showNotification(data.message || 'Product status updated successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to update product status', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    async deleteProduct(productId) {
        if (this.isProcessing) return;
        if (!confirm('Are you sure you want to delete this product?')) return;
        
        this.isProcessing = true;

        try {
            const data = await this.request('includes/admin_actions.php', {
                body: `action=delete_product&product_id=${productId}`
            });

            if (data.success) {
                this.removeProductFromUI(productId);
                this.showNotification(data.message || 'Product deleted successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to delete product', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    // System Settings
    async updateSystemSetting(setting, value) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const data = await this.request('includes/admin_actions.php', {
                body: `action=update_setting&setting=${setting}&value=${encodeURIComponent(value)}`
            });

            if (data.success) {
                this.showNotification(data.message || 'Setting updated successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to update setting', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    // UI Update Methods
    updateShopStatusUI(shopId, status) {
        const statusElements = document.querySelectorAll(`[data-shop-id="${shopId}"] .shop-status`);
        statusElements.forEach(element => {
            element.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            element.className = `shop-status status-${status}`;
        });

        const actionButtons = document.querySelectorAll(`[data-shop-id="${shopId}"] .shop-actions`);
        actionButtons.forEach(container => {
            this.updateShopActions(container, shopId, status);
        });
    }

    updateShopActions(container, shopId, status) {
        let actionsHTML = '';
        
        switch (status) {
            case 'pending':
                actionsHTML = `
                    <button class="btn btn-success btn-sm" onclick="adminAjax.approveShop(${shopId})">
                        Approve
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="adminAjax.rejectShop(${shopId})">
                        Reject
                    </button>
                `;
                break;
            case 'approved':
                actionsHTML = `
                    <button class="btn btn-warning btn-sm" onclick="adminAjax.suspendShop(${shopId})">
                        Suspend
                    </button>
                `;
                break;
            case 'suspended':
                actionsHTML = `
                    <button class="btn btn-success btn-sm" onclick="adminAjax.approveShop(${shopId})">
                        Reactivate
                    </button>
                `;
                break;
            case 'rejected':
                actionsHTML = '<span class="text-muted">Rejected</span>';
                break;
        }
        
        container.innerHTML = actionsHTML;
    }

    updateUserStatusUI(userId, status) {
        const statusElements = document.querySelectorAll(`[data-user-id="${userId}"] .user-status`);
        statusElements.forEach(element => {
            element.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            element.className = `user-status status-${status}`;
        });

        const toggleButtons = document.querySelectorAll(`[data-user-id="${userId}"] .toggle-user-btn`);
        toggleButtons.forEach(button => {
            button.textContent = status === 'active' ? 'Deactivate' : 'Activate';
            button.className = `btn toggle-user-btn btn-${status === 'active' ? 'warning' : 'success'} btn-sm`;
            button.setAttribute('onclick', `adminAjax.toggleUserStatus(${userId}, '${status}')`);
        });
    }

    removeUserFromUI(userId) {
        const userElements = document.querySelectorAll(`[data-user-id="${userId}"]`);
        userElements.forEach(element => {
            if (element.classList.contains('user-row') || element.classList.contains('user-card')) {
                element.remove();
            }
        });
    }

    updateProductStatusUI(productId, status) {
        const statusElements = document.querySelectorAll(`[data-product-id="${productId}"] .product-status`);
        statusElements.forEach(element => {
            element.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            element.className = `product-status status-${status}`;
        });

        const toggleButtons = document.querySelectorAll(`[data-product-id="${productId}"] .toggle-product-btn`);
        toggleButtons.forEach(button => {
            button.textContent = status === 'active' ? 'Deactivate' : 'Activate';
            button.className = `btn toggle-product-btn btn-${status === 'active' ? 'warning' : 'success'} btn-sm`;
            button.setAttribute('onclick', `adminAjax.toggleProductStatus(${productId}, '${status}')`);
        });
    }

    removeProductFromUI(productId) {
        const productElements = document.querySelectorAll(`[data-product-id="${productId}"]`);
        productElements.forEach(element => {
            if (element.classList.contains('product-row') || element.classList.contains('product-card')) {
                element.remove();
            }
        });
    }

    // Analytics and Reports
    async loadSystemAnalytics(period = 'week') {
        try {
            const data = await this.request('includes/admin_analytics.php', {
                body: `action=get_system_analytics&period=${period}`
            });

            if (data.success) {
                this.updateSystemAnalyticsUI(data.analytics);
            }
            
            return data;
        } catch (error) {
            console.error('System Analytics Error:', error);
        }
    }

    updateSystemAnalyticsUI(analytics) {
        // Update total users
        const usersElement = document.getElementById('total-users');
        if (usersElement && analytics.total_users !== undefined) {
            usersElement.textContent = analytics.total_users;
        }

        // Update total shops
        const shopsElement = document.getElementById('total-shops');
        if (shopsElement && analytics.total_shops !== undefined) {
            shopsElement.textContent = analytics.total_shops;
        }

        // Update total products
        const productsElement = document.getElementById('total-products');
        if (productsElement && analytics.total_products !== undefined) {
            productsElement.textContent = analytics.total_products;
        }

        // Update total revenue
        const revenueElement = document.getElementById('total-revenue');
        if (revenueElement && analytics.total_revenue !== undefined) {
            revenueElement.textContent = `Rs. ${analytics.total_revenue}`;
        }
    }
}

    // Bulk Operations
    async bulkUpdateShops(shopIds, action) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const data = await this.request('includes/admin_actions.php', {
                body: `action=bulk_update_shops&shop_ids=${shopIds.join(',')}&bulk_action=${action}`
            });

            if (data.success) {
                this.updateBulkShopsUI(shopIds, action);
                this.showNotification(data.message || 'Bulk update completed successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to perform bulk update', 'error');
            }

            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    updateBulkShopsUI(shopIds, action) {
        shopIds.forEach(shopId => {
            switch (action) {
                case 'approve':
                    this.updateShopStatusUI(shopId, 'approved');
                    break;
                case 'suspend':
                    this.updateShopStatusUI(shopId, 'suspended');
                    break;
                case 'reject':
                    this.updateShopStatusUI(shopId, 'rejected');
                    break;
            }
        });
    }
}

// Create global admin AJAX instance
const adminAjax = new AdminAjaxManager();

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin AJAX Manager initialized');

    // Load analytics if on admin dashboard
    if (document.getElementById('admin-dashboard')) {
        adminAjax.loadSystemAnalytics();
    }
});
