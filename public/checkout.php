<?php
require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/header.php';

if (!isLoggedIn()) {
    redirect(SITE_URL . '/public/login.php');
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$database = new Database();
$db = $database->getConnection();

if (!function_exists('fetchCartItems')) {
    function fetchCartItems(PDO $db, int $userId): array
    {
        $stmt = $db->prepare('SELECT ci.cart_item_id, ci.quantity, p.phone_id, p.phone_name, p.price, p.stock
            FROM cart_items ci
            JOIN phones p ON ci.phone_id = p.phone_id
            WHERE ci.user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$checkoutMessage = $_SESSION['checkout_message'] ?? '';
$checkoutStatus = $_SESSION['checkout_status'] ?? 'success';
unset($_SESSION['checkout_message'], $_SESSION['checkout_status']);

$cartItems = fetchCartItems($db, $userId);
$totalAmount = 0.0;
foreach ($cartItems as $item) {
    $totalAmount += (float)$item['price'] * (int)$item['quantity'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($cartItems)) {
        $_SESSION['cart_message'] = 'Giỏ hàng đang trống. Không thể tạo đơn hàng.';
        $_SESSION['cart_message_type'] = 'warning';
        redirect(SITE_URL . '/public/cart.php');
    }

    try {
        $db->beginTransaction();
        $orderStmt = $db->prepare('INSERT INTO orders (user_id, total_amount, status, order_date)
            VALUES (:user_id, :total_amount, :status, NOW())');
        $orderStmt->execute([
            ':user_id' => $userId,
            ':total_amount' => $totalAmount,
            ':status' => 'pending'
        ]);
        $orderId = (int)$db->lastInsertId();

        $detailStmt = $db->prepare('INSERT INTO order_details (order_id, phone_id, quantity)
            VALUES (:order_id, :phone_id, :quantity)');
        foreach ($cartItems as $item) {
            $detailStmt->execute([
                ':order_id' => $orderId,
                ':phone_id' => $item['phone_id'],
                ':quantity' => (int)$item['quantity']
            ]);
        }

        $deleteStmt = $db->prepare('DELETE FROM cart_items WHERE user_id = :user_id');
        $deleteStmt->execute([':user_id' => $userId]);

        $db->commit();
        $_SESSION['cart_count'] = 0;
        $_SESSION['checkout_message'] = 'Đơn hàng #' . $orderId . ' đã được tạo. Vui lòng chọn phương thức thanh toán.';
        $_SESSION['checkout_status'] = 'success';
        redirect(SITE_URL . '/public/payments.php');
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['checkout_message'] = 'Không thể tạo đơn hàng: ' . $exception->getMessage();
        $_SESSION['checkout_status'] = 'danger';
        redirect(SITE_URL . '/public/checkout.php');
    }
}
?>
<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <h3>Thanh toán</h3>
            <?php if ($checkoutMessage): ?>
                <div class="alert alert-<?= htmlspecialchars($checkoutStatus); ?>">
                    <?= htmlspecialchars($checkoutMessage); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($cartItems)): ?>
                <div class="alert alert-warning">Không có sản phẩm nào trong giỏ hàng.</div>
                <a href="index.php" class="btn btn-outline-primary">Tiếp tục mua sắm</a>
            <?php else: ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Thông tin đơn hàng</h5>
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Số lượng</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['phone_name']); ?></td>
                                        <td class="text-end"><?= (int)$item['quantity']; ?></td>
                                        <td class="text-end"><?= formatPrice((float)$item['price'] * (int)$item['quantity']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="fw-bold border-top">
                                    <td>Tổng</td>
                                    <td class="text-end"></td>
                                    <td class="text-end"><?= formatPrice($totalAmount); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <form method="post" class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Xác nhận đơn hàng</h5>
                        <p class="text-muted">Sau khi tạo đơn, bạn có thể chọn phương thức thanh toán trong trang <a href="payments.php">Đơn hàng</a>.</p>
                        <div class="mb-3">
                            <label class="form-label">Ghi chú (tùy chọn)</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Yêu cầu giao hàng,..."></textarea>
                        </div>
                        <button class="btn btn-success btn-lg w-100">Xác nhận và tạo đơn hàng</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title">Cam kết PhoneStore</h5>
                    <ul class="list-unstyled mb-0">
                        <li><i class="fas fa-check text-success me-2"></i>Giao hàng 48h toàn quốc</li>
                        <li><i class="fas fa-check text-success me-2"></i>Thanh toán đa phương thức</li>
                        <li><i class="fas fa-check text-success me-2"></i>Bảo hành chính hãng</li>
                    </ul>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Cần trợ giúp?</h5>
                    <p class="mb-1">Hotline: <strong>1800-1234</strong></p>
                    <p class="mb-0">Email: <a href="mailto:info@phonestore.com">info@phonestore.com</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>