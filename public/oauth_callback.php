<?php
declare(strict_types=1);

use RuntimeException;
use Throwable;

session_start();

require_once '../config/config.php';
require_once '../includes/database.php';

function fetchGoogleToken(string $code): array {
    $payload = http_build_query([
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code'
    ]);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        
        // FIX SSL + CHẠY ĐƯỢC TRÊN MỌI MÁY (kể cả XAMPP cũ)
        CURLOPT_CAINFO         => __DIR__ . '/../cert/cacert.pem',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Nếu có lỗi mạng/SSL → ném exception luôn, không để hàm return null
    if ($response === false || $error) {
        throw new RuntimeException('Không thể kết nối Google: ' . $error);
    }

    $data = json_decode($response, true);

    // Google trả lỗi (vd: redirect_uri sai, code hết hạn)
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        $msg = $data['error_description'] ?? $response ?? 'Unknown error';
        throw new RuntimeException('Google trả lỗi: ' . $msg);
    }

    if (empty($data['access_token'])) {
        throw new RuntimeException('Không nhận được access_token từ Google');
    }

    return $data; // chắc chắn là array
}

function fetchGoogleUser(string $accessToken): array {
    $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_RETURNTRANSFER => true,
        
        // FIX SSL
        CURLOPT_CAINFO         => __DIR__ . '/../cert/cacert.pem',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($response === false || $error) {
        throw new RuntimeException('Lỗi lấy thông tin user: ' . $error);
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        throw new RuntimeException('Dữ liệu user không hợp lệ');
    }

    if (empty($data['id']) || empty($data['email'])) {
        throw new RuntimeException('Thiếu thông tin bắt buộc từ Google');
    }

    return $data; // chắc chắn là array
}

try {
    if (empty($_GET['code']) || empty($_GET['state'])) {
        throw new RuntimeException('Thiếu mã xác thực từ Google.');
    }

    if (empty($_SESSION['oauth_state']) || $_SESSION['oauth_state'] !== $_GET['state']) {
        throw new RuntimeException('Trạng thái OAuth không hợp lệ.');
    }

    $tokenData = fetchGoogleToken($_GET['code']);
    $userInfo = fetchGoogleUser($tokenData['access_token']);

    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare('SELECT user_id FROM users WHERE oauth_id = :oauth_id OR email = :email LIMIT 1');
    $stmt->execute([
        ':oauth_id' => $userInfo['id'],
        ':email' => $userInfo['email']
    ]);
    $userId = $stmt->fetchColumn();

    if ($userId) {
        $updateStmt = $db->prepare('UPDATE users SET full_name = :full_name, avatar = :avatar, provider = :provider, oauth_id = :oauth_id WHERE user_id = :user_id');
        $updateStmt->execute([
            ':full_name' => $userInfo['name'] ?? 'Khách hàng',
            ':avatar' => $userInfo['picture'] ?? null,
            ':provider' => 'google',
            ':oauth_id' => $userInfo['id'],
            ':user_id' => $userId
        ]);
    } else {
        $insertStmt = $db->prepare('INSERT INTO users (full_name, email, provider, oauth_id, role, created_at)
            VALUES (:full_name, :email, :provider, :oauth_id, :role, NOW())');
        $insertStmt->execute([
            ':full_name' => $userInfo['name'] ?? 'Khách hàng',
            ':email' => $userInfo['email'],
            ':provider' => 'google',
            ':oauth_id' => $userInfo['id'],
            ':role' => 'customer'
        ]);
        $userId = (int)$db->lastInsertId();
    }

    $_SESSION['user_id'] = (int)$userId;
    $_SESSION['full_name'] = $userInfo['name'] ?? 'Khách hàng';
    $_SESSION['email'] = $userInfo['email'];
    $_SESSION['role'] = 'customer';
    $_SESSION['avatar'] = $userInfo['picture'] ?? null;

    $cartStmt = $db->prepare('SELECT SUM(quantity) FROM cart_items WHERE user_id = :user_id');
    $cartStmt->execute([':user_id' => $userId]);
    $_SESSION['cart_count'] = (int)$cartStmt->fetchColumn();

    unset($_SESSION['oauth_state']);

    header('Location: ' . SITE_URL . '/public/index.php');
    exit;
} catch (Throwable $exception) {
    unset($_SESSION['oauth_state']);
    $_SESSION['auth_error'] = $exception->getMessage();
    header('Location: ' . SITE_URL . '/public/login.php');
    exit;
}
