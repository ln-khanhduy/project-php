<?php
declare(strict_types=1);

require_once '../config/config.php';
require_once '../includes/database.php';
require_once '../includes/mailer.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$alertMessage = '';
$alertType = 'danger';
$email = '';
$step = $_POST['step'] ?? 'request';
$resendRequested = isset($_POST['resend']);
if ($resendRequested) {
    $step = 'request';
}
$otpInput = trim($_POST['otp'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: '';
    if ($step === 'request') {
        unset($_SESSION['reset_otp_hash'], $_SESSION['reset_otp_email'], $_SESSION['reset_otp_expires']);

        if ($email === '') {
            $alertMessage = 'Vui lòng nhập email.';
        } else {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $userId = $stmt->fetchColumn();

            if (!$userId) {
                $alertMessage = 'Không tìm thấy tài khoản với email này.';
            } else {
                $otpCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $sent = send_otp_email($email, $otpCode);

                if (!$sent) {
                    $alertMessage = 'Không thể gửi mã OTP ngay bây giờ. Vui lòng thử lại sau.';
                } else {
                    $_SESSION['reset_otp_hash'] = password_hash($otpCode, PASSWORD_DEFAULT);
                    $_SESSION['reset_otp_email'] = $email;
                    $_SESSION['reset_otp_expires'] = time() + OTP_EXPIRE_SECONDS;
                    $alertType = 'info';
                    $alertMessage = 'Đã gửi mã OTP đến email. Vui lòng kiểm tra và nhập mã trong vòng ' . (OTP_EXPIRE_SECONDS / 60) . ' phút.';
                    $step = 'verify';
                    $otpInput = '';
                }
            }
        }
    } elseif ($step === 'verify') {
        $password = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');

        if ($email === '' || $password === '' || $confirm === '' || $otpInput === '') {
            $alertMessage = 'Vui lòng điền đầy đủ các trường bao gồm mã OTP.';
        } elseif ($password !== $confirm) {
            $alertMessage = 'Mật khẩu xác nhận không khớp.';
        } elseif (strlen($password) < 8) {
            $alertMessage = 'Mật khẩu phải có tối thiểu 8 ký tự.';
        } elseif (empty($_SESSION['reset_otp_hash']) || empty($_SESSION['reset_otp_email']) || empty($_SESSION['reset_otp_expires']) || time() > $_SESSION['reset_otp_expires']) {
            $alertMessage = 'Mã OTP không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.';
            $step = 'request';
        } elseif (strcasecmp($email, $_SESSION['reset_otp_email']) !== 0) {
            $alertMessage = 'Email không trùng với phiên đã gửi OTP.';
        } elseif (!password_verify($otpInput, $_SESSION['reset_otp_hash'])) {
            $alertMessage = 'Mã OTP không đúng.';
        } else {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $userId = $stmt->fetchColumn();

            if (!$userId) {
                $alertMessage = 'Không tìm thấy tài khoản với email này.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $update = $db->prepare('UPDATE users SET password = :password, provider = NULL, oauth_id = NULL WHERE user_id = :user_id');
                $update->execute([':password' => $hash, ':user_id' => $userId]);

                $alertType = 'success';
                $alertMessage = 'Mật khẩu đã được cập nhật. Bạn có thể đăng nhập lại ngay bây giờ.';
                unset($_SESSION['reset_otp_hash'], $_SESSION['reset_otp_email'], $_SESSION['reset_otp_expires']);
                $password = $confirm = '';
                $step = 'request';
            }
        }
    }
}

require_once '../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-center mb-3">Đặt lại mật khẩu</h2>
                <?php if (!empty($alertMessage)): ?>
                    <div class="alert alert-<?= htmlspecialchars($alertType); ?>">
                        <?= htmlspecialchars($alertMessage); ?>
                    </div>
                <?php endif; ?>
                <?php $isVerify = ($step === 'verify'); ?>
                <form method="post">
                    <input type="hidden" name="step" value="<?= $isVerify ? 'verify' : 'request'; ?>">
                    <div class="mb-3">
                        <label class="form-label">Email đăng ký</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email); ?>" required <?= $isVerify ? 'readonly' : ''; ?>>
                    </div>
                    <?php if (!$isVerify): ?>
                        <button class="btn btn-primary w-100">Gửi mã OTP</button>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label">Mã OTP</label>
                            <input type="text" name="otp" class="form-control" value="<?= htmlspecialchars($otpInput); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Xác nhận mật khẩu</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button class="btn btn-success w-100">Cập nhật mật khẩu</button>
                            <button type="submit" name="resend" value="1" class="btn btn-link">Gửi lại mã OTP</button>
                        </div>
                    <?php endif; ?>
                </form>
                <p class="text-center mt-3 small text-muted">Nếu bạn đã có tài khoản Gmail thì có thể dùng nút "Đăng nhập bằng Gmail" trên trang đăng nhập để lấy lại quyền truy cập.</p>
            </div>
        </div>
    </div>
</div>
<?php
require_once '../includes/footer.php';
