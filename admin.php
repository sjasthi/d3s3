<?php
/**
 * Admin entry-point.
 */
require_once __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/controllers/AdminController.php';

$controller = new AdminController();
$page = $_GET['page'] ?? 'dashboard';

if ($page === 'panel') {
    $controller->adminPanel();
} else {
    $controller->dashboard();
}
?>
