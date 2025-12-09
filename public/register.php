<?php
declare(strict_types=1);

require_once '../config/config.php';
require_once '../includes/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu đã đăng nhập thì chuyển về trang chủ
if (isset($_SESSION['user_id'])) {
    redirect(SITE_URL . '/public/index.php');
}

$alertMessage = '';
$alertType = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    // Validate dữ liệu
    if (empty($fullName)) {
        $alertMessage = 'Vui lòng nhập họ tên.';
    } elseif (!$email) {
        $alertMessage = 'Email không hợp lệ.';
    } elseif (strlen($password) < 6) {
        $alertMessage = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($password !== $confirmPassword) {
        $alertMessage = 'Mật khẩu xác nhận không khớp.';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        // Kiểm tra email đã tồn tại chưa
        $checkStmt = $db->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
        $checkStmt->execute([':email' => $email]);
        
        if ($checkStmt->fetchColumn()) {
            $alertMessage = 'Email này đã được đăng ký.';
        } else {
            // Mã hóa mật khẩu
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Thêm user mới
            $insertStmt = $db->prepare('INSERT INTO users (full_name, email, password, provider, role, created_at) 
                VALUES (:full_name, :email, :password, :provider, :role, NOW())');
            
            $success = $insertStmt->execute([
                ':full_name' => $fullName,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':provider' => 'local',
                ':role' => 'customer'
            ]);

            if ($success) {
                $alertMessage = 'Đăng ký thành công! Bạn có thể đăng nhập ngay.';
                $alertType = 'success';
                
                // Tự động đăng nhập sau khi đăng ký
                $userId = (int)$db->lastInsertId();
                $_SESSION['user_id'] = $userId;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'customer';
                $_SESSION['provider'] = 'local';
                $_SESSION['cart_count'] = 0;
                
                // Chuyển về trang chủ sau 1 giây
                header('Refresh: 1; url=' . SITE_URL . '/public/index.php');
            } else {
                $alertMessage = 'Có lỗi xảy ra. Vui lòng thử lại.';
            }
        }
    }
}

$formFullName = htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES);
$formEmail = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES);

require_once '../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="card-title mb-3 text-center">Đăng ký tài khoản</h2>
                
                <?php if (!empty($alertMessage)): ?>
                    <div class="alert alert-<?= $alertType; ?>">
                        <?= htmlspecialchars($alertMessage); ?>
                    </div>
                <?php endif; ?>

                <div class="row g-0">
                    <div class="col-md-6 border-end pe-3">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" value="<?= $formFullName; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?= $formEmail; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password" required>
                                <small class="text-muted">Tối thiểu 6 ký tự</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" class="form-control" minlength="6" autocomplete="new-password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Đăng ký</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <span class="text-muted">Đã có tài khoản?</span>
                            <a href="login.php" class="text-decoration-none">Đăng nhập ngay</a>
                        </div>
                    </div>
                    <div class="col-md-6 ps-3 text-center">
                        <p class="text-muted">Hoặc đăng ký nhanh bằng tài khoản Gmail</p>
                        <a href="google_oauth.php" class="btn btn-outline-danger w-100 mb-2">
                            <i class="fab fa-google me-2"></i> Đăng ký bằng Gmail
                        </a>
                        <p class="text-muted small mt-3">
                            Bằng việc đăng ký, bạn đồng ý với <a href="#">Điều khoản sử dụng</a> 
                            và <a href="#">Chính sách bảo mật</a> của chúng tôi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
require_once '../includes/footer.php';
