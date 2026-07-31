/**
 * FoodExpress Custom JavaScript file
 * Handles AJAX cart operations, SweetAlert2 notifications, and payment helper functions.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips/popovers if needed
    
    // Global Toast configuration for SweetAlert2
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    window.showToast = function(icon, title) {
        Toast.fire({
            icon: icon,
            title: title
        });
    }

    // Add to Cart handler
    const addToCartBtns = document.querySelectorAll('.add-to-cart-btn');
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const foodId = this.getAttribute('data-food-id');
            const qtyInput = document.getElementById('qty_' + foodId);
            const quantity = qtyInput ? parseInt(qtyInput.value) : 1;

            if (isNaN(quantity) || quantity < 1) {
                showToast('error', 'Please enter a valid quantity.');
                return;
            }

            // AJAX request to cart-action.php
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('food_id', foodId);
            formData.append('quantity', quantity);

            fetch('cart-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast('success', data.message);
                    // Update cart badge
                    const badge = document.querySelector('.cart-badge');
                    if (badge) {
                        badge.textContent = data.cart_count;
                        badge.style.display = data.cart_count > 0 ? 'inline-block' : 'none';
                    }
                } else if (data.status === 'unauthorized') {
                    Swal.fire({
                        title: 'Login Required',
                        text: 'You need to login to add items to your cart.',
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: '#ff5e3a',
                        cancelButtonColor: '#1a1a2e',
                        confirmButtonText: 'Login Now',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'login.php';
                        }
                    });
                } else {
                    showToast('error', data.message || 'Something went wrong.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Unable to process request.');
            });
        });
    });

    // Cart Page quantity updates
    const qtyInputs = document.querySelectorAll('.cart-qty-input');
    qtyInputs.forEach(input => {
        input.addEventListener('change', function() {
            updateCartQuantity(this);
        });
    });

    window.changeQty = function(cartId, delta) {
        const input = document.getElementById('qty_input_' + cartId);
        if (input) {
            let newVal = parseInt(input.value) + delta;
            if (newVal >= 1) {
                input.value = newVal;
                updateCartQuantity(input);
            }
        }
    }

    function updateCartQuantity(input) {
        const cartId = input.getAttribute('data-cart-id');
        const quantity = parseInt(input.value);

        if (isNaN(quantity) || quantity < 1) {
            showToast('error', 'Quantity must be at least 1.');
            input.value = 1;
            return;
        }

        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('cart_id', cartId);
        formData.append('quantity', quantity);

        fetch('cart-action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('success', data.message);
                
                // Update specific row total
                const rowTotal = document.getElementById('row_total_' + cartId);
                if (rowTotal) {
                    rowTotal.textContent = '$' + parseFloat(data.row_total).toFixed(2);
                }

                // Update subtotal and grand total
                const subtotal = document.getElementById('cart-subtotal');
                const grandTotal = document.getElementById('cart-grandtotal');
                if (subtotal) subtotal.textContent = '$' + parseFloat(data.cart_total).toFixed(2);
                if (grandTotal) grandTotal.textContent = '$' + parseFloat(data.cart_total).toFixed(2);
                
                // Update nav badge
                const badge = document.querySelector('.cart-badge');
                if (badge) {
                    badge.textContent = data.cart_count;
                }
            } else {
                showToast('error', data.message);
                setTimeout(() => {
                    location.reload();
                }, 1500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'Failed to update quantity.');
            setTimeout(() => {
                location.reload();
            }, 1500);
        });
    }

    // Remove Cart Item handler
    window.removeCartItem = function(cartId) {
        Swal.fire({
            title: 'Remove item?',
            text: 'Are you sure you want to remove this item from your cart?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff5e3a',
            cancelButtonColor: '#1a1a2e',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'remove');
                formData.append('cart_id', cartId);

                fetch('cart-action.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showToast('success', data.message);
                        
                        // Remove row from HTML table
                        const row = document.getElementById('cart_row_' + cartId);
                        if (row) {
                            row.remove();
                        }

                        // Check if cart is now empty
                        const remainingRows = document.querySelectorAll('.cart-item-row');
                        if (remainingRows.length === 0) {
                            location.reload(); // Reload to show empty cart message
                        } else {
                            // Update total
                            const subtotal = document.getElementById('cart-subtotal');
                            const grandTotal = document.getElementById('cart-grandtotal');
                            if (subtotal) subtotal.textContent = '$' + parseFloat(data.cart_total).toFixed(2);
                            if (grandTotal) grandTotal.textContent = '$' + parseFloat(data.cart_total).toFixed(2);
                            
                            // Update badge
                            const badge = document.querySelector('.cart-badge');
                            if (badge) {
                                badge.textContent = data.cart_count;
                            }
                        }
                    } else {
                        showToast('error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'Failed to remove item.');
                });
            }
        });
    }

    // Client-side Password Match check
    const regForm = document.getElementById('registerForm');
    if (regForm) {
        regForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    title: 'Error!',
                    text: 'Passwords do not match.',
                    icon: 'error',
                    confirmButtonColor: '#ff5e3a'
                });
            }
        });
    }
});
