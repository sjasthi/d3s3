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

class ClinicalController
{
	private const CLINICAL_ROLES = ['DOCTOR', 'TRIAGE_NURSE', 'NURSE'];

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
					        age_years, phone_e164, address_line1, city, state_province, postal_code,
					        blood_group, allergies, emergency_contact_name, emergency_contact_phone
					   FROM patients WHERE patient_id = ?'
				);
				$stmt->execute([$caseSheet['patient_id']]);
				$patient = $stmt->fetch();
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

		$pdo->prepare('UPDATE case_sheets SET status = ?, updated_at = NOW() WHERE case_sheet_id = ? AND status = ?')
		    ->execute(['INTAKE_COMPLETE', $caseSheetId, 'INTAKE_IN_PROGRESS']);

		$this->writeAuditLog(
			$pdo, $caseSheetId, $_SESSION['user_id'],
			'status', 'INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE'
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

		// Only claim if still INTAKE_COMPLETE (prevents double-claim)
		$stmt = $pdo->prepare(
			'UPDATE case_sheets
			    SET status = ?, assigned_doctor_user_id = ?, updated_at = NOW()
			  WHERE case_sheet_id = ? AND status = ?'
		);
		$stmt->execute(['DOCTOR_REVIEW', $_SESSION['user_id'], $caseSheetId, 'INTAKE_COMPLETE']);

		if ($stmt->rowCount() === 0) {
			$_SESSION['intake_error'] = 'This case sheet is no longer available for review.';
			header('Location: dashboard.php');
			exit;
		}

		$this->writeAuditLog(
			$pdo, $caseSheetId, $_SESSION['user_id'],
			'status', 'INTAKE_COMPLETE', 'DOCTOR_REVIEW'
		);

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

		// Load nurse who created the intake
		$intakeUser = null;
		if (!empty($caseSheet['created_by_user_id'])) {
			$stmt = $pdo->prepare('SELECT first_name, last_name FROM users WHERE user_id = ?');
			$stmt->execute([$caseSheet['created_by_user_id']]);
			$intakeUser = $stmt->fetch();
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
			    (patient_id, visit_type, status, created_by_user_id, chief_complaint)
			 VALUES (?, ?, ?, ?, ?)'
		);
		$stmt->execute([
			$patientId,
			$visitType,
			'INTAKE_IN_PROGRESS',
			$_SESSION['user_id'],
			$chiefComplaint,
		]);

		$newId = (int)$pdo->lastInsertId();

		$this->writeAuditLog(
			$pdo, $newId, $_SESSION['user_id'],
			'status', null, 'INTAKE_IN_PROGRESS'
		);

		header('Location: intake.php?case_sheet_id=' . $newId);
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

		$pdo->prepare(
			'INSERT INTO case_sheet_audit_log
			    (case_sheet_id, user_id, field_name, old_value, new_value, changed_at)
			 VALUES (?, ?, ?, ?, ?, NOW())'
		)->execute([$caseSheetId, $userId, $field, $oldValue, $newValue]);
	}

	// ── Role guards ─────────────────────────────────────────

	private function requireClinicalRole(): void
	{
		$role = $_SESSION['user_role'] ?? '';
		if (!in_array($role, self::CLINICAL_ROLES, true)) {
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
