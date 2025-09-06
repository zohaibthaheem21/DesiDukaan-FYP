/**
 * DesiDukaan - Global AJAX Utility System
 * Handles all AJAX operations across the website without page refreshes
 */

class AjaxManager {
    constructor() {
        this.baseUrl = this.getBaseUrl();
        this.isProcessing = false;
        this.requestQueue = [];
    }

    getBaseUrl() {
        const path = window.location.pathname;
        if (path.includes('/customer/')) return '../';
        if (path.includes('/vendor/')) return '../';
        if (path.includes('/admin/')) return '../';
        return '';
    }

    // Generic AJAX request handler
    async request(url, options = {}) {
        const defaultOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };

        const config = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(this.baseUrl + url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return await response.text();
            }
        } catch (error) {
            console.error('AJAX Error:', error);
            this.showNotification('Network error occurred', 'error');
            throw error;
        }
    }

    // Cart Operations
    async addToCart(productId, quantity = 1) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const data = await this.request('includes/cart.php', {
                body: `action=add_to_cart&product_id=${productId}&quantity=${quantity}`
            });

            if (data.success) {
                this.updateCartUI(productId, true, data);
                this.showNotification(data.message || 'Added to cart successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to add to cart', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    async removeFromCart(productId) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const data = await this.request('includes/cart.php', {
                body: `action=remove_from_cart&product_id=${productId}`
            });

            if (data.success) {
                this.updateCartUI(productId, false, data);

                // Remove cart item from DOM if on cart page
                const cartItem = document.querySelector(`[data-product-id="${productId}"].cart-item`);
                if (cartItem) {
                    cartItem.remove();

                    // Check if cart is empty
                    const remainingItems = document.querySelectorAll('.cart-item');
                    if (remainingItems.length === 0) {
                        location.reload(); // Reload to show empty cart message
                    }
                }

                this.showNotification(data.message || 'Removed from cart successfully!', 'success');
            } else {
                this.showNotification(data.message || 'Failed to remove from cart', 'error');
            }

            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    async updateCartQuantity(productId, quantity) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const data = await this.request('includes/cart.php', {
                body: `action=update_quantity&product_id=${productId}&quantity=${quantity}`
            });

            if (data.success) {
                this.updateQuantityUI(productId, quantity, data);
                this.updateCartCount(data.cart_count);
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    // Wishlist Operations
    async toggleWishlist(productId) {
        if (this.isProcessing) return;
        this.isProcessing = true;

        try {
            const data = await this.request('includes/wishlist.php', {
                body: `action=toggle_wishlist&product_id=${productId}`
            });

            if (data.success) {
                this.updateWishlistUI(productId, data.in_wishlist, data);
                const actionText = data.action === 'added' ? 'Added to wishlist!' : 'Removed from wishlist!';
                this.showNotification(data.message || actionText, 'success');
            } else {
                this.showNotification(data.message || 'Failed to update wishlist', 'error');
            }
            
            return data;
        } finally {
            this.isProcessing = false;
        }
    }

    // UI Update Methods
    updateCartUI(productId, inCart, data) {
        // Update cart count
        if (data.cart_count !== undefined) {
            this.updateCartCount(data.cart_count);
        }

        // Update all cart buttons for this product
        const buttons = document.querySelectorAll(`[data-product-id="${productId}"]`);
        buttons.forEach(button => {
            if (button.tagName === 'BUTTON') {
                if (inCart) {
                    button.textContent = 'Remove from Cart';
                    button.className = 'btn-modern btn-in-cart';
                    button.setAttribute('onclick', `ajaxManager.removeFromCart(${productId})`);
                } else {
                    button.textContent = 'Add to Cart';
                    button.className = 'btn-modern btn-add-cart';
                    button.setAttribute('onclick', `ajaxManager.addToCart(${productId})`);
                }
            }
        });

        // Update modal buttons if open
        const modalButtons = document.querySelectorAll('#productModal [data-product-id="' + productId + '"]');
        modalButtons.forEach(button => {
            if (button.tagName === 'BUTTON') {
                if (inCart) {
                    button.textContent = 'Remove from Cart';
                    button.className = 'btn-modal btn-in-cart';
                    button.setAttribute('onclick', `ajaxManager.removeFromCart(${productId})`);
                } else {
                    button.textContent = 'Add to Cart';
                    button.className = 'btn-modal btn-add-cart';
                    button.setAttribute('onclick', `ajaxManager.addToCartWithQuantity(${productId})`);
                }
            }
        });
    }

    updateWishlistUI(productId, inWishlist, data) {
        // Update wishlist count if provided
        if (data.wishlist_count !== undefined) {
            this.updateWishlistCount(data.wishlist_count);
        }

        // Update all wishlist buttons for this product
        const buttons = document.querySelectorAll(`[data-product-id="${productId}"].wishlist-btn, .wishlist-btn[data-product-id="${productId}"]`);
        buttons.forEach(button => {
            const heartIcon = button.querySelector('.heart-icon');
            if (heartIcon) {
                heartIcon.textContent = inWishlist ? '❤️' : '🤍';
                if (inWishlist) {
                    button.classList.add('in-wishlist');
                } else {
                    button.classList.remove('in-wishlist');
                }
            }
        });

        // Update modal wishlist buttons
        const modalButtons = document.querySelectorAll('#productModal .btn-wishlist');
        modalButtons.forEach(button => {
            const heartIcon = button.querySelector('.heart-icon');
            const wishlistText = button.querySelector('.wishlist-text');
            
            if (heartIcon) {
                heartIcon.textContent = inWishlist ? '❤️' : '🤍';
            }
            
            if (wishlistText) {
                wishlistText.textContent = inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist';
            }
        });
    }

    updateQuantityUI(productId, quantity, data) {
        // Update quantity display
        const quantityDisplays = document.querySelectorAll(`[data-product-id="${productId}"] .quantity-display`);
        quantityDisplays.forEach(display => {
            display.textContent = quantity;
        });

        // Update subtotal if provided
        if (data.subtotal) {
            const subtotalElements = document.querySelectorAll(`[data-product-id="${productId}"] .subtotal-amount`);
            subtotalElements.forEach(element => {
                element.textContent = `Rs. ${data.subtotal}`;
            });
        }

        // Update total if provided
        if (data.total) {
            const totalElements = document.querySelectorAll('.cart-total');
            totalElements.forEach(element => {
                element.textContent = `Rs. ${data.total}`;
            });
        }
    }

    updateCartCount(count) {
        const cartCountElements = document.querySelectorAll('#cart-count, .cart-count');
        cartCountElements.forEach(element => {
            element.textContent = count;
            if (count > 0) {
                element.classList.remove('empty');
            } else {
                element.classList.add('empty');
            }
        });
    }

    updateWishlistCount(count) {
        const wishlistCountElements = document.querySelectorAll('.wishlist-count');
        wishlistCountElements.forEach(element => {
            element.textContent = count;
        });
    }

    // Notification System
    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.ajax-notification');
        existingNotifications.forEach(notification => notification.remove());
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `ajax-notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${this.getNotificationIcon(type)}</span>
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            background: ${this.getNotificationColor(type)};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            word-wrap: break-word;
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 4000);
    }

    getNotificationIcon(type) {
        const icons = {
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️'
        };
        return icons[type] || 'ℹ️';
    }

    getNotificationColor(type) {
        const colors = {
            'success': '#28a745',
            'error': '#dc3545',
            'warning': '#ffc107',
            'info': '#17a2b8'
        };
        return colors[type] || '#17a2b8';
    }

    // Helper method for modal add to cart with quantity
    async addToCartWithQuantity(productId) {
        const quantityInput = document.getElementById('quantity');
        const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : 1;
        return await this.addToCart(productId, quantity);
    }

    // Real-time state synchronization
    async syncCartState() {
        try {
            const data = await this.request('includes/cart.php', {
                body: 'action=get_cart_count'
            });

            if (data.success) {
                this.updateCartCount(data.cart_count);
            }
        } catch (error) {
            console.error('Cart sync error:', error);
        }
    }

    async syncWishlistState() {
        try {
            const data = await this.request('includes/wishlist.php', {
                body: 'action=get_wishlist_count'
            });

            if (data.success) {
                this.updateWishlistCount(data.wishlist_count);
            }
        } catch (error) {
            console.error('Wishlist sync error:', error);
        }
    }

    // Auto-sync states periodically
    startAutoSync() {
        // Sync every 30 seconds
        setInterval(() => {
            this.syncCartState();
            this.syncWishlistState();
        }, 30000);
    }

    // Enhanced error handling
    handleError(error, operation) {
        console.error(`${operation} Error:`, error);

        if (error.message.includes('401') || error.message.includes('403')) {
            this.showNotification('Session expired. Please login again.', 'error');
            setTimeout(() => {
                window.location.href = this.baseUrl + 'auth/login.php';
            }, 2000);
        } else if (error.message.includes('500')) {
            this.showNotification('Server error occurred. Please try again.', 'error');
        } else {
            this.showNotification('Network error. Please check your connection.', 'error');
        }
    }

    // Batch operations for better performance
    async batchUpdateUI(updates) {
        updates.forEach(update => {
            switch (update.type) {
                case 'cart':
                    this.updateCartUI(update.productId, update.inCart, update.data);
                    break;
                case 'wishlist':
                    this.updateWishlistUI(update.productId, update.inWishlist, update.data);
                    break;
                case 'quantity':
                    this.updateQuantityUI(update.productId, update.quantity, update.data);
                    break;
            }
        });
    }
}

// Create global instance
const ajaxManager = new AjaxManager();

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('AJAX Manager initialized');

    // Start auto-sync for logged-in users
    if (document.body.classList.contains('logged-in')) {
        ajaxManager.startAutoSync();
    }

    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Page became visible, sync states
            ajaxManager.syncCartState();
            ajaxManager.syncWishlistState();
        }
    });
});
