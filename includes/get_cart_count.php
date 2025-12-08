<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare('SELECT SUM(quantity) FROM cart_items WHERE user_id = :user_id');
    $stmt->execute([':user_id' => (int)$_SESSION['user_id']]);
    $count = (int)$stmt->fetchColumn();

    $_SESSION['cart_count'] = $count;

    echo json_encode(['success' => true, 'count' => $count]);
} catch (Exception $exception) {
    echo json_encode(['success' => false, 'count' => 0]);
}
