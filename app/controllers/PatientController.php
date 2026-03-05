<?php
/**
 * app/controllers/PatientController.php
 *
 * Handles the patient records page:
 *   - Patient search / list
 *   - Full patient profile view (demographics, case sheets, grievances)
 *   - AJAX patient search
 *
 * IMPORTANT: Every call to view() automatically writes a row to
 * patient_record_access_log so that administrators can audit who has
 * been looking at patient records and when.  Only roles with
 * patient_data read access can reach this controller.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/permissions.php';

class PatientController
{
	// ── Search / list ─────────────────────────────────────────

	public function index(): void
	{
		$this->requireRead();

		$flashError = null;
		if (isset($_SESSION['patients_error'])) {
			$flashError = $_SESSION['patients_error'];
			unset($_SESSION['patients_error']);
		}

		require __DIR__ . '/../views/patients.php';
	}

	// ── Patient profile view ──────────────────────────────────

	public function view(int $patientId): void
	{
		$this->requireRead();

		if ($patientId <= 0) {
			header('Location: patients.php');
			exit;
		}

		$pdo  = getDBConnection();
		$role = $_SESSION['user_role'] ?? '';

		// Load patient record
		$stmt = $pdo->prepare('SELECT * FROM patients WHERE patient_id = ?');
		$stmt->execute([$patientId]);
		$patient = $stmt->fetch();

		if (!$patient) {
			$_SESSION['patients_error'] = 'Patient record not found.';
			header('Location: patients.php');
			exit;
		}

		// Load all case sheets for this patient (most recent first),
		// joining to get the names of the intake nurse and assigned doctor.
		$stmt = $pdo->prepare(
			'SELECT cs.*,
			        u1.first_name AS creator_first, u1.last_name AS creator_last,
			        u2.first_name AS doctor_first,  u2.last_name AS doctor_last
			   FROM case_sheets cs
			   LEFT JOIN users u1 ON u1.user_id = cs.created_by_user_id
			   LEFT JOIN users u2 ON u2.user_id = cs.assigned_doctor_user_id
			  WHERE cs.patient_id = ?
			  ORDER BY cs.visit_datetime DESC'
		);
		$stmt->execute([$patientId]);
		$caseSheets = $stmt->fetchAll();

		// Load patient-portal grievances / feedback (only for roles that can
		// read the feedback resource — same gate as the Feedback menu item).
		$grievances     = [];
		$canSeeFeedback = can($role, 'feedback');
		if ($canSeeFeedback) {
			try {
				$stmt = $pdo->prepare(
					'SELECT pf.feedback_id, pf.feedback_type, pf.rating,
					        pf.feedback_text, pf.status, pf.admin_notes,
					        pf.created_at, pf.updated_at,
					        u.first_name AS related_first, u.last_name AS related_last
					   FROM patient_feedback pf
					   LEFT JOIN users u ON u.user_id = pf.related_user_id
					  WHERE pf.patient_id = ?
					  ORDER BY pf.created_at DESC'
				);
				$stmt->execute([$patientId]);
				$grievances = $stmt->fetchAll();
			} catch (Throwable $e) {
				$grievances = [];
			}
		}

		// Load access log — SUPER_ADMIN and ADMIN only.
		// We load BEFORE logging the current visit so the admin sees the
		// history up to (but not including) this exact page load.
		$accessLog       = [];
		$canSeeAccessLog = in_array($role, ['SUPER_ADMIN', 'ADMIN'], true);
		if ($canSeeAccessLog) {
			try {
				$stmt = $pdo->prepare(
					'SELECT pal.log_id, pal.access_type, pal.ip_address,
					        pal.accessed_at,
					        u.first_name, u.last_name, u.role AS viewer_role
					   FROM patient_record_access_log pal
					   JOIN users u ON u.user_id = pal.accessed_by_user_id
					  WHERE pal.patient_id = ?
					  ORDER BY pal.accessed_at DESC
					  LIMIT 200'
				);
				$stmt->execute([$patientId]);
				$accessLog = $stmt->fetchAll();
			} catch (Throwable $e) {
				$accessLog = [];
			}
		}

		// Write access log entry for the current visit.
		$this->logAccess($pdo, $patientId, 'VIEW_PROFILE');

		require __DIR__ . '/../views/patient_profile.php';
	}

	// ── AJAX patient search ───────────────────────────────────
	// Accepts GET params: name (string), dob (YYYY-MM-DD).
	// At least one must be supplied; name alone requires ≥2 chars.
	// Returns JSON array with visit_count, last_visit, and allergies
	// so the front-end can show a rich, clinically useful result row.

	public function searchAjax(): void
	{
		$this->requireRead();

		header('Content-Type: application/json; charset=utf-8');

		$name = trim($_GET['name'] ?? '');
		$dob  = trim($_GET['dob']  ?? '');

		// Must have at least one meaningful criterion
		if ($name === '' && $dob === '') {
			echo json_encode([]);
			exit;
		}

		// For name-only searches require ≥2 chars to avoid huge unfiltered dumps
		if ($name !== '' && mb_strlen($name) < 2 && $dob === '') {
			echo json_encode([]);
			exit;
		}

		$pdo        = getDBConnection();
		$conditions = [];
		$params     = [];

		if ($name !== '') {
			$like         = '%' . $name . '%';
			$conditions[] = "(p.first_name LIKE ? OR p.last_name LIKE ?"
			              . " OR CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) LIKE ?)";
			array_push($params, $like, $like, $like);
		}

		if ($dob !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
			$conditions[] = 'p.date_of_birth = ?';
			$params[]     = $dob;
		}

		if (empty($conditions)) {
			echo json_encode([]);
			exit;
		}

		$where = implode(' AND ', $conditions);

		$stmt = $pdo->prepare(
			"SELECT p.patient_id, p.patient_code,
			        p.first_name, p.last_name,
			        p.sex, p.date_of_birth, p.age_years,
			        p.phone_e164, p.city,
			        p.blood_group, p.allergies,
			        p.is_active,
			        COUNT(cs.case_sheet_id) AS visit_count,
			        MAX(cs.visit_datetime)  AS last_visit
			   FROM patients p
			   LEFT JOIN case_sheets cs ON cs.patient_id = p.patient_id
			  WHERE {$where}
			  GROUP BY p.patient_id
			  ORDER BY p.last_name, p.first_name
			  LIMIT 50"
		);
		$stmt->execute($params);

		echo json_encode($stmt->fetchAll());
		exit;
	}

	// ── Access log writer ─────────────────────────────────────

	private function logAccess(PDO $pdo, int $patientId, string $type): void
	{
		try {
			$ip        = substr($_SERVER['REMOTE_ADDR']      ?? '', 0, 45);
			$userAgent = substr($_SERVER['HTTP_USER_AGENT']  ?? '', 0, 500);

			$pdo->prepare(
				'INSERT INTO patient_record_access_log
				    (patient_id, accessed_by_user_id, access_type, ip_address, user_agent)
				 VALUES (?, ?, ?, ?, ?)'
			)->execute([$patientId, $_SESSION['user_id'], $type, $ip ?: null, $userAgent ?: null]);
		} catch (Throwable $e) {
			// Silently skip — if the table hasn't been migrated yet the page
			// continues to function; the admin will see an empty log.
		}
	}

	// ── Permission guard ──────────────────────────────────────

	private function requireRead(): void
	{
		if (!can($_SESSION['user_role'] ?? '', 'patient_data')) {
			$_SESSION['dashboard_notice'] = 'You do not have permission to access patient records.';
			header('Location: dashboard.php');
			exit;
		}
	}
}
