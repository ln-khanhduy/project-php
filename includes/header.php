<?php
session_start();
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
        .navbar-brand { font-weight: bold; color: #007bff !important; }
        .card { transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .product-image { height: 200px; object-fit: cover; }
        .price { color: #dc3545; font-weight: bold; }
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
        transform: translateY(-5px); 
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
</style>
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mobile-alt"></i> PhoneStore
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
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
                </ul>
                
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart"></i> Giỏ hàng
                                <?php if(isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                                    <span class="badge bg-danger"><?php echo $_SESSION['cart_count']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
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
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Đăng nhập</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Đăng ký</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <form class="d-flex ms-2" action="search.php" method="GET">
                    <input class="form-control me-2" type="search" name="q" placeholder="Tìm kiếm sản phẩm...">
                    <button class="btn btn-outline-primary" type="submit">Tìm</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mt-4">