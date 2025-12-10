<?php
declare(strict_types=1);

/**
 * kiểm tra quyền admin
 * Sử dụng ở đầu mỗi trang admin
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('require_admin')) {
    function require_admin(): void {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
            $_SESSION['auth_error'] = 'Vui lòng đăng nhập để tiếp tục.';
            header('Location: ' . SITE_URL . '/public/login.php');
            exit;
        }

        if ($_SESSION['role'] !== 'admin') {
            $_SESSION['auth_error'] = 'Bạn không có quyền truy cập trang này.';
            header('Location: ' . SITE_URL . '/public/index.php');
            exit;
        }
    }
}
