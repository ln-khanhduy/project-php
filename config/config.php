<?php
// Cấu hình cơ bản
define('SITE_NAME', 'PhoneStore');
define('SITE_URL', 'http://localhost/phone-store');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/phone-store/uploads/');

// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_NAME', 'phone_store');
define('DB_USER', 'root');
define('DB_PASS', '');

// Hàm chuyển hướng
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Hàm kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Hàm kiểm tra admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Hàm format giá
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' ₫';
}
?>