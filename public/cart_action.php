<?php
require_once '../config/config.php';
require_once '../includes/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    redirect(SITE_URL . '/public/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/public/cart.php');
}

$action = trim((string)($_POST['action'] ?? ''));
$cart_id = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$userId = (int)$_SESSION['user_id'];

$database = new Database();
$db = $database->getConnection();
$message = '';
$type = 'success';

try {
    if ($cart_id <= 0) {
        throw new RuntimeException('Giỏ hàng không hợp lệ.');
    }

    if ($action === 'update') {
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $stmt = $db->prepare('SELECT ci.cart_id, ci.quantity, p.stock
            FROM cart_items ci
            JOIN phones p ON ci.phone_id = p.phone_id
            WHERE ci.cart_id = :cart_id AND ci.user_id = :user_id
            LIMIT 1');
        $stmt->execute([':cart_id' => $cart_id, ':user_id' => $userId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            throw new RuntimeException('Sản phẩm trong giỏ không tìm thấy.');
        }

        $cleanQuantity = min($quantity, max(1, (int)$item['stock']));

        $updateStmt = $db->prepare('UPDATE cart_items SET quantity = :quantity WHERE cart_id = :cart_id');
        $updateStmt->execute([':quantity' => $cleanQuantity, ':cart_id' => $cart_id]);

        $message = 'Đã cập nhật số lượng.';
    } elseif ($action === 'remove') {
        $deleteStmt = $db->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id AND user_id = :user_id');
        $deleteStmt->execute([':cart_id' => $cart_id, ':user_id' => $userId]);
        $message = 'Đã xóa sản phẩm khỏi giỏ hàng.';
    } else {
        throw new RuntimeException('Thao tác giỏ hàng không hợp lệ.');
    }
} catch (Throwable $exception) {
    $message = $exception->getMessage();
    $type = 'danger';
} finally {
    $countStmt = $db->prepare('SELECT SUM(quantity) FROM cart_items WHERE user_id = :user_id');
    $countStmt->execute([':user_id' => $userId]);
    $_SESSION['cart_count'] = (int)$countStmt->fetchColumn();

    $_SESSION['cart_message'] = $message;
    $_SESSION['cart_message_type'] = $type;

    redirect(SITE_URL . '/public/cart.php');
}
