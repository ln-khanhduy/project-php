<?php
require_once '../config/config.php';
require_once '../includes/database.php';

// === PHẢI ĐẶT TRƯỚC BẤT KỲ OUTPUT NÀO ===
session_start(); // nếu chưa có ở config.php

if (!isLoggedIn()) {
    redirect(SITE_URL . '/public/login.php');
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$database = new Database();
$db = $database->getConnection();

// Hàm lấy giỏ hàng
if (!function_exists('fetchCartItems')) {
    function fetchCartItems(PDO $db, int $userId): array {
        $stmt = $db->prepare('
            SELECT ci.cart_id, ci.quantity, p.phone_id, p.phone_name, p.price, p.stock, p.image_url
            FROM cart_items ci
            JOIN phones p ON ci.phone_id = p.phone_id
            WHERE ci.user_id = :user_id
        ');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// === XỬ LÝ TẠO ĐƠN HÀNG (PHẢI ĐẶT TRƯỚC header.php) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cartItems = fetchCartItems($db, $userId);
    $totalAmount = 0.0;
    foreach ($cartItems as $item) {
        $totalAmount += (float)$item['price'] * (int)$item['quantity'];
    }

    if (empty($cartItems)) {
        $_SESSION['checkout_message'] = 'Giỏ hàng trống!';
        $_SESSION['checkout_status'] = 'warning';
        redirect(SITE_URL . '/public/cart.php');
    }

    try {
        $db->beginTransaction();

        // Tạo đơn hàng
        $stmt = $db->prepare('INSERT INTO orders (user_id, total_amount, status, order_date) VALUES (:user_id, :total_amount, "pending", NOW())');
        $stmt->execute([
            ':user_id' => $userId,
            ':total_amount' => $totalAmount
        ]);
        $orderId = (int)$db->lastInsertId();

        // Thêm chi tiết đơn hàng
        $detailStmt = $db->prepare('INSERT INTO order_details (order_id, phone_id, quantity, price) VALUES (:order_id, :phone_id, :quantity, :price)');
        foreach ($cartItems as $item) {
            $detailStmt->execute([
                ':order_id' => $orderId,
                ':phone_id' => $item['phone_id'],
                ':quantity' => (int)$item['quantity'],
                ':price'    => (float)$item['price']
            ]);
        }

        // Xóa giỏ hàng
        $db->prepare('DELETE FROM cart_items WHERE user_id = :user_id')->execute([':user_id' => $userId]);

        $db->commit();

        $_SESSION['cart_count'] = 0;
        $_SESSION['checkout_message'] = "Đơn hàng #$orderId đã được tạo thành công! Vui lòng chọn phương thức thanh toán.";
        $_SESSION['checkout_status'] = 'success';

        // ĐIỀU HƯỚNG ĐÚNG – SẼ HOẠT ĐỘNG VÌ CHƯA CÓ OUTPUT
        redirect(SITE_URL . '/public/payments.php');

    } catch (Throwable $e) {
        $db->rollBack();
        $_SESSION['checkout_message'] = 'Lỗi: ' . $e->getMessage();
        $_SESSION['checkout_status'] = 'danger';
        redirect(SITE_URL . '/public/checkout.php');
    }
}

// === SAU KHI XỬ LÝ XONG MỚI ĐƯỢC IN RA HTML ===
require_once '../includes/header.php';

$checkoutMessage = $_SESSION['checkout_message'] ?? '';
$checkoutStatus  = $_SESSION['checkout_status'] ?? 'success';
unset($_SESSION['checkout_message'], $_SESSION['checkout_status']);

$cartItems = fetchCartItems($db, $userId);
$totalAmount = 0.0;
foreach ($cartItems as $item) {
    $totalAmount += (float)$item['price'] * (int)$item['quantity'];
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="mb-4 text-center">Xác nhận đơn hàng</h2>

            <?php if ($checkoutMessage): ?>
                <div class="alert alert-<?= $checkoutStatus === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                    <?= htmlspecialchars($checkoutMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($cartItems)): ?>
                <div class="text-center py-5">
                    <h4>Giỏ hàng trống</h4>
                    <a href="<?= SITE_URL ?>/public/index.php" class="btn btn-primary">Tiếp tục mua sắm</a>
                </div>
            <?php else: ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Thông tin đơn hàng</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($item['image_url'] ?? 'default.jpg') ?>" width="50" class="me-3 rounded">
                                            <strong><?= htmlspecialchars($item['phone_name']) ?></strong>
                                        </td>
                                        <td class="text-end"><?= $item['quantity'] ?> × <?= formatPrice($item['price']) ?></td>
                                        <td class="text-end fw-bold"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="border-top">
                                    <td colspan="2" class="text-end fw-bold fs-5">Tổng cộng:</td>
                                    <td class="text-end text-danger fw-bold fs-4"><?= formatPrice($totalAmount) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <form method="post" class="text-center">
                    <button type="submit" class="btn btn-success btn-lg px-5">
                        Xác nhận & Tạo đơn hàng
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>