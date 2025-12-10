<?php
declare(strict_types=1);

require_once '../../config/config.php';
require_once '../../includes/database.php';
require_once '../../includes/admin_auth.php';
require_once '../../models/Order.php';

require_admin();

$database = new Database();
$db = $database->getConnection();
$orderModel = new Order($db);

$message = '';
$alertType = 'success';

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $orderId = (int)$_POST['order_id'];
        $status = $_POST['status'];
        $allowedStatuses = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
        
        if (in_array($status, $allowedStatuses, true)) {
            $orderModel->updateStatus($orderId, $status);
            $message = 'Cập nhật trạng thái đơn hàng thành công!';
        }
    } catch (Exception $e) {
        $message = 'Lỗi: ' . $e->getMessage();
        $alertType = 'danger';
    }
}

// Lấy danh sách đơn hàng
$filter = $_GET['filter'] ?? 'all';
$orders = [];

try {
    $sql = "SELECT o.*, u.full_name, u.email, u.phone 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.user_id";
    
    if ($filter !== 'all') {
        $sql .= " WHERE o.status = :status";
    }
    
    $sql .= " ORDER BY o.created_at DESC";
    
    $stmt = $db->prepare($sql);
    if ($filter !== 'all') {
        $stmt->execute([':status' => $filter]);
    } else {
        $stmt->execute();
    }
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Orders error: ' . $e->getMessage());
}

require_once '../../includes/header.php';

$activePage = 'orders';
$pageTitle = 'Quản lý đơn hàng';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../../includes/admin_sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php include '../../includes/admin_header.php'; ?>
            <?php include '../../includes/admin_alert.php'; ?>

            <!-- Filter -->
            <div class="mb-3">
                <div class="btn-group" role="group">
                    <a href="?filter=all" class="btn btn-outline-primary <?= $filter === 'all' ? 'active' : ''; ?>">
                        Tất cả (<?= count($orders); ?>)
                    </a>
                    <a href="?filter=pending" class="btn btn-outline-warning <?= $filter === 'pending' ? 'active' : ''; ?>">
                        Chờ xử lý
                    </a>
                    <a href="?filter=confirmed" class="btn btn-outline-info <?= $filter === 'confirmed' ? 'active' : ''; ?>">
                        Đã xác nhận
                    </a>
                    <a href="?filter=shipping" class="btn btn-outline-primary <?= $filter === 'shipping' ? 'active' : ''; ?>">
                        Đang giao
                    </a>
                    <a href="?filter=completed" class="btn btn-outline-success <?= $filter === 'completed' ? 'active' : ''; ?>">
                        Hoàn thành
                    </a>
                    <a href="?filter=cancelled" class="btn btn-outline-danger <?= $filter === 'cancelled' ? 'active' : ''; ?>">
                        Đã hủy
                    </a>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th>SĐT</th>
                            <th>Địa chỉ</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Không có đơn hàng nào</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= $order['order_id']; ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($order['full_name'] ?? 'N/A'); ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($order['email'] ?? ''); ?></small>
                                </td>
                                <td><?= htmlspecialchars($order['phone'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($order['shipping_address'] ?? 'N/A'); ?></td>
                                <td><strong><?= number_format($order['total_amount']); ?>đ</strong></td>
                                <td>
                                    <?php
                                    $statusMap = [
                                        'pending' => '<span class="badge bg-warning text-dark">Chờ xử lý</span>',
                                        'confirmed' => '<span class="badge bg-info">Đã xác nhận</span>',
                                        'shipping' => '<span class="badge bg-primary">Đang giao</span>',
                                        'completed' => '<span class="badge bg-success">Hoàn thành</span>',
                                        'cancelled' => '<span class="badge bg-danger">Đã hủy</span>'
                                    ];
                                    echo $statusMap[$order['status']] ?? htmlspecialchars($order['status']);
                                    ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewOrder(<?= $order['order_id']; ?>)">
                                        👁️ Xem
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="updateStatus(<?= $order['order_id']; ?>, '<?= $order['status']; ?>')">
                                        ✏️ Sửa
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="order_id" id="status_order_id">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật trạng thái đơn hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" id="status_select" class="form-select" required>
                        <option value="pending">Chờ xử lý</option>
                        <option value="confirmed">Đã xác nhận</option>
                        <option value="shipping">Đang giao hàng</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="cancelled">Đã hủy</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Order Modal -->
<div class="modal fade" id="viewOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết đơn hàng #<span id="view_order_id"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="order_details">
                <div class="text-center"><div class="spinner-border"></div></div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/admin_styles.php'; ?>

<script>
function updateStatus(orderId, currentStatus) {
    document.getElementById('status_order_id').value = orderId;
    document.getElementById('status_select').value = currentStatus;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function viewOrder(orderId) {
    document.getElementById('view_order_id').textContent = orderId;
    const modal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
    modal.show();
    
    // Load order details via AJAX
    fetch('order_details.php?id=' + orderId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('order_details').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('order_details').innerHTML = '<div class="alert alert-danger">Lỗi tải dữ liệu</div>';
        });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
