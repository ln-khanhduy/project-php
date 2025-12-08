<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
 
$cartCount = max(0, (int)($_SESSION['cart_count'] ?? 0));
$provider = $_SESSION['provider'] ?? 'local';
$providerLabelMap = [
    'google' => 'Gmail',
    'local' => 'Email',
];
$providerLabel = $providerLabelMap[$provider] ?? ucfirst($provider);
$avatarUrl = $_SESSION['avatar'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhoneStore - Bán điện thoại chính hãng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
            font-size: 1.5rem;
        }
        .card {
            transition: transform 0.2s;
            border: 1px solid #dee2e6;
        }
        .card:hover {
        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            font-weight: 600;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }
        .provider-badge {
            font-size: 0.65rem;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            margin-left: 0.35rem;
        }
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .product-image {
            width: 100%;
            height: 225px;
            object-fit: cover;
        }
        .image-container {
            height: 225px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .price {
            color: #dc3545;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .hero-section {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }
        .cart-badge {
            font-size: 0.7rem;
            margin-left: 2px;
        }
        .header-layout {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1.5rem;
        }
        .header-left {
            flex: 1;
            min-width: 230px;
        }
        .header-left .navbar-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .header-left .search-form {
            margin-top: 0.35rem;
            max-width: 360px;
        }
        .header-left .search-form .btn {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }
        .header-left .search-form .btn:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }
        .header-right {
            flex: 1 1 500px;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            justify-content: center;
        }
        .header-right .nav-menu {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: flex-end;
            align-items: center;
            height: 100%;
        }
        .header-right .nav-menu .nav-item {
            white-space: nowrap;
        }
        .header-right .nav-menu .dropdown-menu {
            left: auto;
            right: 0;
        }
        .nav-link {
            padding: 0.35rem 0.5rem;
            border-radius: 0.4rem;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .nav-link:hover,
        .nav-link:focus {
            background-color: rgba(0, 123, 255, 0.1);
            color: #003a8c;
        }
        .header-right .user-actions {
            align-self: flex-end;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.25rem;
        }
        .header-right .user-actions .nav-link {
            padding: 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <div class="header-layout w-100">
                <div class="header-left">
                    <a class="navbar-brand" href="index.php">
                        <i class="fas fa-mobile-alt"></i>
                        <span class="fw-bold">PhoneStore</span>
                    </a>
                    <form class="search-form" action="search.php" method="GET">
                        <div class="input-group">
                            <input type="search" class="form-control border-primary" name="q" placeholder="Tìm kiếm sản phẩm..." aria-label="Tìm kiếm">
                            <button class="btn btn-primary" type="submit">Tìm</button>
                        </div>
                    </form>
                </div>
                <div class="header-right">
                    <ul class="navbar-nav nav-menu">
                        <li class="nav-item">
                            <a class="nav-link active" href="index.php">Trang chủ</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                Thương hiệu
                            </a>
                            <ul class="dropdown-menu">
                                <?php
                                $database = new Database();
                                $db = $database->getConnection();
                                $brand_query = "SELECT * FROM brands ORDER BY brand_name";
                                $brand_stmt = $db->prepare($brand_query);
                                $brand_stmt->execute();

                                while ($brand = $brand_stmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<li><a class="dropdown-item" href="products.php?brand='.$brand['brand_id'].'">'.$brand['brand_name'].'</a></li>';
                                }
                                ?>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">Tất cả sản phẩm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payments.php">Đơn hàng</a>
                        </li>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="cart.php">
                                    <i class="fas fa-shopping-cart"></i> Giỏ hàng
                                    <?php if(isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                                        <span class="badge bg-danger"><?php echo $_SESSION['cart_count']; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login.php">Đăng nhập</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="register.php">Đăng ký</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav user-actions">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user"></i> <?php echo $_SESSION['full_name']; ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="profile.php">Hồ sơ</a></li>
                                    <li><a class="dropdown-item" href="orders.php">Đơn hàng</a></li>
                                    <?php if($_SESSION['role'] == 'admin'): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="admin/">Quản trị</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">