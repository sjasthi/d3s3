<?php
/**
 * app/controllers/LabResultsController.php
 *
 * Handles the Labwork queue:
 *   index()         – lists all PENDING lab orders for the lab technician
 *   completeOrder() – AJAX POST: marks an order COMPLETED with result notes
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

class LabResultsController
{
	// ── Labwork queue (GET) ──────────────────────────────

	public function index(): void
	{
		$this->requireRead();

		$pdo = getDBConnection();

		// Pending orders – oldest first so the queue is FIFO
		try {
			$stmt = $pdo->prepare(
				'SELECT lo.order_id, lo.test_name, lo.order_notes, lo.ordered_at,
				        lo.case_sheet_id,
				        p.first_name, p.last_name, p.patient_code,
				        u.first_name AS ordered_by_first, u.last_name AS ordered_by_last
				   FROM lab_orders lo
				   JOIN patients p ON p.patient_id  = lo.patient_id
				   JOIN users    u ON u.user_id      = lo.ordered_by_user_id
				  WHERE lo.status = ?
				  ORDER BY lo.ordered_at ASC'
			);
			$stmt->execute(['PENDING']);
			$pendingOrders = $stmt->fetchAll();

			// Completed orders from the last 48 hours (most recent first)
			$stmt = $pdo->prepare(
				'SELECT lo.order_id, lo.test_name, lo.order_notes, lo.result_notes,
				        lo.ordered_at, lo.completed_at, lo.case_sheet_id,
				        p.first_name, p.last_name, p.patient_code,
				        u.first_name  AS ordered_by_first,  u.last_name  AS ordered_by_last,
				        uc.first_name AS completed_by_first, uc.last_name AS completed_by_last
				   FROM lab_orders lo
				   JOIN patients p  ON p.patient_id   = lo.patient_id
				   JOIN users    u  ON u.user_id       = lo.ordered_by_user_id
				   LEFT JOIN users uc ON uc.user_id    = lo.completed_by_user_id
				  WHERE lo.status = ?
				    AND lo.completed_at >= NOW() - INTERVAL 48 HOUR
				  ORDER BY lo.completed_at DESC
				  LIMIT 50'
			);
			$stmt->execute(['COMPLETED']);
			$recentCompleted = $stmt->fetchAll();
		} catch (Exception $e) {
			// lab_orders table not yet created
			$pendingOrders   = [];
			$recentCompleted = [];
		}

		require __DIR__ . '/../views/lab_results.php';
	}

	// ── Complete a lab order (AJAX POST) ─────────────────

	public function completeOrder(): void
	{
		$this->requireWrite();
		header('Content-Type: application/json');

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			echo json_encode(['success' => false, 'message' => 'POST required']);
			exit;
		}

		$input = json_decode(file_get_contents('php://input'), true);

		if (!hash_equals($_SESSION['csrf_token'] ?? '', $input['csrf_token'] ?? '')) {
			echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
			exit;
		}

		$orderId     = (int)($input['order_id'] ?? 0);
		$resultNotes = trim($input['result_notes'] ?? '');

		if ($orderId <= 0) {
			echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
			exit;
		}

		$pdo  = getDBConnection();
		$stmt = $pdo->prepare(
			'UPDATE lab_orders
			    SET status                = ?,
			        completed_by_user_id  = ?,
			        completed_at          = NOW(),
			        result_notes          = ?
			  WHERE order_id = ?
			    AND status   = ?'
		);
		$stmt->execute([
			'COMPLETED',
			$_SESSION['user_id'],
			$resultNotes !== '' ? $resultNotes : null,
			$orderId,
			'PENDING',
		]);

		if ($stmt->rowCount() === 0) {
			echo json_encode(['success' => false, 'message' => 'Order not found or already completed.']);
			exit;
		}

		echo json_encode(['success' => true]);
		exit;
	}

	// ── Role guards ──────────────────────────────────────

	private function requireRead(): void
	{
		if (!can($_SESSION['user_role'] ?? '', 'labwork')) {
			$_SESSION['dashboard_notice'] = 'You do not have permission to access Labwork.';
			header('Location: dashboard.php');
			exit;
		}
	}

	private function requireWrite(): void
	{
		if (!can($_SESSION['user_role'] ?? '', 'labwork', 'W')) {
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Permission denied.']);
			exit;
		}
	}
}
