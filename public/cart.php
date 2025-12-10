<?php
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../models/Cart.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/public/login.php');
}

// Chỉ customer mới có giỏ hàng
if ($_SESSION['role'] !== 'customer') {
    $_SESSION['auth_error'] = 'Chức năng này chỉ dành cho khách hàng.';
    redirect(SITE_URL . '/public/index.php');
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$database = new Database();
$db = $database->getConnection();
$cartModel = new Cart($db);

require_once '../includes/header.php';

$message = $_SESSION['cart_message'] ?? '';
$alertType = $_SESSION['cart_message_type'] ?? 'success';
unset($_SESSION['cart_message'], $_SESSION['cart_message_type']);

$cartItems = $cartModel->getCartItems($userId);

$totalAmount = 0.0;
foreach ($cartItems as $item) {
    $totalAmount += (float)$item['price'] * (int)$item['quantity'];
}
?>
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h3>Giỏ hàng của bạn</h3>
            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($alertType); ?>">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($cartItems)): ?>
                <div class="alert alert-info">Giỏ hàng đang trống. Hãy thêm sản phẩm từ trang chủ.</div>
                <a href="index.php" class="btn btn-primary">Trở về trang chủ</a>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Sản phẩm</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Tổng</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $index => $item): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td>
                                        <div class="d-flex gap-2 align-items-center">
                                            <img src="<?= SITE_URL; ?>/uploads/<?= htmlspecialchars($item['image_url']); ?>"
                                                 alt="<?= htmlspecialchars($item['phone_name']); ?>"
                                                 width="60" height="60" style="object-fit: cover; border-radius: 0.4rem;">
                                            <div>
                                                <strong><?= htmlspecialchars($item['phone_name']); ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($item['brand_name']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= formatPrice($item['price']); ?></td>
                                    <td>
                                        <form action="cart_action.php" method="post" class="d-flex gap-2 align-items-center">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id']; ?>">
                                            <input type="number" name="quantity" value="<?= (int)$item['quantity']; ?>" min="1" max="<?= max(1, (int)$item['stock']); ?>" class="form-control form-control-sm" style="width: 90px;">
                                            <button class="btn btn-sm btn-outline-primary">Cập nhật</button>
                                        </form>
                                        <small class="text-muted">Kho còn <?= (int)$item['stock']; ?> sp</small>
                                    </td>
                                    <td><?= formatPrice((float)$item['price'] * (int)$item['quantity']); ?></td>
                                    <td>
                                        <form action="cart_action.php" method="post">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="cart_item_id" value="<?= $item['cart_item_id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center border-top pt-3">
                    <div>
                        <p class="mb-1">Tổng tạm tính</p>
                        <h4 class="fw-bold text-danger"><?= formatPrice($totalAmount); ?></h4>
                    </div>
                    <div class="text-end">
                        <a href="checkout.php" class="btn btn-success btn-lg">Tiến hành đặt hàng</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>