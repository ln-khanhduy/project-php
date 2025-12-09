<?php
declare(strict_types=1);

require_once '../config/config.php';
require_once '../includes/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    redirect(SITE_URL . '/public/login.php');
}

$alertMessage = '';
$alertType = 'danger';

$database = new Database();
$db = $database->getConnection();

// Lấy thông tin người dùng hiện tại
$userStmt = $db->prepare('SELECT user_id, full_name, email, avatar, provider, role, created_at FROM users WHERE user_id = :user_id LIMIT 1');
$userStmt->execute([':user_id' => $_SESSION['user_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Nếu không tìm thấy user, đăng xuất để tránh trạng thái xấu
    session_destroy();
    redirect(SITE_URL . '/public/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($fullName === '') {
        $alertMessage = 'Vui lòng nhập họ tên.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $alertMessage = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    } elseif ($password !== '' && $password !== $confirmPassword) {
        $alertMessage = 'Xác nhận mật khẩu không khớp.';
    } else {
        $params = [
            ':full_name' => $fullName,
            ':user_id' => $user['user_id'],
        ];

        $setSql = 'full_name = :full_name';

        if ($password !== '') {
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            $setSql .= ', password = :password';
        }

        $updateSql = "UPDATE users SET $setSql WHERE user_id = :user_id";
        $updateStmt = $db->prepare($updateSql);
        $success = $updateStmt->execute($params);

        if ($success) {
            $_SESSION['full_name'] = $fullName;
            $user['full_name'] = $fullName;
            if ($password !== '') {
                // Không lưu mật khẩu vào session, chỉ báo thành công
            }
            $alertMessage = 'Cập nhật hồ sơ thành công.';
            $alertType = 'success';
        } else {
            $alertMessage = 'Có lỗi xảy ra, vui lòng thử lại.';
        }
    }
}

$fullNameValue = htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES);
$emailValue = htmlspecialchars($user['email'] ?? '', ENT_QUOTES);
$provider = $user['provider'] ?? 'local';
$providerLabel = $provider === 'google' ? 'Google' : 'Email';
$avatar = $user['avatar'] ?? null;
$initial = strtoupper(mb_substr($user['full_name'] ?? 'U', 0, 1));
$createdAt = !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '';

require_once '../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:64px;height:64px;font-size:1.4rem;">
                            <?= $avatar ? '<img src="' . htmlspecialchars($avatar, ENT_QUOTES) . '" alt="avatar" class="img-fluid rounded-circle" style="width:64px;height:64px;object-fit:cover;">' : $initial; ?>
                        </div>
                        <div>
                            <h4 class="mb-1"><?= $fullNameValue; ?></h4>
                            <div class="text-muted small">Tham gia: <?= $createdAt; ?></div>
                            <span class="badge bg-light text-dark border">Đăng nhập bằng <?= htmlspecialchars($providerLabel, ENT_QUOTES); ?></span>
                        </div>
                    </div>
                    <div class="text-md-end text-muted small">
                        <div>Email: <strong><?= $emailValue; ?></strong></div>
                        <div>Vai trò: <strong><?= htmlspecialchars($user['role'] ?? 'customer', ENT_QUOTES); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($alertMessage)): ?>
            <div class="alert alert-<?= $alertType; ?>"><?= htmlspecialchars($alertMessage); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">Cập nhật hồ sơ</h5>
                <form method="post" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Họ tên</label>
                            <input type="text" name="full_name" class="form-control" value="<?= $fullNameValue; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= $emailValue; ?>" disabled>
                            <div class="form-text">Email không thể thay đổi.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mật khẩu mới</label>
                            <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password" placeholder="Để trống nếu không đổi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Xác nhận mật khẩu</label>
                            <input type="password" name="confirm_password" class="form-control" minlength="6" autocomplete="new-password" placeholder="Để trống nếu không đổi">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
require_once '../includes/footer.php';
