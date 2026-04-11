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
require_once __DIR__ . '/app/controllers/PatientPortalController.php';

$controller       = new PatientController();
$portalController = new PatientPortalController();
$action           = $_GET['action'] ?? '';

// POST-only portal account management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$postAction = $_POST['portal_action'] ?? '';
	switch ($postAction) {
		case 'create_account':
			$portalController->processCreateAccount();
			break;
		case 'reset_password':
			$portalController->processResetPassword();
			break;
		case 'toggle_active':
			$portalController->processToggleActive();
			break;
	}
}

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
