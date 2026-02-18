<?php
/**
 * update_case_sheet.php
 * API endpoint to update a single case sheet field and log the change.
 *
 * Every write is audited: old value → new value, user, timestamp.
 *
 * Field routing:
 *   $direct_columns  → UPDATE case_sheets SET `field` = ?
 *   $vitals_fields   → JSON merge into vitals_json
 *   $exam_fields     → JSON merge into exam_notes
 *   $history_fields  → JSON merge into assessment
 *   $lab_fields      → JSON merge into diagnosis
 *   $summary_fields  → JSON merge into plan_notes
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/app/config/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
	echo json_encode(['success' => false, 'message' => 'Unauthorized']);
	exit;
}

// Accept JSON body or POST form
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
	$input = $_POST;
}

// CSRF check
$token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
	echo json_encode(['success' => false, 'message' => 'Invalid request token.']);
	exit;
}

require_once __DIR__ . '/app/config/database.php';
$pdo = getDBConnection();

$caseSheetId = $input['case_sheet_id'] ?? null;
$field       = $input['field'] ?? null;
$value       = $input['value'] ?? null;
$userId      = (int)$_SESSION['user_id'];

if (!$caseSheetId || !$field) {
	echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
	exit;
}

// ── Field whitelists ────────────────────────────────────────

// Direct columns (stored as-is in their own column)
$direct_columns = [
	// Visit / intake
	'visit_type', 'chief_complaint', 'history_present_illness',
	// Doctor treatment plan
	'prescriptions', 'advice',
	// Follow-up and referrals
	'follow_up_date', 'follow_up_notes',
	'referral_to', 'referral_reason',
	'disposition', 'closure_type',
	// Doctor-specific documentation columns
	'doctor_exam_notes', 'doctor_assessment',
	'doctor_diagnosis', 'doctor_plan_notes',
	// Examination diagrams (base64 PNG, stored directly — can be large)
	'diag_breast', 'diag_pelvic', 'diag_via', 'diag_vili',
];

// Nurse intake JSON fields → stored in vitals_json
$vitals_fields = [
	// Classic vitals
	'bp_systolic', 'bp_diastolic', 'pulse', 'temperature',
	'weight_kg', 'height_cm', 'spo2', 'respiratory_rate', 'blood_sugar',
	'bmi', 'obesity_overweight',
	// Personal / symptoms
	'symptoms_complaints', 'duration_of_symptoms',
	// Reproductive
	'number_of_children', 'has_uterus',
	'type_of_delivery', 'delivery_location', 'delivery_source',
	// Menstrual
	'menstrual_age_of_onset', 'menstrual_cycle_frequency',
	'menstrual_duration_of_flow', 'menstrual_lmp', 'menstrual_mh',
	// General exam vitals (legacy names from original form)
	'general_pulse', 'general_bp_systolic', 'general_bp_diastolic',
	'general_height', 'general_weight', 'general_bmi', 'general_obesity_overweight',
	'general_heart', 'general_lungs', 'general_liver',
	'general_spleen', 'general_lymph_glands',
];

// Nurse examination fields → stored in exam_notes (JSON)
$exam_fields = [
	'exam_mouth', 'exam_lips', 'exam_buccal_mucosa',
	'exam_teeth', 'exam_tongue', 'exam_oropharynx',
	'exam_hypo', 'exam_naso_pharynx', 'exam_larynx',
	'exam_nose', 'exam_ears', 'exam_neck',
	'exam_bones_joints', 'exam_abdomen_genital',
	'exam_breast_left', 'exam_breast_right', 'exam_breast_axillary_nodes',
	'exam_pelvic_cervix', 'exam_pelvic_uterus', 'exam_pelvic_ovaries', 'exam_pelvic_adnexa',
	'exam_rectal_skin', 'exam_rectal_remarks',
	'exam_gynae_ps', 'exam_gynae_pv', 'exam_gynae_via', 'exam_gynae_vili',
];

// Nurse history fields → stored in assessment (JSON)
$history_fields = [
	'condition_dm', 'condition_htn', 'condition_tsh', 'condition_heart_disease',
	'condition_others', 'surgical_history',
	'family_history_cancer', 'family_history_tuberculosis', 'family_history_diabetes',
	'family_history_bp', 'family_history_thyroid', 'family_history_other',
];

// Nurse lab fields → stored in diagnosis (JSON)
$lab_fields = [
	'lab_hb_percentage', 'lab_hb_gms', 'lab_fbs', 'lab_tsh', 'lab_sr_creatinine',
	'lab_others',
	'cytology_papsmear', 'cytology_papsmear_notes',
	'cytology_colposcopy', 'cytology_colposcopy_notes',
	'cytology_biopsy', 'cytology_biopsy_notes',
];

// Nurse summary fields → stored in plan_notes (JSON)
$summary_fields = [
	'summary_risk_level', 'summary_referral',
	'summary_patient_acceptance', 'summary_doctor_summary',
];

// ── Audit helpers ───────────────────────────────────────────

function writeAudit(PDO $pdo, $caseSheetId, int $userId, string $field, ?string $old, ?string $new): void
{
	if ($old === $new) {
		return; // Value unchanged — no log entry
	}
	$pdo->prepare(
		'INSERT INTO case_sheet_audit_log
		    (case_sheet_id, user_id, field_name, old_value, new_value, changed_at)
		 VALUES (?, ?, ?, ?, ?, NOW())'
	)->execute([$caseSheetId, $userId, $field, $old, $new]);
}

/**
 * Merge a single key into a JSON column, log the change, and update updated_at.
 */
function mergeJsonField(PDO $pdo, string $column, string $field, $value, $caseSheetId, int $userId): void
{
	// Read current JSON
	$stmt = $pdo->prepare("SELECT `$column` FROM case_sheets WHERE case_sheet_id = ?");
	$stmt->execute([$caseSheetId]);
	$currentJson = $stmt->fetchColumn();
	$data = ($currentJson && is_string($currentJson)) ? json_decode($currentJson, true) : [];
	if (!is_array($data)) {
		$data = [];
	}

	// Capture old value for audit (null if key never set)
	$oldValue = array_key_exists($field, $data) ? (string)$data[$field] : null;
	$newValue = (string)$value;

	// Merge and save
	$data[$field] = $value;
	$pdo->prepare("UPDATE case_sheets SET `$column` = ?, updated_at = NOW() WHERE case_sheet_id = ?")
	    ->execute([json_encode($data), $caseSheetId]);

	writeAudit($pdo, $caseSheetId, $userId, $field, $oldValue, $newValue);
}

// ── Route the field and write ───────────────────────────────

try {
	if (in_array($field, $direct_columns, true)) {
		// Read old value for audit
		$stmt = $pdo->prepare("SELECT `$field` FROM case_sheets WHERE case_sheet_id = ?");
		$stmt->execute([$caseSheetId]);
		$oldValue = $stmt->fetchColumn();
		$oldValue = ($oldValue !== false) ? (string)$oldValue : null;

		// Write new value
		$pdo->prepare("UPDATE case_sheets SET `$field` = ?, updated_at = NOW() WHERE case_sheet_id = ?")
		    ->execute([$value, $caseSheetId]);

		writeAudit($pdo, $caseSheetId, $userId, $field, $oldValue, (string)$value);

	} elseif (in_array($field, $vitals_fields, true)) {
		mergeJsonField($pdo, 'vitals_json', $field, $value, $caseSheetId, $userId);

	} elseif (in_array($field, $exam_fields, true)) {
		mergeJsonField($pdo, 'exam_notes', $field, $value, $caseSheetId, $userId);

	} elseif (in_array($field, $history_fields, true)) {
		mergeJsonField($pdo, 'assessment', $field, $value, $caseSheetId, $userId);

	} elseif (in_array($field, $lab_fields, true)) {
		mergeJsonField($pdo, 'diagnosis', $field, $value, $caseSheetId, $userId);

	} elseif (in_array($field, $summary_fields, true)) {
		mergeJsonField($pdo, 'plan_notes', $field, $value, $caseSheetId, $userId);

	} else {
		echo json_encode(['success' => false, 'message' => 'Invalid field']);
		exit;
	}

	echo json_encode(['success' => true, 'field' => $field]);

} catch (PDOException $e) {
	error_log('Database error in update_case_sheet.php: ' . $e->getMessage());
	echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}
