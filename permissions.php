<?php
/**
 * Permissions Management entry-point.
 * Admin-only – requires SUPER_ADMIN or ADMIN role.
 */
require_once __DIR__ . '/app/config/session.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/controllers/PermissionsController.php';

$controller = new PermissionsController();
$controller->index();
?>
