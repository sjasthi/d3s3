<?php
/*
 * Admin dashboard entry.
 * TODO: add authentication/authorization middleware before dispatch.
 */

require_once __DIR__ . '/../../app/controllers/AdminController.php';

$controller = new AdminController();
$controller->dashboard();
