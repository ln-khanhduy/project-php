<?php
declare(strict_types=1);

require_once '../../config/config.php';
require_once '../../includes/database.php';
require_once '../../includes/admin_auth.php';

require_admin();

require_once '../../includes/header.php';

$activePage = 'home';
$pageTitle = 'Trang chủ Admin';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../../includes/admin_sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <?php include '../../includes/admin_header.php'; ?>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Xin chào Admin!</h5>
                            <p class="card-text">Chào mừng bạn đến với hệ thống quản lý PhoneStore.</p>
                            <p>Sử dụng menu bên trái để quản lý:</p>
                            <ul>
                                <li>Sản phẩm điện thoại</li>
                                <li>Đơn hàng khách hàng</li>
                                <li>Tài khoản người dùng</li>
                                <li>Thương hiệu & danh mục</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin tài khoản</h5>
                            <p><strong>Tên:</strong> <?= htmlspecialchars($_SESSION['full_name']); ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['email']); ?></p>
                            <p><strong>Vai trò:</strong> <span class="badge bg-danger">Quản trị viên</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
