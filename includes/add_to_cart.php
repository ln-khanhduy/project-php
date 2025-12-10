<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.']);
    exit;
}

// Chỉ customer mới có giỏ hàng
if ($_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Chức năng này chỉ dành cho khách hàng.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$phoneId = isset($_POST['phone_id']) ? (int)$_POST['phone_id'] : 0;
$quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

if ($phoneId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ.']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->beginTransaction();

    $productStmt = $db->prepare('SELECT stock FROM phones WHERE phone_id = :phone_id');
    $productStmt->bindValue(':phone_id', $phoneId, PDO::PARAM_INT);
    $productStmt->execute();
    $stock = $productStmt->fetchColumn();

    if ($stock === false) {
        throw new RuntimeException('Không tìm thấy sản phẩm.');
    }

    $existsStmt = $db->prepare('SELECT quantity FROM cart_items WHERE user_id = :user_id AND phone_id = :phone_id');
    $existsStmt->execute([':user_id' => $userId, ':phone_id' => $phoneId]);
    $existingQuantity = (int)$existsStmt->fetchColumn();

    $newQuantity = $existingQuantity + $quantity;
    if ($newQuantity > $stock) {
        $newQuantity = $stock;
    }

    if ($existingQuantity > 0) {
        $updateStmt = $db->prepare('UPDATE cart_items SET quantity = :quantity WHERE user_id = :user_id AND phone_id = :phone_id');
        $updateStmt->execute([
            ':quantity' => $newQuantity,
            ':user_id' => $userId,
            ':phone_id' => $phoneId
        ]);
    } else {
        $insertStmt = $db->prepare('INSERT INTO cart_items (user_id, phone_id, quantity) VALUES (:user_id, :phone_id, :quantity)');
        $insertStmt->execute([
            ':user_id' => $userId,
            ':phone_id' => $phoneId,
            ':quantity' => $newQuantity
        ]);
    }

    $countStmt = $db->prepare('SELECT SUM(quantity) FROM cart_items WHERE user_id = :user_id');
    $countStmt->execute([':user_id' => $userId]);
    $cartCount = (int)$countStmt->fetchColumn();
    $_SESSION['cart_count'] = $cartCount;

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Đã thêm vào giỏ hàng.', 'count' => $cartCount]);
} catch (Throwable $exception) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
}
