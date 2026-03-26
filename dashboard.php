<?php
require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/permissions.php';

$pdo = getDBConnection();

$_userRole       = $_SESSION['user_role'] ?? '';
$_isClinicalRole = can($_userRole, 'case_sheets', 'W');

// ── Data for clinical roles ──────────────────────────────────────────────────
$todayStats      = ['total_today' => 0, 'in_progress' => 0, 'ready' => 0, 'in_review' => 0, 'closed_today' => 0];
$myActiveReviews = [];
$calendarEvents  = [];
$nextEvent       = null;
$staleSheets     = [];

if ($_isClinicalRole) { try {
	// ── Today's visit count (patients who came in today) ─────────────────
	$rowToday = $pdo->query(
		"SELECT COUNT(*) AS total_today,
		        SUM(status = 'CLOSED' AND DATE(closed_at) = CURDATE()) AS closed_today
		   FROM case_sheets
		  WHERE DATE(visit_datetime) = CURDATE()"
	)->fetch(PDO::FETCH_ASSOC);

	// ── Status counts across ALL open sheets (not date-filtered) ─────────
	// In Progress / Ready / In Review include prior-day unclosed sheets.
	$rowStatus = $pdo->query(
		"SELECT SUM(status = 'INTAKE_IN_PROGRESS') AS in_progress,
		        SUM(status = 'INTAKE_COMPLETE')    AS ready,
		        SUM(status = 'DOCTOR_REVIEW')      AS in_review
		   FROM case_sheets
		  WHERE status IN ('INTAKE_IN_PROGRESS','INTAKE_COMPLETE','DOCTOR_REVIEW')"
	)->fetch(PDO::FETCH_ASSOC);

	$todayStats = [
		'total_today'  => (int)($rowToday['total_today']  ?? 0),
		'closed_today' => (int)($rowToday['closed_today'] ?? 0),
		'in_progress'  => (int)($rowStatus['in_progress'] ?? 0),
		'ready'        => (int)($rowStatus['ready']        ?? 0),
		'in_review'    => (int)($rowStatus['in_review']    ?? 0),
	];

	// ── Prior-day open case sheets (for stale-chart banner) ──────────────
	$stmtStale = $pdo->prepare(
		"SELECT cs.case_sheet_id, cs.status, cs.visit_datetime,
		        p.first_name, p.last_name, p.patient_code
		   FROM case_sheets cs
		   JOIN patients p ON p.patient_id = cs.patient_id
		  WHERE cs.status NOT IN ('CLOSED')
		    AND DATE(cs.visit_datetime) < CURDATE()
		  ORDER BY cs.visit_datetime ASC"
	);
	$stmtStale->execute();
	$staleSheets = $stmtStale->fetchAll(PDO::FETCH_ASSOC);

	// ── Prior-day unresolved appointments (for stale-appointments banner) ────
	$staleAppts = [];
	if (can($_userRole, 'appointments')) {
		$_stalApptSql = "SELECT a.appointment_id, a.scheduled_date, a.scheduled_time,
		                        a.status AS appt_status,
		                        p.first_name, p.last_name, p.patient_code,
		                        d.first_name AS doc_first, d.last_name AS doc_last
		                   FROM appointments a
		                   JOIN case_sheets cs ON cs.case_sheet_id = a.case_sheet_id
		                   JOIN patients    p  ON p.patient_id     = cs.patient_id
		                   JOIN users       d  ON d.user_id        = a.doctor_user_id
		                  WHERE a.scheduled_date < CURDATE()
		                    AND a.status IN ('SCHEDULED','CONFIRMED','IN_PROGRESS')";
		$_stalApptParams = [];
		if ($_userRole === 'DOCTOR') {
			$_stalApptSql .= ' AND a.doctor_user_id = ?';
			$_stalApptParams[] = (int)$_SESSION['user_id'];
		}
		$_stalApptSql .= ' ORDER BY a.scheduled_date ASC';
		$_stmtStalAppt = $pdo->prepare($_stalApptSql);
		$_stmtStalAppt->execute($_stalApptParams);
		$staleAppts = $_stmtStalAppt->fetchAll(PDO::FETCH_ASSOC);
	}

	// Doctor's claimed cases
	if ($_userRole === 'DOCTOR') {
		$stmt = $pdo->prepare(
			'SELECT cs.case_sheet_id, cs.visit_type, cs.chief_complaint,
			        cs.visit_datetime, cs.updated_at,
			        p.patient_id, p.first_name, p.last_name, p.patient_code, p.sex, p.age_years
			   FROM case_sheets cs
			   JOIN patients p ON p.patient_id = cs.patient_id
			  WHERE cs.status = ? AND cs.assigned_doctor_user_id = ?
			  ORDER BY cs.updated_at DESC'
		);
		$stmt->execute(['DOCTOR_REVIEW', $_SESSION['user_id']]);
		$myActiveReviews = $stmt->fetchAll();
	}

	// Calendar events
	$calendarEvents = $pdo->query(
		'SELECT title, event_type, start_datetime, end_datetime
		   FROM events WHERE is_active = 1 ORDER BY start_datetime'
	)->fetchAll();

	// Appointment events for calendar
	$calendarAppts = [];
	if (can($_userRole, 'appointments')) {
		$_calApptSql = "SELECT a.appointment_id, a.scheduled_date, a.scheduled_time,
		                       p.first_name, p.last_name,
		                       d.last_name AS doc_last
		                  FROM appointments a
		                  JOIN case_sheets cs ON cs.case_sheet_id = a.case_sheet_id
		                  JOIN patients    p  ON p.patient_id     = cs.patient_id
		                  JOIN users       d  ON d.user_id        = a.doctor_user_id
		                 WHERE a.scheduled_date >= CURDATE()
		                   AND a.status NOT IN ('CANCELLED','NO_SHOW','COMPLETED')";
		$_calApptParams = [];
		if ($_userRole === 'DOCTOR') {
			$_calApptSql .= ' AND a.doctor_user_id = ?';
			$_calApptParams[] = (int)$_SESSION['user_id'];
		}
		$_calApptSql .= ' ORDER BY a.scheduled_date, a.scheduled_time';
		$_calStmt = $pdo->prepare($_calApptSql);
		$_calStmt->execute($_calApptParams);
		$calendarAppts = $_calStmt->fetchAll(PDO::FETCH_ASSOC);
	}

	// Next upcoming event
	$nextEvent = $pdo->query(
		"SELECT title, start_datetime, event_type, location_name
		   FROM events WHERE is_active = 1 AND start_datetime >= NOW()
		   ORDER BY start_datetime LIMIT 1"
	)->fetch(PDO::FETCH_ASSOC);

	// ── Stat tile patient lists (click-to-view popup) ─────────────────────
	$statPatients = [];
	foreach ([
		'total_today'  => "DATE(cs.visit_datetime) = CURDATE()",
		'in_progress'  => "cs.status = 'INTAKE_IN_PROGRESS'",
		'ready'        => "cs.status = 'INTAKE_COMPLETE'",
		'in_review'    => "cs.status = 'DOCTOR_REVIEW'",
		'closed_today' => "cs.status = 'CLOSED' AND DATE(cs.closed_at) = CURDATE()",
	] as $_sk => $_sw) {
		$statPatients[$_sk] = $pdo->query(
			"SELECT p.patient_id, p.first_name, p.last_name, p.patient_code, cs.case_sheet_id, cs.status AS cs_status
			   FROM case_sheets cs
			   JOIN patients p ON p.patient_id = cs.patient_id
			  WHERE {$_sw}
			  ORDER BY cs.visit_datetime ASC"
		)->fetchAll(PDO::FETCH_ASSOC);
	}
	$statPatients['my_active'] = array_map(
		fn($r) => ['patient_id' => $r['patient_id'] ?? null, 'first_name' => $r['first_name'], 'last_name' => $r['last_name'], 'patient_code' => $r['patient_code'], 'case_sheet_id' => $r['case_sheet_id'] ?? null, 'cs_status' => 'DOCTOR_REVIEW'],
		$myActiveReviews
	);

	// ── Today's scheduled appointments (for dashboard cards) ────────────────────
	$todayScheduledAppts = []; // Today's appointments
	if (can($_userRole, 'appointments')) {
		$_apptSql = "SELECT a.appointment_id, a.case_sheet_id, a.scheduled_time,
		                    a.status AS appt_status,
		                    cs.status AS cs_status, cs.chief_complaint,
		                    p.patient_id, p.patient_code, p.first_name, p.last_name,
		                    p.age_years, p.sex,
		                    d.first_name AS doc_first, d.last_name AS doc_last,
		                    d.user_id AS doctor_user_id
		               FROM appointments a
		               JOIN case_sheets cs ON cs.case_sheet_id = a.case_sheet_id
		               JOIN patients    p  ON p.patient_id     = cs.patient_id
		               JOIN users       d  ON d.user_id        = a.doctor_user_id
		              WHERE a.scheduled_date = CURDATE()
		                AND a.status NOT IN ('CANCELLED','NO_SHOW','COMPLETED')";
		$_apptParams = [];
		// Both nurses and doctors: only show appointments where intake is not yet complete.
		// Once intake is complete the patient drops to the queue (nurse) or
		// Patients Ready for Review (doctor).
		$_apptSql .= " AND cs.status IN ('SCHEDULED','INTAKE_IN_PROGRESS')";
		if ($_userRole === 'DOCTOR') {
			$_apptSql .= ' AND a.doctor_user_id = ?';
			$_apptParams[] = (int)$_SESSION['user_id'];
		}
		$_apptSql .= " ORDER BY COALESCE(a.scheduled_time, '23:59:59') ASC";
		$_stmt = $pdo->prepare($_apptSql);
		$_stmt->execute($_apptParams);
		$todayScheduledAppts = $_stmt->fetchAll(PDO::FETCH_ASSOC);

	}
} catch (PDOException $e) {
	// DB error - leave all clinical stats at their zero defaults
} }

// ── Dashboard counts for new features ───────────────────────────────────────
$unreadMessages = 0;
$openTasks      = 0;
$openFeedback   = 0;
$userId = (int)$_SESSION['user_id'];

try {
	$stmt = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE recipient_user_id = ? AND is_read = 0');
	$stmt->execute([$userId]);
	$unreadMessages = (int)$stmt->fetchColumn();

	$stmt = $pdo->prepare(
		'SELECT COUNT(*) FROM tasks WHERE status != ? AND (created_by_user_id = ? OR assigned_to_user_id = ?)'
	);
	$stmt->execute(['DONE', $userId, $userId]);
	$openTasks = (int)$stmt->fetchColumn();

	if (can($_userRole, 'feedback')) {
		if (can($_userRole, 'feedback', 'W')) {
			$stmt = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status IN ('OPEN','UNDER_REVIEW')");
		} else {
			$stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE submitted_by_user_id = ? AND status IN ('OPEN','UNDER_REVIEW')");
			$stmt->execute([$userId]);
		}
		$openFeedback = (int)$stmt->fetchColumn();
	}
} catch (PDOException $e) {
	// DB error - leave counts at zero
}

$roleLabel = [
	'DOCTOR'             => 'Doctor',
	'TRIAGE_NURSE'       => 'Triage Nurse',
	'NURSE'              => 'Nurse',
	'PARAMEDIC'          => 'Paramedic',
	'SUPER_ADMIN'        => 'Super Admin',
	'ADMIN'              => 'Admin',
	'DATA_ENTRY_OPERATOR'=> 'Data Entry Operator',
	'GRIEVANCE_OFFICER'  => 'Grievance Officer',
	'EDUCATION_TEAM'     => 'Education Team',
][$_userRole] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Dashboard | D3S3 CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" />
	<style>
		/* ── Quick action tiles ───────────────────────────────────── */
		.qa-tile {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 1.1rem 0.5rem 0.9rem;
			border-radius: 10px;
			border: 1.5px solid #dee2e6;
			background: #fff;
			text-decoration: none;
			color: #495057;
			transition: box-shadow 0.15s, transform 0.15s, border-color 0.15s;
			min-height: 90px;
			cursor: pointer;
		}
		.qa-tile:hover {
			box-shadow: 0 4px 16px rgba(0,0,0,0.10);
			transform: translateY(-2px);
			text-decoration: none;
			color: #007bff;
			border-color: #007bff;
		}
		.qa-tile.qa-primary {
			background: #007bff;
			border-color: #007bff;
			color: #fff;
		}
		.qa-tile.qa-primary:hover {
			background: #0069d9;
			border-color: #0062cc;
			color: #fff;
			box-shadow: 0 4px 16px rgba(0,123,255,0.35);
		}
		.qa-tile.qa-disabled {
			opacity: 0.55;
			cursor: not-allowed;
			pointer-events: none;
		}
		.qa-icon { font-size: 1.6rem; margin-bottom: 0.4rem; line-height: 1; }
		.qa-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; line-height: 1.2; text-align: center; }
		.qa-soon { font-size: 0.6rem; font-weight: 600; letter-spacing: 0.02em; background: #adb5bd; color: #fff; border-radius: 20px; padding: 1px 6px; margin-top: 3px; }

		/* ── Stat cards ────────────────────────────────────────────── */
		.stat-card { border-radius: 10px; border: 1.5px solid #e9ecef; padding: 1rem 1.1rem; background: #fff; }
		.stat-card .stat-num { font-size: 2rem; font-weight: 700; line-height: 1; }
		.stat-card .stat-lbl { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; color: #6c757d; margin-top: 0.1rem; }
		.stat-card .stat-icon { font-size: 1.5rem; opacity: 0.15; }

		/* ── Stale charts modal rows ───────────────────────────────── */
		.stale-chart-row:hover { background: #fffbf0; }

		/* ── Patient queue ─────────────────────────────────────────── */
		.queue-row { cursor: grab; user-select: none; }
		.queue-row:active { cursor: grabbing; }
		.queue-row.sortable-ghost  { opacity: 0.4; background: #e9ecef !important; }
		.queue-row.sortable-chosen { background: #fff3cd !important; }
		.queue-drag-handle { color: #adb5bd; cursor: grab; padding: 0 8px; font-size: 1.1rem; }
		.queue-drag-handle:hover { color: #495057; }
		.badge-status-in-progress { background-color: #17a2b8; }
		.badge-status-complete    { background-color: #fd7e14; }
		#queueLastUpdated { font-size: 0.75rem; color: #adb5bd; }
		.queue-empty { padding: 1rem 1.25rem; color: #6c757d; }

		/* ── Coming Soon overlay ───────────────────────────────────── */
		.coming-soon-banner {
			display: flex; align-items: center; justify-content: center;
			padding: 2rem 1rem; color: #adb5bd; flex-direction: column; gap: 0.5rem;
		}
		.coming-soon-banner i { font-size: 2rem; }
		.coming-soon-banner span { font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }
	</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed<?= ($_SESSION['font_size'] ?? 'normal') === 'large' ? ' font-size-large' : '' ?>"
      data-theme-server="<?= htmlspecialchars($_SESSION['theme'] ?? 'system') ?>">
<div class="wrapper">

	<!-- ── Navbar ──────────────────────────────────────────────────────── -->
	<nav class="main-header navbar navbar-expand navbar-white navbar-light">
		<ul class="navbar-nav">
			<li class="nav-item">
				<a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Toggle sidebar">
					<i class="fas fa-bars"></i>
				</a>
			</li>
			<li class="nav-item d-none d-sm-inline-block">
				<span class="navbar-brand mb-0 h6 text-primary">CareSystem</span>
			</li>
		</ul>
		<ul class="navbar-nav ml-auto">
			<li class="nav-item d-flex align-items-center">
				<button id="gearBtn" aria-label="Display settings" title="Display settings">
					<i class="fas fa-cog fa-lg"></i>
				</button>
			</li>
			<?php if ($_isClinicalRole): ?>
			<li class="nav-item">
				<a class="btn btn-sm btn-primary" href="intake.php" role="button">
					<i class="fas fa-clipboard-list mr-1"></i>Start Intake
				</a>
			</li>
			<?php endif; ?>
		</ul>
	</nav>

	<!-- Slide-down display settings panel -->
	<div id="settingsPanel" role="dialog" aria-label="Display settings">
		<span class="panel-label">Display settings</span>
		<div class="custom-control custom-switch mb-3">
			<input type="checkbox" class="custom-control-input" id="themeTogglePanel" data-theme-toggle />
			<label class="custom-control-label" for="themeTogglePanel">Dark mode</label>
		</div>
		<div>
			<span class="panel-label">Language</span>
			<div class="btn-group lang-btn-group" role="group" aria-label="Language">
				<button type="button" class="btn btn-sm <?= ($_SESSION['language'] ?? 'en') === 'en' ? 'btn-primary' : 'btn-outline-secondary' ?>" data-lang="en">English</button>
				<button type="button" class="btn btn-sm <?= ($_SESSION['language'] ?? 'en') === 'te' ? 'btn-primary' : 'btn-outline-secondary' ?>" data-lang="te">తెలుగు</button>
			</div>
		</div>
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
	</div>

	<?php require __DIR__ . '/app/views/_sidebar.php'; ?>

	<div class="content-wrapper">

		<!-- ── Page header ───────────────────────────────────────────── -->
		<div class="content-header">
			<div class="container-fluid">
				<div class="row align-items-center">
					<div class="col">
						<h1 class="m-0 text-dark">
							<?php if ($_isClinicalRole): ?>
							Clinical Workspace
							<?php else: ?>
							Dashboard
							<?php endif; ?>
						</h1>
						<p class="text-muted mb-0">
							Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
							<span class="badge badge-secondary ml-1"><?= htmlspecialchars($roleLabel) ?></span>
							&nbsp;&middot;&nbsp;<?= date('l, F j, Y') ?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

			<?php if ($_isClinicalRole): ?>

			<?php if (!empty($staleAppts)): ?>
			<?php $staleApptCount = count($staleAppts); ?>
			<!-- ── Stale appointments notification ─────────────────── -->
			<div class="alert alert-danger alert-dismissible fade show mb-4 py-2 d-flex align-items-center" role="alert">
				<i class="fas fa-calendar-times mr-2"></i>
				<strong><?= $staleApptCount ?> unresolved <?= $staleApptCount === 1 ? 'appointment' : 'appointments' ?> from a previous day<?= $staleApptCount === 1 ? '' : 's' ?></strong>
				<button type="button" class="btn btn-sm btn-danger ml-3 py-0 px-2"
				        data-toggle="modal" data-target="#staleApptsModal">
					<i class="fas fa-eye mr-1"></i>View
				</button>
				<button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<!-- ── Stale appointments modal ────────────────────────── -->
			<div class="modal fade" id="staleApptsModal" tabindex="-1" role="dialog"
			     aria-labelledby="staleApptsModalLabel" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
					<div class="modal-content">
						<div class="modal-header" style="background:#f8d7da;">
							<h5 class="modal-title" id="staleApptsModalLabel">
								<i class="fas fa-calendar-times mr-2 text-danger"></i>
								Unresolved Appointments from Previous Days
							</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body p-0">
							<p class="px-3 pt-3 pb-2 mb-0 small text-muted">
								These appointments were never marked as completed, cancelled, or no-show. Please resolve them in <a href="appointments.php">Appointments</a>.
							</p>
							<ul class="list-unstyled mb-0">
							<?php foreach ($staleAppts as $_sai => $_sa): ?>
							<li class="<?= $_sai < $staleApptCount - 1 ? 'border-bottom' : '' ?>">
								<div class="d-flex align-items-center justify-content-between px-3 py-2">
									<div>
										<strong><?= htmlspecialchars($_sa['first_name'] . ' ' . ($_sa['last_name'] ?? '')) ?></strong>
										<span class="text-muted small ml-1">(<?= htmlspecialchars($_sa['patient_code']) ?>)</span>
										<br>
										<small class="text-muted">
											<?= htmlspecialchars(date('D, M j', strtotime($_sa['scheduled_date']))) ?>
											<?= $_sa['scheduled_time'] ? ' at ' . htmlspecialchars(date('g:i A', strtotime($_sa['scheduled_time']))) : '' ?>
											&middot; Dr. <?= htmlspecialchars($_sa['doc_first'] . ' ' . $_sa['doc_last']) ?>
										</small>
									</div>
									<div class="text-right ml-3">
										<span class="badge badge-danger"><?= htmlspecialchars($_sa['appt_status']) ?></span>
									</div>
								</div>
							</li>
							<?php endforeach; ?>
							</ul>
						</div>
						<div class="modal-footer">
							<a href="appointments.php" class="btn btn-primary btn-sm">
								<i class="fas fa-calendar-alt mr-1"></i>Go to Appointments
							</a>
							<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if (!empty($staleSheets)): ?>
			<?php $staleCount = count($staleSheets); ?>
			<!-- ── Stale open-chart notification ────────────────────── -->
			<div class="alert alert-warning alert-dismissible fade show mb-4 py-2 d-flex align-items-center" role="alert">
				<i class="fas fa-folder-open mr-2"></i>
				<strong><?= $staleCount ?> open <?= $staleCount === 1 ? 'chart' : 'charts' ?> from a previous day<?= $staleCount === 1 ? '' : 's' ?></strong>
				<button type="button" class="btn btn-sm btn-warning ml-3 py-0 px-2"
				        data-toggle="modal" data-target="#staleChartsModal">
					<i class="fas fa-eye mr-1"></i>View
				</button>
				<button type="button" class="close ml-auto" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<!-- ── Stale charts modal ───────────────────────────────── -->
			<div class="modal fade" id="staleChartsModal" tabindex="-1" role="dialog"
			     aria-labelledby="staleChartsModalLabel" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
					<div class="modal-content">
						<div class="modal-header" style="background:#fff3cd;">
							<h5 class="modal-title" id="staleChartsModalLabel">
								<i class="fas fa-folder-open mr-2 text-warning"></i>
								Open Charts from Previous Days
							</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body p-0">
							<p class="px-3 pt-3 pb-2 mb-0 small text-muted">
								<?= $staleCount === 1 ? 'This case sheet has' : 'These case sheets have' ?> not been closed and will remain in the queue until resolved. Click a row to open the chart.
							</p>
							<?php
							$staleStatusLabels = [
								'INTAKE_IN_PROGRESS' => 'In Progress',
								'INTAKE_COMPLETE'    => 'Ready for Doctor',
								'DOCTOR_REVIEW'      => 'In Review',
								'SCHEDULED'          => 'Scheduled',
								'QUEUED'             => 'Queued',
							];
							$staleStatusColors = [
								'INTAKE_IN_PROGRESS' => 'warning',
								'INTAKE_COMPLETE'    => 'info',
								'DOCTOR_REVIEW'      => 'primary',
								'SCHEDULED'          => 'success',
								'QUEUED'             => 'secondary',
							];
							?>
							<ul class="list-unstyled mb-0">
							<?php foreach ($staleSheets as $_si => $_s):
								if ($_s['status'] === 'INTAKE_IN_PROGRESS' || $_s['status'] === 'INTAKE_COMPLETE') {
									$_staleHref = 'intake.php?case_sheet_id=' . (int)$_s['case_sheet_id'];
								} elseif ($_s['status'] === 'DOCTOR_REVIEW') {
									$_staleHref = 'review.php?case_sheet_id=' . (int)$_s['case_sheet_id'];
								} else {
									$_staleHref = '';
								}
								$_staleBadge = $staleStatusColors[$_s['status']] ?? 'secondary';
								$_staleLabel = $staleStatusLabels[$_s['status']] ?? $_s['status'];
							?>
							<li class="<?= $_si < $staleCount - 1 ? 'border-bottom' : '' ?>">
								<?php if ($_staleHref): ?>
								<a href="<?= htmlspecialchars($_staleHref) ?>"
								   class="d-flex align-items-center justify-content-between px-3 py-2 text-dark text-decoration-none stale-chart-row">
								<?php else: ?>
								<div class="d-flex align-items-center justify-content-between px-3 py-2">
								<?php endif; ?>
									<div>
										<strong><?= htmlspecialchars($_s['first_name'] . ' ' . ($_s['last_name'] ?? '')) ?></strong>
										<span class="text-muted small ml-1">(<?= htmlspecialchars($_s['patient_code']) ?>)</span>
										<br>
										<small class="text-muted">Visited <?= htmlspecialchars(date('D, M j', strtotime($_s['visit_datetime']))) ?></small>
									</div>
									<div class="text-right ml-3">
										<span class="badge badge-<?= $_staleBadge ?>"><?= htmlspecialchars($_staleLabel) ?></span>
										<?php if ($_staleHref): ?>
										<br><small class="text-primary">Open &rsaquo;</small>
										<?php endif; ?>
									</div>
								<?php if ($_staleHref): ?>
								</a>
								<?php else: ?>
								</div>
								<?php endif; ?>
							</li>
							<?php endforeach; ?>
							</ul>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- ── Quick Actions ─────────────────────────────────────── -->
			<div class="row mb-4" id="quickActions">
				<?php if ($_userRole === 'DOCTOR'): ?>
				<div class="col-6 col-sm-4 col-md-2 mb-2">
					<a href="#myReviewsCard" class="qa-tile qa-primary" onclick="document.getElementById('myReviewsCard')?.scrollIntoView({behavior:'smooth'});return false;">
						<i class="fas fa-stethoscope qa-icon"></i>
						<span class="qa-label">My Reviews</span>
					</a>
				</div>
				<?php else: ?>
				<div class="col-6 col-sm-4 col-md-2 mb-2">
					<a href="intake.php" class="qa-tile qa-primary">
						<i class="fas fa-clipboard-list qa-icon"></i>
						<span class="qa-label">Start Intake</span>
					</a>
				</div>
				<?php endif; ?>

				<?php if (in_array($_userRole, ['NURSE','TRIAGE_NURSE','ADMIN','SUPER_ADMIN'])): ?>
				<div class="col-6 col-sm-4 col-md-2 mb-2">
					<a href="appointments.php" class="qa-tile">
						<i class="fas fa-calendar-plus qa-icon text-primary"></i>
						<span class="qa-label">New Appointment</span>
					</a>
				</div>
				<?php endif; ?>

				<div class="col-6 col-sm-4 col-md-2 mb-2">
					<a href="#queueCard" class="qa-tile" onclick="document.getElementById('queueCard')?.scrollIntoView({behavior:'smooth'});return false;">
						<i class="fas fa-users qa-icon text-warning"></i>
						<span class="qa-label">Patient Queue</span>
					</a>
				</div>

				<div class="col-6 col-sm-4 col-md-2 mb-2">
					<a href="calendar.php" class="qa-tile">
						<i class="fas fa-calendar-alt qa-icon text-success"></i>
						<span class="qa-label">Calendar</span>
					</a>
				</div>

				<div class="col-6 col-sm-4 col-md-2 mb-2">
					<a href="messages.php" class="qa-tile">
						<i class="fas fa-envelope qa-icon text-info"></i>
						<span class="qa-label">Messages</span>
						<?php if ($unreadMessages > 0): ?>
						<span class="qa-badge"><?= $unreadMessages ?></span>
						<?php endif; ?>
					</a>
				</div>

				<div class="col-6 col-sm-4 col-md-2 mb-2">
					<span class="qa-tile qa-disabled">
						<i class="fas fa-vial qa-icon text-danger"></i>
						<span class="qa-label">Lab Results</span>
						<span class="qa-soon">Coming Soon</span>
					</span>
				</div>

				<div class="col-6 col-sm-4 col-md-2 mb-2">
					<a href="tasks.php" class="qa-tile">
						<i class="fas fa-tasks qa-icon text-secondary"></i>
						<span class="qa-label">Tasks</span>
						<?php if ($openTasks > 0): ?>
						<span class="qa-badge"><?= $openTasks ?></span>
						<?php endif; ?>
					</a>
				</div>
			</div>

			<!-- ── Today's Stats ─────────────────────────────────────── -->
			<div class="row mb-4">
				<div class="col-6 col-md-<?= $_userRole === 'DOCTOR' ? '2' : '3' ?> mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between" data-stat="total_today" role="button" style="cursor:pointer">
						<div>
							<div class="stat-num text-dark"><?= $todayStats['total_today'] ?></div>
							<div class="stat-lbl">Today's Patients</div>
						</div>
						<i class="fas fa-user-injured stat-icon text-primary"></i>
					</div>
				</div>
				<?php if ($_userRole !== 'DOCTOR'): ?>
				<div class="col-6 col-md-3 mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between" data-stat="in_progress" role="button" style="cursor:pointer">
						<div>
							<div class="stat-num text-info"><?= $todayStats['in_progress'] ?></div>
							<div class="stat-lbl">In Progress</div>
						</div>
						<i class="fas fa-hourglass-half stat-icon text-info"></i>
					</div>
				</div>
				<?php endif; ?>
				<div class="col-6 col-md-<?= $_userRole === 'DOCTOR' ? '2' : '3' ?> mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between" data-stat="ready" role="button" style="cursor:pointer">
						<div>
							<div class="stat-num text-warning"><?= $todayStats['ready'] ?></div>
							<div class="stat-lbl">Ready for Doctor</div>
						</div>
						<i class="fas fa-user-clock stat-icon text-warning"></i>
					</div>
				</div>
				<div class="col-6 col-md-<?= $_userRole === 'DOCTOR' ? '2' : '3' ?> mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between" data-stat="in_review" role="button" style="cursor:pointer">
						<div>
							<div class="stat-num text-primary"><?= $todayStats['in_review'] ?></div>
							<div class="stat-lbl">In Review</div>
						</div>
						<i class="fas fa-notes-medical stat-icon text-primary"></i>
					</div>
				</div>
				<div class="col-6 col-md-<?= $_userRole === 'DOCTOR' ? '2' : '3' ?> mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between" data-stat="closed_today" role="button" style="cursor:pointer">
						<div>
							<div class="stat-num text-success"><?= $todayStats['closed_today'] ?></div>
							<div class="stat-lbl">Closed Today</div>
						</div>
						<i class="fas fa-check-circle stat-icon text-success"></i>
					</div>
				</div>
				<?php if ($_userRole === 'DOCTOR'): ?>
				<div class="col-6 col-md-2 mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between" data-stat="my_active" role="button" style="border-color:#17a2b8;cursor:pointer">
						<div>
							<div class="stat-num text-info"><?= count($myActiveReviews) ?></div>
							<div class="stat-lbl">My Active</div>
						</div>
						<i class="fas fa-stethoscope stat-icon text-info"></i>
					</div>
				</div>
				<div class="col-6 col-md-2 mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between" data-stat="in_progress" role="button" style="cursor:pointer">
						<div>
							<div class="stat-num text-info"><?= $todayStats['in_progress'] ?></div>
							<div class="stat-lbl">In Intake</div>
						</div>
						<i class="fas fa-hourglass-half stat-icon text-info"></i>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<!-- ── Stat Detail Modal ────────────────────────────────── -->
			<div class="modal fade" id="statDetailModal" tabindex="-1" role="dialog" aria-labelledby="statDetailModalLabel" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title" id="statDetailModalLabel">Patients</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						</div>
						<div class="modal-body p-0">
							<ul id="statDetailList" class="list-unstyled mb-0"></ul>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
						</div>
					</div>
				</div>
			</div>

			<!-- ── Appointment Action Modal ──────────────────────── -->
			<div class="modal fade" id="apptActionModal" tabindex="-1" role="dialog" aria-labelledby="apptActionModalLabel" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
						<div class="modal-header bg-primary text-white py-2">
							<h5 class="modal-title mb-0" id="apptActionModalLabel">
								<i class="fas fa-user-circle mr-2"></i><span id="aamPatientName"></span>
							</h5>
							<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<p class="text-muted small mb-3" id="aamInfo"></p>

							<!-- Start Intake -->
							<a id="aamStartIntakeBtn" href="#" class="btn btn-primary btn-block mb-2">
								<i class="fas fa-clipboard-list mr-2"></i>Start Intake
							</a>

							<hr class="my-3" />

							<!-- Cancel -->
							<h6 class="text-danger mb-2"><i class="fas fa-times-circle mr-1"></i>Cancel Appointment</h6>
							<textarea id="aamCancelNote" class="form-control form-control-sm mb-2" rows="2"
							          placeholder="Reason for cancellation (optional)…"></textarea>
							<button type="button" id="aamCancelBtn" class="btn btn-sm btn-outline-danger">
								<i class="fas fa-ban mr-1"></i>Confirm Cancellation
							</button>

							<hr class="my-3" />

							<!-- Reschedule -->
							<h6 class="text-warning mb-2"><i class="fas fa-calendar-alt mr-1"></i>Reschedule Appointment</h6>
							<div class="form-row mb-2">
								<div class="col-7">
									<label class="small text-muted mb-1">New Date <span class="text-danger">*</span></label>
									<input type="date" id="aamReschedDate" class="form-control form-control-sm" />
								</div>
								<div class="col-5">
									<label class="small text-muted mb-1">New Time (optional)</label>
									<input type="time" id="aamReschedTime" class="form-control form-control-sm" />
								</div>
							</div>
							<textarea id="aamReschedNote" class="form-control form-control-sm mb-2" rows="2"
							          placeholder="Note (optional)…"></textarea>
							<button type="button" id="aamReschedBtn" class="btn btn-sm btn-outline-warning">
								<i class="fas fa-calendar-check mr-1"></i>Confirm Reschedule
							</button>

							<div id="aamAlert" class="alert d-none mt-3 mb-0"></div>
						</div>
					</div>
				</div>
			</div>


			<!-- ── Today's Appointments ───────────────────────────────── -->
			<?php if (!empty($todayScheduledAppts)): ?>
			<div class="card card-outline card-primary mb-4" id="todayApptsCard">
				<div class="card-header d-flex align-items-center justify-content-between">
					<h3 class="card-title mb-0">
						<i class="fas fa-calendar-day mr-2"></i>Today's Appointments
						<span class="badge badge-primary ml-2"><?= count($todayScheduledAppts) ?></span>
					</h3>
					<a href="appointments.php" class="btn btn-sm btn-outline-primary">
						<i class="fas fa-calendar-alt mr-1"></i>All Appointments
					</a>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive">
						<table class="table table-hover table-sm mb-0">
							<thead class="thead-light">
								<tr>
									<th style="width:80px">Time</th>
									<th>Patient</th>
									<th class="d-none d-md-table-cell">Doctor</th>
									<th class="d-none d-lg-table-cell">Chief Complaint</th>
									<th style="width:120px">Status</th>
									<th></th>
								</tr>
							</thead>
							<tbody id="todayApptsTbody">
							<?php
							$_isNurseOrAdmin = in_array($_userRole, ['NURSE','TRIAGE_NURSE','ADMIN','SUPER_ADMIN']);
							$_apptBadges = ['SCHEDULED'=>'badge-info','CONFIRMED'=>'badge-primary','IN_PROGRESS'=>'badge-warning'];
							$_apptLabels = ['SCHEDULED'=>'Scheduled','CONFIRMED'=>'Confirmed','IN_PROGRESS'=>'In Progress'];
							foreach ($todayScheduledAppts as $_ta):
								$_taName       = htmlspecialchars($_ta['first_name'] . ' ' . ($_ta['last_name'] ?? ''));
								$_taCode       = htmlspecialchars($_ta['patient_code']);
								$_taDoc        = htmlspecialchars('Dr. ' . $_ta['doc_first'] . ' ' . $_ta['doc_last']);
								$_taTime       = $_ta['scheduled_time'] ? htmlspecialchars(date('g:i A', strtotime($_ta['scheduled_time']))) : '';
								$_taCsStatus   = $_ta['cs_status'] ?? '';
								$_taIntakeIncomplete = in_array($_taCsStatus, ['SCHEDULED', 'INTAKE_IN_PROGRESS']);
								if ($_userRole === 'DOCTOR' && $_taCsStatus === 'INTAKE_COMPLETE') {
									$_taStatusBadge = '<span class="badge badge-success">Ready for Review</span>';
								} else {
									$_taStatusBadge = '<span class="badge ' . ($_apptBadges[$_ta['appt_status']] ?? 'badge-secondary') . '">'
										. ($_apptLabels[$_ta['appt_status']] ?? htmlspecialchars($_ta['appt_status'])) . '</span>';
								}
							?>
							<tr data-appt-id="<?= (int)$_ta['appointment_id'] ?>">
								<td class="text-nowrap small text-muted"><?= $_taTime ?: '<span class="text-muted">&mdash;</span>' ?></td>
								<td>
									<a href="patients.php?action=view&id=<?= (int)$_ta['patient_id'] ?>" class="font-weight-bold text-primary">
										<?= $_taName ?>
									</a>
									<br><small class="text-muted"><?= $_taCode ?><?= $_ta['age_years'] ? ' &middot; ' . (int)$_ta['age_years'] . 'y' : '' ?></small>
								</td>
								<td class="small d-none d-md-table-cell"><?= $_taDoc ?></td>
								<td class="small d-none d-lg-table-cell text-muted"><?= htmlspecialchars($_ta['chief_complaint'] ?? '—') ?></td>
								<td><?= $_taStatusBadge ?></td>
								<td class="text-right text-nowrap">
									<?php if ($_userRole === 'DOCTOR' && $_taCsStatus === 'INTAKE_COMPLETE'): ?>
										<form method="post" action="intake.php?action=claim" style="display:inline">
											<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
											<input type="hidden" name="case_sheet_id" value="<?= (int)$_ta['case_sheet_id'] ?>">
											<button type="submit" class="btn btn-sm btn-success mr-1"><i class="fas fa-stethoscope mr-1"></i>Review</button>
										</form>
									<?php elseif ($_isNurseOrAdmin && $_taIntakeIncomplete): ?>
										<a href="intake.php?case_sheet_id=<?= (int)$_ta['case_sheet_id'] ?>" class="btn btn-sm btn-warning mr-1">
											<i class="fas fa-pencil-alt mr-1"></i><?= $_taCsStatus === 'INTAKE_IN_PROGRESS' ? 'Continue' : 'Start' ?> Intake
										</a>
										<button type="button" class="btn btn-sm btn-danger no-show-btn mr-1"
										        data-appointment-id="<?= (int)$_ta['appointment_id'] ?>"
										        data-case-sheet-id="<?= (int)$_ta['case_sheet_id'] ?>"
										        data-patient-name="<?= $_taName ?>">
											<i class="fas fa-user-times mr-1"></i>No Show
										</button>
									<?php endif; ?>
									<button type="button" class="btn btn-sm btn-outline-warning mr-1 taReschedBtn"
									        data-appt-id="<?= (int)$_ta['appointment_id'] ?>"
									        data-patient-name="<?= $_taName ?>"
									        data-patient-code="<?= $_taCode ?>"
									        data-doc-name="<?= $_taDoc ?>"
									        data-scheduled-time="<?= $_taTime ?>"
									        data-case-sheet-id="<?= (int)$_ta['case_sheet_id'] ?>"
									        title="Reschedule"><i class="fas fa-calendar-alt"></i></button>
									<button type="button" class="btn btn-sm btn-outline-danger taCancelBtn"
									        data-appt-id="<?= (int)$_ta['appointment_id'] ?>"
									        data-patient-name="<?= $_taName ?>"
									        data-patient-code="<?= $_taCode ?>"
									        data-doc-name="<?= $_taDoc ?>"
									        data-scheduled-time="<?= $_taTime ?>"
									        data-case-sheet-id="<?= (int)$_ta['case_sheet_id'] ?>"
									        title="Cancel"><i class="fas fa-times"></i></button>
								</td>
							</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<?php endif; ?>
			<!-- ── Bottom two-column section ──────────────────────────── -->

						<!-- ── Live Patient Queue ────────────────────────────────── -->
			<div class="card card-outline card-warning mb-4" id="queueCard">
				<div class="card-header d-flex align-items-center justify-content-between">
					<h3 class="card-title mb-0">
						<i class="fas fa-users mr-2"></i>
						<?= $_userRole === 'DOCTOR' ? 'Patients Ready for Review' : 'Patient Queue' ?>
						<span class="badge badge-warning ml-2" id="queueCount">…</span>
					</h3>
					<div class="d-flex align-items-center">
						<span id="queueLastUpdated" class="mr-3"></span>
						<span class="text-muted small mr-3 d-none d-sm-inline" title="Drag rows to re-prioritize">
							<i class="fas fa-grip-vertical mr-1"></i>Drag to prioritize
						</span>
						<button class="btn btn-sm btn-outline-secondary" id="queueRefreshBtn" title="Refresh now">
							<i class="fas fa-sync-alt"></i>
						</button>
					</div>
				</div>
				<div class="card-body p-0">
					<div class="table-responsive">
						<table class="table table-hover table-striped mb-0">
							<thead>
								<tr>
									<th style="width:30px"></th>
									<th style="width:32px">#</th>
									<th>Patient</th>
									<th>Status</th>
									<th>Visit Type</th>
									<th>Chief Complaint</th>
									<th>Time</th>
									<th class="d-none d-md-table-cell">Intake By</th>
									<th></th>
								</tr>
							</thead>
							<tbody id="queueTbody">
								<tr><td colspan="9" class="queue-empty text-center">
									<i class="fas fa-spinner fa-spin mr-1"></i> Loading queue…
								</td></tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>


			<!-- ── Doctor: My Active Reviews ─────────────────────────── -->
			<?php if ($_userRole === 'DOCTOR'): ?>
			<div class="card card-outline card-info mb-4" id="myReviewsCard">
				<div class="card-header d-flex align-items-center">
					<h3 class="card-title mb-0">
						<i class="fas fa-stethoscope mr-2"></i>My Active Reviews
						<span class="badge badge-info ml-2"><?= count($myActiveReviews) ?></span>
					</h3>
				</div>
				<div class="card-body p-0">
					<?php if (empty($myActiveReviews)): ?>
					<p class="text-muted m-3"><i class="fas fa-check-circle text-success mr-1"></i>No active reviews — all caught up.</p>
					<?php else: ?>
					<div class="table-responsive">
						<table class="table table-hover table-striped mb-0">
							<thead>
								<tr>
									<th>Patient</th>
									<th>Visit Type</th>
									<th>Chief Complaint</th>
									<th>Visit Time</th>
									<th>Last Updated</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($myActiveReviews as $r): ?>
								<tr>
									<td>
										<a href="patients.php?action=view&id=<?= (int)$r['patient_id'] ?>" class="font-weight-bold text-primary"><?= htmlspecialchars($r['first_name'] . ' ' . ($r['last_name'] ?? '')) ?></a>
										<br><small class="text-muted"><?= htmlspecialchars($r['patient_code']) ?>
										<?= $r['sex'] && $r['sex'] !== 'UNKNOWN' ? ' &middot; ' . htmlspecialchars($r['sex']) : '' ?>
										<?= $r['age_years'] ? ' &middot; ' . (int)$r['age_years'] . 'y' : '' ?></small>
									</td>
									<td><span class="badge badge-secondary"><?= htmlspecialchars($r['visit_type']) ?></span></td>
									<td><?= htmlspecialchars($r['chief_complaint'] ?? '') ?></td>
									<td><small><?= date('g:i A', strtotime($r['visit_datetime'])) ?></small></td>
									<td><small class="text-muted"><?= date('M j g:i A', strtotime($r['updated_at'])) ?></small></td>
									<td>
										<a href="review.php?case_sheet_id=<?= (int)$r['case_sheet_id'] ?>" class="btn btn-sm btn-info">
											<i class="fas fa-notes-medical mr-1"></i>Continue
										</a>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>

			<div class="row">
				<!-- Upcoming calendar events (real data) -->
				<div class="col-lg-6 mb-4">
					<div class="card shadow-sm h-100">
						<div class="card-header border-0 d-flex justify-content-between align-items-center">
							<h3 class="card-title mb-0">
								<i class="fas fa-calendar-alt mr-2 text-primary"></i>Upcoming Events
							</h3>
							<a href="calendar.php" class="btn btn-sm btn-outline-primary">Full calendar</a>
						</div>
						<div class="card-body p-2">
							<div id="clinicalCalendarWidget"></div>
						</div>
					</div>
				</div>

				<!-- Right column: Alerts + Tasks (both coming soon) -->
				<div class="col-lg-6 mb-4">
					<div class="card shadow-sm mb-3">
						<div class="card-header border-0 d-flex align-items-center justify-content-between">
							<h3 class="card-title mb-0">
								<i class="fas fa-bell mr-2 text-danger"></i>Active Alerts
							</h3>
							<span class="badge badge-secondary">Coming Soon</span>
						</div>
						<div class="card-body p-0">
							<div class="coming-soon-banner">
								<i class="fas fa-bell-slash"></i>
								<span>Clinical alerts coming soon</span>
							</div>
						</div>
					</div>

					<div class="card shadow-sm">
						<div class="card-header border-0 d-flex align-items-center justify-content-between">
							<h3 class="card-title mb-0">
								<i class="fas fa-tasks mr-2 text-primary"></i>My Tasks
								<?php if ($openTasks > 0): ?>
								<span class="badge badge-warning ml-1"><?= $openTasks ?></span>
								<?php endif; ?>
							</h3>
							<a href="tasks.php" class="btn btn-sm btn-outline-primary">View all</a>
						</div>
						<div class="card-body p-3">
							<p class="text-muted mb-2 small">
								<?php if ($openTasks > 0): ?>
								You have <strong><?= $openTasks ?></strong> open task<?= $openTasks !== 1 ? 's' : '' ?>.
								<?php else: ?>
								<i class="fas fa-check-circle text-success mr-1"></i>No open tasks — all caught up.
								<?php endif; ?>
							</p>
							<a href="tasks.php?action=create" class="btn btn-sm btn-outline-secondary">
								<i class="fas fa-plus mr-1"></i>New Task
							</a>
						</div>
					</div>
				</div>
			</div>

			<?php else: ?>
			<!-- ── Non-clinical role landing ───────────────────────── -->
			<div class="row mt-2 mb-4">
				<div class="col-sm-6 col-md-3 mb-3">
					<a href="messages.php" class="card text-center p-3 shadow-sm h-100 d-flex flex-column align-items-center justify-content-center text-decoration-none">
						<i class="fas fa-envelope fa-2x text-info mb-2"></i>
						<strong>Messages</strong>
						<?php if ($unreadMessages > 0): ?>
						<span class="badge badge-danger mt-1"><?= $unreadMessages ?> unread</span>
						<?php else: ?>
						<small class="text-muted">Inbox</small>
						<?php endif; ?>
					</a>
				</div>
				<div class="col-sm-6 col-md-3 mb-3">
					<a href="tasks.php" class="card text-center p-3 shadow-sm h-100 d-flex flex-column align-items-center justify-content-center text-decoration-none">
						<i class="fas fa-tasks fa-2x text-primary mb-2"></i>
						<strong>Tasks</strong>
						<?php if ($openTasks > 0): ?>
						<span class="badge badge-warning mt-1"><?= $openTasks ?> open</span>
						<?php else: ?>
						<small class="text-muted">To-Do List</small>
						<?php endif; ?>
					</a>
				</div>
				<?php if (can($_userRole, 'feedback')): ?>
				<div class="col-sm-6 col-md-3 mb-3">
					<a href="feedback.php" class="card text-center p-3 shadow-sm h-100 d-flex flex-column align-items-center justify-content-center text-decoration-none">
						<i class="fas fa-comment-dots fa-2x text-warning mb-2"></i>
						<strong>Feedback</strong>
						<?php if ($openFeedback > 0): ?>
						<span class="badge badge-danger mt-1"><?= $openFeedback ?> open</span>
						<?php else: ?>
						<small class="text-muted">Grievances</small>
						<?php endif; ?>
					</a>
				</div>
				<?php endif; ?>
				<?php if (can($_userRole, 'assets')): ?>
				<div class="col-sm-6 col-md-3 mb-3">
					<a href="assets.php" class="card text-center p-3 shadow-sm h-100 d-flex flex-column align-items-center justify-content-center text-decoration-none">
						<i class="fas fa-boxes fa-2x text-secondary mb-2"></i>
						<strong>Assets</strong>
						<small class="text-muted">Resource Library</small>
					</a>
				</div>
				<?php endif; ?>
				<div class="col-sm-6 col-md-3 mb-3">
					<a href="profile.php" class="card text-center p-3 shadow-sm h-100 d-flex flex-column align-items-center justify-content-center text-decoration-none">
						<i class="fas fa-user-circle fa-2x text-success mb-2"></i>
						<strong>My Profile</strong>
						<small class="text-muted">Account settings</small>
					</a>
				</div>
			</div>
			<?php endif; ?>

			</div>
		</section>
	</div>

	<footer class="main-footer text-sm">
		<strong>CareSystem</strong> &middot; Designed for clarity in bright conditions.
	</footer>
</div>

<?php if (can($_userRole, 'appointments', 'W')): ?>
<!-- ── No Show Modal ─────────────────────────────────────────────── -->
<div class="modal fade" id="noShowModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">No Show — <span id="noShowPatientName"></span></h5>
				<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
			</div>
			<div class="modal-body">
				<div id="noShowAlert" class="alert d-none" role="alert"></div>
				<div id="noShowChoicePanel">
					<p class="mb-3">What would you like to do?</p>
					<div class="d-flex">
						<button type="button" class="btn btn-warning mr-2" id="noShowShowReschedule">
							<i class="fas fa-calendar-alt mr-1"></i>Reschedule
						</button>
						<button type="button" class="btn btn-danger" id="noShowCancelBtn">
							<i class="fas fa-times mr-1"></i>Cancel Appointment
						</button>
					</div>
				</div>
				<div id="noShowReschedulePanel" class="d-none mt-3">
					<hr>
					<div class="form-group">
						<label for="noShowNewDate" class="font-weight-bold">New Date <span class="text-danger">*</span></label>
						<input type="date" class="form-control" id="noShowNewDate">
					</div>
					<div class="form-group mb-0">
						<label for="noShowNewTime">New Time (optional)</label>
						<input type="time" class="form-control" id="noShowNewTime">
					</div>
					<div class="mt-3">
						<button type="button" class="btn btn-warning" id="noShowConfirmReschedule">
							<i class="fas fa-calendar-check mr-1"></i>Confirm Reschedule
						</button>
						<button type="button" class="btn btn-link" id="noShowBackBtn">Back</button>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if (can($_userRole, 'appointments', 'W')): ?>
<!-- ── Assign Doctor Modal (nurse dashboard) ─────────────────────── -->
<div class="modal fade" id="dashAssignDoctorModal" tabindex="-1" role="dialog" aria-labelledby="dashAssignDoctorTitle" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="dashAssignDoctorTitle">Assign Doctor</h5>
				<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
			</div>
			<div class="modal-body">
				<div id="dashAssignAlert" class="alert d-none" role="alert"></div>
				<p class="text-muted small mb-3">
					Assigning a doctor will move this case to <strong>Doctor Review</strong> status immediately.
				</p>
				<div class="form-group mb-0">
					<label for="dashDoctorSelect" class="font-weight-bold">Select Doctor</label>
					<select class="form-control" id="dashDoctorSelect">
						<option value="">Loading doctors…</option>
					</select>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="dashAssignConfirmBtn">
					<i class="fas fa-user-md mr-1"></i>Assign
				</button>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
'use strict';

/* ── Calendar widget ────────────────────────────────────────────── */
<?php if ($_isClinicalRole): ?>
var typeColors = {
	'MEDICAL_CAMP':        '#007bff',
	'EDUCATIONAL_SEMINAR': '#28a745',
	'TRAINING':            '#17a2b8',
	'MEETING':             '#ffc107',
	'OTHER':               '#6c757d'
};
var calEvents = <?= json_encode(array_map(function ($e) {
	return [
		'title' => $e['title'],
		'start' => $e['start_datetime'],
		'end'   => $e['end_datetime'],
		'type'  => $e['event_type'],
		'color' => '',
	];
}, $calendarEvents), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
calEvents.forEach(function (e) { e.color = typeColors[e.type] || '#6c757d'; });

var apptCalEvents = <?= json_encode(array_map(function ($a) {
	$start = $a['scheduled_date'];
	if (!empty($a['scheduled_time'])) {
		$start .= 'T' . $a['scheduled_time'];
	}
	$title = trim($a['first_name'] . ' ' . ($a['last_name'] ?? ''));
	if (!empty($a['doc_last'])) {
		$title .= ' · Dr. ' . $a['doc_last'];
	}
	return [
		'title' => $title,
		'start' => $start,
		'color' => '#6f42c1',
		'type'  => 'APPOINTMENT',
		'url'   => 'appointments.php',
	];
}, $calendarAppts), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
calEvents = calEvents.concat(apptCalEvents);

document.addEventListener('DOMContentLoaded', function () {
	var calEl = document.getElementById('clinicalCalendarWidget');
	if (!calEl) return;
	new FullCalendar.Calendar(calEl, {
		initialView:   'listWeek',
		headerToolbar: { left: 'prev,next', center: 'title', right: '' },
		events:        calEvents,
		height:        280,
		noEventsText:  'No upcoming events'
	}).render();
});

/* ── Stat tile popup ────────────────────────────────────────────── */
var STAT_PATIENTS = <?= json_encode($statPatients ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?>;
var STAT_LABELS = {
	total_today:  "Today's Patients",
	in_progress:  'In Progress / In Intake',
	ready:        'Ready for Doctor',
	in_review:    'In Review',
	closed_today: 'Closed Today',
	my_active:    'My Active Reviews'
};
document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.stat-card[data-stat]').forEach(function (card) {
		card.addEventListener('click', function () {
			var key      = card.dataset.stat;
			var patients = STAT_PATIENTS[key] || [];
			var label    = STAT_LABELS[key] || key;
			document.getElementById('statDetailModalLabel').textContent = label + ' (' + patients.length + ')';
			var list = document.getElementById('statDetailList');
			if (patients.length === 0) {
				list.innerHTML = '<li class="px-3 py-2 text-muted">No patients.</li>';
			} else {
				list.innerHTML = patients.map(function (p, i) {
					var href = '#';
					if (p.case_sheet_id) {
						if (p.cs_status === 'DOCTOR_REVIEW') {
							href = 'review.php?case_sheet_id=' + p.case_sheet_id;
						} else if (p.cs_status === 'INTAKE_IN_PROGRESS' || p.cs_status === 'INTAKE_COMPLETE') {
							href = 'intake.php?case_sheet_id=' + p.case_sheet_id;
						} else if (p.patient_id) {
							href = 'patients.php?action=view&patient_id=' + p.patient_id;
						}
					} else if (p.patient_id) {
						href = 'patients.php?action=view&patient_id=' + p.patient_id;
					}
					return '<li class="px-3 py-2' + (i < patients.length - 1 ? ' border-bottom' : '') + '">' +
						'<a href="' + href + '" class="d-flex justify-content-between align-items-center text-decoration-none text-dark">' +
						'<span><strong>' + p.first_name + ' ' + p.last_name + '</strong></span>' +
						'<small class="text-muted ml-2">' + p.patient_code + '</small>' +
						'</a></li>';
				}).join('');
			}
			$('#statDetailModal').modal('show');
		});
	});
});

/* ── Appointment action modal ─────────────────────────────────────── */
var CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
var currentApptId = null;

$(document).on('click', '.taReschedBtn, .taCancelBtn', function () {
	currentApptId = parseInt($(this).data('appt-id'));
	var caseSheetId = parseInt($(this).data('case-sheet-id'));
	var patientName = $(this).data('patient-name');
	var patientCode = $(this).data('patient-code');
	var docName     = $(this).data('doc-name');
	var time        = $(this).data('scheduled-time') || '—';
	$('#aamPatientName').text(patientName);
	$('#aamInfo').html(patientCode + ' &middot; ' + time + ' &middot; ' + docName);
	$('#aamStartIntakeBtn').attr('href', 'intake.php?case_sheet_id=' + caseSheetId);
	$('#aamCancelNote, #aamReschedNote').val('');
	$('#aamReschedDate, #aamReschedTime').val('');
	$('#aamAlert').addClass('d-none').empty();
	$('#apptActionModal').modal('show');
});

$('#aamCancelBtn').on('click', function () {
	if (!currentApptId) return;
	var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Cancelling…');
	$.ajax({
		url: 'appointments.php?action=cancel',
		method: 'POST',
		contentType: 'application/json',
		data: JSON.stringify({
			csrf_token:     CSRF_TOKEN,
			appointment_id: currentApptId,
			note:           $('#aamCancelNote').val().trim() || null
		}),
		dataType: 'json',
		success: function (data) {
			if (data.success) {
				$('#aamAlert').attr('class', 'alert alert-success').text('Appointment cancelled.').removeClass('d-none');
				$('[data-appt-id="' + currentApptId + '"]').closest('tr').fadeOut(400, function () { $(this).remove(); });
				setTimeout(function () { $('#apptActionModal').modal('hide'); }, 1400);
			} else {
				$('#aamAlert').attr('class', 'alert alert-danger').text(data.message || 'Could not cancel.').removeClass('d-none');
			}
		},
		error: function () {
			$('#aamAlert').attr('class', 'alert alert-danger').text('A network error occurred.').removeClass('d-none');
		},
		complete: function () { $btn.prop('disabled', false).html('<i class="fas fa-ban mr-1"></i>Confirm Cancellation'); }
	});
});

$('#aamReschedBtn').on('click', function () {
	if (!currentApptId) return;
	var newDate = $('#aamReschedDate').val();
	if (!newDate) {
		$('#aamAlert').attr('class', 'alert alert-warning').text('Please select a new date.').removeClass('d-none');
		return;
	}
	var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Rescheduling…');
	$.ajax({
		url: 'appointments.php?action=reschedule',
		method: 'POST',
		contentType: 'application/json',
		data: JSON.stringify({
			csrf_token:     CSRF_TOKEN,
			appointment_id: currentApptId,
			scheduled_date: newDate,
			scheduled_time: $('#aamReschedTime').val() || null,
			note:           $('#aamReschedNote').val().trim() || null
		}),
		dataType: 'json',
		success: function (data) {
			if (data.success) {
				$('#aamAlert').attr('class', 'alert alert-success').text('Appointment rescheduled to ' + data.new_date_fmt + '.').removeClass('d-none');
				$('[data-appt-id="' + currentApptId + '"]').closest('tr').fadeOut(400, function () { $(this).remove(); });
				setTimeout(function () { $('#apptActionModal').modal('hide'); }, 1400);
			} else {
				$('#aamAlert').attr('class', 'alert alert-danger').text(data.message || 'Could not reschedule.').removeClass('d-none');
			}
		},
		error: function () {
			$('#aamAlert').attr('class', 'alert alert-danger').text('A network error occurred.').removeClass('d-none');
		},
		complete: function () { $btn.prop('disabled', false).html('<i class="fas fa-calendar-check mr-1"></i>Confirm Reschedule'); }
	});
});

/* ── Live patient queue ─────────────────────────────────────────── */
var USER_ROLE  = <?= json_encode($_userRole) ?>;
var POLL_MS    = 15000;
var pollTimer  = null;
var sortable   = null;

function statusBadge(status) {
	if (status === 'INTAKE_IN_PROGRESS') return '<span class="badge badge-status-in-progress text-white">In Progress</span>';
	if (status === 'INTAKE_COMPLETE')    return '<span class="badge badge-status-complete text-white">Ready</span>';
	if (status === 'SCHEDULED')          return '<span class="badge badge-success text-white">Scheduled</span>';
	if (status === 'DOCTOR_REVIEW')      return '<span class="badge badge-primary text-white">In Review</span>';
	return '<span class="badge badge-secondary">' + status + '</span>';
}

function actionCell(row) {
	if (USER_ROLE === 'DOCTOR' && row.status === 'INTAKE_COMPLETE') {
		return '<form method="post" action="intake.php?action=claim" style="display:inline">' +
			'<input type="hidden" name="csrf_token" value="' + CSRF_TOKEN + '">' +
			'<input type="hidden" name="case_sheet_id" value="' + row.case_sheet_id + '">' +
			'<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-stethoscope mr-1"></i>Review</button>' +
			'</form>';
	}
	if ((USER_ROLE === 'NURSE' || USER_ROLE === 'TRIAGE_NURSE') && row.status === 'INTAKE_IN_PROGRESS') {
		return '<a href="intake.php?case_sheet_id=' + row.case_sheet_id + '" class="btn btn-sm btn-warning">' +
			'<i class="fas fa-pencil-alt mr-1"></i>Continue</a>';
	}
	if ((USER_ROLE === 'NURSE' || USER_ROLE === 'TRIAGE_NURSE') && row.status === 'INTAKE_COMPLETE') {
		if (row.doctor_name) {
			// Doctor already assigned — show name with a reassign option
			return '<span class="text-success small"><i class="fas fa-user-md mr-1"></i>' +
				$('<span>').text(row.doctor_name).html() + '</span> ' +
				'<button type="button" class="btn btn-sm btn-outline-secondary dash-assign-btn ml-1" ' +
				'data-case-sheet-id="' + row.case_sheet_id + '" ' +
				'data-patient-name="' + row.patient_name + '" ' +
				'title="Reassign"><i class="fas fa-exchange-alt"></i></button>';
		}
		return '<button type="button" class="btn btn-sm btn-info dash-assign-btn" ' +
			'data-case-sheet-id="' + row.case_sheet_id + '" ' +
			'data-patient-name="' + row.patient_name + '">' +
			'<i class="fas fa-user-md mr-1"></i>Assign Doctor</button>';
	}
	return '';
}

function renderQueue(rows) {
	var tbody = document.getElementById('queueTbody');
	if (!tbody) return;
	if (!rows || rows.length === 0) {
		tbody.innerHTML = '<tr><td colspan="9" class="queue-empty text-center text-muted">' +
			'<i class="fas fa-check-circle text-success mr-1"></i>No patients in queue right now.</td></tr>';
		document.getElementById('queueCount').textContent = '0';
		if (sortable) { sortable.destroy(); sortable = null; }
		return;
	}
	document.getElementById('queueCount').textContent = rows.length;
	var html = '';
	rows.forEach(function (row, idx) {
		var meta = row.patient_code;
		if (row.sex)       meta += ' &middot; ' + row.sex;
		if (row.age_years) meta += ' &middot; ' + row.age_years + 'y';
		html += '<tr class="queue-row" data-id="' + row.case_sheet_id + '">';
		html += '<td class="queue-drag-handle"><i class="fas fa-grip-vertical"></i></td>';
		html += '<td class="text-muted">' + (idx + 1) + '</td>';
		html += '<td><a href="patients.php?action=view&id=' + row.patient_id + '" class="font-weight-bold text-primary">' + row.patient_name + '</a><br><small class="text-muted">' + meta + '</small></td>';
		html += '<td>' + statusBadge(row.status) + '</td>';
		html += '<td><span class="badge badge-info">' + (row.visit_type || '') + '</span></td>';
		html += '<td>' + (row.chief_complaint || '') + '</td>';
		html += '<td><small>' + row.visit_time + '</small></td>';
		html += '<td class="d-none d-md-table-cell"><small>' + (row.intake_by || '') + '</small></td>';
		html += '<td>' + actionCell(row) + '</td>';
		html += '</tr>';
	});
	tbody.innerHTML = html;

	if (sortable) { sortable.destroy(); }
	sortable = Sortable.create(tbody, {
		handle:      '.queue-drag-handle',
		animation:   150,
		ghostClass:  'sortable-ghost',
		chosenClass: 'sortable-chosen',
		onEnd: saveOrder
	});
}

function saveOrder() {
	var positions = [];
	document.querySelectorAll('#queueTbody .queue-row').forEach(function (tr, idx) {
		positions.push({ case_sheet_id: parseInt(tr.dataset.id), position: idx + 1 });
		var cells = tr.querySelectorAll('td');
		if (cells[1]) cells[1].textContent = idx + 1;
	});
	fetch('queue_reorder.php', {
		method:  'POST',
		headers: { 'Content-Type': 'application/json' },
		body:    JSON.stringify({ csrf_token: CSRF_TOKEN, positions: positions })
	}).catch(function (e) { console.error('Queue reorder failed:', e); });
}

function pollQueue() {
	fetch('queue_poll.php')
		.then(function (r) { return r.json(); })
		.then(function (data) {
			if (data.success) {
				renderQueue(data.rows);
				var el = document.getElementById('queueLastUpdated');
				if (el) {
					var d = new Date(data.updated_at * 1000);
					el.textContent = 'Updated ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
				}
			}
		})
		.catch(function (e) { console.error('Queue poll error:', e); });
}

document.addEventListener('DOMContentLoaded', function () {
	pollQueue();
	pollTimer = setInterval(pollQueue, POLL_MS);

	var btn = document.getElementById('queueRefreshBtn');
	if (btn) {
		btn.addEventListener('click', function () {
			var icon = btn.querySelector('i');
			icon.classList.add('fa-spin');
			pollQueue();
			setTimeout(function () { icon.classList.remove('fa-spin'); }, 800);
			clearInterval(pollTimer);
			pollTimer = setInterval(pollQueue, POLL_MS);
		});
	}
});
<?php endif; ?>
	// ── No Show modal ──────────────────────────────────────
	var noShowApptId = null;

	$(document).on('click', '.no-show-btn', function () {
		noShowApptId = $(this).data('appointment-id');
		$('#noShowPatientName').text($(this).data('patient-name'));
		$('#noShowAlert').addClass('d-none').text('');
		$('#noShowChoicePanel').removeClass('d-none');
		$('#noShowReschedulePanel').addClass('d-none');
		$('#noShowNewDate, #noShowNewTime').val('');
		$('#noShowModal').modal('show');
	});

	$('#noShowShowReschedule').on('click', function () {
		$('#noShowChoicePanel').addClass('d-none');
		$('#noShowReschedulePanel').removeClass('d-none');
	});

	$('#noShowBackBtn').on('click', function () {
		$('#noShowReschedulePanel').addClass('d-none');
		$('#noShowChoicePanel').removeClass('d-none');
		$('#noShowAlert').addClass('d-none').text('');
	});

	$('#noShowConfirmReschedule').on('click', function () {
		var newDate = $('#noShowNewDate').val();
		if (!newDate) {
			$('#noShowAlert').attr('class', 'alert alert-warning').text('Please select a new date.').removeClass('d-none');
			return;
		}
		var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Saving…');
		$.ajax({
			url: 'appointments.php?action=reschedule',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				csrf_token:     CSRF_TOKEN,
				appointment_id: noShowApptId,
				scheduled_date: newDate,
				scheduled_time: $('#noShowNewTime').val() || null,
			}),
			dataType: 'json',
			success: function (r) {
				$btn.prop('disabled', false).html('<i class="fas fa-calendar-check mr-1"></i>Confirm Reschedule');
				if (r.success) {
					$('#noShowModal').modal('hide');
					window.location.reload();
				} else {
					$('#noShowAlert').attr('class', 'alert alert-danger').text(r.message || 'Reschedule failed.').removeClass('d-none');
				}
			},
			error: function () {
				$btn.prop('disabled', false).html('<i class="fas fa-calendar-check mr-1"></i>Confirm Reschedule');
				$('#noShowAlert').attr('class', 'alert alert-danger').text('A network error occurred.').removeClass('d-none');
			}
		});
	});

	$('#noShowCancelBtn').on('click', function () {
		var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Cancelling…');
		$.ajax({
			url: 'appointments.php?action=cancel',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				csrf_token:     CSRF_TOKEN,
				appointment_id: noShowApptId,
			}),
			dataType: 'json',
			success: function (r) {
				$btn.prop('disabled', false).html('<i class="fas fa-times mr-1"></i>Cancel Appointment');
				if (r.success) {
					$('#noShowModal').modal('hide');
					window.location.reload();
				} else {
					$('#noShowAlert').attr('class', 'alert alert-danger').text(r.message || 'Cancellation failed.').removeClass('d-none');
				}
			},
			error: function () {
				$btn.prop('disabled', false).html('<i class="fas fa-times mr-1"></i>Cancel Appointment');
				$('#noShowAlert').attr('class', 'alert alert-danger').text('A network error occurred.').removeClass('d-none');
			}
		});
	});

	// ── Assign Doctor from queue (nurse) ──────────────────
	var dashAssignCaseSheetId = null;
	var dashDoctorsLoaded = false;

	function dashLoadDoctors() {
		if (dashDoctorsLoaded) return;
		$.getJSON('appointments.php?action=get-doctors', function (data) {
			var $sel = $('#dashDoctorSelect').empty();
			if (!data.doctors || data.doctors.length === 0) {
				$sel.append('<option value="">No active doctors found</option>');
				return;
			}
			$sel.append('<option value="">— Select a doctor —</option>');
			data.doctors.forEach(function (d) {
				$sel.append('<option value="' + d.user_id + '">Dr. ' + $('<span>').text(d.first_name + ' ' + d.last_name).html() + '</option>');
			});
			dashDoctorsLoaded = true;
		});
	}

	$(document).on('click', '.dash-assign-btn', function () {
		dashAssignCaseSheetId = $(this).data('case-sheet-id');
		var patientName = $(this).data('patient-name');
		$('#dashAssignDoctorTitle').text('Assign Doctor — ' + patientName);
		$('#dashAssignAlert').addClass('d-none').text('');
		$('#dashDoctorSelect').val('');
		dashLoadDoctors();
		$('#dashAssignDoctorModal').modal('show');
	});

	$('#dashAssignConfirmBtn').on('click', function () {
		var doctorId = $('#dashDoctorSelect').val();
		if (!doctorId) {
			$('#dashAssignAlert').attr('class', 'alert alert-warning').text('Please select a doctor.').removeClass('d-none');
			return;
		}
		var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Assigning…');
		$.ajax({
			url: 'appointments.php?action=assign-doctor',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				csrf_token:     CSRF_TOKEN,
				case_sheet_id:  dashAssignCaseSheetId,
				doctor_user_id: parseInt(doctorId),
			}),
			dataType: 'json',
			success: function (r) {
				$btn.prop('disabled', false).html('<i class="fas fa-user-md mr-1"></i>Assign');
				if (r.success) {
					$('#dashAssignAlert').attr('class', 'alert alert-success').text(r.message).removeClass('d-none');
					setTimeout(function () {
						$('#dashAssignDoctorModal').modal('hide');
						pollQueue();
					}, 1200);
				} else {
					$('#dashAssignAlert').attr('class', 'alert alert-danger').text(r.message || 'Assignment failed.').removeClass('d-none');
				}
			},
			error: function () {
				$btn.prop('disabled', false).html('<i class="fas fa-user-md mr-1"></i>Assign');
				$('#dashAssignAlert').attr('class', 'alert alert-danger').text('A network error occurred.').removeClass('d-none');
			}
		});
	});

}());
</script>
</body>
</html>
