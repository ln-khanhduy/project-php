<?php
declare(strict_types=1);


require_once '../config/config.php';
require_once '../includes/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    redirect(SITE_URL . '/public/index.php');
}

$alertMessage = $_SESSION['auth_error'] ?? '';
unset($_SESSION['auth_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    if (!$email || $password === '') {
        $alertMessage = 'Vui lòng điền đầy đủ email và mật khẩu.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare('SELECT user_id, full_name, email, password, role, provider, oauth_id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $alertMessage = 'Email hoặc mật khẩu không đúng.';
        } else {
            $storedPassword = $user['password'] ?? '';
            $passwordMatched = false;

            if ($storedPassword !== '' && password_verify($password, $storedPassword)) {
                $passwordMatched = true;
            } elseif ($storedPassword !== '' && hash('sha256', $password) === $storedPassword) {
                $passwordMatched = true;
            } elseif ($storedPassword === $password) {
                $passwordMatched = true;
            }

            if ($passwordMatched) {
                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['full_name'] = $user['full_name'] ?? 'Khách hàng';
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'] ?? 'customer';
                $_SESSION['provider'] = $user['provider'] ?? 'local';
                $_SESSION['oauth_id'] = $user['oauth_id'] ?? null;

                $countStmt = $db->prepare('SELECT SUM(quantity) FROM cart_items WHERE user_id = :user_id');
                $countStmt->execute([':user_id' => $user['user_id']]);
                $_SESSION['cart_count'] = (int)$countStmt->fetchColumn();

                redirect(SITE_URL . '/public/index.php');
            }

            $alertMessage = 'Email hoặc mật khẩu không đúng.';
        }
    }
}

$formEmail = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES);

require_once '../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="card-title mb-3 text-center">Đăng nhập vào PhoneStore</h2>
                <?php if (!empty($alertMessage)): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($alertMessage); ?>
                    </div>
                <?php endif; ?>
                <div class="row g-0">
                    <div class="col-md-6 border-end pe-3">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= $formEmail; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu</label>
                                <input type="password" name="password" class="form-control" autocomplete="current-password" required>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-primary">Đăng nhập</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <a href="reset_password.php" class="text-decoration-none">Quên mật khẩu?</a>
                            <span class="mx-2">|</span>
                            <a href="register.php" class="text-decoration-none">Đăng ký tài khoản</a>
                        </div>
                    </div>
                    <div class="col-md-6 ps-3 text-center">
                        <p class="text-muted">Hoặc đăng nhập nhanh bằng tài khoản Gmail</p>
                        <a href="google_oauth.php" class="btn btn-outline-danger w-100 mb-2">
                            <i class="fab fa-google me-2"></i> Đăng nhập bằng Gmail
                        </a>
                        <p class="text-muted small">Chúng tôi chỉ cần email và tên để tạo tài khoản. Mọi thông tin khác đều bảo mật.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
require_once '../includes/footer.php';
