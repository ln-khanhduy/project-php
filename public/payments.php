<?php
declare(strict_types=1);


require_once '../config/config.php';
require_once '../includes/database.php';

session_start();
if (!isLoggedIn()) {
    redirect(SITE_URL . '/public/login.php');
}

$database = new Database();
$db = $database->getConnection();

$orderStmt = $db->prepare('SELECT o.*, p.method AS payment_method, p.status AS payment_status
    FROM orders o
    LEFT JOIN payments p ON o.payment_id = p.payment_id
    WHERE o.user_id = :user_id
    ORDER BY o.order_date DESC');
$orderStmt->execute([':user_id' => $_SESSION['user_id']]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

$alertMessage = $_SESSION['payment_message'] ?? '';
$alertType = $_SESSION['payment_status'] ?? 'success';
$checkoutMessage = $_SESSION['checkout_message'] ?? '';
$checkoutType = $_SESSION['checkout_status'] ?? 'success';
unset($_SESSION['payment_message'], $_SESSION['payment_status'], $_SESSION['checkout_message'], $_SESSION['checkout_status']);

require_once '../includes/header.php';
?>
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title mb-3">Quản lý đơn hàng</h3>
                <p class="text-muted">Bạn có thể chọn phương thức thanh toán để xử lý đơn hàng hiện có.</p>
                <?php if ($checkoutMessage): ?>
                    <div class="alert alert-<?= htmlspecialchars($checkoutType); ?>">
                        <?= htmlspecialchars($checkoutMessage); ?>
                    </div>
                <?php endif; ?>
                <?php if ($alertMessage): ?>
                    <div class="alert alert-<?= htmlspecialchars($alertType); ?>">
                        <?= htmlspecialchars($alertMessage); ?>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Mã</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Phương thức</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?= $order['order_id']; ?></td>
                                    <td><?= formatPrice($order['total_amount']); ?></td>
                                    <td><?= ucfirst($order['status']); ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                    <td><?= htmlspecialchars($order['payment_method'] ?? 'Chưa chọn'); ?></td>
                                    <td>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <form method="post" action="process_payment.php" class="d-flex gap-2">
                                                <input type="hidden" name="order_id" value="<?= $order['order_id']; ?>">
                                                <select name="method" class="form-select form-select-sm">
                                                    <?php foreach (['momo','zalopay','credit_card','paypal','bank_transfer','cod'] as $method): ?>
                                                        <option value="<?= $method; ?>"><?= strtoupper($method); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-sm btn-primary">Thanh toán</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-success">Đã thanh toán</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Chưa có đơn hàng nào.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
