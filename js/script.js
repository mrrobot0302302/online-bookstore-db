// Main JavaScript file for Online Bookstore

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all interactive components
    
    // 1. Format credit card number input
    const cardNumberInput = document.getElementById('card_number');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formatted.substring(0, 19);
        });
    }
    
    // 2. Format expiry date input
    const expiryDateInput = document.getElementById('expiry_date');
    if (expiryDateInput) {
        expiryDateInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value.substring(0, 5);
        });
    }
    
    // 3. Add to cart buttons
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');
    addToCartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const quantityInput = this.querySelector('input[name="quantity"]');
            const maxStock = parseInt(quantityInput.max);
            const quantity = parseInt(quantityInput.value);
            
            if (quantity > maxStock) {
                e.preventDefault();
                alert(`Only ${maxStock} items available in stock.`);
                quantityInput.value = maxStock;
                return false;
            }
            
            if (quantity <= 0) {
                e.preventDefault();
                alert('Please enter a valid quantity.');
                return false;
            }
            
            // Show loading indicator
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Adding...';
            submitBtn.disabled = true;
            
            setTimeout(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 1000);
        });
    });
    
    // 4. Checkout form validation
    const checkoutForm = document.querySelector('form[action="checkout.php"]');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const cardNumber = document.getElementById('card_number').value;
            const expiryDate = document.getElementById('expiry_date').value;
            const cvv = document.getElementById('cvv').value;
            
            // Remove spaces for validation
            const cleanCardNumber = cardNumber.replace(/\s+/g, '');
            
            if (cleanCardNumber.length !== 16 || !/^\d+$/.test(cleanCardNumber)) {
                e.preventDefault();
                alert('Please enter a valid 16-digit card number.');
                return false;
            }
            
            if (!/^(0[1-9]|1[0-2])\/?([0-9]{2})$/.test(expiryDate)) {
                e.preventDefault();
                alert('Please enter a valid expiry date in MM/YY format.');
                return false;
            }
            
            // Check if card is expired
            const [month, year] = expiryDate.split('/');
            const currentYear = new Date().getFullYear() % 100;
            const currentMonth = new Date().getMonth() + 1;
            
            if (parseInt(year) < currentYear || 
                (parseInt(year) === currentYear && parseInt(month) < currentMonth)) {
                e.preventDefault();
                alert('This card has expired. Please use a valid card.');
                return false;
            }
            
            if (!/^\d{3}$/.test(cvv)) {
                e.preventDefault();
                alert('Please enter a valid 3-digit CVV.');
                return false;
            }
            
            // Show processing message
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.textContent = 'Processing...';
            submitBtn.disabled = true;
        });
    }
    
    // 5. Dynamic report options
    const reportTypeSelect = document.getElementById('report_type');
    if (reportTypeSelect) {
        reportTypeSelect.addEventListener('change', function() {
            const reportOptions = document.getElementById('report_options');
            const selectedValue = this.value;
            
            let optionsHTML = '';
            if (selectedValue === 'daily_sales') {
                optionsHTML = `
                    <div class="form-group">
                        <label for="date">Select Date:</label>
                        <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                `;
            } else if (selectedValue === 'book_orders') {
                optionsHTML = `
                    <div class="form-group">
                        <label for="isbn">Book ISBN:</label>
                        <input type="text" name="isbn" placeholder="Enter ISBN">
                    </div>
                `;
            }
            
            reportOptions.innerHTML = optionsHTML;
        });
    }
    
    // 6. Cart quantity updates
    const updateQuantityForms = document.querySelectorAll('.inline-form input[name="quantity"]');
    updateQuantityForms.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value < 1) {
                this.value = 1;
            }
        });
    });
    
    // 7. Search form enhancements
    const searchForms = document.querySelectorAll('.search-form form');
    searchForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input[type="text"], input[type="number"]');
            let hasValue = false;
            
            inputs.forEach(input => {
                if (input.value.trim() !== '') {
                    hasValue = true;
                }
            });
            
            if (!hasValue) {
                e.preventDefault();
                alert('Please enter at least one search criteria.');
                return false;
            }
        });
    });
    
    // 8. Confirm actions (delete, clear cart, etc.)
    const confirmActions = document.querySelectorAll('[onclick*="confirm"]');
    confirmActions.forEach(element => {
        element.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to proceed?')) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // 9. Auto-hide messages after 5 seconds
    setTimeout(() => {
        const messages = document.querySelectorAll('.success, .error');
        messages.forEach(msg => {
            if (msg) {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            }
        });
    }, 5000);
    
    // 10. Password strength indicator
    const passwordInput = document.getElementById('new_password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            const indicator = document.getElementById('password-strength');
            
            if (!indicator) {
                const div = document.createElement('div');
                div.id = 'password-strength';
                div.style.marginTop = '5px';
                div.style.fontSize = '14px';
                this.parentNode.appendChild(div);
            }
            
            const indicatorDiv = document.getElementById('password-strength');
            indicatorDiv.textContent = `Strength: ${strength.label}`;
            indicatorDiv.style.color = strength.color;
        });
    }
});

// Password strength checker
function checkPasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^a-zA-Z0-9]/.test(password)) score++;
    
    if (score <= 2) return { label: 'Weak', color: '#f56565' };
    if (score <= 4) return { label: 'Medium', color: '#d69e2e' };
    return { label: 'Strong', color: '#48bb78' };
}

// Toggle password visibility
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
}

// Format currency
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Calculate cart total
function calculateCartTotal() {
    const rows = document.querySelectorAll('.cart-items tbody tr');
    let total = 0;
    
    rows.forEach(row => {
        if (!row.classList.contains('total-row')) {
            const price = parseFloat(row.querySelector('td:nth-child(2)').textContent.replace('$', ''));
            const quantity = parseInt(row.querySelector('input[name="quantity"]').value);
            total += price * quantity;
            
            const totalCell = row.querySelector('td:nth-child(4)');
            totalCell.textContent = formatCurrency(price * quantity);
        }
    });
    
    const totalRow = document.querySelector('.total-row td:last-child');
    if (totalRow) {
        totalRow.textContent = formatCurrency(total);
    }
    
    return total;
}