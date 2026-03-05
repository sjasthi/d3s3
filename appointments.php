<?php
/**
 * appointments.php – Appointments page entry-point.
 *
 * Routes:
 *   GET  (default)                 → appointments list view
 *   GET  ?action=patient-search    → AJAX patient search (patients with upcoming appointments)
 *   GET  ?action=get-doctors       → AJAX list of active doctors
 *   POST ?action=assign-doctor     → AJAX assign INTAKE_COMPLETE case sheet to a doctor
 *   POST ?action=create-patient    → AJAX create new patient + stub case sheet
 *   POST ?action=cancel            → AJAX cancel an appointment (with optional note)
 *   POST ?action=reschedule        → AJAX reschedule an appointment to a new date/time
 */

require_once __DIR__ . '/app/config/session.php';

if (!isset($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/controllers/AppointmentController.php';

$controller = new AppointmentController();
$action = $_GET['action'] ?? '';

switch ($action) {
	case 'patient-search':
		$controller->patientSearch();
		break;
	case 'get-doctors':
		$controller->getDoctors();
		break;
	case 'assign-doctor':
		$controller->assignToDoctor();
		break;
	case 'create':
		$controller->create();
		break;
	case 'update-status':
		$controller->updateStatus();
		break;
	case 'patient-cases':
		$controller->getPatientCases();
		break;
	case 'create-patient':
		$controller->createPatient();
		break;
	case 'cancel':
		$controller->cancelAppointment();
		break;
	case 'reschedule':
		$controller->rescheduleAppointment();
		break;
	default:
		$controller->index();
		break;
}
