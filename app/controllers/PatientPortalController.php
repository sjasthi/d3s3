<?php
/**
 * app/controllers/PatientPortalController.php
 *
 * Handles all patient-portal actions:
 *   Patient-facing (requires patient session):
 *     login / processLogin / logout
 *     dashboard / appointments / healthRecord / labResults
 *     messages / processSendMessage
 *     feedback / processSubmitFeedback
 *     profile / processUpdateProfile
 *
 *   Staff-facing (requires staff session + patient_data read):
 *     staffMessages / processStaffReply
 *     processCreateAccount (POST, called from patients.php)
 *     processResetPassword (POST, called from patients.php)
 *     processToggleActive  (POST, called from patients.php)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

class PatientPortalController
{
	// ── Helpers ──────────────────────────────────────────────────────────────

	private function requirePatientAuth(): void
	{
		if (empty($_SESSION['patient_account_id']) || empty($_SESSION['patient_id'])) {
			header('Location: patient_login.php');
			exit;
		}
	}

	private function requireStaffAuth(string $resource = 'patient_data', string $action = 'R'): void
	{
		if (empty($_SESSION['user_id'])) {
			header('Location: login.php');
			exit;
		}
		if (!can($_SESSION['user_role'] ?? '', $resource, $action)) {
			header('Location: dashboard.php');
			exit;
		}
	}

	private function csrf(): string
	{
		return $_SESSION['csrf_token'] ?? '';
	}

	private function checkCsrf(): void
	{
		if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
			http_response_code(403);
			exit('Invalid CSRF token.');
		}
	}

	// ── Patient login ─────────────────────────────────────────────────────────

	public function login(): void
	{
		// Already authenticated patients go straight to portal
		if (!empty($_SESSION['patient_account_id'])) {
			header('Location: patient_portal.php');
			exit;
		}

		$loginError   = null;
		$loginSuccess = null;

		if (isset($_SESSION['portal_login_success'])) {
			$loginSuccess = $_SESSION['portal_login_success'];
			unset($_SESSION['portal_login_success']);
		}

		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$loginError = $this->processLogin();
		}

		require __DIR__ . '/../views/portal/login.php';
	}

	private function processLogin(): ?string
	{
		if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
			return 'Invalid request. Please try again.';
		}

		$identifier = trim($_POST['identifier'] ?? '');
		$password   = $_POST['password'] ?? '';

		if ($identifier === '' || $password === '') {
			return 'Email/username and password are required.';
		}

		$pdo  = getDBConnection();
		$stmt = $pdo->prepare(
			'SELECT pa.*, p.first_name, p.last_name, p.patient_id
			   FROM patient_accounts pa
			   JOIN patients p ON p.patient_id = pa.patient_id
			  WHERE (pa.email = ? OR pa.username = ?)
			  LIMIT 1'
		);
		$stmt->execute([$identifier, $identifier]);
		$account = $stmt->fetch();

		if (!$account || !password_verify($password, $account['password_hash'])) {
			return 'Incorrect email/username or password.';
		}

		if (!$account['is_active']) {
			return 'This account has been deactivated. Please contact the clinic.';
		}

		session_regenerate_id(true);
		$_SESSION['patient_account_id'] = (int)$account['patient_account_id'];
		$_SESSION['patient_id']         = (int)$account['patient_id'];
		$_SESSION['patient_name']       = trim($account['first_name'] . ' ' . ($account['last_name'] ?? ''));

		$pdo->prepare(
			'UPDATE patient_accounts SET last_login_at = NOW() WHERE patient_account_id = ?'
		)->execute([$account['patient_account_id']]);

		header('Location: patient_portal.php');
		exit;
	}

	public function logout(): void
	{
		unset(
			$_SESSION['patient_account_id'],
			$_SESSION['patient_id'],
			$_SESSION['patient_name']
		);
		$_SESSION['portal_login_success'] = 'You have been signed out.';
		header('Location: patient_login.php');
		exit;
	}

	// ── Dashboard ─────────────────────────────────────────────────────────────

	public function dashboard(): void
	{
		$this->requirePatientAuth();
		$pdo       = getDBConnection();
		$patientId = (int)$_SESSION['patient_id'];
		$accountId = (int)$_SESSION['patient_account_id'];

		// Next upcoming appointment
		$nextAppt = null;
		try {
			$stmt = $pdo->prepare(
				'SELECT a.scheduled_date, a.scheduled_time, a.visit_mode, a.status,
				        u.first_name AS doctor_first, u.last_name AS doctor_last
				   FROM appointments a
				   JOIN case_sheets cs ON cs.case_sheet_id = a.case_sheet_id
				   LEFT JOIN users u   ON u.user_id = a.doctor_user_id
				  WHERE cs.patient_id = ?
				    AND a.status NOT IN (\'CANCELLED\',\'NO_SHOW\',\'COMPLETED\')
				    AND a.scheduled_date >= CURDATE()
				  ORDER BY a.scheduled_date ASC, a.scheduled_time ASC
				  LIMIT 1'
			);
			$stmt->execute([$patientId]);
			$nextAppt = $stmt->fetch();
		} catch (Throwable $e) { /* appointments table may not exist */ }

		// Unread messages from staff
		$unreadMessages = 0;
		try {
			$stmt = $pdo->prepare(
				'SELECT COUNT(*) FROM portal_message_threads
				  WHERE patient_account_id = ? AND patient_unread = 1'
			);
			$stmt->execute([$accountId]);
			$unreadMessages = (int)$stmt->fetchColumn();
		} catch (Throwable $e) {}

		// Recent lab results
		$recentLabs = [];
		try {
			$stmt = $pdo->prepare(
				'SELECT lo.test_name, lo.status, lo.completed_at, lo.result_notes
				   FROM lab_orders lo
				  WHERE lo.patient_id = ? AND lo.status = \'COMPLETED\'
				  ORDER BY lo.completed_at DESC
				  LIMIT 3'
			);
			$stmt->execute([$patientId]);
			$recentLabs = $stmt->fetchAll();
		} catch (Throwable $e) {}

		// Recent feedback submissions
		$recentFeedback = [];
		try {
			$stmt = $pdo->prepare(
				'SELECT portal_feedback_id, feedback_type, subject, status, created_at
				   FROM portal_feedback
				  WHERE patient_account_id = ?
				  ORDER BY created_at DESC
				  LIMIT 3'
			);
			$stmt->execute([$accountId]);
			$recentFeedback = $stmt->fetchAll();
		} catch (Throwable $e) {}

		// Unread sent resources (patient_assets not yet read)
		$newResources = 0;
		try {
			$stmt = $pdo->prepare(
				'SELECT COUNT(*) FROM patient_assets WHERE patient_id = ? AND is_read = 0'
			);
			$stmt->execute([$patientId]);
			$newResources = (int)$stmt->fetchColumn();
		} catch (Throwable $e) {}

		require __DIR__ . '/../views/portal/dashboard.php';
	}

	// ── Appointments ──────────────────────────────────────────────────────────

	public function appointments(): void
	{
		$this->requirePatientAuth();
		$pdo       = getDBConnection();
		$patientId = (int)$_SESSION['patient_id'];

		$appointments = [];
		try {
			$stmt = $pdo->prepare(
				'SELECT a.appointment_id, a.scheduled_date, a.scheduled_time,
				        a.visit_mode, a.status, a.notes,
				        u.first_name AS doctor_first, u.last_name AS doctor_last,
				        cs.chief_complaint, cs.visit_type
				   FROM appointments a
				   JOIN case_sheets cs ON cs.case_sheet_id = a.case_sheet_id
				   LEFT JOIN users u   ON u.user_id = a.doctor_user_id
				  WHERE cs.patient_id = ?
				  ORDER BY a.scheduled_date DESC, a.scheduled_time DESC'
			);
			$stmt->execute([$patientId]);
			$appointments = $stmt->fetchAll();
		} catch (Throwable $e) {}

		require __DIR__ . '/../views/portal/appointments.php';
	}

	// ── Health Record ─────────────────────────────────────────────────────────

	public function healthRecord(): void
	{
		$this->requirePatientAuth();
		$pdo       = getDBConnection();
		$patientId = (int)$_SESSION['patient_id'];

		$patient = null;
		$stmt = $pdo->prepare('SELECT * FROM patients WHERE patient_id = ?');
		$stmt->execute([$patientId]);
		$patient = $stmt->fetch();

		$caseSheets = [];
		try {
			$stmt = $pdo->prepare(
				'SELECT cs.case_sheet_id, cs.visit_datetime, cs.visit_type, cs.status,
				        cs.chief_complaint, cs.assessment, cs.diagnosis, cs.plan_notes,
				        cs.prescriptions, cs.advice, cs.follow_up_date,
				        cs.doctor_diagnosis, cs.doctor_plan_notes,
				        u.first_name AS doctor_first, u.last_name AS doctor_last
				   FROM case_sheets cs
				   LEFT JOIN users u ON u.user_id = cs.assigned_doctor_user_id
				  WHERE cs.patient_id = ?
				    AND cs.status = \'CLOSED\'
				  ORDER BY cs.visit_datetime DESC'
			);
			$stmt->execute([$patientId]);
			$caseSheets = $stmt->fetchAll();
		} catch (Throwable $e) {}

		require __DIR__ . '/../views/portal/health_record.php';
	}

	// ── Lab Results ───────────────────────────────────────────────────────────

	public function labResults(): void
	{
		$this->requirePatientAuth();
		$pdo       = getDBConnection();
		$patientId = (int)$_SESSION['patient_id'];

		$labOrders = [];
		try {
			$stmt = $pdo->prepare(
				'SELECT lo.order_id, lo.test_name, lo.order_notes,
				        lo.status, lo.ordered_at, lo.completed_at, lo.result_notes,
				        cs.visit_datetime, cs.chief_complaint
				   FROM lab_orders lo
				   JOIN case_sheets cs ON cs.case_sheet_id = lo.case_sheet_id
				  WHERE lo.patient_id = ?
				  ORDER BY lo.ordered_at DESC'
			);
			$stmt->execute([$patientId]);
			$labOrders = $stmt->fetchAll();
		} catch (Throwable $e) {}

		require __DIR__ . '/../views/portal/lab_results.php';
	}

	// ── Messages ──────────────────────────────────────────────────────────────

	public function messages(): void
	{
		$this->requirePatientAuth();
		$pdo       = getDBConnection();
		$accountId = (int)$_SESSION['patient_account_id'];

		$flashSuccess = null;
		$flashError   = null;
		if (isset($_SESSION['portal_msg_success'])) {
			$flashSuccess = $_SESSION['portal_msg_success'];
			unset($_SESSION['portal_msg_success']);
		}
		if (isset($_SESSION['portal_msg_error'])) {
			$flashError = $_SESSION['portal_msg_error'];
			unset($_SESSION['portal_msg_error']);
		}

		// Active thread for inline view
		$activeThreadId = (int)($_GET['thread'] ?? 0);
		$activeThread   = null;
		$threadMessages = [];

		$threads = [];
		try {
			$stmt = $pdo->prepare(
				'SELECT thread_id, subject, last_message_at, patient_unread
				   FROM portal_message_threads
				  WHERE patient_account_id = ?
				  ORDER BY last_message_at DESC'
			);
			$stmt->execute([$accountId]);
			$threads = $stmt->fetchAll();

			if ($activeThreadId > 0) {
				// Verify thread belongs to this patient
				foreach ($threads as $t) {
					if ((int)$t['thread_id'] === $activeThreadId) {
						$activeThread = $t;
						break;
					}
				}

				if ($activeThread) {
					$stmt = $pdo->prepare(
						'SELECT pm.portal_message_id, pm.sender_type, pm.body, pm.sent_at,
						        u.first_name AS staff_first, u.last_name AS staff_last
						   FROM portal_messages pm
						   LEFT JOIN users u ON u.user_id = pm.sender_user_id
						  WHERE pm.thread_id = ?
						  ORDER BY pm.sent_at ASC'
					);
					$stmt->execute([$activeThreadId]);
					$threadMessages = $stmt->fetchAll();

					// Mark patient_unread = 0 now that they've viewed it
					$pdo->prepare(
						'UPDATE portal_message_threads SET patient_unread = 0 WHERE thread_id = ?'
					)->execute([$activeThreadId]);
				}
			}
		} catch (Throwable $e) {}

		require __DIR__ . '/../views/portal/messages.php';
	}

	public function processSendMessage(): void
	{
		$this->requirePatientAuth();
		$this->checkCsrf();

		$accountId = (int)$_SESSION['patient_account_id'];
		$action    = $_POST['msg_action'] ?? 'new';

		if ($action === 'reply') {
			$threadId = (int)($_POST['thread_id'] ?? 0);
			$body     = trim($_POST['body'] ?? '');

			if ($threadId <= 0 || $body === '') {
				$_SESSION['portal_msg_error'] = 'Message body cannot be empty.';
				header('Location: patient_portal.php?page=messages&thread=' . $threadId);
				exit;
			}
			if (mb_strlen($body) > 5000) {
				$_SESSION['portal_msg_error'] = 'Message is too long (max 5,000 characters).';
				header('Location: patient_portal.php?page=messages&thread=' . $threadId);
				exit;
			}

			$pdo = getDBConnection();

			// Verify thread ownership
			$stmt = $pdo->prepare(
				'SELECT thread_id FROM portal_message_threads
				  WHERE thread_id = ? AND patient_account_id = ?'
			);
			$stmt->execute([$threadId, $accountId]);
			if (!$stmt->fetch()) {
				$_SESSION['portal_msg_error'] = 'Thread not found.';
				header('Location: patient_portal.php?page=messages');
				exit;
			}

			$pdo->prepare(
				'INSERT INTO portal_messages (thread_id, sender_type, body) VALUES (?, \'PATIENT\', ?)'
			)->execute([$threadId, $body]);

			$pdo->prepare(
				'UPDATE portal_message_threads
				    SET last_message_at = NOW(), staff_unread = 1
				  WHERE thread_id = ?'
			)->execute([$threadId]);

			$_SESSION['portal_msg_success'] = 'Reply sent.';
			header('Location: patient_portal.php?page=messages&thread=' . $threadId);
			exit;
		}

		// New thread
		$subject = trim($_POST['subject'] ?? '');
		$body    = trim($_POST['body'] ?? '');

		if ($subject === '' || $body === '') {
			$_SESSION['portal_msg_error'] = 'Subject and message are required.';
			header('Location: patient_portal.php?page=messages&compose=1');
			exit;
		}
		if (mb_strlen($subject) > 200) {
			$_SESSION['portal_msg_error'] = 'Subject is too long (max 200 characters).';
			header('Location: patient_portal.php?page=messages&compose=1');
			exit;
		}
		if (mb_strlen($body) > 5000) {
			$_SESSION['portal_msg_error'] = 'Message is too long (max 5,000 characters).';
			header('Location: patient_portal.php?page=messages&compose=1');
			exit;
		}

		$pdo = getDBConnection();
		$pdo->prepare(
			'INSERT INTO portal_message_threads
			    (patient_account_id, subject, last_message_at, patient_unread, staff_unread)
			 VALUES (?, ?, NOW(), 0, 1)'
		)->execute([$accountId, $subject]);

		$threadId = (int)$pdo->lastInsertId();

		$pdo->prepare(
			'INSERT INTO portal_messages (thread_id, sender_type, body) VALUES (?, \'PATIENT\', ?)'
		)->execute([$threadId, $body]);

		$_SESSION['portal_msg_success'] = 'Message sent.';
		header('Location: patient_portal.php?page=messages&thread=' . $threadId);
		exit;
	}

	// ── Feedback ──────────────────────────────────────────────────────────────

	public function feedback(): void
	{
		$this->requirePatientAuth();
		$pdo       = getDBConnection();
		$accountId = (int)$_SESSION['patient_account_id'];

		$flashSuccess = null;
		$flashError   = null;
		if (isset($_SESSION['portal_fb_success'])) {
			$flashSuccess = $_SESSION['portal_fb_success'];
			unset($_SESSION['portal_fb_success']);
		}
		if (isset($_SESSION['portal_fb_error'])) {
			$flashError = $_SESSION['portal_fb_error'];
			unset($_SESSION['portal_fb_error']);
		}

		$myFeedback = [];
		try {
			$stmt = $pdo->prepare(
				'SELECT pf.portal_feedback_id, pf.feedback_type, pf.subject,
				        pf.description, pf.rating, pf.status, pf.created_at,
				        u.first_name AS related_first, u.last_name AS related_last
				   FROM portal_feedback pf
				   LEFT JOIN users u ON u.user_id = pf.related_user_id
				  WHERE pf.patient_account_id = ?
				  ORDER BY pf.created_at DESC'
			);
			$stmt->execute([$accountId]);
			$myFeedback = $stmt->fetchAll();
		} catch (Throwable $e) {}

		// Load staff list for "which staff does this concern?"
		$staffList = [];
		try {
			$staffList = $pdo->query(
				'SELECT user_id, first_name, last_name, role
				   FROM users WHERE is_active = 1
				   ORDER BY first_name, last_name'
			)->fetchAll();
		} catch (Throwable $e) {}

		require __DIR__ . '/../views/portal/feedback.php';
	}

	public function processSubmitFeedback(): void
	{
		$this->requirePatientAuth();
		$this->checkCsrf();

		$accountId    = (int)$_SESSION['patient_account_id'];
		$validTypes   = ['GRIEVANCE', 'COMPLAINT', 'POSITIVE', 'SUGGESTION'];
		$feedbackType = $_POST['feedback_type'] ?? '';
		$subject      = trim($_POST['subject'] ?? '');
		$description  = trim($_POST['description'] ?? '');
		$relatedUser  = (int)($_POST['related_user_id'] ?? 0);
		$rating       = isset($_POST['rating']) && (int)$_POST['rating'] > 0
		                ? max(1, min(5, (int)$_POST['rating']))
		                : null;

		if (!in_array($feedbackType, $validTypes, true)) {
			$_SESSION['portal_fb_error'] = 'Please select a feedback type.';
			header('Location: patient_portal.php?page=feedback');
			exit;
		}
		if ($subject === '' || $description === '') {
			$_SESSION['portal_fb_error'] = 'Subject and description are required.';
			header('Location: patient_portal.php?page=feedback');
			exit;
		}
		if (mb_strlen($description) > 5000) {
			$_SESSION['portal_fb_error'] = 'Description is too long (max 5,000 characters).';
			header('Location: patient_portal.php?page=feedback');
			exit;
		}

		$pdo = getDBConnection();

		// Validate related user if provided
		if ($relatedUser > 0) {
			$stmt = $pdo->prepare('SELECT user_id FROM users WHERE user_id = ? AND is_active = 1');
			$stmt->execute([$relatedUser]);
			if (!$stmt->fetch()) {
				$relatedUser = 0;
			}
		}

		$pdo->prepare(
			'INSERT INTO portal_feedback
			    (patient_account_id, feedback_type, subject, description, related_user_id, rating)
			 VALUES (?, ?, ?, ?, ?, ?)'
		)->execute([
			$accountId, $feedbackType, $subject, $description,
			$relatedUser ?: null, $rating,
		]);

		$_SESSION['portal_fb_success'] = 'Your feedback has been submitted. Thank you.';
		header('Location: patient_portal.php?page=feedback');
		exit;
	}

	// ── Profile ───────────────────────────────────────────────────────────────

	public function profile(): void
	{
		$this->requirePatientAuth();
		$pdo       = getDBConnection();
		$patientId = (int)$_SESSION['patient_id'];

		$flashSuccess = null;
		$flashError   = null;
		if (isset($_SESSION['portal_profile_success'])) {
			$flashSuccess = $_SESSION['portal_profile_success'];
			unset($_SESSION['portal_profile_success']);
		}
		if (isset($_SESSION['portal_profile_error'])) {
			$flashError = $_SESSION['portal_profile_error'];
			unset($_SESSION['portal_profile_error']);
		}

		$stmt = $pdo->prepare('SELECT * FROM patients WHERE patient_id = ?');
		$stmt->execute([$patientId]);
		$patient = $stmt->fetch();

		require __DIR__ . '/../views/portal/profile.php';
	}

	public function processUpdateProfile(): void
	{
		$this->requirePatientAuth();
		$this->checkCsrf();

		$patientId = (int)$_SESSION['patient_id'];
		$allergies = trim($_POST['allergies'] ?? '');

		// Sanitise: strip HTML, limit length
		$allergies = strip_tags($allergies);
		if (mb_strlen($allergies) > 255) {
			$_SESSION['portal_profile_error'] = 'Allergies field is too long (max 255 characters).';
			header('Location: patient_portal.php?page=profile');
			exit;
		}

		$pdo = getDBConnection();
		$pdo->prepare(
			'UPDATE patients SET allergies = ?, updated_at = NOW() WHERE patient_id = ?'
		)->execute([$allergies ?: null, $patientId]);

		$_SESSION['portal_profile_success'] = 'Your allergy information has been updated.';
		header('Location: patient_portal.php?page=profile');
		exit;
	}

	// ── Patient: resources (assets sent by staff + public assets) ────────────

	public function resources(): void
	{
		$this->requirePatientAuth();

		$pdo       = getDBConnection();
		$patientId = (int)$_SESSION['patient_id'];

		$flashSuccess = null;
		if (isset($_SESSION['portal_resources_success'])) {
			$flashSuccess = $_SESSION['portal_resources_success'];
			unset($_SESSION['portal_resources_success']);
		}

		// Assets sent directly to this patient (newest first)
		$sentAssets = [];
		try {
			$stmt = $pdo->prepare(
				'SELECT a.*, pa.patient_asset_id, pa.sent_at, pa.is_read, pa.note,
				        u.first_name AS sender_first, u.last_name AS sender_last
				   FROM patient_assets pa
				   JOIN assets a ON a.asset_id = pa.asset_id
				   LEFT JOIN users u ON u.user_id = pa.sent_by_user_id
				  WHERE pa.patient_id = ? AND a.is_active = 1
				  ORDER BY pa.sent_at DESC'
			);
			$stmt->execute([$patientId]);
			$sentAssets = $stmt->fetchAll();
		} catch (Throwable $e) {}

		// Public assets visible to all portal patients
		$publicAssets = [];
		try {
			$publicAssets = $pdo->query(
				"SELECT * FROM assets WHERE is_public = 1 AND is_active = 1
				  ORDER BY asset_type, title"
			)->fetchAll();
		} catch (Throwable $e) {}

		require __DIR__ . '/../views/portal/resources.php';
	}

	public function processMarkAssetRead(): void
	{
		$this->requirePatientAuth();
		$this->checkCsrf();

		$assetId   = (int)($_POST['asset_id'] ?? 0);
		$patientId = (int)$_SESSION['patient_id'];

		if ($assetId > 0) {
			$pdo = getDBConnection();
			try {
				$pdo->prepare(
					'UPDATE patient_assets SET is_read = 1 WHERE asset_id = ? AND patient_id = ?'
				)->execute([$assetId, $patientId]);
			} catch (Throwable $e) {}
		}

		header('Location: patient_portal.php?page=resources');
		exit;
	}

	// ── Staff: portal message management ─────────────────────────────────────
	// Exposed via portal_messages.php (requires staff session + patient_data read)

	public function staffMessages(): void
	{
		$this->requireStaffAuth('patient_data');

		$pdo = getDBConnection();

		$flashSuccess = null;
		if (isset($_SESSION['portal_staff_msg_success'])) {
			$flashSuccess = $_SESSION['portal_staff_msg_success'];
			unset($_SESSION['portal_staff_msg_success']);
		}

		$activeThreadId = (int)($_GET['thread'] ?? 0);
		$activeThread   = null;
		$threadMessages = [];
		$activePatient  = null;

		$threads = [];
		try {
			$stmt = $pdo->query(
				'SELECT pmt.thread_id, pmt.subject, pmt.last_message_at, pmt.staff_unread,
				        p.first_name, p.last_name, p.patient_code, p.patient_id,
				        pmt.patient_account_id
				   FROM portal_message_threads pmt
				   JOIN patient_accounts pa ON pa.patient_account_id = pmt.patient_account_id
				   JOIN patients p          ON p.patient_id = pa.patient_id
				  ORDER BY pmt.staff_unread DESC, pmt.last_message_at DESC'
			);
			$threads = $stmt->fetchAll();

			if ($activeThreadId > 0) {
				foreach ($threads as $t) {
					if ((int)$t['thread_id'] === $activeThreadId) {
						$activeThread = $t;
						break;
					}
				}

				if ($activeThread) {
					$stmt = $pdo->prepare(
						'SELECT pm.portal_message_id, pm.sender_type, pm.body, pm.sent_at,
						        u.first_name AS staff_first, u.last_name AS staff_last
						   FROM portal_messages pm
						   LEFT JOIN users u ON u.user_id = pm.sender_user_id
						  WHERE pm.thread_id = ?
						  ORDER BY pm.sent_at ASC'
					);
					$stmt->execute([$activeThreadId]);
					$threadMessages = $stmt->fetchAll();

					// Mark staff_unread = 0
					$pdo->prepare(
						'UPDATE portal_message_threads SET staff_unread = 0 WHERE thread_id = ?'
					)->execute([$activeThreadId]);

					// Refresh thread in list
					foreach ($threads as &$t) {
						if ((int)$t['thread_id'] === $activeThreadId) {
							$t['staff_unread'] = 0;
						}
					}
					unset($t);
				}
			}
		} catch (Throwable $e) {}

		require __DIR__ . '/../views/portal_messages.php';
	}

	public function processStaffReply(): void
	{
		$this->requireStaffAuth('patient_data');
		$this->checkCsrf();

		$threadId = (int)($_POST['thread_id'] ?? 0);
		$body     = trim($_POST['body'] ?? '');

		if ($threadId <= 0 || $body === '') {
			$_SESSION['portal_staff_msg_success'] = 'Message body cannot be empty.';
			header('Location: portal_messages.php?thread=' . $threadId);
			exit;
		}
		if (mb_strlen($body) > 5000) {
			$_SESSION['portal_staff_msg_success'] = 'Message is too long (max 5,000 characters).';
			header('Location: portal_messages.php?thread=' . $threadId);
			exit;
		}

		$pdo    = getDBConnection();
		$userId = (int)$_SESSION['user_id'];

		$pdo->prepare(
			'INSERT INTO portal_messages (thread_id, sender_type, sender_user_id, body)
			 VALUES (?, \'STAFF\', ?, ?)'
		)->execute([$threadId, $userId, $body]);

		$pdo->prepare(
			'UPDATE portal_message_threads
			    SET last_message_at = NOW(), patient_unread = 1
			  WHERE thread_id = ?'
		)->execute([$threadId]);

		$_SESSION['portal_staff_msg_success'] = 'Reply sent to patient.';
		header('Location: portal_messages.php?thread=' . $threadId);
		exit;
	}

	// ── Staff: create / manage patient portal accounts ────────────────────────
	// All three actions are POST-only, called from patients.php

	public function processCreateAccount(): void
	{
		$this->requireStaffAuth('users', 'W');
		$this->checkCsrf();

		$patientId = (int)($_POST['patient_id'] ?? 0);
		$email     = trim($_POST['portal_email'] ?? '');
		$username  = trim($_POST['portal_username'] ?? '') ?: null;
		$password  = $_POST['portal_password'] ?? '';
		$confirm   = $_POST['portal_password_confirm'] ?? '';

		if ($patientId <= 0) {
			$_SESSION['patients_error'] = 'Invalid patient.';
			header('Location: patients.php');
			exit;
		}

		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$_SESSION['patients_error'] = 'A valid email address is required.';
			header('Location: patients.php?action=view&id=' . $patientId);
			exit;
		}

		if (strlen($password) < 8) {
			$_SESSION['patients_error'] = 'Password must be at least 8 characters.';
			header('Location: patients.php?action=view&id=' . $patientId);
			exit;
		}

		if ($password !== $confirm) {
			$_SESSION['patients_error'] = 'Passwords do not match.';
			header('Location: patients.php?action=view&id=' . $patientId);
			exit;
		}

		$pdo = getDBConnection();

		// Verify patient exists
		$stmt = $pdo->prepare('SELECT patient_id FROM patients WHERE patient_id = ? AND is_active = 1');
		$stmt->execute([$patientId]);
		if (!$stmt->fetch()) {
			$_SESSION['patients_error'] = 'Patient not found.';
			header('Location: patients.php');
			exit;
		}

		// Check no existing account
		$stmt = $pdo->prepare('SELECT patient_account_id FROM patient_accounts WHERE patient_id = ?');
		$stmt->execute([$patientId]);
		if ($stmt->fetch()) {
			$_SESSION['patients_error'] = 'This patient already has a portal account.';
			header('Location: patients.php?action=view&id=' . $patientId);
			exit;
		}

		try {
			$pdo->prepare(
				'INSERT INTO patient_accounts
				    (patient_id, username, email, password_hash, created_by_user_id)
				 VALUES (?, ?, ?, ?, ?)'
			)->execute([
				$patientId,
				$username,
				$email,
				password_hash($password, PASSWORD_DEFAULT),
				$_SESSION['user_id'],
			]);

			$_SESSION['patients_success'] = 'Portal account created. The patient can now log in at patient_login.php.';
		} catch (Throwable $e) {
			if (str_contains($e->getMessage(), 'uq_pac_email') || str_contains($e->getMessage(), 'Duplicate')) {
				$_SESSION['patients_error'] = 'That email address is already in use by another portal account.';
			} else {
				$_SESSION['patients_error'] = 'Could not create account. Please try again.';
			}
		}

		header('Location: patients.php?action=view&id=' . $patientId);
		exit;
	}

	public function processResetPassword(): void
	{
		$this->requireStaffAuth('users', 'W');
		$this->checkCsrf();

		$patientId = (int)($_POST['patient_id'] ?? 0);
		$accountId = (int)($_POST['account_id'] ?? 0);
		$password  = $_POST['new_password'] ?? '';
		$confirm   = $_POST['new_password_confirm'] ?? '';

		if (strlen($password) < 8) {
			$_SESSION['patients_error'] = 'New password must be at least 8 characters.';
			header('Location: patients.php?action=view&id=' . $patientId);
			exit;
		}
		if ($password !== $confirm) {
			$_SESSION['patients_error'] = 'Passwords do not match.';
			header('Location: patients.php?action=view&id=' . $patientId);
			exit;
		}

		$pdo = getDBConnection();
		$pdo->prepare(
			'UPDATE patient_accounts SET password_hash = ?, updated_at = NOW()
			  WHERE patient_account_id = ?'
		)->execute([password_hash($password, PASSWORD_DEFAULT), $accountId]);

		$_SESSION['patients_success'] = 'Portal password has been reset.';
		header('Location: patients.php?action=view&id=' . $patientId);
		exit;
	}

	public function processToggleActive(): void
	{
		$this->requireStaffAuth('users', 'W');
		$this->checkCsrf();

		$patientId = (int)($_POST['patient_id'] ?? 0);
		$accountId = (int)($_POST['account_id'] ?? 0);
		$newState  = (int)(bool)($_POST['activate'] ?? 0);   // 1 = activate, 0 = deactivate

		$pdo = getDBConnection();
		$pdo->prepare(
			'UPDATE patient_accounts SET is_active = ?, updated_at = NOW()
			  WHERE patient_account_id = ?'
		)->execute([$newState, $accountId]);

		$_SESSION['patients_success'] = $newState
			? 'Portal account activated.'
			: 'Portal account deactivated.';
		header('Location: patients.php?action=view&id=' . $patientId);
		exit;
	}
}
