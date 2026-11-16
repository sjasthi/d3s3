<?php
/**
 * case_file.php — upload and view private case attachments (Issue #7 ultrasound).
 *
 *   POST action=upload   kind=ultrasound|lab_image
 *   GET  action=view&id=
 */

require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/permissions.php';
require_once __DIR__ . '/app/config/private_storage.php';
require __DIR__ . '/app/middleware/auth.php';

$action = $_GET['action'] ?? 'upload';
$pdo = getDBConnection();

if ($action === 'view') {
	viewCaseFile($pdo);
}
uploadCaseFile($pdo);

function caseFileRules(): array
{
	return [
		'ultrasound' => ['ext' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 'max' => 8 * 1024 * 1024],
		'lab_image'  => ['ext' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 'max' => 8 * 1024 * 1024],
	];
}

function viewCaseFile(PDO $pdo): void
{
	$id = (int)($_GET['id'] ?? 0);
	$stmt = $pdo->prepare('SELECT * FROM case_attachments WHERE attachment_id = ?');
	$stmt->execute([$id]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$row || !canViewAttachment($row)) {
		http_response_code(403);
		echo 'You cannot open this file.';
		exit;
	}
	sendPrivateFile($row['stored_name'], $row['original_name'], $row['mime_type']);
}

function uploadCaseFile(PDO $pdo): void
{
	header('Content-Type: application/json');
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		echo json_encode(['success' => false, 'message' => 'POST required']);
		exit;
	}
	$token = $_POST['csrf_token'] ?? '';
	if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
		echo json_encode(['success' => false, 'message' => 'Invalid request token.']);
		exit;
	}

	$kind = $_POST['kind'] ?? '';
	$rules = caseFileRules();
	if (!isset($rules[$kind])) {
		echo json_encode(['success' => false, 'message' => 'Unknown file type.']);
		exit;
	}

	$role = $_SESSION['user_role'] ?? '';
	$canWriteCase = can($role, 'case_sheets', 'W');
	$canWriteLab = can($role, 'labwork', 'W');
	if ($kind === 'lab_image' && !$canWriteCase && !$canWriteLab) {
		echo json_encode(['success' => false, 'message' => 'You cannot add files to this record.']);
		exit;
	}
	if ($kind !== 'lab_image' && !$canWriteCase) {
		echo json_encode(['success' => false, 'message' => 'You cannot add files to this record.']);
		exit;
	}

	$caseSheetId = (int)($_POST['case_sheet_id'] ?? 0);
	$patientId = (int)($_POST['patient_id'] ?? 0);

	if ($caseSheetId > 0) {
		$stmt = $pdo->prepare('SELECT patient_id FROM case_sheets WHERE case_sheet_id = ?');
		$stmt->execute([$caseSheetId]);
		$casePatient = $stmt->fetchColumn();
		if ($casePatient === false) {
			echo json_encode(['success' => false, 'message' => 'Case sheet not found.']);
			exit;
		}
		$patientId = (int)$casePatient;
	}

	if ($patientId <= 0 || empty($_FILES['file'])) {
		echo json_encode(['success' => false, 'message' => 'A file and a patient are required.']);
		exit;
	}

	try {
		$saved = storePrivateFile($_FILES['file'], 'case-files', $rules[$kind]['ext'], $rules[$kind]['max']);
	} catch (RuntimeException $e) {
		echo json_encode(['success' => false, 'message' => $e->getMessage()]);
		exit;
	}

	$pdo->prepare(
		'INSERT INTO case_attachments
		    (case_sheet_id, patient_id, kind, stored_name, original_name, mime_type, file_size, locked, uploaded_by_user_id)
		 VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)'
	)->execute([
		$caseSheetId > 0 ? $caseSheetId : null,
		$patientId,
		$kind,
		$saved['stored_name'],
		$saved['original_name'],
		$saved['mime'],
		$saved['size'],
		(int)$_SESSION['user_id'],
	]);

	echo json_encode([
		'success' => true,
		'attachment_id' => (int)$pdo->lastInsertId(),
	]);
	exit;
}

function canViewAttachment(array $row): bool
{
	$role = $_SESSION['user_role'] ?? '';
	if (!can($role, 'case_sheets') && !can($role, 'labwork')) {
		return false;
	}
	return true;
}
