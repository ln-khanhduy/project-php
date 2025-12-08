<?php
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../models/Phones.php';
require_once '../includes/header.php';

// Khởi tạo DB và Model
$database = new Database();
$db = $database->getConnection();
$phone = new Phone($db);

// Lấy ID từ URL
$phoneId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($phoneId <= 0 || !$phone->readOne($phoneId)) {
    echo '<div class="container py-5"><div class="alert alert-danger text-center">Sản phẩm không tồn tại hoặc đã bị xóa!</div></div>';
    require_once '../includes/footer.php';
    exit;
}
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/public/index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($phone->phone_name) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Hình ảnh sản phẩm -->
        <div class="col-lg-6">
            <div class="sticky-top" style="top: 100px;">
                <div class="text-center mb-4 position-relative overflow-hidden rounded shadow">
                    <img id="mainImage"
                         src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($phone->image_url) ?>"
                         class="img-fluid rounded"
                         alt="<?= htmlspecialchars($phone->phone_name) ?>"
                         onerror="this.src='<?= SITE_URL ?>/images/default.jpg'"
                         style="max-height: 580px; object-fit: contain; transition: transform 0.4s;">
                </div>

                <!-- Thumbnail (có thể mở rộng thêm ảnh phụ sau) -->
                <div class="d-flex justify-content-center gap-2">
                    <div class="border rounded overflow-hidden" style="width: 90px; height: 90px; cursor: pointer;">
                        <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($phone->image_url) ?>"
                             class="w-100 h-100 object-fit-cover"
                             alt="Thumb"
                             onclick="document.getElementById('mainImage').src = this.src">
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin sản phẩm -->
        <div class="col-lg-6">
            <h1 class="fw-bold mb-3"><?= htmlspecialchars($phone->phone_name) ?></h1>

            <div class="mb-3">
                <span class="badge bg-primary fs-6"><?= htmlspecialchars($phone->brand_name) ?></span>
                <span class="badge bg-secondary fs-6"><?= htmlspecialchars($phone->category_name) ?></span>
                <?php if ($phone->stock <= 0): ?>
                    <span class="badge bg-danger fs-6">Hết hàng</span>
                <?php elseif ($phone->stock <= 10): ?>
                    <span class="badge bg-warning text-dark fs-6">Chỉ còn <?= $phone->stock ?> sản phẩm</span>
                <?php else: ?>
                    <span class="badge bg-success fs-6">Còn hàng</span>
                <?php endif; ?>
            </div>

            <div class="price fs-1 text-danger fw-bold mb-4">
                <?= formatPrice($phone->price) ?>
            </div>

            <div class="alert alert-light border mb-4">
                <strong>Mô tả sản phẩm:</strong><br>
                <?= nl2br(htmlspecialchars($phone->description ?? '')) ?: '<em class="text-muted">Đang cập nhật mô tả...</em>' ?>
            </div>

            <!-- Nút hành động -->
            <div class="d-flex flex-column flex-sm-row gap-3 mb-5">
                <?php if ($phone->stock > 0): ?>
                    <button class="btn btn-primary btn-lg px-5 add-to-cart"
                            data-id="<?= $phone->phone_id ?>"
                            data-name="<?= htmlspecialchars($phone->phone_name) ?>">
                        <i class="fas fa-cart-plus me-2"></i>Thêm vào giỏ hàng
                    </button>
                <?php else: ?>
                    <button class="btn btn-secondary btn-lg px-5" disabled>
                        <i class="fas fa-ban me-2"></i>Hết hàng
                    </button>
                <?php endif; ?>

                <a href="<?= SITE_URL ?>/public/index.php" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-arrow-left me-2"></i>Quay lại danh sách
                </a>
            </div>

            <!-- Thông số kỹ thuật -->
            <h4 class="mb-4"><i class="fas fa-microchip text-primary me-2"></i>Thông số kỹ thuật</h4>
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr><td class="bg-light fw-bold">Màn hình</td><td><?= htmlspecialchars($phone->screen ?? 'Đang cập nhật') ?></td></tr>
                    <tr><td class="bg-light fw-bold">Hệ điều hành</td><td><?= htmlspecialchars($phone->os ?? 'Đang cập nhật') ?></td></tr>
                    <tr><td class="bg-light fw-bold">Chip xử lý</td><td><?= htmlspecialchars($phone->cpu ?? 'Đang cập nhật') ?></td></tr>
                    <tr><td class="bg-light fw-bold">RAM</td><td><?= htmlspecialchars($phone->ram ?? 'Đang cập nhật') ?> GB</td></tr>
                    <tr><td class="bg-light fw-bold">Bộ nhớ trong</td><td><?= htmlspecialchars($phone->storage ?? 'Đang cập nhật') ?> GB</td></tr>
                    <tr><td class="bg-light fw-bold">Camera sau</td><td><?= htmlspecialchars($phone->camera ?? 'Đang cập nhật') ?></td></tr>
                    <tr><td class="bg-light fw-bold">Pin</td><td><?= htmlspecialchars($phone->battery ?? 'Đang cập nhật') ?> mAh</td></tr>
                    <tr><td class="bg-light fw-bold">Trọng lượng</td><td><?= htmlspecialchars($phone->weight ?? 'Đang cập nhật') ?> g</td></tr>
                    <tr><td class="bg-light fw-bold">Tồn kho</td><td><strong class="<?= $phone->stock > 0 ? 'text-success' : 'text-danger' ?>"><?= $phone->stock ?> sản phẩm</strong></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Toast thông báo thêm giỏ hàng -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
    <div id="cartToast" class="toast align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i>
                <strong id="toastMessage">Đã thêm vào giỏ hàng!</strong>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<style>
    #mainImage:hover { transform: scale(1.05); }
    .add-to-cart:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,123,255,0.3);
    }
</style>

<script>
document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', function() {
        const phoneId = this.dataset.id;
        const phoneName = this.dataset.name;
        const original = this.innerHTML;

        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang thêm...';
        this.disabled = true;

        fetch('../includes/add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'phone_id=' + encodeURIComponent(phoneId) + '&quantity=1'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('toastMessage').textContent = `"${phoneName}" đã được thêm vào giỏ hàng!`;
                new bootstrap.Toast(document.getElementById('cartToast')).show();
                if (typeof updateCartCount === 'function') updateCartCount();
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể thêm sản phẩm'));
            }
        })
        .catch(() => alert('Lỗi kết nối!'))
        .finally(() => {
            this.innerHTML = original;
            this.disabled = false;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>