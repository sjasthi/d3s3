<?php
/**
 * Human-readable case sheet reference from Issue #5:
 *   patient_code + "_" + sequence  →  e.g. 20260204005_01
 *
 * The database primary key remains case_sheets.case_sheet_id (bigint).
 */

/**
 * @param string $patientCode YYYYMMDDNNN
 * @param int    $sequence    1-based visit order for that patient
 */
function formatCaseSheetCode(string $patientCode, int $sequence): string
{
	$patientCode = trim($patientCode);
	if ($patientCode === '' || $sequence < 1) {
		return $patientCode !== '' ? $patientCode : '';
	}
	return $patientCode . '_' . str_pad((string)$sequence, 2, '0', STR_PAD_LEFT);
}

/**
 * Build a map of case_sheet_id → display code for one patient's sheets.
 * Sequence is chronological by case_sheet_id ascending.
 *
 * @param array<int,array{case_sheet_id:int|string,patient_code?:string}> $rows
 * @return array<int,string>
 */
function caseSheetDisplayCodes(array $rows, string $patientCode = ''): array
{
	$ordered = $rows;
	usort($ordered, static function ($a, $b): int {
		return ((int)($a['case_sheet_id'] ?? 0)) <=> ((int)($b['case_sheet_id'] ?? 0));
	});
	$map = [];
	$seq = 1;
	foreach ($ordered as $row) {
		$id = (int)($row['case_sheet_id'] ?? 0);
		if ($id <= 0) {
			continue;
		}
		$code = $patientCode !== '' ? $patientCode : (string)($row['patient_code'] ?? '');
		$map[$id] = formatCaseSheetCode($code, $seq);
		$seq++;
	}
	return $map;
}
