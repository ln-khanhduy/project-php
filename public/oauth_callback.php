<?php
declare(strict_types=1);

use RuntimeException;
use Throwable;

session_start();

require_once '../config/config.php';
require_once '../includes/database.php';

function fetchGoogleToken(string $code): array {
    $payload = http_build_query([
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ]);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new RuntimeException('Không thể trao đổi mã với Google: ' . curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($response, true);
    if (empty($data['access_token'])) {
        throw new RuntimeException('Google trả về phản hồi không hợp lệ.');
    }

    return $data;
}

function fetchGoogleUser(string $accessToken): array {
    $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new RuntimeException('Không thể lấy thông tin người dùng: ' . curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($response, true);
    if (empty($data['id']) || empty($data['email'])) {
        throw new RuntimeException('Thông tin Google không đầy đủ.');
    }

    return $data;
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
