<?php
/**
 * case_file.php
 * Upload, play, and view confidential case files.
 *
 *   POST action=upload   kind=ultrasound|voice|consent|lab_image
 *   GET  action=view&id=
 */

require_once __DIR__ . '/app/config/session.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/permissions.php';
require_once __DIR__ . '/app/config/private_storage.php';
require_once __DIR__ . '/app/config/record_scope.php';

$action = $_GET['action'] ?? 'upload';
$pdo = getDBConnection();

if ($action === 'view') {
	viewCaseFile($pdo);
}
uploadCaseFile($pdo);

function caseFileRules(): array
{
	return [
		'ultrasound' => ['ext' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 'max' => 8 * 1024 * 1024, 'locked' => 0],
		'lab_image'  => ['ext' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 'max' => 8 * 1024 * 1024, 'locked' => 0],
		'consent'    => ['ext' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 'max' => 8 * 1024 * 1024, 'locked' => 0],
		'voice'      => ['ext' => ['webm', 'ogg', 'mp3', 'm4a', 'wav'], 'max' => 15 * 1024 * 1024, 'locked' => 1],
	];
}

function viewCaseFile(PDO $pdo): void
{
	$id = (int)($_GET['id'] ?? 0);
	$stmt = $pdo->prepare('SELECT * FROM case_attachments WHERE attachment_id = ?');
	$stmt->execute([$id]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$row || !canViewAttachment($pdo, $row)) {
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

	$caseSheetId = (int)($_POST['case_sheet_id'] ?? 0);
	$patientId = (int)($_POST['patient_id'] ?? 0);
	$isPatient = !empty($_SESSION['patient_id']) && empty($_SESSION['user_id']);

	if ($isPatient) {
		if ($kind !== 'voice') {
			echo json_encode(['success' => false, 'message' => 'Patients can only add a voice recording.']);
			exit;
		}
		$patientId = (int)$_SESSION['patient_id'];
		if ($caseSheetId <= 0) {
			$stmt = $pdo->prepare('SELECT case_sheet_id FROM case_sheets WHERE patient_id = ? ORDER BY visit_datetime DESC LIMIT 1');
			$stmt->execute([$patientId]);
			$caseSheetId = (int)$stmt->fetchColumn();
		}
	} else {
		if (empty($_SESSION['user_id']) || !can($_SESSION['user_role'] ?? '', 'case_sheets')) {
			echo json_encode(['success' => false, 'message' => 'Unauthorized']);
			exit;
		}
		$canWriteCase = can($_SESSION['user_role'] ?? '', 'case_sheets', 'W');
		$canWriteLab = can($_SESSION['user_role'] ?? '', 'labwork', 'W');
		if ($kind === 'lab_image' && !$canWriteCase && !$canWriteLab) {
			echo json_encode(['success' => false, 'message' => 'You cannot add files to this record.']);
			exit;
		}
		if ($kind !== 'lab_image' && !$canWriteCase) {
			echo json_encode(['success' => false, 'message' => 'You cannot add files to this record.']);
			exit;
		}
	}

	if ($caseSheetId > 0) {
		$stmt = $pdo->prepare('SELECT * FROM case_sheets WHERE case_sheet_id = ?');
		$stmt->execute([$caseSheetId]);
		$case = $stmt->fetch(PDO::FETCH_ASSOC);
		if (!$case) {
			echo json_encode(['success' => false, 'message' => 'Case sheet not found.']);
			exit;
		}
		if (!$isPatient && !triageOwnsCase($case)) {
			echo json_encode(['success' => false, 'message' => 'You can only add files to case sheets you created.']);
			exit;
		}
		$patientId = (int)$case['patient_id'];
		if (!$isPatient) {
			$pstmt = $pdo->prepare('SELECT * FROM patients WHERE patient_id = ?');
			$pstmt->execute([$patientId]);
			if (!nurseOwnsPatient($pstmt->fetch(PDO::FETCH_ASSOC))) {
				echo json_encode(['success' => false, 'message' => 'You can only add files to patients you registered.']);
				exit;
			}
		} elseif ((int)$case['patient_id'] !== (int)$_SESSION['patient_id']) {
			echo json_encode(['success' => false, 'message' => 'That case sheet is not yours.']);
			exit;
		}
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
		 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
	)->execute([
		$caseSheetId > 0 ? $caseSheetId : null,
		$patientId,
		$kind,
		$saved['stored_name'],
		$saved['original_name'],
		$saved['mime'],
		$saved['size'],
		$rules[$kind]['locked'],
		$isPatient ? null : (int)$_SESSION['user_id'],
	]);

	echo json_encode([
		'success' => true,
		'attachment_id' => (int)$pdo->lastInsertId(),
		'locked' => (bool)$rules[$kind]['locked'],
	]);
	exit;
}

function canViewAttachment(PDO $pdo, array $row): bool
{
	if (!empty($_SESSION['patient_id']) && (int)$row['patient_id'] === (int)$_SESSION['patient_id']) {
		return true;
	}
	if (empty($_SESSION['user_id'])) {
		return false;
	}
	if (!can($_SESSION['user_role'] ?? '', 'case_sheets') && !can($_SESSION['user_role'] ?? '', 'labwork')) {
		return false;
	}
	$pstmt = $pdo->prepare('SELECT * FROM patients WHERE patient_id = ?');
	$pstmt->execute([(int)$row['patient_id']]);
	if (!nurseOwnsPatient($pstmt->fetch(PDO::FETCH_ASSOC))) {
		return false;
	}
	if (!empty($row['case_sheet_id'])) {
		$cstmt = $pdo->prepare('SELECT * FROM case_sheets WHERE case_sheet_id = ?');
		$cstmt->execute([(int)$row['case_sheet_id']]);
		return triageOwnsCase($cstmt->fetch(PDO::FETCH_ASSOC));
	}
	return true;
}
