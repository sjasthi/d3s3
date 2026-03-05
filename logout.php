<?php
require_once __DIR__ . '/app/config/session.php';

// 1. Wipe all session data for this request
$_SESSION = [];

// 2. Expire the session cookie in the browser
$params = session_get_cookie_params();
setcookie(
    session_name(), '',
    time() - 42000,
    $params['path'],
    $params['domain'],
    $params['secure'],
    $params['httponly']
);

// 3. Destroy the server-side session
session_destroy();

header('Location: login.php');
exit;
?>
