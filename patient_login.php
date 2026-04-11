<?php
/**
 * patient_login.php – Patient portal login entry-point.
 *
 * Separate from the staff login.php.
 * Already-authenticated patients are redirected to patient_portal.php.
 */

require_once __DIR__ . '/app/config/session.php';

if (!isset($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/app/controllers/PatientPortalController.php';

$controller = new PatientPortalController();
$controller->login();
