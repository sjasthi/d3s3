<?php
/**
 * queue_reorder.php
 * Updates queue_position for a batch of case sheets after a drag-reorder.
 *
 * POST body (JSON): { csrf_token, positions: [{case_sheet_id, position}, ...] }
 *
 * Returns: { success: true }
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

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
	echo json_encode(['success' => false, 'message' => 'Invalid request body']);
	exit;
}

// CSRF check
$token = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
	echo json_encode(['success' => false, 'message' => 'Invalid request token']);
	exit;
}

$positions = $input['positions'] ?? [];
if (!is_array($positions) || empty($positions)) {
	echo json_encode(['success' => false, 'message' => 'No positions provided']);
	exit;
}

require_once __DIR__ . '/app/config/database.php';
$pdo = getDBConnection();

$stmt = $pdo->prepare(
	'UPDATE case_sheets SET queue_position = ? WHERE case_sheet_id = ? AND is_locked = 0'
);

try {
	$pdo->beginTransaction();
	foreach ($positions as $item) {
		$id  = isset($item['case_sheet_id']) ? (int)$item['case_sheet_id'] : 0;
		$pos = isset($item['position'])      ? (float)$item['position']     : 0;
		if ($id <= 0) {
			continue;
		}
		$stmt->execute([$pos, $id]);
	}
	$pdo->commit();
	echo json_encode(['success' => true]);
} catch (PDOException $e) {
	$pdo->rollBack();
	error_log('queue_reorder.php error: ' . $e->getMessage());
	echo json_encode(['success' => false, 'message' => 'Database error']);
}
