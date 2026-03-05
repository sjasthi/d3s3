<?php

/**
 * app/controllers/AppointmentController.php
 *
 * Handles the appointments workflow:
 *   - index():          upcoming appointments list (today / next 7 days / pending assignment)
 *   - patientSearch():  AJAX search for patients with upcoming SCHEDULED appointments
 *   - getDoctors():     AJAX list of active doctors (for the assignment modal)
 *   - assignToDoctor(): AJAX POST – nurse assigns an INTAKE_COMPLETE case sheet to a doctor
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

class AppointmentController
{
	// ── Main appointments view ──────────────────────────────────────────────

	public function index(): void
	{
		$this->requireClinicalRole();

		$role         = $_SESSION['user_role'] ?? '';
		$isAdminRole  = can($role, 'users');
		$isNurseRole  = can($role, 'appointments', 'W') && !$isAdminRole;
		$isDoctorRole = ($role === 'DOCTOR');

		$tab = $_GET['tab'] ?? 'today';

		$pdo = getDBConnection();

		// ── Today's appointments ──────────────────────────────────────────
		$todaySql = "SELECT a.appointment_id,
		                    a.case_sheet_id,
		                    a.scheduled_date,
		                    a.scheduled_time,
		                    a.visit_mode,
		                    a.status       AS appt_status,
		                    a.doctor_user_id,
		                    a.notes,
		                    p.patient_id,
		                    p.patient_code,
		                    p.first_name,
		                    p.last_name,
		                    p.age_years,
		                    p.sex,
		                    cs.chief_complaint,
		                    cs.visit_type,
		                    d.first_name   AS doc_first,
		                    d.last_name    AS doc_last,
		                    e.title        AS event_title
		               FROM appointments a
		               JOIN case_sheets cs ON cs.case_sheet_id = a.case_sheet_id
		               JOIN patients    p  ON p.patient_id     = cs.patient_id
		               JOIN users       d  ON d.user_id        = a.doctor_user_id
		               LEFT JOIN events e  ON e.event_id       = a.event_id
		              WHERE a.scheduled_date = CURDATE()
		                AND a.status NOT IN ('CANCELLED','NO_SHOW')";

		$todayParams = [];
		if ($isDoctorRole) {
			$todaySql .= ' AND a.doctor_user_id = ?';
			$todayParams[] = (int)$_SESSION['user_id'];
		}
		$todaySql .= " ORDER BY COALESCE(a.scheduled_time, '23:59:59') ASC";

		$stmt = $pdo->prepare($todaySql);
		$stmt->execute($todayParams);
		$todayAppts = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// ── Upcoming appointments (next 7 days, not today) ────────────────
		$upcomingSql = "SELECT a.appointment_id,
		                       a.case_sheet_id,
		                       a.scheduled_date,
		                       a.scheduled_time,
		                       a.visit_mode,
		                       a.status       AS appt_status,
		                       a.doctor_user_id,
		                       a.notes,
		                       p.patient_id,
		                       p.patient_code,
		                       p.first_name,
		                       p.last_name,
		                       p.age_years,
		                       p.sex,
		                       cs.chief_complaint,
		                       cs.visit_type,
		                       d.first_name   AS doc_first,
		                       d.last_name    AS doc_last,
		                       e.title        AS event_title
		                  FROM appointments a
		                  JOIN case_sheets cs ON cs.case_sheet_id = a.case_sheet_id
		                  JOIN patients    p  ON p.patient_id     = cs.patient_id
		                  JOIN users       d  ON d.user_id        = a.doctor_user_id
		                  LEFT JOIN events e  ON e.event_id       = a.event_id
		                 WHERE a.scheduled_date > CURDATE()
		                   AND a.scheduled_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
		                   AND a.status NOT IN ('CANCELLED','NO_SHOW')";

		$upcomingParams = [];
		if ($isDoctorRole) {
			$upcomingSql .= ' AND a.doctor_user_id = ?';
			$upcomingParams[] = (int)$_SESSION['user_id'];
		}
		$upcomingSql .= " ORDER BY a.scheduled_date ASC, COALESCE(a.scheduled_time, '23:59:59') ASC";

		$stmt = $pdo->prepare($upcomingSql);
		$stmt->execute($upcomingParams);
		$upcomingAppts = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// ── Cases pending doctor assignment (nurses + admins only) ────────
		$pendingCases = [];
		$doctors      = [];

		if ($isNurseRole || $isAdminRole) {
			$stmt = $pdo->prepare(
				"SELECT cs.case_sheet_id,
				        cs.chief_complaint,
				        cs.visit_type,
				        cs.updated_at,
				        cs.assigned_doctor_user_id,
				        p.patient_id,
				        p.patient_code,
				        p.first_name,
				        p.last_name,
				        p.age_years,
				        p.sex,
				        d.first_name AS doc_first,
				        d.last_name  AS doc_last
				   FROM case_sheets cs
				   JOIN patients p ON p.patient_id    = cs.patient_id
				   LEFT JOIN users d ON d.user_id     = cs.assigned_doctor_user_id
				  WHERE cs.status = 'INTAKE_COMPLETE'
				  ORDER BY cs.updated_at ASC"
			);
			$stmt->execute();
			$pendingCases = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$stmt = $pdo->prepare(
				"SELECT user_id, first_name, last_name
				   FROM users
				  WHERE role = 'DOCTOR' AND is_active = 1
				  ORDER BY first_name, last_name"
			);
			$stmt->execute();
			$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		require __DIR__ . '/../views/appointments.php';
	}

	// ── Patient appointment search (AJAX) ───────────────────────────────────

	public function patientSearch(): void
	{
		$this->requireClinicalRole();
		header('Content-Type: application/json');

		$q = trim($_GET['q'] ?? '');
		if (strlen($q) < 2) {
			echo json_encode(['success' => true, 'results' => []]);
			exit;
		}

		$pdo  = getDBConnection();
		$like = '%' . $q . '%';

		// Find patients who have at least one upcoming SCHEDULED appointment
		$stmt = $pdo->prepare(
			"SELECT DISTINCT
			        p.patient_id,
			        p.patient_code,
			        p.first_name,
			        p.last_name,
			        p.sex,
			        p.age_years
			   FROM patients p
			   JOIN case_sheets cs ON cs.patient_id    = p.patient_id
			   JOIN appointments a  ON a.case_sheet_id = cs.case_sheet_id
			  WHERE a.status = 'SCHEDULED'
			    AND a.scheduled_date >= CURDATE()
			    AND (
			        p.patient_code LIKE ?
			        OR p.first_name  LIKE ?
			        OR p.last_name   LIKE ?
			        OR CONCAT(p.first_name, ' ', COALESCE(p.last_name,'')) LIKE ?
			    )
			  ORDER BY p.first_name, p.last_name
			  LIMIT 10"
		);
		$stmt->execute([$like, $like, $like, $like]);
		$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (empty($patients)) {
			echo json_encode(['success' => true, 'results' => []]);
			exit;
		}

		// Fetch upcoming SCHEDULED appointments for matched patients
		$patientIds   = array_column($patients, 'patient_id');
		$placeholders = implode(',', array_fill(0, count($patientIds), '?'));

		$stmt = $pdo->prepare(
			"SELECT a.appointment_id,
			        a.case_sheet_id,
			        a.doctor_user_id,
			        a.scheduled_date,
			        a.scheduled_time,
			        a.visit_mode,
			        a.event_id,
			        a.status        AS appt_status,
			        a.notes,
			        cs.patient_id,
			        cs.chief_complaint,
			        cs.visit_type,
			        d.first_name    AS doc_first,
			        d.last_name     AS doc_last,
			        e.title         AS event_title
			   FROM appointments a
			   JOIN case_sheets cs ON cs.case_sheet_id = a.case_sheet_id
			   JOIN users       d  ON d.user_id        = a.doctor_user_id
			   LEFT JOIN events e  ON e.event_id       = a.event_id
			  WHERE cs.patient_id IN ($placeholders)
			    AND a.status = 'SCHEDULED'
			    AND a.scheduled_date >= CURDATE()
			  ORDER BY a.scheduled_date ASC, a.scheduled_time ASC"
		);
		$stmt->execute($patientIds);
		$appts = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Group appointments by patient_id
		$apptsByPatient = [];
		foreach ($appts as $a) {
			$apptsByPatient[(int)$a['patient_id']][] = $a;
		}

		$results = [];
		foreach ($patients as $p) {
			$pid = (int)$p['patient_id'];
			if (empty($apptsByPatient[$pid])) continue;

			$patientAppts = [];
			foreach ($apptsByPatient[$pid] as $a) {
				$patientAppts[] = [
					'appointment_id'     => (int)$a['appointment_id'],
					'case_sheet_id'      => (int)$a['case_sheet_id'],
					'doctor_user_id'     => (int)$a['doctor_user_id'],
					'doctor_name'        => 'Dr. ' . trim($a['doc_first'] . ' ' . $a['doc_last']),
					'scheduled_date'     => $a['scheduled_date'],
					'scheduled_date_fmt' => date('D, M j, Y', strtotime($a['scheduled_date'])),
					'scheduled_time'     => $a['scheduled_time'],
					'scheduled_time_fmt' => $a['scheduled_time']
						? date('g:i A', strtotime($a['scheduled_time']))
						: null,
					'visit_mode'         => $a['visit_mode'],
					'event_id'           => $a['event_id'] ? (int)$a['event_id'] : null,
					'event_title'        => $a['event_title'],
					'notes'              => $a['notes'],
					'chief_complaint'    => $a['chief_complaint'],
				];
			}

			$results[] = [
				'patient_id'   => $pid,
				'patient_code' => $p['patient_code'],
				'first_name'   => $p['first_name'],
				'last_name'    => $p['last_name'] ?? '',
				'sex'          => ($p['sex'] && $p['sex'] !== 'UNKNOWN') ? $p['sex'] : null,
				'age_years'    => $p['age_years'] ? (int)$p['age_years'] : null,
				'appointments' => $patientAppts,
			];
		}

		echo json_encode(['success' => true, 'results' => $results]);
		exit;
	}

	// ── Get active doctors (AJAX) ───────────────────────────────────────────

	public function getDoctors(): void
	{
		$this->requireClinicalRole();
		header('Content-Type: application/json');

		$pdo  = getDBConnection();
		$stmt = $pdo->prepare(
			"SELECT user_id, first_name, last_name
			   FROM users
			  WHERE role = 'DOCTOR' AND is_active = 1
			  ORDER BY first_name, last_name"
		);
		$stmt->execute();

		echo json_encode(['success' => true, 'doctors' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
		exit;
	}

	// ── Assign case sheet to doctor (AJAX POST) ─────────────────────────────

	public function assignToDoctor(): void
	{
		header('Content-Type: application/json');

		if (!isset($_SESSION['user_id'])) {
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			exit;
		}

		$role = $_SESSION['user_role'] ?? '';
		if (!can($role, 'appointments', 'W')) {
			echo json_encode(['success' => false, 'message' => 'Only nurses can assign patients to doctors.']);
			exit;
		}

		$body = json_decode(file_get_contents('php://input'), true);
		if (!$body) {
			echo json_encode(['success' => false, 'message' => 'Invalid request body.']);
			exit;
		}

		if (!hash_equals($_SESSION['csrf_token'] ?? '', $body['csrf_token'] ?? '')) {
			echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
			exit;
		}

		$caseSheetId  = (int)($body['case_sheet_id']  ?? 0);
		$doctorUserId = (int)($body['doctor_user_id'] ?? 0);

		if ($caseSheetId <= 0 || $doctorUserId <= 0) {
			echo json_encode(['success' => false, 'message' => 'Missing case_sheet_id or doctor_user_id.']);
			exit;
		}

		$pdo = getDBConnection();

		// Verify the doctor exists and is active
		$stmt = $pdo->prepare(
			"SELECT user_id, first_name, last_name FROM users
			  WHERE user_id = ? AND role = 'DOCTOR' AND is_active = 1"
		);
		$stmt->execute([$doctorUserId]);
		$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$doctor) {
			echo json_encode(['success' => false, 'message' => 'Doctor not found or inactive.']);
			exit;
		}

		// Verify the case sheet is assignable
		$stmt = $pdo->prepare(
			"SELECT cs.case_sheet_id, cs.status, cs.assigned_doctor_user_id,
			        p.first_name, p.last_name, p.patient_code
			   FROM case_sheets cs
			   JOIN patients p ON p.patient_id = cs.patient_id
			  WHERE cs.case_sheet_id = ?"
		);
		$stmt->execute([$caseSheetId]);
		$cs = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$cs) {
			echo json_encode(['success' => false, 'message' => 'Case sheet not found.']);
			exit;
		}

		if ($cs['status'] !== 'INTAKE_COMPLETE') {
			echo json_encode(['success' => false, 'message' => 'Only completed intake case sheets can be assigned.']);
			exit;
		}

		$oldDoctorId = $cs['assigned_doctor_user_id'];

		$pdo->prepare(
			"UPDATE case_sheets SET assigned_doctor_user_id = ?, updated_at = NOW()
			  WHERE case_sheet_id = ?"
		)->execute([$doctorUserId, $caseSheetId]);

		// Audit log
		$pdo->prepare(
			"INSERT INTO case_sheet_audit_log (case_sheet_id, user_id, field_name, old_value, new_value, changed_at)
			 VALUES (?, ?, 'assigned_doctor_user_id', ?, ?, NOW())"
		)->execute([
			$caseSheetId,
			$_SESSION['user_id'],
			$oldDoctorId ?? 'null',
			(string)$doctorUserId,
		]);

		$doctorName  = trim($doctor['first_name'] . ' ' . $doctor['last_name']);
		$patientName = trim($cs['first_name'] . ' ' . $cs['last_name']);

		echo json_encode([
			'success'     => true,
			'message'     => $patientName . ' (' . $cs['patient_code'] . ') assigned to Dr. ' . $doctorName . '.',
			'doctor_name' => 'Dr. ' . $doctorName,
		]);
		exit;
	}

	// ── Create appointment (AJAX POST) ─────────────────────────────────────

	public function create(): void
	{
		header('Content-Type: application/json');

		if (!isset($_SESSION['user_id'])) {
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			exit;
		}

		$role = $_SESSION['user_role'] ?? '';
		if (!can($role, 'appointments', 'W')) {
			echo json_encode(['success' => false, 'message' => 'Only nurses and admins can create appointments.']);
			exit;
		}

		$body = json_decode(file_get_contents('php://input'), true);
		if (!$body) {
			echo json_encode(['success' => false, 'message' => 'Invalid request body.']);
			exit;
		}

		if (!hash_equals($_SESSION['csrf_token'] ?? '', $body['csrf_token'] ?? '')) {
			echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
			exit;
		}

		$caseSheetId   = (int)($body['case_sheet_id']  ?? 0);
		$doctorUserId  = (int)($body['doctor_user_id'] ?? 0);
		$scheduledDate = trim($body['scheduled_date']  ?? '');
		$scheduledTime = trim($body['scheduled_time']  ?? '') ?: null;
		$visitMode     = $body['visit_mode'] ?? 'IN_PERSON';
		$notes         = trim($body['notes'] ?? '') ?: null;

		if ($caseSheetId <= 0 || $doctorUserId <= 0 || $scheduledDate === '') {
			echo json_encode(['success' => false, 'message' => 'Patient, case sheet, doctor, and date are required.']);
			exit;
		}

		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduledDate)) {
			echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
			exit;
		}

		if ($scheduledTime && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $scheduledTime)) {
			$scheduledTime = null;
		}

		$allowedModes = ['IN_PERSON', 'REMOTE', 'CAMP'];
		if (!in_array($visitMode, $allowedModes, true)) {
			$visitMode = 'IN_PERSON';
		}

		$pdo = getDBConnection();

		// Verify doctor is active
		$stmt = $pdo->prepare(
			"SELECT user_id, first_name, last_name FROM users
			  WHERE user_id = ? AND role = 'DOCTOR' AND is_active = 1"
		);
		$stmt->execute([$doctorUserId]);
		$doctor = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$doctor) {
			echo json_encode(['success' => false, 'message' => 'Doctor not found or inactive.']);
			exit;
		}

		// Verify case sheet exists and is schedulable
		$stmt = $pdo->prepare(
			"SELECT cs.case_sheet_id, cs.status, cs.patient_id,
			        p.first_name, p.last_name, p.patient_code
			   FROM case_sheets cs
			   JOIN patients p ON p.patient_id = cs.patient_id
			  WHERE cs.case_sheet_id = ?"
		);
		$stmt->execute([$caseSheetId]);
		$cs = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$cs) {
			echo json_encode(['success' => false, 'message' => 'Case sheet not found.']);
			exit;
		}
		if ($cs['status'] === 'CLOSED') {
			echo json_encode(['success' => false, 'message' => 'Closed case sheets cannot be scheduled.']);
			exit;
		}

		// Ensure the patient has a patient_code (issue one if missing)
		$this->ensurePatientCode($pdo, (int)$cs['patient_id']);

		// Insert appointment
		$pdo->prepare(
			"INSERT INTO appointments
			    (case_sheet_id, doctor_user_id, scheduled_date, scheduled_time,
			     visit_mode, notes, created_by_user_id)
			 VALUES (?, ?, ?, ?, ?, ?, ?)"
		)->execute([
			$caseSheetId, $doctorUserId, $scheduledDate,
			$scheduledTime, $visitMode, $notes, $_SESSION['user_id'],
		]);

		$appointmentId = (int)$pdo->lastInsertId();

		// Update case sheet: assign doctor and advance status to SCHEDULED
		$pdo->prepare(
			"UPDATE case_sheets
			    SET assigned_doctor_user_id = ?,
			        status                  = 'SCHEDULED',
			        updated_at              = NOW()
			  WHERE case_sheet_id = ? AND status != 'CLOSED'"
		)->execute([$doctorUserId, $caseSheetId]);

		$patientName   = trim($cs['first_name'] . ' ' . $cs['last_name']);
		$doctorName    = trim($doctor['first_name'] . ' ' . $doctor['last_name']);
		$dateFormatted = date('D, M j, Y', strtotime($scheduledDate));

		echo json_encode([
			'success'        => true,
			'appointment_id' => $appointmentId,
			'message'        => 'Appointment scheduled for ' . $patientName
			                    . ' with Dr. ' . $doctorName
			                    . ' on ' . $dateFormatted . '.',
		]);
		exit;
	}

	// ── Update appointment status (AJAX POST) ───────────────────────────────

	public function updateStatus(): void
	{
		header('Content-Type: application/json');

		if (!isset($_SESSION['user_id'])) {
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			exit;
		}

		$role = $_SESSION['user_role'] ?? '';
		$body = json_decode(file_get_contents('php://input'), true);
		if (!$body) {
			echo json_encode(['success' => false, 'message' => 'Invalid request body.']);
			exit;
		}

		if (!hash_equals($_SESSION['csrf_token'] ?? '', $body['csrf_token'] ?? '')) {
			echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
			exit;
		}

		$appointmentId = (int)($body['appointment_id'] ?? 0);
		$newStatus     = $body['status'] ?? '';

		$allowed = ['SCHEDULED', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'NO_SHOW'];
		if ($appointmentId <= 0 || !in_array($newStatus, $allowed, true)) {
			echo json_encode(['success' => false, 'message' => 'Invalid request.']);
			exit;
		}

		$pdo = getDBConnection();

		$stmt = $pdo->prepare(
			"SELECT appointment_id, case_sheet_id, doctor_user_id, status
			   FROM appointments WHERE appointment_id = ?"
		);
		$stmt->execute([$appointmentId]);
		$appt = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$appt) {
			echo json_encode(['success' => false, 'message' => 'Appointment not found.']);
			exit;
		}

		$isDoctor = ($role === 'DOCTOR');

		if ($isDoctor) {
			if ((int)$appt['doctor_user_id'] !== (int)$_SESSION['user_id']) {
				echo json_encode(['success' => false, 'message' => 'You can only update your own appointments.']);
				exit;
			}
			if (!in_array($newStatus, ['IN_PROGRESS', 'COMPLETED', 'NO_SHOW'], true)) {
				echo json_encode(['success' => false, 'message' => 'Doctors can only mark In Progress, Completed, or No Show.']);
				exit;
			}
		} elseif (!can($role, 'appointments', 'W')) {
			echo json_encode(['success' => false, 'message' => 'Permission denied.']);
			exit;
		}

		$pdo->prepare(
			"UPDATE appointments SET status = ?, updated_at = NOW() WHERE appointment_id = ?"
		)->execute([$newStatus, $appointmentId]);

		// When completed, close the case sheet
		if ($newStatus === 'COMPLETED') {
			$pdo->prepare(
				"UPDATE case_sheets SET status = 'CLOSED', updated_at = NOW()
				  WHERE case_sheet_id = ? AND status != 'CLOSED'"
			)->execute([$appt['case_sheet_id']]);
		}

		echo json_encode(['success' => true, 'status' => $newStatus, 'message' => 'Status updated.']);
		exit;
	}

	// ── Get schedulable case sheets for a patient (AJAX GET) ────────────────

	public function getPatientCases(): void
	{
		$this->requireClinicalRole();
		header('Content-Type: application/json');

		$patientId = (int)($_GET['patient_id'] ?? 0);
		if ($patientId <= 0) {
			echo json_encode(['success' => true, 'cases' => []]);
			exit;
		}

		$pdo = getDBConnection();
		$stmt = $pdo->prepare(
			"SELECT cs.case_sheet_id, cs.status, cs.visit_type, cs.chief_complaint,
			        cs.assigned_doctor_user_id,
			        d.first_name AS doc_first, d.last_name AS doc_last
			   FROM case_sheets cs
			   LEFT JOIN users d ON d.user_id = cs.assigned_doctor_user_id
			  WHERE cs.patient_id = ?
			    AND cs.status NOT IN ('CLOSED')
			  ORDER BY cs.visit_datetime DESC
			  LIMIT 20"
		);
		$stmt->execute([$patientId]);

		$cases = [];
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
			$cases[] = [
				'case_sheet_id'           => (int)$c['case_sheet_id'],
				'status'                  => $c['status'],
				'visit_type'              => $c['visit_type'],
				'chief_complaint'         => $c['chief_complaint'],
				'assigned_doctor_user_id' => $c['assigned_doctor_user_id'] ? (int)$c['assigned_doctor_user_id'] : null,
				'assigned_doctor_name'    => $c['doc_first']
				                             ? trim($c['doc_first'] . ' ' . $c['doc_last'])
				                             : null,
			];
		}

		echo json_encode(['success' => true, 'cases' => $cases]);
		exit;
	}

	// ── Create patient + stub case sheet (AJAX POST) ───────────────────────
	// Called when a new patient is registered directly from the appointment
	// scheduling modal.  Creates the patient record (trigger assigns patient_code)
	// and a stub INTAKE_IN_PROGRESS case sheet so the appointment can be linked.

	public function createPatient(): void
	{
		header('Content-Type: application/json');

		if (!isset($_SESSION['user_id'])) {
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			exit;
		}

		$role = $_SESSION['user_role'] ?? '';
		if (!can($role, 'appointments', 'W')) {
			echo json_encode(['success' => false, 'message' => 'Permission denied.']);
			exit;
		}

		$body = json_decode(file_get_contents('php://input'), true);
		if (!$body) {
			echo json_encode(['success' => false, 'message' => 'Invalid request body.']);
			exit;
		}

		if (!hash_equals($_SESSION['csrf_token'] ?? '', $body['csrf_token'] ?? '')) {
			echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
			exit;
		}

		$firstName = trim($body['first_name'] ?? '');
		if ($firstName === '') {
			echo json_encode(['success' => false, 'message' => 'First name is required.']);
			exit;
		}

		$lastName  = trim($body['last_name']     ?? '') ?: null;
		$dob       = trim($body['date_of_birth'] ?? '') ?: null;
		$phone     = trim($body['phone_e164']    ?? '') ?: null;
		$email     = trim($body['email']         ?? '') ?: null;
		$address   = trim($body['address_line1'] ?? '') ?: null;
		$city      = trim($body['city']          ?? '') ?: null;

		if ($dob !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
			$dob = null;
		}

		$pdo = getDBConnection();

		try {
			// Insert patient – the BEFORE INSERT trigger assigns patient_code
			$pdo->prepare(
				'INSERT INTO patients
				    (first_name, last_name, date_of_birth, phone_e164, email,
				     address_line1, city, first_seen_date)
				 VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())'
			)->execute([$firstName, $lastName, $dob, $phone, $email, $address, $city]);

			$patientId = (int)$pdo->lastInsertId();

			// Fetch the auto-generated patient_code
			$row = $pdo->prepare('SELECT patient_code FROM patients WHERE patient_id = ?');
			$row->execute([$patientId]);
			$patientCode = $row->fetchColumn();

			// Create a stub case sheet so the appointment can be linked immediately.
			// The nurse will complete the full intake when the patient arrives.
			$pdo->prepare(
				'INSERT INTO case_sheets
				    (patient_id, visit_type, status, created_by_user_id, chief_complaint)
				 VALUES (?, ?, ?, ?, ?)'
			)->execute([$patientId, 'OTHER', 'INTAKE_IN_PROGRESS', $_SESSION['user_id'], '']);

			$caseSheetId = (int)$pdo->lastInsertId();

			echo json_encode([
				'success'       => true,
				'patient_id'    => $patientId,
				'patient_code'  => $patientCode,
				'case_sheet_id' => $caseSheetId,
				'first_name'    => $firstName,
				'last_name'     => $lastName ?? '',
			]);
		} catch (\PDOException $e) {
			http_response_code(500);
			echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
		}
		exit;
	}

	// ── Ensure patient has a patient_code (issue one if missing) ───────────
	// The DB trigger assigns codes automatically on INSERT.  This method
	// handles the edge case of pre-existing records that somehow lack a code.

	private function ensurePatientCode(PDO $pdo, int $patientId): void
	{
		$stmt = $pdo->prepare(
			'SELECT patient_code, first_seen_date FROM patients WHERE patient_id = ?'
		);
		$stmt->execute([$patientId]);
		$p = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$p || !empty($p['patient_code'])) {
			return; // Already has a code or patient not found
		}

		// Replicate the trigger logic to generate a code
		$seenDate = $p['first_seen_date'] ?? date('Y-m-d');

		$pdo->prepare(
			'INSERT INTO patient_daily_sequence (seq_date, last_n)
			 VALUES (?, LAST_INSERT_ID(1))
			 ON DUPLICATE KEY UPDATE last_n = LAST_INSERT_ID(last_n + 1)'
		)->execute([$seenDate]);

		$n    = (int)$pdo->query('SELECT LAST_INSERT_ID()')->fetchColumn();
		$code = date('Ymd', strtotime($seenDate)) . str_pad($n, 3, '0', STR_PAD_LEFT);

		$pdo->prepare(
			"UPDATE patients SET patient_code = ?
			  WHERE patient_id = ? AND (patient_code IS NULL OR patient_code = '')"
		)->execute([$code, $patientId]);
	}

	// ── Cancel appointment (AJAX POST) ─────────────────────────────────────

	public function cancelAppointment(): void
	{
		header('Content-Type: application/json');

		if (!isset($_SESSION['user_id'])) {
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			exit;
		}

		$role = $_SESSION['user_role'] ?? '';
		$body = json_decode(file_get_contents('php://input'), true);

		if (!$body || !hash_equals($_SESSION['csrf_token'] ?? '', $body['csrf_token'] ?? '')) {
			echo json_encode(['success' => false, 'message' => 'Invalid request.']);
			exit;
		}

		$appointmentId = (int)($body['appointment_id'] ?? 0);
		$note          = trim($body['note'] ?? '') ?: null;

		if ($appointmentId <= 0) {
			echo json_encode(['success' => false, 'message' => 'Invalid appointment.']);
			exit;
		}

		$pdo  = getDBConnection();
		$stmt = $pdo->prepare('SELECT appointment_id, case_sheet_id, doctor_user_id, status FROM appointments WHERE appointment_id = ?');
		$stmt->execute([$appointmentId]);
		$appt = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$appt) {
			echo json_encode(['success' => false, 'message' => 'Appointment not found.']);
			exit;
		}

		$isDoctor = ($role === 'DOCTOR');
		if ($isDoctor) {
			if ((int)$appt['doctor_user_id'] !== (int)$_SESSION['user_id']) {
				echo json_encode(['success' => false, 'message' => 'You can only cancel your own appointments.']);
				exit;
			}
		} elseif (!can($role, 'appointments', 'W')) {
			echo json_encode(['success' => false, 'message' => 'Permission denied.']);
			exit;
		}

		if (in_array($appt['status'], ['COMPLETED', 'CANCELLED'], true)) {
			echo json_encode(['success' => false, 'message' => 'Appointment is already ' . strtolower($appt['status']) . '.']);
			exit;
		}

		try {
			$pdo->prepare("UPDATE appointments SET status = 'CANCELLED', notes = ?, updated_at = NOW() WHERE appointment_id = ?")
			    ->execute([$note, $appointmentId]);

			// Revert case sheet so it can be rescheduled
			$pdo->prepare("UPDATE case_sheets SET status = 'INTAKE_IN_PROGRESS', updated_at = NOW() WHERE case_sheet_id = ? AND status = 'SCHEDULED'")
			    ->execute([$appt['case_sheet_id']]);

			echo json_encode(['success' => true]);
		} catch (\PDOException $e) {
			http_response_code(500);
			echo json_encode(['success' => false, 'message' => 'Database error.']);
		}
		exit;
	}

	// ── Reschedule appointment (AJAX POST) ──────────────────────────────────

	public function rescheduleAppointment(): void
	{
		header('Content-Type: application/json');

		if (!isset($_SESSION['user_id'])) {
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			exit;
		}

		$role = $_SESSION['user_role'] ?? '';
		$body = json_decode(file_get_contents('php://input'), true);

		if (!$body || !hash_equals($_SESSION['csrf_token'] ?? '', $body['csrf_token'] ?? '')) {
			echo json_encode(['success' => false, 'message' => 'Invalid request.']);
			exit;
		}

		$appointmentId = (int)($body['appointment_id'] ?? 0);
		$newDate       = trim($body['scheduled_date'] ?? '');
		$newTime       = trim($body['scheduled_time'] ?? '') ?: null;
		$note          = trim($body['note'] ?? '') ?: null;

		if ($appointmentId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
			echo json_encode(['success' => false, 'message' => 'A valid appointment and new date are required.']);
			exit;
		}

		if ($newTime && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $newTime)) {
			$newTime = null;
		}

		$pdo  = getDBConnection();
		$stmt = $pdo->prepare('SELECT appointment_id, case_sheet_id, doctor_user_id, status FROM appointments WHERE appointment_id = ?');
		$stmt->execute([$appointmentId]);
		$appt = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$appt) {
			echo json_encode(['success' => false, 'message' => 'Appointment not found.']);
			exit;
		}

		$isDoctor = ($role === 'DOCTOR');
		if ($isDoctor) {
			if ((int)$appt['doctor_user_id'] !== (int)$_SESSION['user_id']) {
				echo json_encode(['success' => false, 'message' => 'You can only reschedule your own appointments.']);
				exit;
			}
		} elseif (!can($role, 'appointments', 'W')) {
			echo json_encode(['success' => false, 'message' => 'Permission denied.']);
			exit;
		}

		if (in_array($appt['status'], ['COMPLETED', 'CANCELLED'], true)) {
			echo json_encode(['success' => false, 'message' => 'Cannot reschedule a ' . strtolower($appt['status']) . ' appointment.']);
			exit;
		}

		try {
			$pdo->prepare(
				"UPDATE appointments
				    SET scheduled_date = ?, scheduled_time = ?, notes = ?,
				        status = 'SCHEDULED', updated_at = NOW()
				  WHERE appointment_id = ?"
			)->execute([$newDate, $newTime, $note, $appointmentId]);

			// If intake had already started, re-link the case sheet to the new date
			$pdo->prepare(
				"UPDATE case_sheets SET status = 'SCHEDULED', updated_at = NOW()
				  WHERE case_sheet_id = ? AND status = 'INTAKE_IN_PROGRESS'"
			)->execute([$appt['case_sheet_id']]);

			echo json_encode([
				'success'      => true,
				'new_date_fmt' => date('D, M j, Y', strtotime($newDate)),
			]);
		} catch (\PDOException $e) {
			http_response_code(500);
			echo json_encode(['success' => false, 'message' => 'Database error.']);
		}
		exit;
	}

	// ── Role guard ──────────────────────────────────────────────────────────

	private function requireClinicalRole(): void
	{
		if (!can($_SESSION['user_role'] ?? '', 'case_sheets')) {
			$_SESSION['dashboard_notice'] = 'You do not have permission to access this page.';
			header('Location: dashboard.php');
			exit;
		}
	}
}
