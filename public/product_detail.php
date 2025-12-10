<?php
declare(strict_types=1);

require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../models/Phones.php';

$database = new Database();
$db = $database->getConnection();

$phoneId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$phoneId) {
    $_SESSION['error'] = 'Sản phẩm không hợp lệ.';
    redirect(SITE_URL . '/public/products.php');
}

$phoneModel = new Phone($db);

// Chuẩn hóa đường dẫn ảnh: thêm prefix uploads nếu chỉ lưu tên file
function normalizeImageUrl(?string $url): string {
    if (empty($url)) {
        return '';
    }
    if (preg_match('#^https?://#i', $url) || str_starts_with($url, '/project-php/')) {
        return $url;
    }
    return '/project-php/uploads/' . ltrim($url, '/');
}

// Lấy thông tin sản phẩm
$stmt = $db->prepare("SELECT p.phone_id, p.brand_id, p.category_id, p.phone_name, p.price, p.stock, 
                             p.description, p.image_url, p.screen, p.os, p.cpu, p.ram, 
                             p.storage, p.camera, p.battery, p.sim, p.weight, p.created_at,
                             b.brand_name, c.category_name 
                      FROM phones p 
                      LEFT JOIN brands b ON p.brand_id = b.brand_id 
                      LEFT JOIN categories c ON p.category_id = c.category_id 
                      WHERE p.phone_id = :phone_id");
$stmt->execute([':phone_id' => $phoneId]);
$phone = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$phone) {
    $_SESSION['error'] = 'Sản phẩm không tồn tại.';
    redirect(SITE_URL . '/public/products.php');
}

// Chuẩn hóa đường dẫn ảnh: nếu chỉ lưu tên file, thêm prefix uploads
$imageUrl = normalizeImageUrl($phone['image_url'] ?? '');

// Lấy sản phẩm liên quan (cùng thương hiệu)
$relatedStmt = $db->prepare("SELECT p.*, b.brand_name 
                             FROM phones p 
                             LEFT JOIN brands b ON p.brand_id = b.brand_id 
                             WHERE p.brand_id = :brand_id AND p.phone_id != :phone_id 
                             ORDER BY p.created_at DESC 
                             LIMIT 4");
$relatedStmt->execute([
    ':brand_id' => $phone['brand_id'],
    ':phone_id' => $phoneId
]);
$relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/project-php/public/index.php">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="/project-php/public/products.php">Sản phẩm</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($phone['phone_name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Image -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-0">
                    <?php if (!empty($imageUrl)): ?>
                        <img src="<?= htmlspecialchars($imageUrl); ?>" 
                             alt="<?= htmlspecialchars($phone['phone_name']); ?>" 
                             class="img-fluid rounded" 
                             style="width: 100%; max-height: 500px; object-fit: contain;">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center bg-light rounded" style="height: 500px;">
                            <span class="text-muted fs-3">No Image</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h3 mb-3"><?= htmlspecialchars($phone['phone_name']); ?></h1>
                    
                    <div class="mb-3">
                        <span class="badge bg-primary me-2">
                            <i class="fas fa-tag"></i> <?= htmlspecialchars($phone['brand_name'] ?? 'N/A'); ?>
                        </span>
                        <?php if (!empty($phone['category_name'])): ?>
                            <span class="badge bg-secondary">
                                <i class="fas fa-folder"></i> <?= htmlspecialchars($phone['category_name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h2 class="text-danger fw-bold mb-3">
                        <?= number_format((float)$phone['price']); ?>₫
                    </h2>

                    <div class="mb-3">
                        <span class="fw-bold">Tình trạng: </span>
                        <?php if ((int)$phone['stock'] > 0): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle"></i> Còn hàng (<?= (int)$phone['stock']; ?>)
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger">
                                <i class="fas fa-times-circle"></i> Hết hàng
                            </span>
                        <?php endif; ?>
                    </div>
                    <!-- Thông số kỹ thuật chi tiết -->
                    <div class="mb-4">
                        <h5 class="mb-3">Thông số kỹ thuật</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <?php if (!empty($phone['screen'])): ?>
                                        <tr>
                                            <td class="fw-bold" style="width: 40%;">
                                                <i class="fas fa-mobile-alt text-primary"></i> Màn hình
                                            </td>
                                            <td><?= htmlspecialchars($phone['screen']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($phone['os'])): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <i class="fas fa-layer-group text-primary"></i> Hệ điều hành
                                            </td>
                                            <td><?= htmlspecialchars($phone['os']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($phone['cpu'])): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <i class="fas fa-microchip text-primary"></i> Bộ xử lý
                                            </td>
                                            <td><?= htmlspecialchars($phone['cpu']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($phone['ram'])): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <i class="fas fa-memory text-primary"></i> RAM
                                            </td>
                                            <td><?= htmlspecialchars($phone['ram']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($phone['storage'])): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <i class="fas fa-hdd text-primary"></i> Bộ nhớ trong
                                            </td>
                                            <td><?= htmlspecialchars($phone['storage']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($phone['camera'])): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <i class="fas fa-camera text-primary"></i> Camera
                                            </td>
                                            <td><?= htmlspecialchars($phone['camera']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($phone['battery'])): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <i class="fas fa-battery-full text-primary"></i> Pin
                                            </td>
                                            <td><?= htmlspecialchars($phone['battery']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($phone['sim'])): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <i class="fas fa-sim-card text-primary"></i> SIM
                                            </td>
                                            <td><?= htmlspecialchars($phone['sim']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($phone['weight'])): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <i class="fas fa-weight text-primary"></i> Trọng lượng
                                            </td>
                                            <td><?= htmlspecialchars($phone['weight']); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if (!empty($phone['description'])): ?>
                        <div class="mb-4">
                            <h5>Mô tả sản phẩm</h5>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($phone['description'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($phone['specifications'])): ?>
                        <div class="mb-4">
                            <h5>Thông số kỹ thuật</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;"><?= htmlspecialchars($phone['specifications']); ?></pre>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <?php if (isLoggedIn() && isCustomer()): ?>
                            <?php if ((int)($phone['stock_quantity'] ?? 0) > 0): ?>
                                <form action="/project-php/includes/add_to_cart.php" method="POST" class="add-to-cart-form">
                                    <input type="hidden" name="phone_id" value="<?= $phone['phone_id']; ?>">
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">Số lượng</span>
                                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?= (int)($phone['stock_quantity'] ?? 0); ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg w-100" disabled>
                                    <i class="fas fa-ban"></i> Hết hàng
                                </button>
                            <?php endif; ?>
                        <?php elseif (!isLoggedIn()): ?>
                            <a href="/project-php/public/login.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập để mua hàng
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg w-100" disabled>
                                <i class="fas fa-info-circle"></i> Chỉ khách hàng mới mua được
                            </button>
                        <?php endif; ?>
                        
                        <a href="/project-php/public/products.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại danh sách
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h4 class="mb-4">Sản phẩm liên quan</h4>
            </div>
            <?php foreach ($relatedProducts as $related): ?>
                <?php $relatedImage = normalizeImageUrl($related['image_url'] ?? ''); ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="image-container">
                            <?php if (!empty($relatedImage)): ?>
                                <img src="<?= htmlspecialchars($relatedImage); ?>" 
                                     class="card-img-top product-image" 
                                     alt="<?= htmlspecialchars($related['phone_name']); ?>">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center bg-light" style="height: 225px;">
                                    <span class="text-muted">No Image</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($related['phone_name']); ?></h6>
                            <p class="price"><?= number_format((float)$related['price']); ?>₫</p>
                            <a href="product_detail.php?id=<?= $related['phone_id']; ?>" class="btn btn-sm btn-primary w-100">
                                Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// AJAX add to cart
document.querySelectorAll('.add-to-cart-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const btn = this.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang thêm...';
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart count in header
                const cartBadge = document.querySelector('.badge.bg-danger');
                if (cartBadge) {
                    cartBadge.textContent = data.cart_count;
                }
                
                // Show success message
                btn.innerHTML = '<i class="fas fa-check"></i> Đã thêm!';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-primary');
                    btn.disabled = false;
                }, 2000);
            } else {
                alert(data.message || 'Có lỗi xảy ra!');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Không thể thêm vào giỏ hàng!');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
