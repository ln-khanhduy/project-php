<?php
session_start();

require_once '../config/config.php';

if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    die('Chưa cấu hình Google OAuth. Vui lòng cập nhật CLIENT_ID và CLIENT_SECRET.');
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'online',
    'include_granted_scopes' => 'true',
    'prompt' => 'select_account'
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
header('Location: ' . $authUrl);
exit;
