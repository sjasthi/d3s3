<?php
/**
 * packet.php — printable patient packet and a WhatsApp handoff.
 * Patients often have no internet login, but a family member has WhatsApp.
 * The packet text is placed in a wa.me message. No third-party API key is stored.
 */

require_once __DIR__ . '/app/config/session.php';
require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/permissions.php';
require_once __DIR__ . '/app/config/record_scope.php';

if (!can($_SESSION['user_role'] ?? '', 'patient_data')) {
	header('Location: dashboard.php');
	exit;
}

$patientId = (int)($_GET['patient_id'] ?? 0);
$pdo = getDBConnection();
$stmt = $pdo->prepare('SELECT * FROM patients WHERE patient_id = ?');
$stmt->execute([$patientId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient || !nurseOwnsPatient($patient)) {
	$_SESSION['patients_error'] = 'Patient record not found.';
	header('Location: patients.php');
	exit;
}

$caseWhere = 'patient_id = ?';
$caseParams = [$patientId];
if (scopeRole() === 'TRIAGE_NURSE') {
	$caseWhere .= ' AND created_by_user_id = ?';
	$caseParams[] = (int)$_SESSION['user_id'];
}
$stmt = $pdo->prepare("SELECT * FROM case_sheets WHERE $caseWhere ORDER BY visit_datetime DESC LIMIT 1");
$stmt->execute($caseParams);
$case = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$name = trim($patient['first_name'] . ' ' . ($patient['last_name'] ?? ''));
$lines = [
	'D3S3 CareSystem patient packet',
	'Patient: ' . $name . ' (' . $patient['patient_code'] . ')',
];
if ($case) {
	$lines[] = 'Visit: ' . ($case['visit_datetime'] ?? '') . ' ' . ($case['visit_type'] ?? '');
	$lines[] = 'Chief complaint: ' . ($case['chief_complaint'] ?? '');
	$lines[] = 'Plan: ' . trim(strip_tags((string)($case['doctor_plan_notes'] ?: $case['plan_notes'] ?? '')));
	$lines[] = 'Medicines: ' . trim((string)($case['prescriptions'] ?? ''));
	$lines[] = 'Advice: ' . trim((string)($case['advice'] ?? ''));
	$lines[] = 'Follow-up date: ' . ($case['follow_up_date'] ?? '');
	$lines[] = 'Referral: ' . trim((string)($case['referral_to'] ?? ''));
} else {
	$lines[] = 'No case sheet is available for this packet yet.';
}
$lines = array_map(static function ($line) {
	return preg_replace('/\s+/', ' ', trim($line));
}, $lines);
$message = implode("\n", array_filter($lines, static fn($l) => $l !== ''));

$digits = preg_replace('/\D+/', '', (string)($patient['phone_e164'] ?? ''));
$waUrl = $digits !== '' ? 'https://wa.me/' . $digits . '?text=' . rawurlencode($message) : '';

require __DIR__ . '/app/views/packet.php';
