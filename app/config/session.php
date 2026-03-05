<?php
/**
 * app/config/session.php
 *
 * Starts the session with secure cookie settings.
 * Auto-detects HTTPS so it works on both local dev and BlueHost production.
 * Require this file instead of calling session_start() directly.
 */

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}
