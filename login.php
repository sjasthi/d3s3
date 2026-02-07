<?php
/**
 * login.php – authentication entry-point.
 *
 * Starts the session and ensures a CSRF token is available
 * before handing off to the controller.  Already-authenticated
 * visitors are redirected straight to the dashboard.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/app/controllers/UserController.php';

$controller = new UserController();
$controller->login();
?>
