<?php
/**
 * get_patient.php
 * API endpoint to fetch patient information for case sheet.
 */

require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/permissions.php';
require_once __DIR__ . '/app/config/record_scope.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
	echo json_encode(['success' => false, 'message' => 'Unauthorized']);
	exit;
}

if (!can($_SESSION['user_role'] ?? '', 'patient_data')) {
	echo json_encode(['success' => false, 'message' => 'Forbidden']);
	exit;
}

require_once __DIR__ . '/app/config/database.php';
$pdo = getDBConnection();

$patient_id = (int)($_GET['patient_id'] ?? 0);

if (!$patient_id) {
	echo json_encode(['success' => false, 'message' => 'Patient ID required']);
	exit;
}

try {
	$stmt = $pdo->prepare(
		'SELECT patient_id, patient_code, first_name, last_name,
		        age_years, sex, date_of_birth,
		        address_line1, city, state_province, postal_code,
		        phone_e164, emergency_contact_name, emergency_contact_phone,
		        blood_group, allergies, created_by_user_id
		   FROM patients
		  WHERE patient_id = ?'
	);
	$stmt->execute([$patient_id]);
	$patient = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($patient && nurseOwnsPatient($patient)) {
		unset($patient['created_by_user_id']);
		echo json_encode(['success' => true, 'patient' => $patient]);
	} else {
		echo json_encode(['success' => false, 'message' => 'Patient not found']);
	}
} catch (PDOException $e) {
	error_log('Database error in get_patient.php: ' . $e->getMessage());
	echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
