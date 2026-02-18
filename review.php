<?php
/**
 * review.php – Doctor review entry-point.
 *
 * Routes:
 *   GET  (default)       → doctorReview() — show review form
 *   POST ?action=close   → closeCaseSheet() — finalize and close chart
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
	case 'close':
		$controller->closeCaseSheet();
		break;
	default:
		$controller->doctorReview();
		break;
}
