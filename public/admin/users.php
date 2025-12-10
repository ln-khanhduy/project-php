<?php
declare(strict_types=1);

require_once '../../config/config.php';
require_once '../../includes/database.php';
require_once '../../includes/admin_auth.php';
require_once '../../models/User.php';

require_admin();

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

$message = '';
$alertType = 'success';

// Xử lý khóa/mở khóa user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_lock'])) {
    try {
        $userId = (int)$_POST['user_id'];
        // Lấy trạng thái hiện tại
        $user = $userModel->findById($userId);
        $newLockStatus = $user['is_locked'] ? 0 : 1;
        $userModel->update($userId, ['is_locked' => $newLockStatus]);
        $message = 'Cập nhật trạng thái người dùng thành công!';
    } catch (Exception $e) {
        $message = 'Lỗi: ' . $e->getMessage();
        $alertType = 'danger';
    }
}

// Xử lý thay đổi role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    try {
        $userId = (int)$_POST['user_id'];
        $role = $_POST['role'];
        
        if (in_array($role, ['customer', 'admin'], true)) {
            $userModel->update($userId, ['role' => $role]);
            $message = 'Thay đổi quyền người dùng thành công!';
        }
    } catch (Exception $e) {
        $message = 'Lỗi: ' . $e->getMessage();
        $alertType = 'danger';
    }
}

// Lấy danh sách người dùng
$filter = $_GET['filter'] ?? 'all';
$users = [];

try {
    $sql = "SELECT u.*, COUNT(DISTINCT o.order_id) as total_orders, COALESCE(SUM(o.total_amount), 0) as total_spent
            FROM users u
            LEFT JOIN orders o ON u.user_id = o.user_id";
    
    if ($filter === 'admin') {
        $sql .= " WHERE u.role = 'admin'";
    } elseif ($filter === 'customer') {
        $sql .= " WHERE u.role = 'customer'";
    } elseif ($filter === 'locked') {
        $sql .= " WHERE u.is_locked = 1";
    }
    
    $sql .= " GROUP BY u.user_id ORDER BY u.created_at DESC";
    
    $stmt = $db->query($sql);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Users error: ' . $e->getMessage());
}

require_once '../../includes/header.php';

$activePage = 'users';
$pageTitle = 'Quản lý người dùng';
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
                        Tất cả (<?= count($users); ?>)
                    </a>
                    <a href="?filter=customer" class="btn btn-outline-info <?= $filter === 'customer' ? 'active' : ''; ?>">
                        Khách hàng
                    </a>
                    <a href="?filter=admin" class="btn btn-outline-success <?= $filter === 'admin' ? 'active' : ''; ?>">
                        Admin
                    </a>
                    <a href="?filter=locked" class="btn btn-outline-danger <?= $filter === 'locked' ? 'active' : ''; ?>">
                        Đã khóa
                    </a>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Quyền</th>
                            <th>Provider</th>
                            <th>Đơn hàng</th>
                            <th>Tổng chi</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="10" class="text-center">Không có người dùng nào</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['user_id']; ?></td>
                                <td><?= htmlspecialchars($user['full_name']); ?></td>
                                <td><?= htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['role'] === 'admin' ? 'success' : 'secondary'; ?>">
                                        <?= $user['role'] === 'admin' ? 'Admin' : 'Khách hàng'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['provider'] === 'google'): ?>
                                        <span class="badge bg-danger">Google</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Local</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $user['total_orders']; ?></td>
                                <td><?= number_format((float)$user['total_spent']); ?>đ</td>
                                <td>
                                    <?php if ($user['is_locked']): ?>
                                        <span class="badge bg-danger">🔒 Khóa</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">✓ Hoạt động</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">
                                        <button type="submit" name="toggle_lock" class="btn btn-sm btn-<?= $user['is_locked'] ? 'success' : 'warning'; ?>">
                                            <?= $user['is_locked'] ? '🔓 Mở' : '🔒 Khóa'; ?>
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-primary" onclick="changeRole(<?= $user['user_id']; ?>, '<?= $user['role']; ?>')">
                                        ⚙️ Quyền
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

<!-- Change Role Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="user_id" id="role_user_id">
                <div class="modal-header">
                    <h5 class="modal-title">Thay đổi quyền người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Quyền</label>
                    <select name="role" id="role_select" class="form-select" required>
                        <option value="customer">Khách hàng</option>
                        <option value="admin">Admin</option>
                    </select>
                    <div class="alert alert-warning mt-3">
                        <small> Lưu ý: Admin có toàn quyền truy cập hệ thống!</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="change_role" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/admin_styles.php'; ?>

<script>
function changeRole(userId, currentRole) {
    document.getElementById('role_user_id').value = userId;
    document.getElementById('role_select').value = currentRole;
    new bootstrap.Modal(document.getElementById('roleModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>
