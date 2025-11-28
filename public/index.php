<?php
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../models/Phones.php';
require_once '../includes/header.php';

$database = new Database();
$db = $database->getConnection();
$phone = new Phone($db);

// Lấy sản phẩm mới nhất
$stmt = $phone->readAll();
$new_phones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy sản phẩm bán chạy
$popular_query = "SELECT p.*, b.brand_name, c.category_name 
                  FROM phones p
                  LEFT JOIN brands b ON p.brand_id = b.brand_id
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  ORDER BY p.stock DESC LIMIT 6";
$popular_stmt = $db->prepare($popular_query);
$popular_stmt->execute();
$popular_phones = $popular_stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách thương hiệu
$brands_query = "SELECT * FROM brands";
$brands_stmt = $db->prepare($brands_query);
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Hero Section -->
<div class="hero-section bg-primary text-white py-5 mb-5 rounded">
    <div class="container text-center">
        <h1 class="display-4 fw-bold">Chào mừng đến với PhoneStore</h1>
        <p class="lead">Điện thoại chính hãng - Giá tốt nhất thị trường</p>
        <a href="products.php" class="btn btn-light btn-lg mt-3">Mua sắm ngay</a>
    </div>
</div>

<!-- Thương hiệu nổi bật -->
<div class="row mb-5">
    <div class="col-12">
        <h2 class="text-center mb-4">Thương hiệu nổi bật</h2>
        <div class="row text-center">
            <?php foreach ($brands as $brand): ?>
            <div class="col-md-3 col-6 mb-3">
                <a href="products.php?brand=<?php echo $brand['brand_id']; ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title text-dark"><?php echo htmlspecialchars($brand['brand_name']); ?></h5>
                            <p class="text-muted">Xem sản phẩm</p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Sản phẩm bán chạy -->
<div class="row mb-5">
    <div class="col-12">
        <h2 class="text-center mb-4">Sản phẩm bán chạy</h2>
    </div>
    
    <?php if($popular_phones): ?>
        <?php foreach($popular_phones as $product): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="image-container" style="height: 225px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                        <img src="../images/<?php echo htmlspecialchars($product['image_url']); ?>" 
                             class="card-img-top product-image" 
                             alt="<?php echo htmlspecialchars($product['phone_name']); ?>"
                             style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;"
                             onerror="this.src='../images/default.jpg'">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['phone_name']); ?></h5>
                        <p class="card-text">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($product['brand_name']); ?></span>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        </p>
                        <p class="card-text text-muted">
                            <?php 
                            $description = $product['description'];
                            echo htmlspecialchars(mb_substr($description, 0, 100, 'UTF-8') . (strlen($description) > 100 ? '...' : ''));
                            ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price text-danger fw-bold"><?php echo formatPrice($product['price']); ?></span>
                            <span class="text-muted">Còn: <?php echo $product['stock']; ?> sp</span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-grid gap-2">
                            <a href="product_detail.php?id=<?php echo $product['phone_id']; ?>" 
                               class="btn btn-outline-primary btn-sm">Xem chi tiết</a>
                            <?php if($product['stock'] > 0): ?>
                                <button class="btn btn-primary btn-sm add-to-cart" 
                                        data-id="<?php echo $product['phone_id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Thêm giỏ hàng
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>Hết hàng</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center">
            <p class="text-muted">Không có sản phẩm nào.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Sản phẩm mới -->
<div class="row mb-5">
    <div class="col-12">
        <h2 class="text-center mb-4">Sản phẩm mới nhất</h2>
    </div>
    
    <?php if($new_phones): ?>
        <?php foreach($new_phones as $product): ?>
            <div class="col-lg-3 col-md-4 col-6 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="image-container" style="height: 225px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                        <img src="../images/<?php echo htmlspecialchars($product['image_url']); ?>" 
                             class="card-img-top product-image" 
                             alt="<?php echo htmlspecialchars($product['phone_name']); ?>"
                             style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain;"
                             onerror="this.src='../images/default.jpg'">
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?php echo htmlspecialchars($product['phone_name']); ?></h6>
                        <p class="card-text">
                            <small class="text-muted"><?php echo htmlspecialchars($product['brand_name']); ?></small>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price text-danger fw-bold"><?php echo formatPrice($product['price']); ?></span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent p-2">
                        <div class="d-grid gap-1">
                            <a href="product_detail.php?id=<?php echo $product['phone_id']; ?>" 
                               class="btn btn-outline-primary btn-sm">Chi tiết</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center">
            <p class="text-muted">Không có sản phẩm mới.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Features Section -->
<div class="row mb-5">
    <div class="col-md-3 col-6 text-center mb-3">
        <div class="border rounded p-3 h-100">
            <i class="fas fa-shipping-fast fa-2x text-primary mb-2"></i>
            <h6>Miễn phí vận chuyển</h6>
            <small class="text-muted">Cho đơn hàng từ 5 triệu</small>
        </div>
    </div>
    <div class="col-md-3 col-6 text-center mb-3">
        <div class="border rounded p-3 h-100">
            <i class="fas fa-shield-alt fa-2x text-primary mb-2"></i>
            <h6>Bảo hành chính hãng</h6>
            <small class="text-muted">12-24 tháng</small>
        </div>
    </div>
    <div class="col-md-3 col-6 text-center mb-3">
        <div class="border rounded p-3 h-100">
            <i class="fas fa-undo-alt fa-2x text-primary mb-2"></i>
            <h6>Đổi trả dễ dàng</h6>
            <small class="text-muted">Trong 7 ngày</small>
        </div>
    </div>
    <div class="col-md-3 col-6 text-center mb-3">
        <div class="border rounded p-3 h-100">
            <i class="fas fa-headset fa-2x text-primary mb-2"></i>
            <h6>Hỗ trợ 24/7</h6>
            <small class="text-muted">Hotline: 1800-1234</small>
        </div>
    </div>
</div>

<script>
// Hàm cập nhật số lượng giỏ hàng
function updateCartCount() {
    fetch('../includes/get_cart_count.php')
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Cập nhật badge giỏ hàng
            const cartBadges = document.querySelectorAll('.cart-badge');
            cartBadges.forEach(badge => {
                badge.textContent = data.count;
                if(data.count > 0) {
                    badge.style.display = 'inline';
                } else {
                    badge.style.display = 'none';
                }
            });
        }
    });
}

// Xử lý thêm vào giỏ hàng
document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function() {
        const phoneId = this.getAttribute('data-id');
        
        // Hiển thị loading
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang thêm...';
        this.disabled = true;
        
        fetch('../includes/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'phone_id=' + phoneId + '&quantity=1'
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Hiển thị thông báo thành công
                showAlert('Đã thêm vào giỏ hàng!', 'success');
                updateCartCount();
            } else {
                showAlert('Lỗi: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Có lỗi xảy ra!', 'error');
        })
        .finally(() => {
            // Khôi phục trạng thái nút
            this.innerHTML = originalText;
            this.disabled = false;
        });
    });
});

// Hàm hiển thị thông báo
function showAlert(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Tự động xóa sau 3 giây
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}

// Khởi tạo khi trang load
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
});
</script>

<?php require_once '../includes/footer.php'; ?>