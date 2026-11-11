<?php
/**
 * app/controllers/FeedbackController.php
 *
 * Handles feedback / grievance submissions and management.
 *
 * Access matrix:
 *   RW  – SUPER_ADMIN, ADMIN, GRIEVANCE_OFFICER  (can submit + manage)
 *   R   – DOCTOR, TRIAGE_NURSE, NURSE, PARAMEDIC, EDUCATION_TEAM (read-only)
 *   N   – DATA_ENTRY_OPERATOR (no access)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

class FeedbackController
{
	public function index(): void
	{
		$this->requireRead();

		$action = $_GET['action'] ?? 'list';

		if ($action === 'view' && isset($_GET['id'])) {
			$this->view((int)$_GET['id']);
		} elseif ($action === 'submit') {
			$this->submit();
		} else {
			$this->listing();
		}
	}

	// ── List ─────────────────────────────────────────────────

	private function listing(): void
	{
		$pdo  = getDBConnection();
		$role = $_SESSION['user_role'] ?? '';

		$flashSuccess = null;
		if (isset($_SESSION['feedback_success'])) {
			$flashSuccess = $_SESSION['feedback_success'];
			unset($_SESSION['feedback_success']);
		}

		// GRIEVANCE_OFFICER / ADMIN / SUPER_ADMIN see all; others see only own
		if (can($role, 'feedback', 'W')) {
			$stmt = $pdo->prepare(
				'SELECT f.*,
				        u.first_name AS submitter_first, u.last_name AS submitter_last
				   FROM feedback f
				   LEFT JOIN users u ON f.submitted_by_user_id = u.user_id
				  ORDER BY f.created_at DESC'
			);
			$stmt->execute();
		} else {
			$stmt = $pdo->prepare(
				'SELECT f.*,
				        u.first_name AS submitter_first, u.last_name AS submitter_last
				   FROM feedback f
				   LEFT JOIN users u ON f.submitted_by_user_id = u.user_id
				  WHERE f.submitted_by_user_id = ?
				  ORDER BY f.created_at DESC'
			);
			$stmt->execute([$_SESSION['user_id']]);
		}

		$feedbackList = $stmt->fetchAll();
		$canWrite     = can($role, 'feedback', 'W');

		require __DIR__ . '/../views/feedback.php';
	}

	// ── Detail / View ────────────────────────────────────────

	private function view(int $id): void
	{
		$pdo  = getDBConnection();
		$role = $_SESSION['user_role'] ?? '';

		$stmt = $pdo->prepare(
			'SELECT f.*,
			        u.first_name AS submitter_first, u.last_name AS submitter_last,
			        a.first_name AS assignee_first, a.last_name AS assignee_last
			   FROM feedback f
			   LEFT JOIN users u ON f.submitted_by_user_id = u.user_id
			   LEFT JOIN users a ON f.assigned_to_user_id  = a.user_id
			  WHERE f.feedback_id = ?'
		);
		$stmt->execute([$id]);
		$feedback = $stmt->fetch();

		if (!$feedback) {
			$_SESSION['feedback_error'] = 'Feedback record not found.';
			header('Location: feedback.php');
			exit;
		}

		// R-only users may only view their own submissions
		if (!can($role, 'feedback', 'W')
			&& (int)$feedback['submitted_by_user_id'] !== (int)$_SESSION['user_id']
		) {
			$_SESSION['feedback_error'] = 'You do not have permission to view that record.';
			header('Location: feedback.php');
			exit;
		}

		$flashError = null;
		if (isset($_SESSION['feedback_error'])) {
			$flashError = $_SESSION['feedback_error'];
			unset($_SESSION['feedback_error']);
		}

		$flashSuccess = null;
		if (isset($_SESSION['feedback_success'])) {
			$flashSuccess = $_SESSION['feedback_success'];
			unset($_SESSION['feedback_success']);
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
			$this->requireWrite();
			if ($_POST['action'] === 'update_status') {
				$error = $this->processUpdateStatus($id);
				if ($error !== null) {
					$flashError = $error;
				} else {
					$_SESSION['feedback_success'] = 'Status updated successfully.';
					header("Location: feedback.php?action=view&id={$id}");
					exit;
				}
			}
		}

		// Fetch grief officers for assignment dropdown (W roles only)
		$grievanceUsers = [];
		if (can($role, 'feedback', 'W')) {
			$stmt = $pdo->prepare(
				"SELECT user_id, first_name, last_name
				   FROM users
				  WHERE is_active = 1
				    AND role IN ('SUPER_ADMIN','ADMIN','GRIEVANCE_OFFICER')
				  ORDER BY last_name, first_name"
			);
			$stmt->execute();
			$grievanceUsers = $stmt->fetchAll();
		}

		$canWrite = can($role, 'feedback', 'W');

		require __DIR__ . '/../views/feedback_detail.php';
	}

	// ── Submit ───────────────────────────────────────────────

	private function submit(): void
	{
		$this->requireWrite();

		$formError = null;

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$formError = $this->processCreate();
			// on success processCreate() redirects
		}

		require __DIR__ . '/../views/feedback_submit.php';
	}

	// ── Processors ──────────────────────────────────────────

	private function processCreate(): ?string
	{
		if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
			return 'Invalid request token.';
		}

		$subject     = trim($_POST['subject'] ?? '');
		$description = trim($_POST['description'] ?? '');

		if ($subject === '') {
			return 'Subject is required.';
		}
		if ($description === '') {
			return 'Description is required.';
		}
		if (strlen($subject) > 200) {
			return 'Subject may not exceed 200 characters.';
		}
		if (strlen($description) > 5000) {
			return 'Description may not exceed 5,000 characters.';
		}

		$pdo  = getDBConnection();
		$stmt = $pdo->prepare(
			'INSERT INTO feedback (subject, description, submitted_by_user_id)
			 VALUES (?, ?, ?)'
		);
		$stmt->execute([$subject, $description, $_SESSION['user_id']]);
		$newId = (int)$pdo->lastInsertId();

		$_SESSION['feedback_success'] = 'Feedback submitted successfully.';
		header("Location: feedback.php?action=view&id={$newId}");
		exit;
	}

	private function processUpdateStatus(int $id): ?string
	{
		if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
			return 'Invalid request token.';
		}

		$validStatuses = ['OPEN', 'UNDER_REVIEW', 'RESOLVED', 'CLOSED'];
		$status = $_POST['status'] ?? '';
		if (!in_array($status, $validStatuses, true)) {
			return 'Invalid status value.';
		}

		$assignedTo = !empty($_POST['assigned_to_user_id'])
			? (int)$_POST['assigned_to_user_id']
			: null;

		$pdo  = getDBConnection();
		$stmt = $pdo->prepare(
			'UPDATE feedback SET status = ?, assigned_to_user_id = ? WHERE feedback_id = ?'
		);
		$stmt->execute([$status, $assignedTo, $id]);
		return null;
	}

	// ── Guards ───────────────────────────────────────────────

	private function requireRead(): void
	{
		if (!can($_SESSION['user_role'] ?? '', 'feedback')) {
			$_SESSION['dashboard_notice'] = 'You do not have permission to access this page.';
			header('Location: dashboard.php');
			exit;
		}
	}

	private function requireWrite(): void
	{
		if (!can($_SESSION['user_role'] ?? '', 'feedback', 'W')) {
			$_SESSION['dashboard_notice'] = 'You do not have permission to perform this action.';
			header('Location: dashboard.php');
			exit;
		}
	}
}
