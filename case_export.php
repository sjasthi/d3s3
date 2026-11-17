<?php
/**
 * case_export.php — searchable case-sheet table + Excel-compatible download.
 *
 * Issue #8: stakeholders export case sheets (jQuery DataTables + .xls).
 * Role scoping:
 *   TRIAGE_NURSE / NURSE — case sheets they created
 *   DOCTOR — case sheets assigned to them
 *   Other roles with case_sheets read — all rows
 *
 * Download respects the DataTables filter by accepting filtered case_sheet
 * ids (and/or a search string). Ids are always intersected with the
 * role-scoped result set so clients cannot widen access.
 */

require_once __DIR__ . '/app/config/session.php';
require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/permissions.php';

$role = (string)($_SESSION['user_role'] ?? '');
if (!can($role, 'case_sheets')) {
	$_SESSION['dashboard_notice'] = 'You do not have permission to export case sheets.';
	header('Location: dashboard.php');
	exit;
}

$pdo = getDBConnection();
$userId = (int)($_SESSION['user_id'] ?? 0);
$isDownload = (($_GET['format'] ?? '') === 'xlsx');

$where = '1=1';
$params = [];
if ($role === 'NURSE' || $role === 'TRIAGE_NURSE') {
	$where .= ' AND cs.created_by_user_id = ?';
	$params[] = $userId;
} elseif ($role === 'DOCTOR') {
	$where .= ' AND cs.assigned_doctor_user_id = ?';
	$params[] = $userId;
}

$stmt = $pdo->prepare(
	"SELECT p.patient_code, p.first_name, p.last_name, p.sex, p.age_years, p.phone_e164,
	        p.address_line1, p.city, p.state_province, p.postal_code,
	        cs.case_sheet_id, cs.visit_datetime, cs.visit_type, cs.status, cs.chief_complaint,
	        cs.history_present_illness, cs.vitals_json, cs.assessment, cs.diagnosis,
	        cs.exam_notes, cs.prescriptions, cs.advice, cs.follow_up_date, cs.referral_to,
	        cs.closure_type, cs.created_by_name, cs.assigned_doctor_name
	   FROM case_sheets cs
	   JOIN patients p ON p.patient_id = cs.patient_id
	  WHERE $where
	  ORDER BY cs.visit_datetime DESC"
);
$stmt->execute($params);
$rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Pull a vital/lab value with legacy alias support.
 */
function exportVital(array $vitals, array $keys): string
{
	foreach ($keys as $key) {
		if (isset($vitals[$key]) && $vitals[$key] !== '' && $vitals[$key] !== null) {
			return (string)$vitals[$key];
		}
	}
	return '';
}

function exportFlatten($value): string
{
	if ($value === null) {
		return '';
	}
	if (is_array($value) || is_object($value)) {
		$value = json_encode($value, JSON_UNESCAPED_UNICODE);
	}
	return preg_replace('/\s+/', ' ', trim((string)$value));
}

/**
 * Force Excel to treat the cell as text (patient codes, phones, etc.).
 */
function exportAsExcelText($value): string
{
	$text = trim((string)$value);
	if ($text === '') {
		return '';
	}
	return '="' . str_replace('"', '""', $text) . '"';
}

function exportRowMatchesSearch(array $row, string $needle): bool
{
	$needle = mb_strtolower($needle);
	$haystacks = [
		$row['patient_code'] ?? '',
		trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
		(string)($row['age_years'] ?? ''),
		$row['phone_e164'] ?? '',
		$row['address_line1'] ?? '',
		$row['city'] ?? '',
		$row['visit_type'] ?? '',
		$row['status'] ?? '',
		$row['chief_complaint'] ?? '',
		$row['history_present_illness'] ?? '',
		$row['advice'] ?? '',
		$row['referral_to'] ?? '',
		$row['created_by_name'] ?? '',
		$row['assigned_doctor_name'] ?? '',
		$row['vitals_json'] ?? '',
		$row['diagnosis'] ?? '',
		$row['assessment'] ?? '',
		$row['exam_notes'] ?? '',
		$row['prescriptions'] ?? '',
	];
	foreach ($haystacks as $hay) {
		if ($hay !== '' && mb_stripos((string)$hay, $needle) !== false) {
			return true;
		}
	}
	return false;
}

// Apply the table filter only on download. Ids come from DataTables
// "search applied" rows; always intersect with the role-scoped set.
$searchQuery = trim((string)($_GET['q'] ?? ''));
if ($isDownload) {
	$allowedIds = [];
	foreach ($rawRows as $row) {
		$allowedIds[(int)$row['case_sheet_id']] = true;
	}

	if (isset($_GET['ids']) && (string)$_GET['ids'] !== '') {
		$requested = array_filter(array_map('intval', explode(',', (string)$_GET['ids'])));
		$keep = [];
		foreach ($requested as $id) {
			if (isset($allowedIds[$id])) {
				$keep[$id] = true;
			}
		}
		$rawRows = array_values(array_filter(
			$rawRows,
			static function (array $row) use ($keep): bool {
				return isset($keep[(int)$row['case_sheet_id']]);
			}
		));
	} elseif ($searchQuery !== '') {
		$rawRows = array_values(array_filter(
			$rawRows,
			static function (array $row) use ($searchQuery): bool {
				return exportRowMatchesSearch($row, $searchQuery);
			}
		));
	}
}

$exportRows = [];
$serial = 1;
foreach ($rawRows as $row) {
	$vitals = [];
	if (!empty($row['vitals_json'])) {
		$decoded = json_decode($row['vitals_json'], true);
		if (is_array($decoded)) {
			$vitals = $decoded;
		}
	}
	$labs = [];
	if (!empty($row['diagnosis'])) {
		$decoded = json_decode($row['diagnosis'], true);
		if (is_array($decoded)) {
			$labs = $decoded;
		}
	}

	$height = exportVital($vitals, ['general_height', 'height_cm', 'height']);
	$weight = exportVital($vitals, ['general_weight', 'weight_kg', 'weight']);
	$bmi = exportVital($vitals, ['general_bmi', 'bmi']);
	$bpSys = exportVital($vitals, ['general_bp_systolic', 'bp_systolic']);
	$bpDia = exportVital($vitals, ['general_bp_diastolic', 'bp_diastolic']);
	$bp = ($bpSys !== '' || $bpDia !== '') ? trim($bpSys . '/' . $bpDia, '/') : '';

	$hb = exportVital($labs, ['lab_hb_gms', 'lab_hb_percentage', 'hb']);
	$fbs = exportVital($labs, ['lab_fbs', 'fbs']);
	$creat = exportVital($labs, ['lab_sr_creatinine', 'sr_creatinine']);
	$tsh = exportVital($labs, ['lab_tsh', 'tsh']);

	$address = trim(implode(', ', array_filter([
		$row['address_line1'] ?? '',
		$row['city'] ?? '',
		$row['state_province'] ?? '',
		$row['postal_code'] ?? '',
	])));

	$patientCode = (string)($row['patient_code'] ?? '');
	$phone = (string)($row['phone_e164'] ?? '');

	$exportRows[] = [
		'_case_sheet_id' => (int)$row['case_sheet_id'],
		'S No' => $serial++,
		'Patient code' => $isDownload ? exportAsExcelText($patientCode) : $patientCode,
		'Full Name' => trim($row['first_name'] . ' ' . ($row['last_name'] ?? '')),
		'Age' => $row['age_years'] ?? '',
		'Sex' => $row['sex'] ?? '',
		'Ph No' => $isDownload ? exportAsExcelText($phone) : $phone,
		'Address' => $isDownload ? exportAsExcelText($address) : $address,
		'Visit date' => $row['visit_datetime'] ? date('Y-m-d', strtotime($row['visit_datetime'])) : '',
		'Visit type' => $row['visit_type'] ?? '',
		'Status' => $row['status'] ?? '',
		'Complaints' => $row['chief_complaint'] ?? '',
		'History' => $row['history_present_illness'] ?? '',
		'Height (cms)' => $height,
		'Weight (Kgs)' => $weight,
		'BMI' => $bmi,
		'BP' => $isDownload ? exportAsExcelText($bp) : $bp,
		'Hb %' => $hb,
		'Sugar (FBS)' => $fbs,
		'Sr. Creatinine' => $creat,
		'TSH Value' => $tsh,
		'Assessment' => exportFlatten($row['assessment'] ?? ''),
		'Diagnosis' => is_array($labs) && $labs !== [] ? exportFlatten($labs) : exportFlatten($row['diagnosis'] ?? ''),
		'Exam notes' => exportFlatten($row['exam_notes'] ?? ''),
		'Prescriptions' => exportFlatten($row['prescriptions'] ?? ''),
		'Advice' => exportFlatten($row['advice'] ?? ''),
		'Follow-up' => $row['follow_up_date'] ?? '',
		'Referral' => $row['referral_to'] ?? '',
		'Closure' => $row['closure_type'] ?? '',
		'Created by' => $row['created_by_name'] ?? '',
		'Doctor' => $row['assigned_doctor_name'] ?? '',
	];
}

if ($isDownload) {
	$headers = ['S No', 'Patient code', 'Full Name', 'Age', 'Sex', 'Ph No', 'Address',
		'Visit date', 'Visit type', 'Status', 'Complaints', 'History', 'Height (cms)',
		'Weight (Kgs)', 'BMI', 'BP', 'Hb %', 'Sugar (FBS)', 'Sr. Creatinine', 'TSH Value',
		'Assessment', 'Diagnosis', 'Exam notes', 'Prescriptions', 'Advice', 'Follow-up',
		'Referral', 'Closure', 'Created by', 'Doctor'];
	header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
	header('Content-Disposition: attachment; filename="case-sheets.xls"');
	echo "\xEF\xBB\xBF";
	$out = fopen('php://output', 'w');
	fputcsv($out, $headers);
	foreach ($exportRows as $row) {
		$line = [];
		foreach ($headers as $header) {
			$line[] = $row[$header] ?? '';
		}
		fputcsv($out, $line);
	}
	fclose($out);
	exit;
}

$pageRows = $exportRows;
require __DIR__ . '/app/views/case_export.php';
