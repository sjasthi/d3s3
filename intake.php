<?php
/**
 * intake.php – Patient intake entry-point.
 *
 * Routes:
 *   GET/POST (default)             → intake form / process intake
 *   GET  ?action=patient-search    → AJAX patient search
 *   POST ?action=register-patient  → AJAX new patient registration
 *   POST ?action=claim             → doctor claims case sheet for review
 *   GET  ?action=view              → read-only case sheet summary
 *   POST ?action=amend             → log amendment start, redirect to edit mode
 */

require_once __DIR__ . '/app/config/session.php';

if (!isset($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/controllers/ClinicalController.php';

$controller = new ClinicalController();
$action = $_GET['action'] ?? '';

switch ($action) {
	case 'patient-search':
		$controller->patientSearch();
		break;
	case 'register-patient':
		$controller->registerPatient();
		break;
	case 'claim':
		$controller->claimForReview();
		break;
	case 'complete':
		$controller->completeIntake();
		break;
	case 'view':
		$controller->viewIntake();
		break;
	case 'amend':
		$controller->amendIntake();
		break;
	case 'update-patient':
		$controller->updatePatient();
		break;
	case 'order-lab-test':
		$controller->orderLabTest();
		break;
	case 'get-lab-orders':
		$controller->getLabOrders();
		break;
	default:
		$controller->intake();
		break;
}
