<?php
/**
 * lab_results.php – Labwork entry-point.
 *
 * Routes:
 *   GET  (default)           → Labwork queue (pending orders)
 *   POST ?action=complete    → AJAX: mark a lab order as completed
 */

require_once __DIR__ . '/app/config/session.php';

if (!isset($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/controllers/LabResultsController.php';

$controller = new LabResultsController();
$action = $_GET['action'] ?? '';

switch ($action) {
	case 'complete':
		$controller->completeOrder();
		break;
	default:
		$controller->index();
		break;
}
