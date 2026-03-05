<?php
/**
 * patients.php – Patient records page entry-point.
 *
 * Routes:
 *   GET  (default)           → patient search / list
 *   GET  ?action=view&id=X   → patient profile with full medical history
 *   GET  ?action=search      → AJAX JSON patient search (returns JSON)
 */

require_once __DIR__ . '/app/config/session.php';

if (!isset($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/controllers/PatientController.php';

$controller = new PatientController();
$action     = $_GET['action'] ?? '';

switch ($action) {
	case 'view':
		$controller->view((int)($_GET['id'] ?? 0));
		break;
	case 'search':
		$controller->searchAjax();
		break;
	default:
		$controller->index();
		break;
}
