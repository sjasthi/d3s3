<?php
/**
 * portal_messages.php – Staff-facing patient portal message management.
 *
 * Shows all patient-initiated message threads and allows staff to reply.
 * Requires a staff session with patient_data read access.
 *
 * Routes:
 *   GET  (default)          → thread list + optional active thread view
 *   GET  ?thread=X          → view thread X inline
 *   POST action=staff_reply → send a reply to a patient thread
 */

require_once __DIR__ . '/app/config/session.php';

if (!isset($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/controllers/PatientPortalController.php';

$controller = new PatientPortalController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	switch ($action) {
		case 'staff_reply':
			$controller->processStaffReply();
			break;
		default:
			header('Location: portal_messages.php');
			exit;
	}
}

$controller->staffMessages();
