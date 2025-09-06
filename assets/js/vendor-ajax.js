/**
 * DesiDukaan - Vendor AJAX System
 * Handles vendor-specific AJAX operations
 */

class VendorAjaxManager extends AjaxManager {
    constructor() {
        super();
    }

    // Product Management
    async toggleProductStatus(productId, currentStatus) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const data = await this.request('includes/vendor_actions.php', {
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
            const data = await this.request('includes/vendor_actions.php', {
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

    async updateProductStock(productId, newStock) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const data = await this.request('includes/vendor_actions.php', {
                body: `action=update_stock&product_id=${productId}&stock=${newStock}`
            });

            if (data.success) {
                this.updateStockUI(productId, newStock);
                this.showNotification(data.message || 'Stock updated successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to update stock', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    // Order Management
    async updateOrderStatus(orderId, newStatus) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const data = await this.request('includes/vendor_actions.php', {
                body: `action=update_order_status&order_id=${orderId}&status=${newStatus}`
            });

            if (data.success) {
                this.updateOrderStatusUI(orderId, newStatus);
                this.showNotification(data.message || 'Order status updated successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to update order status', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    // UI Update Methods
    updateProductStatusUI(productId, status) {
        const statusElements = document.querySelectorAll(`[data-product-id="${productId}"] .product-status`);
        statusElements.forEach(element => {
            element.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            element.className = `product-status status-${status}`;
        });

        const toggleButtons = document.querySelectorAll(`[data-product-id="${productId}"] .toggle-status-btn`);
        toggleButtons.forEach(button => {
            button.textContent = status === 'active' ? 'Deactivate' : 'Activate';
            button.className = `btn toggle-status-btn btn-${status === 'active' ? 'warning' : 'success'}`;
            button.setAttribute('onclick', `vendorAjax.toggleProductStatus(${productId}, '${status}')`);
        });
    }

    removeProductFromUI(productId) {
        const productElements = document.querySelectorAll(`[data-product-id="${productId}"]`);
        productElements.forEach(element => {
            if (element.classList.contains('product-row') || element.classList.contains('product-card')) {
                element.remove();
            }
        });

        // Update product count if displayed
        const countElements = document.querySelectorAll('.product-count');
        countElements.forEach(element => {
            const currentCount = parseInt(element.textContent) || 0;
            element.textContent = Math.max(0, currentCount - 1);
        });
    }

    updateStockUI(productId, stock) {
        const stockElements = document.querySelectorAll(`[data-product-id="${productId}"] .product-stock`);
        stockElements.forEach(element => {
            element.textContent = stock;
            
            // Update stock status class
            element.className = 'product-stock';
            if (stock <= 0) {
                element.classList.add('out-of-stock');
            } else if (stock <= 10) {
                element.classList.add('low-stock');
            } else {
                element.classList.add('in-stock');
            }
        });
    }

    updateOrderStatusUI(orderId, status) {
        const statusElements = document.querySelectorAll(`[data-order-id="${orderId}"] .order-status`);
        statusElements.forEach(element => {
            element.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            element.className = `order-status status-${status}`;
        });

        const actionButtons = document.querySelectorAll(`[data-order-id="${orderId}"] .order-actions`);
        actionButtons.forEach(container => {
            // Update available actions based on new status
            this.updateOrderActions(container, orderId, status);
        });
    }

    updateOrderActions(container, orderId, status) {
        let actionsHTML = '';
        
        switch (status) {
            case 'pending':
                actionsHTML = `
                    <button class="btn btn-success btn-sm" onclick="vendorAjax.updateOrderStatus(${orderId}, 'confirmed')">
                        Confirm
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="vendorAjax.updateOrderStatus(${orderId}, 'cancelled')">
                        Cancel
                    </button>
                `;
                break;
            case 'confirmed':
                actionsHTML = `
                    <button class="btn btn-primary btn-sm" onclick="vendorAjax.updateOrderStatus(${orderId}, 'shipped')">
                        Ship
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="vendorAjax.updateOrderStatus(${orderId}, 'cancelled')">
                        Cancel
                    </button>
                `;
                break;
            case 'shipped':
                actionsHTML = `
                    <button class="btn btn-success btn-sm" onclick="vendorAjax.updateOrderStatus(${orderId}, 'delivered')">
                        Mark Delivered
                    </button>
                `;
                break;
            case 'delivered':
            case 'cancelled':
                actionsHTML = '<span class="text-muted">No actions available</span>';
                break;
        }
        
        container.innerHTML = actionsHTML;
    }

    // Bulk Operations
    async bulkUpdateProducts(productIds, action, value = null) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const data = await this.request('includes/vendor_actions.php', {
                body: `action=bulk_update&product_ids=${productIds.join(',')}&bulk_action=${action}&value=${value || ''}`
            });

            if (data.success) {
                this.updateBulkUI(productIds, action, value);
                this.showNotification(data.message || 'Bulk update completed successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to perform bulk update', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    updateBulkUI(productIds, action, value) {
        productIds.forEach(productId => {
            switch (action) {
                case 'activate':
                    this.updateProductStatusUI(productId, 'active');
                    break;
                case 'deactivate':
                    this.updateProductStatusUI(productId, 'inactive');
                    break;
                case 'delete':
                    this.removeProductFromUI(productId);
                    break;
                case 'update_stock':
                    this.updateStockUI(productId, value);
                    break;
            }
        });
    }

    // Analytics and Reports
    async loadAnalytics(period = 'week') {
        try {
            const data = await this.request('includes/vendor_analytics.php', {
                body: `action=get_analytics&period=${period}`
            });

            if (data.success) {
                this.updateAnalyticsUI(data.analytics);
            }
            
            return data;
        } catch (error) {
            console.error('Analytics Error:', error);
        }
    }

    updateAnalyticsUI(analytics) {
        // Update sales metrics
        const salesElement = document.getElementById('total-sales');
        if (salesElement && analytics.total_sales !== undefined) {
            salesElement.textContent = `Rs. ${analytics.total_sales}`;
        }

        // Update order count
        const ordersElement = document.getElementById('total-orders');
        if (ordersElement && analytics.total_orders !== undefined) {
            ordersElement.textContent = analytics.total_orders;
        }

        // Update product count
        const productsElement = document.getElementById('total-products');
        if (productsElement && analytics.total_products !== undefined) {
            productsElement.textContent = analytics.total_products;
        }

        // Update revenue chart if exists
        if (analytics.revenue_chart && window.updateRevenueChart) {
            window.updateRevenueChart(analytics.revenue_chart);
        }
    }
}

// Create global vendor AJAX instance
const vendorAjax = new VendorAjaxManager();

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Vendor AJAX Manager initialized');
    
    // Load analytics if on dashboard
    if (document.getElementById('vendor-dashboard')) {
        vendorAjax.loadAnalytics();
    }
});
