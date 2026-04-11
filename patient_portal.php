<?php
/**
 * patient_portal.php – Patient portal main entry-point.
 *
 * Routes:
 *   GET  ?page=dashboard      → patient dashboard (default)
 *   GET  ?page=appointments   → upcoming & past appointments
 *   GET  ?page=health_record  → read-only case sheet history
 *   GET  ?page=lab_results    → lab test results
 *   GET  ?page=messages       → inbox / compose / thread view
 *   GET  ?page=feedback       → submit & view feedback
 *   GET  ?page=profile        → demographics + allergy update
 *   POST action=logout           → destroys patient session
 *   POST action=send_message     → sends or replies to a portal message
 *   POST action=submit_feedback  → submits patient feedback
 *   POST action=update_profile   → updates patient allergies
 *   POST action=mark_asset_read  → marks a sent asset as read
 *   GET  ?page=resources         → sent + public assets
 */

require_once __DIR__ . '/app/config/session.php';

if (!isset($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require __DIR__ . '/app/middleware/patient_auth.php';
require_once __DIR__ . '/app/controllers/PatientPortalController.php';

$controller = new PatientPortalController();

// POST actions (PRG pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	switch ($action) {
		case 'logout':
			$controller->logout();
			break;
		case 'send_message':
			$controller->processSendMessage();
			break;
		case 'submit_feedback':
			$controller->processSubmitFeedback();
			break;
		case 'update_profile':
			$controller->processUpdateProfile();
			break;
		case 'mark_asset_read':
			$controller->processMarkAssetRead();
			break;
		default:
			header('Location: patient_portal.php');
			exit;
	}
}

// GET pages
$page = $_GET['page'] ?? 'dashboard';
switch ($page) {
	case 'appointments':
		$controller->appointments();
		break;
	case 'health_record':
		$controller->healthRecord();
		break;
	case 'lab_results':
		$controller->labResults();
		break;
	case 'messages':
		$controller->messages();
		break;
	case 'feedback':
		$controller->feedback();
		break;
	case 'profile':
		$controller->profile();
		break;
	case 'resources':
		$controller->resources();
		break;
	default:
		$controller->dashboard();
		break;
}
