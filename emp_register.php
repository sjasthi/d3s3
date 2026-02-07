<?php
/**
 * emp_register.php – create-employee entry-point.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/app/controllers/UserController.php';

$controller = new UserController();
$controller->createAccount();
?>
