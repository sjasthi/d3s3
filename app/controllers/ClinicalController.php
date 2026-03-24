<?php

/**
 * app/controllers/ClinicalController.php
 *
 * Handles the patient intake and doctor review workflow:
 *   - intake form: Step 1 (patient selection) + Step 2 (full tabbed form)
 *   - patient search (AJAX)
 *   - new patient registration (AJAX)
 *   - complete intake → INTAKE_COMPLETE
 *   - doctor claim-for-review → DOCTOR_REVIEW
 *   - doctor review form (5 tabs + audit log)
 *   - close case sheet → CLOSED + full audit trail
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

class ClinicalController
{
	// Roles with write (RW) access to case_sheets per the access matrix.
	// Used only as a reference; guards now call can() directly.
	private const CLINICAL_ROLES = ['SUPER_ADMIN', 'ADMIN', 'DOCTOR', 'TRIAGE_NURSE', 'NURSE', 'PARAMEDIC', 'DATA_ENTRY_OPERATOR'];

	// ── Intake form ─────────────────────────────────────────

	public function intake(): void
	{
		$this->requireClinicalRole();

		$flashSuccess = null;
		if (isset($_SESSION['intake_success'])) {
			$flashSuccess = $_SESSION['intake_success'];
			unset($_SESSION['intake_success']);
		}

		$formError = null;
		$caseSheet = null;
		$patient   = null;
		$labOrders = [];

		// Step 2: If case_sheet_id in URL, load the full form
		$caseSheetId = (int)($_GET['case_sheet_id'] ?? 0);
		if ($caseSheetId > 0) {
			$pdo  = getDBConnection();
			$stmt = $pdo->prepare('SELECT * FROM case_sheets WHERE case_sheet_id = ?');
			$stmt->execute([$caseSheetId]);
			$caseSheet = $stmt->fetch();

			if ($caseSheet) {
				$stmt = $pdo->prepare(
					'SELECT patient_id, patient_code, first_name, last_name, sex, date_of_birth,
					        age_years, phone_e164, email, address_line1, city, state_province, postal_code,
					        blood_group, allergies, emergency_contact_name, emergency_contact_phone
					   FROM patients WHERE patient_id = ?'
				);
				$stmt->execute([$caseSheet['patient_id']]);
				$patient = $stmt->fetch();

				// Load previous closed case sheet vitals for comparison panels in intake
				$prevVitals = [];
				try {
					$_prevStmt = $pdo->prepare(
						'SELECT vitals_json FROM case_sheets
						  WHERE patient_id = ?
						    AND case_sheet_id != ?
						    AND status = ?
						    AND vitals_json IS NOT NULL
						    AND vitals_json != ?
						  ORDER BY visit_datetime DESC
						  LIMIT 1'
					);
					$_prevStmt->execute([$caseSheet['patient_id'], $caseSheetId, 'CLOSED', '{}']);
					$_prevRow = $_prevStmt->fetch();
					if ($_prevRow) {
						$prevVitals = json_decode($_prevRow['vitals_json'], true) ?: [];
					}
				} catch (Exception $e) {
					$prevVitals = [];
				}

				// Load existing lab orders for this case sheet
				try {
					$stmt = $pdo->prepare(
						'SELECT lo.order_id, lo.test_name, lo.order_notes, lo.status, lo.ordered_at,
						        u.first_name AS ordered_by_first, u.last_name AS ordered_by_last
						   FROM lab_orders lo
						   JOIN users u ON u.user_id = lo.ordered_by_user_id
						  WHERE lo.case_sheet_id = ?
						  ORDER BY lo.ordered_at DESC'
					);
					$stmt->execute([$caseSheetId]);
					$labOrders = $stmt->fetchAll();
				} catch (Exception $e) {
					$labOrders = []; // table not yet created
				}
			}
		}

		// Step 1: POST creates the case sheet and redirects to Step 2
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$caseSheetId) {
			$formError = $this->processCreateCaseSheet();
		}

		require __DIR__ . '/../views/intake.php';
	}

	// ── Complete intake (mark as INTAKE_COMPLETE) ────────────

	public function completeIntake(): void
	{
		$this->requireClinicalRole();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: intake.php');
			exit;
		}

		if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
			$_SESSION['intake_error'] = 'Invalid request token.';
			header('Location: intake.php');
			exit;
		}

		$caseSheetId = (int)($_POST['case_sheet_id'] ?? 0);
		if ($caseSheetId <= 0) {
			header('Location: intake.php');
			exit;
		}

		$pdo = getDBConnection();

		$stmt = $pdo->prepare(
			'SELECT cs.case_sheet_id, p.first_name, p.last_name, p.patient_code
			   FROM case_sheets cs
			   JOIN patients p ON p.patient_id = cs.patient_id
			  WHERE cs.case_sheet_id = ?'
		);
		$stmt->execute([$caseSheetId]);
		$row = $stmt->fetch();

		if (!$row) {
			header('Location: intake.php');
			exit;
		}

		// Capture current status for audit log (also handles SCHEDULED → INTAKE_COMPLETE
		// when a nurse starts intake directly from a scheduled appointment)
		$oldStatus = $pdo->prepare('SELECT status FROM case_sheets WHERE case_sheet_id = ?');
		$oldStatus->execute([$caseSheetId]);
		$oldStatus = $oldStatus->fetchColumn() ?: 'INTAKE_IN_PROGRESS';

		$pdo->prepare(
			"UPDATE case_sheets SET status = 'INTAKE_COMPLETE', updated_at = NOW()
			  WHERE case_sheet_id = ? AND status IN ('INTAKE_IN_PROGRESS', 'SCHEDULED')"
		)->execute([$caseSheetId]);

		$this->writeAuditLog(
			$pdo, $caseSheetId, $_SESSION['user_id'],
			'status', $oldStatus, 'INTAKE_COMPLETE'
		);

		$_SESSION['intake_success'] = 'Intake completed for '
			. htmlspecialchars($row['first_name'] . ' ' . ($row['last_name'] ?? ''))
			. ' (' . htmlspecialchars($row['patient_code']) . '). Case sheet is now in the doctor queue.';

		header('Location: intake.php');
		exit;
	}

	// ── Doctor claims case sheet for review ──────────────────

	public function claimForReview(): void
	{
		$this->requireDoctorRole();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: dashboard.php');
			exit;
		}

		if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
			$_SESSION['intake_error'] = 'Invalid request token.';
			header('Location: dashboard.php');
			exit;
		}

		$caseSheetId = (int)($_POST['case_sheet_id'] ?? 0);
		if ($caseSheetId <= 0) {
			header('Location: dashboard.php');
			exit;
		}

		$pdo = getDBConnection();

		// Claim if INTAKE_COMPLETE or SCHEDULED (nurse may have pre-assigned a doctor)
		$stmt = $pdo->prepare(
			'UPDATE case_sheets
			    SET status = ?, assigned_doctor_user_id = ?, assigned_doctor_name = ?, updated_at = NOW()
			  WHERE case_sheet_id = ? AND status IN (?, ?)'
		);
		$stmt->execute(['DOCTOR_REVIEW', $_SESSION['user_id'], trim($_SESSION['user_name'] ?? ''), $caseSheetId, 'INTAKE_COMPLETE', 'SCHEDULED']);

		if ($stmt->rowCount() === 0) {
			$_SESSION['intake_error'] = 'This case sheet is no longer available for review.';
			header('Location: dashboard.php');
			exit;
		}

		$this->writeAuditLog(
			$pdo, $caseSheetId, $_SESSION['user_id'],
			'status', 'INTAKE_COMPLETE', 'DOCTOR_REVIEW'
		);

		// Advance linked appointment to IN_PROGRESS so it reflects active review
		$pdo->prepare(
			"UPDATE appointments SET status = 'IN_PROGRESS', updated_at = NOW()
			  WHERE case_sheet_id = ? AND status IN ('SCHEDULED','CONFIRMED')"
		)->execute([$caseSheetId]);

		header('Location: review.php?case_sheet_id=' . $caseSheetId);
		exit;
	}

	// ── Doctor review form ───────────────────────────────────

	public function doctorReview(): void
	{
		$this->requireDoctorRole();

		$flashError = null;
		if (isset($_SESSION['review_error'])) {
			$flashError = $_SESSION['review_error'];
			unset($_SESSION['review_error']);
		}

		$caseSheetId = (int)($_GET['case_sheet_id'] ?? 0);
		if ($caseSheetId <= 0) {
			header('Location: dashboard.php');
			exit;
		}

		$pdo = getDBConnection();

		// Must be assigned to this doctor and status = DOCTOR_REVIEW
		$stmt = $pdo->prepare(
			'SELECT * FROM case_sheets
			  WHERE case_sheet_id = ?
			    AND assigned_doctor_user_id = ?
			    AND status = ?'
		);
		$stmt->execute([$caseSheetId, $_SESSION['user_id'], 'DOCTOR_REVIEW']);
		$caseSheet = $stmt->fetch();

		if (!$caseSheet) {
			$_SESSION['dashboard_notice'] = 'Case sheet not found or not assigned to you.';
			header('Location: dashboard.php');
			exit;
		}

		// Load patient
		$stmt = $pdo->prepare(
			'SELECT patient_id, patient_code, first_name, last_name, sex, date_of_birth,
			        age_years, phone_e164, address_line1, city, state_province, postal_code,
			        blood_group, allergies, emergency_contact_name, emergency_contact_phone
			   FROM patients WHERE patient_id = ?'
		);
		$stmt->execute([$caseSheet['patient_id']]);
		$patient = $stmt->fetch();

		// Load most recent closed case sheet with vitals data for comparison panel.
		// Uses separate queries per data type so each panel finds the most recent
		// case sheet that actually has that data — not necessarily the same visit.
		// Wrapped in try/catch so any DB error simply hides the panel rather than crashing.
		$prevCaseSheet = null;
		$prevVitals    = [];
		$prevLabData   = [];
		try {
			$stmt = $pdo->prepare(
				'SELECT case_sheet_id, visit_datetime, vitals_json
				   FROM case_sheets
				  WHERE patient_id = ?
				    AND case_sheet_id != ?
				    AND status = ?
				    AND vitals_json IS NOT NULL
				    AND vitals_json != ?
				  ORDER BY visit_datetime DESC
				  LIMIT 1'
			);
			$stmt->execute([$caseSheet['patient_id'], $caseSheetId, 'CLOSED', '{}']);
			$prevVitalsSheet = $stmt->fetch();
			if ($prevVitalsSheet) {
				$prevCaseSheet = $prevVitalsSheet; // used by view for visit_datetime display
				$prevVitals    = json_decode($prevVitalsSheet['vitals_json'], true) ?: [];
			}

			$stmt = $pdo->prepare(
				'SELECT diagnosis
				   FROM case_sheets
				  WHERE patient_id = ?
				    AND case_sheet_id != ?
				    AND status = ?
				    AND diagnosis IS NOT NULL
				    AND diagnosis != ?
				  ORDER BY visit_datetime DESC
				  LIMIT 1'
			);
			$stmt->execute([$caseSheet['patient_id'], $caseSheetId, 'CLOSED', '{}']);
			$prevLabSheet = $stmt->fetch();
			if ($prevLabSheet) {
				$prevLabData = json_decode($prevLabSheet['diagnosis'], true) ?: [];
			}
		} catch (Exception $e) {
			// Leave comparison panels empty — better than a crash
			$prevCaseSheet = null;
			$prevVitals    = [];
			$prevLabData   = [];
		}

		// Load all prior closed case sheets for Patient History tab
		$stmt = $pdo->prepare(
			'SELECT cs.case_sheet_id, cs.visit_datetime, cs.visit_type,
			        cs.closure_type, cs.chief_complaint,
			        cs.vitals_json, cs.assessment, cs.diagnosis,
			        cs.plan_notes, cs.exam_notes,
			        cs.doctor_assessment, cs.doctor_diagnosis,
			        cs.doctor_plan_notes, cs.prescriptions, cs.follow_up_notes,
			        u.first_name AS doctor_first, u.last_name AS doctor_last
			   FROM case_sheets cs
			   LEFT JOIN users u ON u.user_id = cs.assigned_doctor_user_id
			  WHERE cs.patient_id = ?
			    AND cs.case_sheet_id != ?
			    AND cs.status = ?
			  ORDER BY cs.visit_datetime DESC'
		);
		$stmt->execute([$caseSheet['patient_id'], $caseSheetId, 'CLOSED']);
		$priorCaseSheets = $stmt->fetchAll();

		// Resolve intake nurse name.
		// Priority: snapshotted created_by_name → live users JOIN → audit log fallback.
		$intakeUser = null;
		if (!empty($caseSheet['created_by_name'])) {
			$parts = explode(' ', trim($caseSheet['created_by_name']), 2);
			$intakeUser = ['first_name' => $parts[0], 'last_name' => $parts[1] ?? ''];
		} elseif (!empty($caseSheet['created_by_user_id'])) {
			$stmt = $pdo->prepare('SELECT first_name, last_name FROM users WHERE user_id = ?');
			$stmt->execute([$caseSheet['created_by_user_id']]);
			$intakeUser = $stmt->fetch() ?: null;
		}
		if (!$intakeUser) {
			$stmt = $pdo->prepare(
				'SELECT u.first_name, u.last_name
				   FROM case_sheet_audit_log al
				   JOIN users u ON u.user_id = al.user_id
				  WHERE al.case_sheet_id = ? AND al.field_name = ? AND al.new_value = ?
				  ORDER BY al.changed_at ASC LIMIT 1'
			);
			$stmt->execute([$caseSheetId, 'status', 'INTAKE_IN_PROGRESS']);
			$intakeUser = $stmt->fetch() ?: null;
		}

		// Load audit log for this case sheet (most recent first)
		$stmt = $pdo->prepare(
			'SELECT al.field_name, al.old_value, al.new_value, al.changed_at,
			        u.first_name, u.last_name
			   FROM case_sheet_audit_log al
			   JOIN users u ON u.user_id = al.user_id
			  WHERE al.case_sheet_id = ?
			  ORDER BY al.changed_at DESC
			  LIMIT 100'
		);
		$stmt->execute([$caseSheetId]);
		$auditLog = $stmt->fetchAll();

		require __DIR__ . '/../views/review.php';
	}

	// ── Close (finalize) case sheet ─────────────────────────

	public function closeCaseSheet(): void
	{
		$this->requireDoctorRole();

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: dashboard.php');
			exit;
		}

		if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
			$_SESSION['review_error'] = 'Invalid request token.';
			header('Location: dashboard.php');
			exit;
		}

		$caseSheetId = (int)($_POST['case_sheet_id'] ?? 0);
		if ($caseSheetId <= 0) {
			header('Location: dashboard.php');
			exit;
		}

		$closureType = $_POST['closure_type'] ?? 'DISCHARGED';
		$validClosureTypes = ['DISCHARGED', 'FOLLOW_UP', 'REFERRAL', 'PENDING'];
		if (!in_array($closureType, $validClosureTypes, true)) {
			$closureType = 'DISCHARGED';
		}

		$pdo = getDBConnection();

		// Fetch current values for audit trail
		$stmt = $pdo->prepare(
			'SELECT cs.status, cs.closure_type, cs.is_closed, cs.is_locked,
			        p.first_name, p.last_name, p.patient_code
			   FROM case_sheets cs
			   JOIN patients p ON p.patient_id = cs.patient_id
			  WHERE cs.case_sheet_id = ?
			    AND cs.assigned_doctor_user_id = ?'
		);
		$stmt->execute([$caseSheetId, $_SESSION['user_id']]);
		$row = $stmt->fetch();

		if (!$row || $row['status'] !== 'DOCTOR_REVIEW') {
			$_SESSION['review_error'] = 'Unable to close this case sheet.';
			header('Location: review.php?case_sheet_id=' . $caseSheetId);
			exit;
		}

		$userId = $_SESSION['user_id'];
		$now    = date('Y-m-d H:i:s');

		$pdo->prepare(
			'UPDATE case_sheets
			    SET status = ?, is_closed = 1, closed_at = ?, closed_by_user_id = ?,
			        closure_type = ?, is_locked = 1, updated_at = ?
			  WHERE case_sheet_id = ?'
		)->execute(['CLOSED', $now, $userId, $closureType, $now, $caseSheetId]);

		// Audit each closure field
		$closureAudit = [
			['status',            $row['status'],                      'CLOSED'],
			['is_closed',         (string)(int)$row['is_closed'],      '1'],
			['closure_type',      $row['closure_type'] ?? 'PENDING',   $closureType],
			['closed_by_user_id', null,                                (string)$userId],
			['closed_at',         null,                                $now],
			['is_locked',         (string)(int)$row['is_locked'],      '1'],
		];

		foreach ($closureAudit as [$field, $old, $new]) {
			$this->writeAuditLog($pdo, $caseSheetId, $userId, $field, $old, $new);
		}

		// Mark linked appointment as COMPLETED
		$pdo->prepare(
			"UPDATE appointments SET status = 'COMPLETED', updated_at = NOW()
			  WHERE case_sheet_id = ? AND status NOT IN ('CANCELLED','NO_SHOW','COMPLETED')"
		)->execute([$caseSheetId]);

		$_SESSION['dashboard_notice'] = 'Chart closed for '
			. htmlspecialchars($row['first_name'] . ' ' . ($row['last_name'] ?? ''))
			. ' (' . htmlspecialchars($row['patient_code']) . ').';

		header('Location: dashboard.php');
		exit;
	}

	// ── Patient search (AJAX) ───────────────────────────────

	public function patientSearch(): void
	{
		$this->requireClinicalRole();
		header('Content-Type: application/json');

		$q = trim($_GET['q'] ?? '');
		if (strlen($q) < 2) {
			echo json_encode(['success' => true, 'patients' => []]);
			exit;
		}

		$pdo  = getDBConnection();
		$like = '%' . $q . '%';
		$stmt = $pdo->prepare(
			'SELECT patient_id, patient_code, first_name, last_name,
			        sex, age_years, phone_e164
			   FROM patients
			  WHERE is_active = 1
			    AND (patient_code LIKE ?
			      OR first_name LIKE ?
			      OR last_name LIKE ?
			      OR phone_e164 LIKE ?)
			  ORDER BY last_name, first_name
			  LIMIT 10'
		);
		$stmt->execute([$like, $like, $like, $like]);

		echo json_encode(['success' => true, 'patients' => $stmt->fetchAll()]);
		exit;
	}

	// ── Register new patient (AJAX) ─────────────────────────

	public function registerPatient(): void
	{
		$this->requireClinicalRole();
		header('Content-Type: application/json');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			echo json_encode(['success' => false, 'message' => 'POST required']);
			exit;
		}

		if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
			echo json_encode(['success' => false, 'message' => 'Invalid request token.']);
			exit;
		}

		$firstName = trim($_POST['first_name'] ?? '');
		$lastName  = trim($_POST['last_name'] ?? '');
		$sex       = $_POST['sex'] ?? 'UNKNOWN';
		$dob       = $_POST['date_of_birth'] ?? null;
		$ageYears  = $_POST['age_years'] ?? null;
		$phone     = trim($_POST['phone_e164'] ?? '');

		if ($firstName === '') {
			echo json_encode(['success' => false, 'message' => 'First name is required.']);
			exit;
		}

		$validSex = ['MALE', 'FEMALE', 'OTHER', 'UNKNOWN'];
		if (!in_array($sex, $validSex, true)) {
			$sex = 'UNKNOWN';
		}

		if ($dob !== null && $dob !== '') {
			$dob = date('Y-m-d', strtotime($dob)) ?: null;
		} else {
			$dob = null;
		}

		$ageYears = ($ageYears !== null && $ageYears !== '') ? (int)$ageYears : null;

		$pdo  = getDBConnection();
		$stmt = $pdo->prepare(
			'INSERT INTO patients (first_name, last_name, sex, date_of_birth, age_years, phone_e164, first_seen_date)
			 VALUES (?, ?, ?, ?, ?, ?, CURDATE())'
		);
		$stmt->execute([$firstName, $lastName ?: null, $sex, $dob, $ageYears, $phone ?: null]);
		$patientId = (int)$pdo->lastInsertId();

		// Fetch the auto-generated patient_code (set by database trigger)
		$row = $pdo->prepare('SELECT patient_code FROM patients WHERE patient_id = ?');
		$row->execute([$patientId]);
		$patientCode = $row->fetchColumn();

		echo json_encode([
			'success'      => true,
			'patient_id'   => $patientId,
			'patient_code' => $patientCode,
			'first_name'   => $firstName,
			'last_name'    => $lastName,
		]);
		exit;
	}

	// ── Create case sheet (Step 1 POST) ─────────────────────

	private function processCreateCaseSheet(): ?string
	{
		if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
			return 'Invalid request token.';
		}

		$patientId      = (int)($_POST['patient_id'] ?? 0);
		$visitType      = $_POST['visit_type'] ?? '';
		$chiefComplaint = trim($_POST['chief_complaint'] ?? '');

		if ($patientId <= 0) {
			return 'Please select a patient.';
		}

		$validTypes = ['CAMP', 'CLINIC', 'FOLLOW_UP', 'EMERGENCY', 'OTHER'];
		if (!in_array($visitType, $validTypes, true)) {
			return 'Please select a valid visit type.';
		}

		if ($chiefComplaint === '') {
			return 'Chief complaint is required.';
		}

		$pdo  = getDBConnection();
		$stmt = $pdo->prepare('SELECT patient_id FROM patients WHERE patient_id = ? AND is_active = 1');
		$stmt->execute([$patientId]);
		if (!$stmt->fetch()) {
			return 'Patient not found.';
		}

		$stmt = $pdo->prepare(
			'INSERT INTO case_sheets
			    (patient_id, visit_type, status, created_by_user_id, created_by_name, chief_complaint)
			 VALUES (?, ?, ?, ?, ?, ?)'
		);
		$stmt->execute([
			$patientId,
			$visitType,
			'INTAKE_IN_PROGRESS',
			$_SESSION['user_id'],
			trim($_SESSION['user_name'] ?? ''),
			$chiefComplaint,
		]);

		$newId = (int)$pdo->lastInsertId();

		$this->writeAuditLog(
			$pdo, $newId, $_SESSION['user_id'],
			'status', null, 'INTAKE_IN_PROGRESS'
		);

		// ── Carry over persistent fields from the most recent prior case sheet ──
		// These are fields that don't change visit-to-visit and save the nurse
		// from re-entering stable background data on every return visit.
		$stmt = $pdo->prepare(
			'SELECT vitals_json, assessment
			   FROM case_sheets
			  WHERE patient_id = ?
			    AND case_sheet_id != ?
			    AND status = ?
			  ORDER BY visit_datetime DESC
			  LIMIT 1'
		);
		$stmt->execute([$patientId, $newId, 'CLOSED']);
		$prev = $stmt->fetch();

		if ($prev) {
			$prevVitals  = !empty($prev['vitals_json']) ? (json_decode($prev['vitals_json'], true) ?: []) : [];
			$prevHistory = !empty($prev['assessment'])  ? (json_decode($prev['assessment'],  true) ?: []) : [];

			// Fields to carry over from vitals_json
			$carryVitalsKeys = [
				// Background information
				'medicine_sources', 'occupation', 'education', 'diet',
				// Reproductive history
				'number_of_children', 'has_uterus',
				'type_of_delivery', 'delivery_location', 'delivery_source',
				// Menstrual — age of onset only (other menstrual fields are visit-specific)
				'menstrual_age_of_onset',
			];

			// Fields to carry over from assessment (JSON)
			$carryHistoryKeys = [
				// Medical conditions
				'condition_dm', 'condition_htn', 'condition_tsh', 'condition_heart_disease',
				'condition_others', 'surgical_history',
				// Allergies
				'allergies_json', 'no_known_allergies',
				// Family history
				'family_history_cancer', 'family_history_tuberculosis', 'family_history_diabetes',
				'family_history_bp', 'family_history_thyroid', 'family_history_other',
			];

			$newVitals  = [];
			$newHistory = [];

			foreach ($carryVitalsKeys as $key) {
				if (isset($prevVitals[$key])) {
					$newVitals[$key] = $prevVitals[$key];
				}
			}
			foreach ($carryHistoryKeys as $key) {
				if (isset($prevHistory[$key])) {
					$newHistory[$key] = $prevHistory[$key];
				}
			}

			if (!empty($newVitals) || !empty($newHistory)) {
				$pdo->prepare(
					'UPDATE case_sheets
					    SET vitals_json = ?, assessment = ?, updated_at = NOW()
					  WHERE case_sheet_id = ?'
				)->execute([
					!empty($newVitals)  ? json_encode($newVitals)  : null,
					!empty($newHistory) ? json_encode($newHistory) : null,
					$newId,
				]);
			}
		}

		header('Location: intake.php?case_sheet_id=' . $newId);
		exit;
	}

	// ── Update patient record (AJAX) ────────────────────────

	public function updatePatient(): void
	{
		$this->requireClinicalRole();

		header('Content-Type: application/json');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
			exit;
		}

		$input = json_decode(file_get_contents('php://input'), true);

		if (!isset($input['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'])) {
			echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
			exit;
		}

		$patientId   = (int)($input['patient_id'] ?? 0);
		$caseSheetId = (int)($input['case_sheet_id'] ?? 0); // optional — used for audit log
		if ($patientId <= 0) {
			echo json_encode(['success' => false, 'message' => 'Missing patient ID.']);
			exit;
		}

		// Whitelist of editable fields (patient_code is NOT included)
		$allowed = [
			'first_name', 'last_name', 'sex', 'date_of_birth', 'age_years',
			'phone_e164', 'email', 'address_line1', 'city', 'state_province',
			'postal_code', 'blood_group', 'allergies',
			'emergency_contact_name', 'emergency_contact_phone',
		];

		// Normalise / validate individual fields before building the query
		$validSex = ['MALE', 'FEMALE', 'OTHER', 'UNKNOWN'];
		if (isset($input['sex'])) {
			// sex is NOT NULL in the DB -- default to UNKNOWN if blank or invalid
			if (!in_array($input['sex'], $validSex, true)) {
				$input['sex'] = 'UNKNOWN';
			}
		}

		// age_years is a smallint -- cast to int or null
		if (isset($input['age_years'])) {
			$input['age_years'] = ($input['age_years'] === '' || $input['age_years'] === null)
				? null
				: (int)$input['age_years'];
		}

		// Collect only the fields present in the input
		$fieldsToUpdate = [];
		foreach ($allowed as $field) {
			if (array_key_exists($field, $input)) {
				$fieldsToUpdate[$field] = ($input[$field] === '' || $input[$field] === null)
					? null
					: $input[$field];
			}
		}

		if (empty($fieldsToUpdate)) {
			echo json_encode(['success' => false, 'message' => 'No fields to update.']);
			exit;
		}

		try {
			$pdo = getDBConnection();

			// ── Read current values before update (for audit log) ──────────
			$colList = implode(', ', array_map(fn($f) => "`$f`", array_keys($fieldsToUpdate)));
			$stmt    = $pdo->prepare("SELECT $colList FROM patients WHERE patient_id = ?");
			$stmt->execute([$patientId]);
			$currentValues = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

			// ── Perform the update ─────────────────────────────────────────
			$setClauses = array_map(fn($f) => "`$f` = ?", array_keys($fieldsToUpdate));
			$params     = array_values($fieldsToUpdate);
			$params[]   = $patientId;

			$pdo->prepare(
				'UPDATE patients SET ' . implode(', ', $setClauses) . ' WHERE patient_id = ?'
			)->execute($params);

			// ── Write audit log for each changed field ──────────────────────
			// Only logs if a case_sheet_id was provided — backward compatible
			// with existing intake.php calls that don't pass one.
			// Field names are prefixed "patient." to distinguish from case sheet fields.
			if ($caseSheetId > 0) {
				foreach ($fieldsToUpdate as $field => $newVal) {
					$oldVal    = array_key_exists($field, $currentValues)
						? ($currentValues[$field] === null ? null : (string)$currentValues[$field])
						: null;
					$newValStr = $newVal === null ? null : (string)$newVal;
					$this->writeAuditLog(
						$pdo, $caseSheetId, $_SESSION['user_id'],
						'patient.' . $field, $oldVal, $newValStr
					);
				}
			}

			// ── Return the refreshed patient row so JS can update the display
			$stmt = $pdo->prepare(
				'SELECT patient_id, patient_code, first_name, last_name, sex, date_of_birth,
				        age_years, phone_e164, email, address_line1, city, state_province,
				        postal_code, blood_group, allergies,
				        emergency_contact_name, emergency_contact_phone
				   FROM patients WHERE patient_id = ?'
			);
			$stmt->execute([$patientId]);
			$patient = $stmt->fetch(PDO::FETCH_ASSOC);

			echo json_encode(['success' => true, 'message' => 'Patient information updated.', 'patient' => $patient]);
		} catch (Exception $e) {
			error_log('updatePatientInfo error: ' . $e->getMessage());
			echo json_encode(['success' => false, 'message' => 'Database error.']);
		}
		exit;
	}

	// ── Shared audit log writer ──────────────────────────────

	public static function writeAuditLog(
		PDO $pdo,
		int $caseSheetId,
		int $userId,
		string $field,
		?string $oldValue,
		?string $newValue
	): void {
		if ($oldValue === $newValue) {
			return; // No change — no log entry needed
		}

		$changedByName = trim($_SESSION['user_name'] ?? '');
		$pdo->prepare(
			'INSERT INTO case_sheet_audit_log
			    (case_sheet_id, user_id, changed_by_name, field_name, old_value, new_value, changed_at)
			 VALUES (?, ?, ?, ?, ?, ?, NOW())'
		)->execute([$caseSheetId, $userId, $changedByName ?: null, $field, $oldValue, $newValue]);
	}

	// ── Order lab tests (AJAX POST) ────────────────────────

	public function orderLabTest(): void
	{
		$this->requireClinicalRole();
		header('Content-Type: application/json');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			echo json_encode(['success' => false, 'message' => 'POST required']);
			exit;
		}

		$input = json_decode(file_get_contents('php://input'), true);

		if (!hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'] ?? '')) {
			echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
			exit;
		}

		$caseSheetId = (int)($input['case_sheet_id'] ?? 0);
		$tests       = $input['tests'] ?? [];
		$notes       = trim($input['notes'] ?? '');

		if ($caseSheetId <= 0 || empty($tests)) {
			echo json_encode(['success' => false, 'message' => 'Please select at least one test.']);
			exit;
		}

		$pdo  = getDBConnection();
		$stmt = $pdo->prepare('SELECT patient_id FROM case_sheets WHERE case_sheet_id = ?');
		$stmt->execute([$caseSheetId]);
		$row = $stmt->fetch();

		if (!$row) {
			echo json_encode(['success' => false, 'message' => 'Case sheet not found.']);
			exit;
		}

		$patientId = (int)$row['patient_id'];
		$userId    = (int)$_SESSION['user_id'];

		$insert = $pdo->prepare(
			'INSERT INTO lab_orders (case_sheet_id, patient_id, test_name, order_notes, ordered_by_user_id)
			 VALUES (?, ?, ?, ?, ?)'
		);

		$inserted = [];
		foreach ($tests as $testName) {
			$testName = trim((string)$testName);
			if ($testName === '') {
				continue;
			}
			$insert->execute([$caseSheetId, $patientId, $testName, $notes !== '' ? $notes : null, $userId]);
			$inserted[] = ['order_id' => (int)$pdo->lastInsertId(), 'test_name' => $testName];
		}

		$orderedBy = trim(($_SESSION['user_name'] ?? ''));

		echo json_encode([
			'success'    => true,
			'orders'     => $inserted,
			'notes'      => $notes,
			'ordered_by' => $orderedBy,
		]);
		exit;
	}

	// ── Get lab orders for a case sheet (AJAX GET) ──────────

	public function getLabOrders(): void
	{
		$this->requireClinicalRole();
		header('Content-Type: application/json');

		$caseSheetId = (int)($_GET['case_sheet_id'] ?? 0);
		if ($caseSheetId <= 0) {
			echo json_encode(['success' => false, 'message' => 'Missing case sheet ID.']);
			exit;
		}

		$pdo  = getDBConnection();
		$stmt = $pdo->prepare(
			'SELECT lo.order_id, lo.test_name, lo.order_notes, lo.status, lo.ordered_at,
			        u.first_name AS ordered_by_first, u.last_name AS ordered_by_last
			   FROM lab_orders lo
			   JOIN users u ON u.user_id = lo.ordered_by_user_id
			  WHERE lo.case_sheet_id = ?
			  ORDER BY lo.ordered_at DESC'
		);
		$stmt->execute([$caseSheetId]);

		echo json_encode(['success' => true, 'orders' => $stmt->fetchAll()]);
		exit;
	}

	// ── Role guards ─────────────────────────────────────────

	private function requireClinicalRole(): void
	{
		if (!can($_SESSION['user_role'] ?? '', 'case_sheets', 'W')) {
			$_SESSION['dashboard_notice'] = 'You do not have permission to access this page.';
			header('Location: dashboard.php');
			exit;
		}
	}

	private function requireDoctorRole(): void
	{
		if (($_SESSION['user_role'] ?? '') !== 'DOCTOR') {
			$_SESSION['dashboard_notice'] = 'You do not have permission to access this page.';
			header('Location: dashboard.php');
			exit;
		}
	}
}
