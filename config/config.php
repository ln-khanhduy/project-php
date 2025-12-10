<?php
// Đọc file .env nếu tồn tại
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Bỏ qua dòng comment
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Cấu hình cơ bản
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'PhoneStore');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://localhost/project-php');
//define('SITE_URL', $_ENV['SITE_URL'] ?? 'https://doantkweb.infinityfreeapp.com/project-php/'); // deploy thì mở ra
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/phone-store/uploads/');
define('MAIL_FROM_EMAIL', $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@phonestore.local');
define('MAIL_FROM_NAME', SITE_NAME);
define('OTP_EXPIRE_SECONDS', 300);
define('OTP_EMAIL_SUBJECT', '[' . SITE_NAME . '] Mã OTP đặt lại mật khẩu');
define('SMTP_USE_SMTP', filter_var($_ENV['SMTP_USE_SMTP'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.example.com');
define('SMTP_PORT', (int)($_ENV['SMTP_PORT'] ?? 587));   
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_TIMEOUT', (int)($_ENV['SMTP_TIMEOUT'] ?? 60));

// Cấu hình database
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'phone_store');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Google OAuth
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
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

// Hàm kiểm tra customer
function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

// Hàm format giá
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' ₫';
}
?>