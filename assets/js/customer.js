/**
 * DesiDukaan - Enhanced Customer Dashboard JavaScript
 * Version: 2.0 - Improved reliability and user experience
 */

// Global state management
let isProcessing = false;
const DEBOUNCE_DELAY = 300;

// Initialize wishlist states on page load
function initializeWishlistStates() {
    const wishlistButtons = document.querySelectorAll('.wishlist-btn[data-product-id]');

    wishlistButtons.forEach(btn => {
        const productId = btn.getAttribute('data-product-id');
        if (productId) {
            // Check current wishlist state
            fetch((window.location.pathname.includes('/customer/') ? '../' : '') + 'includes/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=check_wishlist&product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const heartIcon = btn.querySelector('.heart-icon');
                    if (heartIcon) {
                        if (data.in_wishlist) {
                            heartIcon.textContent = '❤️';
                            btn.classList.add('in-wishlist');
                        } else {
                            heartIcon.textContent = '🤍';
                            btn.classList.remove('in-wishlist');
                        }
                    }
                }
            })
            .catch(error => console.error('Error checking wishlist state:', error));
        }
    });
}

// Enhanced notification system
function showEnhancedNotification(message, type = 'info', duration = 4000) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.enhanced-notification');
    existingNotifications.forEach(notification => notification.remove());

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `enhanced-notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${getNotificationIcon(type)}</span>
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
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        animation: slideInRight 0.5s ease;
        max-width: 400px;
        word-wrap: break-word;
    `;

    document.body.appendChild(notification);

    // Auto remove after duration
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.5s ease';
            setTimeout(() => notification.remove(), 500);
        }
    }, duration);
}

function getNotificationIcon(type) {
    const icons = {
        'success': '✅',
        'error': '❌',
        'warning': '⚠️',
        'info': 'ℹ️'
    };
    return icons[type] || 'ℹ️';
}

function getNotificationColor(type) {
    const colors = {
        'success': 'linear-gradient(135deg, #28a745, #20c997)',
        'error': 'linear-gradient(135deg, #dc3545, #c82333)',
        'warning': 'linear-gradient(135deg, #ffc107, #e0a800)',
        'info': 'linear-gradient(135deg, #17a2b8, #138496)'
    };
    return colors[type] || colors.info;
}

// Enhanced Cart Management Functions
function addToCart(productId, quantity = 1) {
    if (isProcessing) {
        showNotification('Please wait, processing your request...', 'info');
        return;
    }

    isProcessing = true;
    showLoading();

    // Determine the correct path based on current location
    const basePath = window.location.pathname.includes('/customer/') ? '../' : '';

    fetch(basePath + 'includes/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add_to_cart&product_id=${productId}&quantity=${quantity}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        isProcessing = false;

        if (data.success) {
            showEnhancedNotification(data.message || 'Added to cart successfully!', 'success');
            updateCartCount(data.cart_count);

            // Update button state - find all buttons for this product
            const buttons = document.querySelectorAll(`[data-product-id="${productId}"]`);
            buttons.forEach(button => {
                if (button.tagName === 'BUTTON') {
                    button.textContent = 'Remove from Cart';
                    button.className = 'btn-modern btn-in-cart';
                    button.setAttribute('onclick', `removeFromCart(${productId})`);
                }
            });
        } else {
            showEnhancedNotification(data.message || 'Failed to add to cart', 'error');
            // If login required, redirect to login page
            if (data.message && data.message.includes('login')) {
                setTimeout(() => {
                    window.location.href = (window.location.pathname.includes('/customer/') ? '../' : '') + 'auth/login.php';
                }, 2000);
            }
        }
    })
    .catch(error => {
        hideLoading();
        isProcessing = false;
        console.error('Cart Error:', error);
        showNotification('Network error. Please check your connection and try again.', 'error');
    });
}

function removeFromCart(productId) {
    if (isProcessing) {
        showNotification('Please wait, processing your request...', 'info');
        return;
    }

    isProcessing = true;
    showLoading();

    // Determine the correct path based on current location
    const basePath = window.location.pathname.includes('/customer/') ? '../' : '';

    fetch(basePath + 'includes/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove_from_cart&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        isProcessing = false;

        if (data.success) {
            showEnhancedNotification(data.message || 'Removed from cart successfully!', 'success');
            updateCartCount(data.cart_count);

            // Update button state - find all buttons for this product
            const buttons = document.querySelectorAll(`[data-product-id="${productId}"]`);
            buttons.forEach(button => {
                if (button.tagName === 'BUTTON') {
                    button.textContent = 'Add to Cart';
                    button.className = 'btn-modern btn-add-cart';
                    button.setAttribute('onclick', `addToCart(${productId})`);
                }
            });
        } else {
            showNotification(data.message || 'Failed to remove from cart', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        isProcessing = false;
        console.error('Cart Error:', error);
        showNotification('Network error. Please try again.', 'error');
    });
}

function updateCartCount(count = null) {
    if (count === null) {
        // Determine the correct path based on current location
        const basePath = window.location.pathname.includes('/customer/') ? '../' : '';

        // Fetch current cart count
        fetch(basePath + 'includes/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_cart_count'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCountDisplay(data.cart_count);
            }
        })
        .catch(error => console.error('Error fetching cart count:', error));
    } else {
        updateCartCountDisplay(count);
    }
}

function updateCartCountDisplay(count) {
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = count;
        if (count > 0) {
            cartCountElement.classList.remove('empty');
        } else {
            cartCountElement.classList.add('empty');
        }
    }
}

function updateProductCardState(productId, inCart) {
    const productCard = document.querySelector(`[data-product-id="${productId}"]`);
    if (productCard) {
        const addButton = productCard.querySelector('.btn-add-cart');
        const inCartButton = productCard.querySelector('.btn-in-cart');
        
        if (inCart) {
            if (addButton) {
                addButton.className = 'btn-modern btn-in-cart';
                addButton.innerHTML = '<span class="btn-icon">✅</span><span class="btn-text">In Cart</span>';
                addButton.onclick = () => removeFromCart(productId);
            }
        } else {
            if (inCartButton) {
                inCartButton.className = 'btn-modern btn-add-cart';
                inCartButton.innerHTML = '<span class="btn-icon">🛒</span><span class="btn-text">Add to Cart</span>';
                inCartButton.onclick = () => addToCart(productId);
            }
        }
    }
}

// Enhanced Wishlist Functions
function toggleWishlist(productId) {
    if (isProcessing) {
        showNotification('Please wait, processing your request...', 'info');
        return;
    }

    isProcessing = true;
    showLoading();

    // Determine the correct path based on current location
    const basePath = window.location.pathname.includes('/customer/') ? '../' : '';

    fetch(basePath + 'includes/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=toggle_wishlist&product_id=${productId}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        hideLoading();
        isProcessing = false;

        if (data.success) {
            const actionText = data.action === 'added' ? 'Added to wishlist! ❤️' : 'Removed from wishlist! 💔';
            showNotification(data.message || actionText, 'success');

            // Update ALL wishlist buttons for this product across the entire page
            const allWishlistBtns = document.querySelectorAll(`
                button[data-product-id="${productId}"].wishlist-btn,
                [data-product-id="${productId}"] .wishlist-btn,
                .wishlist-btn[data-product-id="${productId}"]
            `);

            allWishlistBtns.forEach(btn => {
                const heartIcon = btn.querySelector('.heart-icon');
                if (heartIcon) {
                    // Add animation effect
                    btn.classList.add('wishlist-animation');
                    setTimeout(() => {
                        btn.classList.remove('wishlist-animation');
                    }, 600);

                    if (data.in_wishlist) {
                        heartIcon.textContent = '❤️';
                        btn.classList.add('in-wishlist');
                    } else {
                        heartIcon.textContent = '🤍';
                        btn.classList.remove('in-wishlist');
                    }
                }
            });

            // Also update modal wishlist buttons if modal is open
            const modalWishlistBtns = document.querySelectorAll(`#productModal .btn-wishlist, #productModal .btn-modal.btn-wishlist`);
            modalWishlistBtns.forEach(btn => {
                const heartIcon = btn.querySelector('.heart-icon');
                const wishlistText = btn.querySelector('.wishlist-text');

                // Add animation effect to modal button too
                btn.classList.add('wishlist-animation');
                setTimeout(() => {
                    btn.classList.remove('wishlist-animation');
                }, 600);

                if (heartIcon) {
                    if (data.in_wishlist) {
                        heartIcon.textContent = '❤️';
                        btn.classList.add('in-wishlist');
                    } else {
                        heartIcon.textContent = '🤍';
                        btn.classList.remove('in-wishlist');
                    }
                }

                if (wishlistText) {
                    if (data.in_wishlist) {
                        wishlistText.textContent = 'Remove from Wishlist';
                    } else {
                        wishlistText.textContent = 'Add to Wishlist';
                    }
                }
            });
        } else {
            showNotification(data.message || 'Failed to update wishlist', 'error');
            // If login required, redirect to login page
            if (data.message && data.message.includes('login')) {
                setTimeout(() => {
                    window.location.href = (window.location.pathname.includes('/customer/') ? '../' : '') + 'auth/login.php';
                }, 2000);
            }
        }
    })
    .catch(error => {
        hideLoading();
        isProcessing = false;
        console.error('Wishlist Error:', error);
        showNotification('Network error. Please check your connection and try again.', 'error');
    });
}

// Enhanced Quantity Control Functions
function updateCartItemQuantity(productId, newQuantity) {
    if (newQuantity <= 0) {
        removeFromCart(productId);
        return;
    }

    if (isProcessing) {
        showNotification('Please wait, processing your request...', 'info');
        return;
    }

    isProcessing = true;
    showLoading();

    const basePath = window.location.pathname.includes('/customer/') ? '../' : '';

    fetch(basePath + 'includes/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_quantity&product_id=${productId}&quantity=${newQuantity}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        isProcessing = false;

        if (data.success) {
            showNotification('Quantity updated successfully! 📦', 'success');

            // Update quantity display without full page reload
            const quantityDisplay = document.querySelector(`[data-product-id="${productId}"] .quantity-display`);
            if (quantityDisplay) {
                quantityDisplay.textContent = newQuantity;
                quantityDisplay.classList.add('quantity-update-animation');
                setTimeout(() => {
                    quantityDisplay.classList.remove('quantity-update-animation');
                }, 600);
            }

            // Update cart count and total
            updateCartCount(data.cart_count);

            // Update subtotal if element exists
            const subtotalElement = document.querySelector(`[data-product-id="${productId}"] .subtotal-amount`);
            if (subtotalElement && data.cart_total) {
                // Calculate new subtotal (this would need product price)
                const priceElement = document.querySelector(`[data-product-id="${productId}"] .cart-item-price`);
                if (priceElement) {
                    const price = parseFloat(priceElement.textContent.replace(/[^\d.]/g, ''));
                    const newSubtotal = price * newQuantity;
                    subtotalElement.textContent = `Rs. ${newSubtotal.toFixed(2)}`;
                }
            }
        } else {
            showNotification('Failed to update quantity', 'error');
        }
    })
    .catch(error => {
        hideLoading();
        isProcessing = false;
        console.error('Quantity Update Error:', error);
        showNotification('Network error. Please try again.', 'error');
    });
}

// Product Modal Functions
function openProductModal(productId) {
    const modal = document.getElementById('productModal');
    const modalContent = document.getElementById('modalContent');

    // Show loading in modal
    modalContent.innerHTML = `
        <div style="padding: 4rem; text-align: center;">
            <div class="loading-spinner"></div>
            <p>Loading product details...</p>
        </div>
    `;

    modal.classList.add('active');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';


    // Fetch product details
    fetch(`get_product_details.php?id=${productId}`)
        .then(response => response.text())
        .then(html => {
            modalContent.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            modalContent.innerHTML = `
                <div style="padding: 4rem; text-align: center;">
                    <h3>Error loading product details</h3>
                    <p>Please try again later.</p>
                </div>
            `;
        });
}

// Buy Now Function - Global scope for modal access
window.buyNowProduct = function(productId) {
    const quantityInput = document.getElementById('quantity');
    const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : 1;
    console.log('Buying now:', productId, 'quantity:', quantity);
    // Redirect to buy now page with product ID and quantity
    window.location.href = `buy_now.php?product_id=${productId}&quantity=${quantity}`;
};

// Quantity Control Functions - Global scope for modal access
window.changeQuantity = function(delta) {
    const quantityInput = document.getElementById('quantity');
    const currentValue = parseInt(quantityInput.value);
    const newValue = currentValue + delta;
    const max = parseInt(quantityInput.max);
    const min = parseInt(quantityInput.min);

    if (newValue >= min && newValue <= max) {
        quantityInput.value = newValue;
    }
};

window.changeModalImage = function(src, thumbnail) {
    const mainImage = document.getElementById('modalMainImage');
    const thumbnails = document.querySelectorAll('.thumbnail-image');

    mainImage.src = src;

    thumbnails.forEach(thumb => thumb.classList.remove('active'));
    thumbnail.classList.add('active');
};

function closeProductModal() {
    const modal = document.getElementById('productModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';


}

// Filter and Search Functions
function updateFilters() {
    const sortSelect = document.querySelector('select[name="sort"]');
    const searchInput = document.querySelector('input[name="search"]');
    
    const params = new URLSearchParams();
    
    if (searchInput && searchInput.value) {
        params.append('search', searchInput.value);
    }
    
    if (sortSelect && sortSelect.value) {
        params.append('sort', sortSelect.value);
    }
    
    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.location.href = newUrl;
}

// Notification System
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${getNotificationIcon(type)}</span>
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
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        border-left: 4px solid ${getNotificationColor(type)};
        animation: slideInRight 0.3s ease-out;
        max-width: 400px;
    `;
    
    const content = notification.querySelector('.notification-content');
    content.style.cssText = `
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.5rem;
    `;
    
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.style.cssText = `
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: #999;
        margin-left: auto;
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

function getNotificationIcon(type) {
    switch (type) {
        case 'success': return '✅';
        case 'error': return '❌';
        case 'warning': return '⚠️';
        default: return 'ℹ️';
    }
}

function getNotificationColor(type) {
    switch (type) {
        case 'success': return '#28a745';
        case 'error': return '#dc3545';
        case 'warning': return '#ffc107';
        default: return '#007bff';
    }
}

// Loading Functions
function showLoading() {
    const existingLoader = document.querySelector('.global-loader');
    if (existingLoader) return;
    
    const loader = document.createElement('div');
    loader.className = 'global-loader';
    loader.innerHTML = `
        <div class="loader-backdrop">
            <div class="loader-content">
                <div class="loading-spinner"></div>
                <p>Processing...</p>
            </div>
        </div>
    `;
    
    loader.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    const backdrop = loader.querySelector('.loader-backdrop');
    backdrop.style.cssText = `
        background: rgba(0, 0, 0, 0.5);
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    `;
    
    const content = loader.querySelector('.loader-content');
    content.style.cssText = `
        background: white;
        padding: 2rem;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    `;
    
    document.body.appendChild(loader);
}

function hideLoading() {
    const loader = document.querySelector('.global-loader');
    if (loader) {
        loader.remove();
    }
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1rem;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Update cart count
    updateCartCount();
    
    // Add keyboard navigation for modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeProductModal();
        }
    });
    
    // Handle sort change
    const sortSelect = document.querySelector('select[name="sort"]');
    if (sortSelect) {
        sortSelect.addEventListener('change', updateFilters);
    }
});

// Function to refresh all wishlist states
function refreshWishlistStates() {
    initializeWishlistStates();
}

// Enhanced modal close function with wishlist refresh
function closeProductModalWithRefresh() {
    closeProductModal();
    // Refresh wishlist states when modal closes
    setTimeout(() => {
        refreshWishlistStates();
    }, 100);
}
