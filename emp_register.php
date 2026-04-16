<?php
/**
 * emp_register.php – create-employee entry-point (SUPER_ADMIN and ADMIN only).
 */

require_once __DIR__ . '/app/config/session.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/config/permissions.php';

// Role guard: only SUPER_ADMIN and ADMIN may create staff accounts.
if (!can($_SESSION['user_role'] ?? '', 'users')) {
    $_SESSION['dashboard_notice'] = 'You do not have permission to access that page.';
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/app/controllers/UserController.php';

$controller = new UserController();
$controller->createAccount();
?>
