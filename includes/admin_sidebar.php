<?php
/**
 * Admin Sidebar Component
 * Hiển thị menu điều hướng cho trang quản trị
 * 
 * @param string $activePage - Tên trang đang active (dashboard, products, orders, users, brands)
 */

$activePage = $activePage ?? 'dashboard';
?>

<nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
    <div class="position-sticky pt-3">
        <h5 class="px-3 mb-3">Admin Panel</h5>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                     Thống kê
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'products' ? 'active' : ''; ?>" href="products.php">
                     Quản lý sản phẩm
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'orders' ? 'active' : ''; ?>" href="orders.php">
                     Quản lý đơn hàng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'users' ? 'active' : ''; ?>" href="users.php">
                     Quản lý người dùng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $activePage === 'brands' ? 'active' : ''; ?>" href="brands.php">
                     Thương hiệu & Danh mục
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../index.php">
                    ← Về trang chủ
                </a>
            </li>
        </ul>
    </div>
</nav>
