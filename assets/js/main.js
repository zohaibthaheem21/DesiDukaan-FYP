/**
 * DesiDukaan - Main JavaScript File
 */

// Language toggle functionality
function toggleLanguage(lang) {
    // Determine the correct path to set_language.php based on current location
    let basePath = '';
    const currentPath = window.location.pathname;

    if (currentPath.includes('/auth/') || currentPath.includes('/customer/') ||
        currentPath.includes('/seller/') || currentPath.includes('/admin/')) {
        basePath = '../';
    }

    // Send AJAX request to update session language
    fetch(basePath + 'config/set_language.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'language=' + lang
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to apply new language
            location.reload();
        } else {
            console.error('Language toggle failed:', data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Fallback: try to reload anyway
        location.reload();
    });
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        const errorElement = input.parentNode.querySelector('.error-message');
        
        // Remove existing error styling
        input.classList.remove('error');
        if (errorElement) {
            errorElement.remove();
        }
        
        // Validate field
        if (!input.value.trim()) {
            showFieldError(input, 'This field is required');
            isValid = false;
        } else {
            // Specific validations
            switch (input.type) {
                case 'email':
                    if (!validateEmail(input.value)) {
                        showFieldError(input, 'Please enter a valid email address');
                        isValid = false;
                    }
                    break;
                case 'password':
                    if (input.value.length < 8) {
                        showFieldError(input, 'Password must be at least 8 characters');
                        isValid = false;
                    }
                    break;
            }
            
            // CNIC validation
            if (input.name === 'cnic' && !validateCNIC(input.value)) {
                showFieldError(input, 'Please enter a valid 13-digit CNIC number');
                isValid = false;
            }
            
            // Mobile validation
            if (input.name === 'mobile_number' && !validateMobile(input.value)) {
                showFieldError(input, 'Please enter a valid mobile number (03XXXXXXXXX)');
                isValid = false;
            }
            
            // Password confirmation
            if (input.name === 'confirm_password') {
                const passwordField = form.querySelector('input[name="password"]');
                if (passwordField && input.value !== passwordField.value) {
                    showFieldError(input, 'Passwords do not match');
                    isValid = false;
                }
            }
        }
    });
    
    return isValid;
}

function showFieldError(input, message) {
    input.classList.add('error');
    const errorElement = document.createElement('div');
    errorElement.className = 'error-message';
    errorElement.textContent = message;
    input.parentNode.appendChild(errorElement);
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validateCNIC(cnic) {
    const cleanCNIC = cnic.replace(/[^0-9]/g, '');
    return cleanCNIC.length === 13;
}

function validateMobile(mobile) {
    const cleanMobile = mobile.replace(/[^0-9]/g, '');
    return cleanMobile.length === 11 && cleanMobile.startsWith('03');
}

// File upload preview
function previewImage(input, previewId) {
    const file = input.files[0];
    const preview = document.getElementById(previewId);
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });

    // Initialize hamburger menu
    const hamburger = document.getElementById('hamburger-menu');
    const navbarNav = document.getElementById('navbar-nav');

    if (hamburger && navbarNav) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navbarNav.classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!hamburger.contains(e.target) && !navbarNav.contains(e.target)) {
                hamburger.classList.remove('active');
                navbarNav.classList.remove('active');
            }
        });

        // Close menu when clicking on a nav link
        const navLinks = navbarNav.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                hamburger.classList.remove('active');
                navbarNav.classList.remove('active');
            });
        });
    }

    // Initialize cart count for customer pages
    if (window.location.pathname.includes('/customer/')) {
        updateCartCount();
    }

    // Initialize language toggle buttons
    const langButtons = document.querySelectorAll('.language-toggle button');
    langButtons.forEach(button => {
        button.addEventListener('click', function() {
            const lang = this.dataset.lang;
            toggleLanguage(lang);
        });
    });

    // Form submission with validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this.id)) {
                e.preventDefault();
            }
        });
    });

    // File input change handlers
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.dataset.preview;
            if (previewId) {
                previewImage(this, previewId);
            }
        });
    });
});

// Splash screen transition
function startSplashTransition() {
    setTimeout(() => {
        const splashScreen = document.querySelector('.splash-screen');
        if (splashScreen) {
            splashScreen.style.opacity = '0';
            setTimeout(() => {
                window.location.href = 'user-type-selection.php';
            }, 500);
        }
    }, 3000); // 3 seconds
}

// Utility functions
function showLoading(element) {
    element.innerHTML = '<span class="loading">Loading...</span>';
    element.disabled = true;
}

function hideLoading(element, originalText) {
    element.innerHTML = originalText;
    element.disabled = false;
}

function formatCNIC(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value.length > 5) {
        value = value.substring(0, 5) + '-' + value.substring(5);
    }
    if (value.length > 13) {
        value = value.substring(0, 13) + '-' + value.substring(13, 14);
    }
    input.value = value;
}

function formatMobile(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value.length > 4) {
        value = value.substring(0, 4) + '-' + value.substring(4);
    }
    if (value.length > 8) {
        value = value.substring(0, 8) + '-' + value.substring(8);
    }
    input.value = value;
}

// AJAX helper function
function makeRequest(url, data, callback) {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(data)
    })
    .then(response => response.json())
    .then(callback)
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Cart count update function (basic version for navbar)
function updateCartCount() {
    // Only run on customer pages
    if (!window.location.pathname.includes('/customer/')) {
        return;
    }

    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        fetch('../includes/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_cart_count'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cartCountElement.textContent = data.cart_count;
                if (data.cart_count > 0) {
                    cartCountElement.classList.remove('empty');
                } else {
                    cartCountElement.classList.add('empty');
                }
            }
        })
        .catch(error => console.error('Error fetching cart count:', error));
    }
}
