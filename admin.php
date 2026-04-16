<?php
/**
 * Admin entry-point (SUPER_ADMIN and ADMIN only).
 */
require_once __DIR__ . '/app/config/session.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/config/permissions.php';

// Role guard: only SUPER_ADMIN and ADMIN may access any admin page.
if (!can($_SESSION['user_role'] ?? '', 'users')) {
    $_SESSION['dashboard_notice'] = 'You do not have permission to access that page.';
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/app/controllers/AdminController.php';

$controller = new AdminController();
$page = $_GET['page'] ?? 'dashboard';

if ($page === 'panel') {
    $controller->adminPanel();
} else {
    $controller->dashboard();
}
?>
