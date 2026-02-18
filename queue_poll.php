<?php
/**
 * queue_poll.php
 * Returns the current patient queue as JSON.
 *
 * Role access:
 *   DOCTOR        → sees INTAKE_COMPLETE (ready for doctor)
 *   NURSE / TRIAGE_NURSE → sees INTAKE_IN_PROGRESS + INTAKE_COMPLETE
 *   Any clinical  → all active, non-closed cases for today
 *
 * Response: { rows: [...], updated_at: <unix ts> }
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

$role = $_SESSION['user_role'] ?? '';
$allowedRoles = ['DOCTOR', 'TRIAGE_NURSE', 'NURSE'];
if (!in_array($role, $allowedRoles, true)) {
	echo json_encode(['success' => false, 'message' => 'Forbidden']);
	exit;
}

require_once __DIR__ . '/app/config/database.php';
$pdo = getDBConnection();

// Determine which statuses to show per role
if ($role === 'DOCTOR') {
	$statuses = ['INTAKE_COMPLETE'];
} else {
	// NURSE, TRIAGE_NURSE: see full queue including in-progress
	$statuses = ['INTAKE_IN_PROGRESS', 'INTAKE_COMPLETE'];
}

$placeholders = implode(',', array_fill(0, count($statuses), '?'));

$stmt = $pdo->prepare(
	"SELECT cs.case_sheet_id,
	        cs.patient_id,
	        cs.status,
	        cs.visit_type,
	        cs.chief_complaint,
	        cs.visit_datetime,
	        cs.queue_position,
	        p.first_name,
	        p.last_name,
	        p.patient_code,
	        p.sex,
	        p.age_years,
	        u.first_name  AS intake_first,
	        u.last_name   AS intake_last,
	        ad.first_name AS doctor_first,
	        ad.last_name  AS doctor_last
	   FROM case_sheets cs
	   JOIN patients  p  ON p.patient_id  = cs.patient_id
	   LEFT JOIN users u  ON u.user_id    = cs.created_by_user_id
	   LEFT JOIN users ad ON ad.user_id   = cs.assigned_doctor_user_id
	  WHERE cs.status IN ($placeholders)
	    AND DATE(cs.visit_datetime) = CURDATE()
	  ORDER BY COALESCE(cs.queue_position, 999999) ASC, cs.visit_datetime ASC"
);
$stmt->execute($statuses);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format for JSON
$out = [];
foreach ($rows as $r) {
	$out[] = [
		'case_sheet_id'  => (int)$r['case_sheet_id'],
		'patient_id'     => (int)$r['patient_id'],
		'status'         => $r['status'],
		'visit_type'     => $r['visit_type'],
		'chief_complaint'=> $r['chief_complaint'] ?? '',
		'visit_time'     => date('g:i A', strtotime($r['visit_datetime'])),
		'queue_position' => $r['queue_position'] !== null ? (float)$r['queue_position'] : null,
		'patient_name'   => trim(htmlspecialchars($r['first_name'] . ' ' . ($r['last_name'] ?? ''))),
		'patient_code'   => $r['patient_code'],
		'sex'            => ($r['sex'] && $r['sex'] !== 'UNKNOWN') ? $r['sex'] : '',
		'age_years'      => $r['age_years'] ? (int)$r['age_years'] : null,
		'intake_by'      => trim(($r['intake_first'] ?? '') . ' ' . ($r['intake_last'] ?? '')),
		'doctor_name'    => $r['doctor_first'] ? trim($r['doctor_first'] . ' ' . $r['doctor_last']) : null,
	];
}

echo json_encode(['success' => true, 'rows' => $out, 'updated_at' => time()]);
