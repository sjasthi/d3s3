<?php
/**
 * case_export.php — download case sheets as an Excel-compatible spreadsheet.
 */

require_once __DIR__ . '/app/config/session.php';
require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/permissions.php';
require_once __DIR__ . '/app/config/record_scope.php';

if (!can($_SESSION['user_role'] ?? '', 'case_sheets')) {
	$_SESSION['dashboard_notice'] = 'You do not have permission to export case sheets.';
	header('Location: dashboard.php');
	exit;
}

$pdo = getDBConnection();
$role = scopeRole();
$where = '1=1';
$params = [];
if ($role === 'NURSE') {
	$where .= ' AND p.created_by_user_id = ?';
	$params[] = (int)$_SESSION['user_id'];
}
if ($role === 'TRIAGE_NURSE') {
	$where .= ' AND cs.created_by_user_id = ?';
	$params[] = (int)$_SESSION['user_id'];
}
if ($role === 'DOCTOR') {
	$where .= ' AND cs.assigned_doctor_user_id = ?';
	$params[] = (int)$_SESSION['user_id'];
}

$stmt = $pdo->prepare(
	"SELECT p.patient_code, p.first_name, p.last_name, p.sex, p.age_years, p.phone_e164,
	        cs.case_sheet_id, cs.visit_datetime, cs.visit_type, cs.status, cs.chief_complaint,
	        cs.diagnosis, cs.prescriptions, cs.advice, cs.follow_up_date, cs.referral_to,
	        cs.closure_type, cs.prior_case_sheet_id
	   FROM case_sheets cs
	   JOIN patients p ON p.patient_id = cs.patient_id
	  WHERE $where
	  ORDER BY cs.visit_datetime DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (($_GET['format'] ?? '') === 'xlsx') {
	$headers = $rows ? array_keys($rows[0]) : ['patient_code'];
	header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
	header('Content-Disposition: attachment; filename="case-sheets.xls"');
	echo "\xEF\xBB\xBF";
	$out = fopen('php://output', 'w');
	fputcsv($out, $headers);
	foreach ($rows as $row) {
		fputcsv($out, array_map(static function ($v) {
			return is_string($v) ? preg_replace('/\s+/', ' ', $v) : $v;
		}, $row));
	}
	fclose($out);
	exit;
}

$pageRows = $rows;
require __DIR__ . '/app/views/case_export.php';
