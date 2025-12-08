<?php
// Cấu hình cơ bản
define('SITE_NAME', 'PhoneStore');
define('SITE_URL', 'http://localhost/project-php');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/phone-store/uploads/');
define('MAIL_FROM_EMAIL', 'noreply@phonestore.local');
define('MAIL_FROM_NAME', SITE_NAME);
define('OTP_EXPIRE_SECONDS', 300);
define('OTP_EMAIL_SUBJECT', '[' . SITE_NAME . '] Mã OTP đặt lại mật khẩu');
define('SMTP_USE_SMTP', true);
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);   
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME', 'khanhduy030204@gmail.com');
define('SMTP_PASSWORD', '612004Tra');
define('SMTP_TIMEOUT', 60);

// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_NAME', 'phone_store');
define('DB_USER', 'root');
define('DB_PASS', '612004tra');

// Google OAuth
define('GOOGLE_CLIENT_ID', '71491710738-7605j5cou1t7nn9i38fop77qvqu6i8uj.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/public/oauth_callback.php');

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