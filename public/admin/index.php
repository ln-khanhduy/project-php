<?php
declare(strict_types=1);

require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu admin, chuyển đến trang chủ admin
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    redirect(SITE_URL . '/public/admin/dashboard.php');
}

// Nếu không phải admin hoặc chưa đăng nhập, về trang chủ
redirect(SITE_URL . '/public/index.php');
