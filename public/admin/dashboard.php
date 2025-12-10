<?php
declare(strict_types=1);

require_once '../../config/config.php';
require_once '../../includes/database.php';
require_once '../../includes/admin_auth.php';

require_admin();

$database = new Database();
$db = $database->getConnection();

// Thống kê
$stats = [
    'total_products' => 0,
    'total_orders' => 0,
    'total_users' => 0,
    'total_revenue' => 0,
    'pending_orders' => 0
];

try {
    $stmt = $db->query('SELECT COUNT(*) FROM phones');
    $stats['total_products'] = (int)$stmt->fetchColumn();

    $stmt = $db->query('SELECT COUNT(*) FROM orders');
    $stats['total_orders'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
    $stats['total_users'] = (int)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('completed', 'shipping')");
    $stats['total_revenue'] = (float)$stmt->fetchColumn();

    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
    $stats['pending_orders'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Dashboard stats error: ' . $e->getMessage());
}

// Đơn hàng gần đây
$recentOrders = [];
try {
    $stmt = $db->query("SELECT o.*, u.full_name, u.email 
                        FROM orders o 
                        LEFT JOIN users u ON o.user_id = u.user_id 
                        ORDER BY o.created_at DESC 
                        LIMIT 10");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Recent orders error: ' . $e->getMessage());
}

require_once '../../includes/header.php';

$activePage = 'dashboard';
$pageTitle = 'Dashboard';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../../includes/admin_sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php include '../../includes/admin_header.php'; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Sản phẩm</h5>
                            <h2><?= number_format($stats['total_products']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">Đơn hàng</h5>
                            <h2><?= number_format($stats['total_orders']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Người dùng</h5>
                            <h2><?= number_format($stats['total_users']); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Doanh thu</h5>
                            <h2><?= number_format($stats['total_revenue']); ?>đ</h2>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($stats['pending_orders'] > 0): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Thông báo:</strong> Có <?= $stats['pending_orders']; ?> đơn hàng chờ xử lý!
            </div>
            <?php endif; ?>

            <!-- Recent Orders -->
            <h3 class="mb-3">Đơn hàng gần đây</h3>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th>Email</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Chưa có đơn hàng nào</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?= $order['order_id']; ?></td>
                                <td><?= htmlspecialchars($order['full_name'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($order['email'] ?? 'N/A'); ?></td>
                                <td><?= number_format($order['total_amount']); ?>đ</td>
                                <td>
                                    <?php
                                    $statusMap = [
                                        'pending' => '<span class="badge bg-warning">Chờ xử lý</span>',
                                        'confirmed' => '<span class="badge bg-info">Đã xác nhận</span>',
                                        'shipping' => '<span class="badge bg-primary">Đang giao</span>',
                                        'completed' => '<span class="badge bg-success">Hoàn thành</span>',
                                        'cancelled' => '<span class="badge bg-danger">Đã hủy</span>'
                                    ];
                                    echo $statusMap[$order['status']] ?? $order['status'];
                                    ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/admin_styles.php'; ?>
<?php require_once '../../includes/footer.php'; ?>
