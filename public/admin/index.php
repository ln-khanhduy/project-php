<?php
declare(strict_types=1);

require_once '../../config/config.php';
require_once '../../includes/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn() || !isAdmin()) {
    redirect(SITE_URL . '/public/login.php');
}

$database = new Database();
$db = $database->getConnection();
$db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_locked TINYINT(1) DEFAULT 0");

$message = '';
$alertType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_order'], $_POST['order_id'], $_POST['order_status'])) {
            $orderId = (int)$_POST['order_id'];
            $status = $_POST['order_status'];
            $allowed = ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'];
            if (in_array($status, $allowed, true)) {
                $stmt = $db->prepare('UPDATE orders SET status = :status WHERE order_id = :order_id');
                $stmt->execute([':status' => $status, ':order_id' => $orderId]);
                $message = 'Cập nhật trạng thái đơn hàng thành công.';
            }
        } elseif (isset($_POST['toggle_user'], $_POST['user_id'])) {
            $userId = (int)$_POST['user_id'];
            $lockStmt = $db->prepare('SELECT is_locked FROM users WHERE user_id = :user_id LIMIT 1');
            $lockStmt->execute([':user_id' => $userId]);
            $isLocked = (int)$lockStmt->fetchColumn();
            $toggleStmt = $db->prepare('UPDATE users SET is_locked = :locked WHERE user_id = :user_id');
            $toggleStmt->execute([':locked' => $isLocked ? 0 : 1, ':user_id' => $userId]);
            $message = $isLocked ? 'Đã mở khóa người dùng.' : 'Đã khóa người dùng.';
        } elseif (isset($_POST['delete_product_id'])) {
            $productId = (int)$_POST['delete_product_id'];
            $deleteStmt = $db->prepare('DELETE FROM phones WHERE phone_id = :product_id');
            $deleteStmt->execute([':product_id' => $productId]);
            $message = 'Đã xóa sản phẩm.';
        } elseif (isset($_POST['add_product'])) {
            $brandId = (int)($_POST['brand_id'] ?? 0);
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['phone_name'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $stock = (int)($_POST['stock'] ?? 0);
            $image = trim($_POST['image_url'] ?? 'default.jpg');

            if ($name === '' || $price <= 0 || $brandId <= 0 || $categoryId <= 0) {
                throw new RuntimeException('Vui lòng điền đầy đủ thông tin sản phẩm.');
            }

            $insertStmt = $db->prepare('INSERT INTO phones (brand_id, category_id, phone_name, description, price, stock, image_url, created_at)
                VALUES (:brand_id, :category_id, :phone_name, :description, :price, :stock, :image_url, NOW())');
            $insertStmt->execute([
                ':brand_id' => $brandId,
                ':category_id' => $categoryId,
                ':phone_name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':stock' => $stock,
                ':image_url' => $image,
            ]);
            $message = 'Đã thêm sản phẩm mới.';
        } elseif (isset($_POST['update_product'], $_POST['product_id'])) {
            $productId = (int)$_POST['product_id'];
            $price = (float)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);

            if ($productId <= 0 || $price < 0 || $stock < 0) {
                throw new RuntimeException('Thông tin cập nhật không hợp lệ.');
            }

            $productStmt = $db->prepare('SELECT phone_name FROM phones WHERE phone_id = :product_id LIMIT 1');
            $productStmt->execute([':product_id' => $productId]);
            $productName = $productStmt->fetchColumn();

            if (!$productName) {
                throw new RuntimeException('Không tìm thấy sản phẩm cần cập nhật.');
            }

            $updateStmt = $db->prepare('UPDATE phones SET price = :price, stock = :stock WHERE phone_id = :product_id');
            $updateStmt->execute([
                ':price' => $price,
                ':stock' => $stock,
                ':product_id' => $productId,
            ]);
            $message = 'Đã cập nhật sản phẩm "' . $productName . '".';
        }
    } catch (Throwable $exception) {
        $message = $exception->getMessage();
        $alertType = 'danger';
    }
}

$stats = [];
$stats['totalProducts'] = (int)$db->query('SELECT COUNT(*) FROM phones')->fetchColumn();
$stats['totalOrders'] = (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalCustomerStmt = $db->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
$totalCustomerStmt->execute([':role' => 'customer']);
$stats['totalCustomers'] = (int)$totalCustomerStmt->fetchColumn();
$revenueStmt = $db->query("SELECT IFNULL(SUM(total_amount), 0) FROM orders WHERE status IN ('confirmed','shipping','completed')");
$stats['totalRevenue'] = (float)$revenueStmt->fetchColumn();

$bestStmt = $db->query('SELECT p.phone_name, SUM(od.quantity) AS sold
    FROM order_details od
    JOIN phones p ON od.phone_id = p.phone_id
    GROUP BY p.phone_id
    ORDER BY sold DESC
    LIMIT 5');
$bestSellers = $bestStmt->fetchAll(PDO::FETCH_ASSOC);

$productStmt = $db->query('SELECT p.*, b.brand_name, c.category_name FROM phones p
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    ORDER BY p.created_at DESC');
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

$userStmt = $db->query('SELECT user_id, full_name, email, role, is_locked, created_at FROM users ORDER BY created_at DESC LIMIT 30');
$users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

$orderStmt = $db->query('SELECT o.*, u.full_name FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    ORDER BY o.order_date DESC
    LIMIT 20');
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

$brandList = $db->query('SELECT brand_id, brand_name FROM brands ORDER BY brand_name')->fetchAll(PDO::FETCH_ASSOC);
$categoryList = $db->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị PhoneStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="<?= SITE_URL; ?>/public/admin/index.php">Bảng điều khiển Admin</a>
            <div class="ms-auto">
                <a class="btn btn-outline-light" href="<?= SITE_URL; ?>/public/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
    </nav>

                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Cập nhật sản phẩm</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="update_product" value="1">
                            <div class="col-12">
                                <label class="form-label">Sản phẩm</label>
                                <select name="product_id" class="form-select" required>
                                    <option value="">Chọn sản phẩm cần cập nhật</option>
                                    <?php foreach ($products as $prod): ?>
                                        <option value="<?= $prod['phone_id']; ?>"><?= htmlspecialchars($prod['phone_name']); ?> (<?= htmlspecialchars($prod['brand_name']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Giá mới (₫)</label>
                                <input type="number" step="0.01" min="0" name="price" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kho mới</label>
                                <input type="number" min="0" name="stock" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-warning w-100">Lưu cập nhật</button>
                            </div>
                        </form>
                    </div>
                </div>

    <div class="container-fluid mt-4 mb-5 px-4">
        <?php if ($message): ?>
            <div class="alert alert-<?= $alertType; ?>">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <?php foreach (['Tổng sản phẩm' => $stats['totalProducts'], 'Tổng đơn hàng' => $stats['totalOrders'], 'Khách hàng' => $stats['totalCustomers']] as $label => $value): ?>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <small class="text-muted"><?php echo $label; ?></small>
                            <h3 class="fw-bold"><?php echo number_format((int)$value); ?></h3>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <small class="text-muted">Doanh thu (xác nhận)</small>
                        <h3 class="fw-bold text-danger"><?= formatPrice($stats['totalRevenue']); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Top sản phẩm bán chạy</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if ($bestSellers): ?>
                            <?php foreach ($bestSellers as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= htmlspecialchars($item['phone_name']); ?></span>
                                    <span class="badge bg-primary rounded-pill"><?= (int)$item['sold']; ?> sp</span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted">Chưa có đơn hàng nào.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Thêm sản phẩm</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" class="row g-2">
                            <input type="hidden" name="add_product" value="1">
                            <div class="col-12">
                                <label class="form-label">Tên sản phẩm</label>
                                <input type="text" name="phone_name" class="form-control" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Thương hiệu</label>
                                <select name="brand_id" class="form-select" required>
                                    <option value="">Chọn thương hiệu</option>
                                    <?php foreach ($brandList as $brand): ?>
                                        <option value="<?= $brand['brand_id']; ?>"><?= htmlspecialchars($brand['brand_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Loại</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Chọn loại</option>
                                    <?php foreach ($categoryList as $category): ?>
                                        <option value="<?= $category['category_id']; ?>"><?= htmlspecialchars($category['category_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Giá bán</label>
                                <input type="number" step="0.01" min="0" name="price" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kho</label>
                                <input type="number" min="0" name="stock" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Ảnh (tên file)</label>
                                <input type="text" name="image_url" class="form-control" placeholder="vd: iphone15pro.jpg">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Mô tả</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-success w-100">Thêm mới</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Danh sách sản phẩm</h5>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tên</th>
                                    <th>Thương hiệu</th>
                                    <th>Loại</th>
                                    <th>Giá</th>
                                    <th>Kho</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?= $product['phone_id']; ?></td>
                                        <td><?= htmlspecialchars($product['phone_name']); ?></td>
                                        <td><?= htmlspecialchars($product['brand_name']); ?></td>
                                        <td><?= htmlspecialchars($product['category_name']); ?></td>
                                        <td><?= formatPrice($product['price']); ?></td>
                                        <td><?= (int)$product['stock']; ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="delete_product_id" value="<?= $product['phone_id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger">Xóa</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Danh sách người dùng</h5>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Vai trò</th>
                                    <th>Trạng thái</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['user_id']; ?></td>
                                        <td><?= htmlspecialchars($user['full_name']); ?></td>
                                        <td><?= htmlspecialchars($user['email']); ?></td>
                                        <td><?= htmlspecialchars($user['role']); ?></td>
                                        <td>
                                            <?php if ((int)$user['is_locked']): ?>
                                                <span class="badge bg-danger">Đã khóa</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Đang hoạt động</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="toggle_user" value="1">
                                                <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">
                                                <button class="btn btn-sm <?= ((int)$user['is_locked']) ? 'btn-outline-success' : 'btn-outline-warning'; ?>">
                                                    <?= ((int)$user['is_locked']) ? 'Mở khóa' : 'Khóa'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Danh sách đơn hàng</h5>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Mã</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày</th>
                                    <th>Cập nhật</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['order_id']; ?></td>
                                        <td><?= htmlspecialchars($order['full_name'] ?? 'Khách vãng lai'); ?></td>
                                        <td><?= formatPrice($order['total_amount']); ?></td>
                                        <td>
                                            <form id="order-form-<?= $order['order_id']; ?>" method="post" class="d-flex gap-2 align-items-center">
                                                <input type="hidden" name="update_order" value="1">
                                                <input type="hidden" name="order_id" value="<?= $order['order_id']; ?>">
                                                <select name="order_status" class="form-select form-select-sm">
                                                    <?php foreach (['pending','confirmed','shipping','completed','cancelled'] as $status): ?>
                                                        <option value="<?= $status; ?>" <?= $order['status'] === $status ? 'selected' : ''; ?>><?= ucfirst($status); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" form="order-form-<?= $order['order_id']; ?>">Lưu</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
