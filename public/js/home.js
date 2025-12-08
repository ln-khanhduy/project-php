(() => {
    const CART_COUNT_URL = '../includes/get_cart_count.php';
    const ADD_TO_CART_URL = '../includes/add_to_cart.php';

    const updateCartCount = () => {
        fetch(CART_COUNT_URL)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartBadges = document.querySelectorAll('.cart-badge');
                    cartBadges.forEach(badge => {
                        badge.textContent = data.count;
                        badge.style.display = data.count > 0 ? 'inline' : 'none';
                    });
                }
            })
            .catch(() => {
                // silently ignore cart count failures
            });
    };

    const showAlert = (message, type) => {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alertDiv);

        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    };

    const bindAddToCartButtons = () => {
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function () {
                const phoneId = this.getAttribute('data-id');
                const originalText = this.innerHTML;

                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang thêm...';
                this.disabled = true;

                fetch(ADD_TO_CART_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `phone_id=${phoneId}&quantity=1`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('Đã thêm vào giỏ hàng!', 'success');
                            updateCartCount();
                        } else {
                            showAlert(`Lỗi: ${data.message}`, 'error');
                        }
                    })
                    .catch(() => {
                        showAlert('Có lỗi xảy ra!', 'error');
                    })
                    .finally(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    });
            });
        });
    };

    const initCarousel = () => {
        const heroCarouselEl = document.getElementById('homepageCarousel');
        if (heroCarouselEl && typeof bootstrap !== 'undefined') {
            new bootstrap.Carousel(heroCarouselEl, {
                interval: 2500,
                ride: 'carousel',
                wrap: true,
                pause: false
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        initCarousel();
        updateCartCount();
        bindAddToCartButtons();
    });
})();
