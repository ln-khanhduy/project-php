<?php
declare(strict_types=1);

use RuntimeException;
use Throwable;

require_once '../config/config.php';
require_once '../includes/database.php';

session_start();
if (!isLoggedIn()) {
    redirect(SITE_URL . '/public/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/public/payments.php');
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$method = $_POST['method'] ?? '';
$allowedMethods = ['momo', 'zalopay', 'credit_card', 'paypal', 'bank_transfer', 'cod'];

try {
    if ($orderId <= 0 || !in_array($method, $allowedMethods, true)) {
        throw new RuntimeException('Thông tin thanh toán không hợp lệ.');
    }

    $database = new Database();
    $db = $database->getConnection();

    $orderStmt = $db->prepare('SELECT * FROM orders WHERE order_id = :order_id AND user_id = :user_id LIMIT 1');
    $orderStmt->execute([':order_id' => $orderId, ':user_id' => $_SESSION['user_id'] ?? 0]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new RuntimeException('Đơn hàng không tồn tại.');
    }

    if ($order['status'] !== 'pending') {
        throw new RuntimeException('Đơn hàng đã được xử lý.');
    }

    $db->beginTransaction();
    $paymentStmt = $db->prepare('INSERT INTO payments (order_id, user_id, method, amount, status, transaction_id, paid_at)
        VALUES (:order_id, :user_id, :method, :amount, :status, :transaction_id, NOW())');
    $transactionId = uniqid('pay_', true);
    $paymentStmt->execute([
        ':order_id' => $orderId,
        ':user_id' => $order['user_id'],
        ':method' => $method,
        ':amount' => $order['total_amount'],
        ':status' => 'paid',
        ':transaction_id' => $transactionId,
    ]);

    $paymentId = (int)$db->lastInsertId();
    $newStatus = 'confirmed';
    if ($method === 'cod') {
        $newStatus = 'shipping';
    }

    $updateOrder = $db->prepare('UPDATE orders SET status = :status, payment_id = :payment_id WHERE order_id = :order_id');
    $updateOrder->execute([
        ':status' => $newStatus,
        ':payment_id' => $paymentId,
        ':order_id' => $orderId,
    ]);

    $db->commit();
    $_SESSION['payment_message'] = 'Thanh toán thành công qua ' . strtoupper($method) . '.';
    $_SESSION['payment_status'] = 'success';
} catch (Throwable $exception) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $_SESSION['payment_message'] = $exception->getMessage();
    $_SESSION['payment_status'] = 'danger';
}

redirect(SITE_URL . '/public/payments.php');
