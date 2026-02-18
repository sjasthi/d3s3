<?php
require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/config/database.php';

$pdo = getDBConnection();

$_userRole = $_SESSION['user_role'] ?? '';
$_isClinicalRole = in_array($_userRole, ['DOCTOR', 'TRIAGE_NURSE', 'NURSE'], true);

// ── Data for clinical roles ──────────────────────────────────────────────────
$todayStats     = ['total_today' => 0, 'in_progress' => 0, 'ready' => 0, 'in_review' => 0, 'closed_today' => 0];
$myActiveReviews = [];
$calendarEvents  = [];
$nextEvent       = null;

if ($_isClinicalRole) {
	// Today's queue stats
	$row = $pdo->query(
		"SELECT COUNT(*) AS total_today,
		        SUM(status = 'INTAKE_IN_PROGRESS') AS in_progress,
		        SUM(status = 'INTAKE_COMPLETE')    AS ready,
		        SUM(status = 'DOCTOR_REVIEW')      AS in_review,
		        SUM(status = 'CLOSED')             AS closed_today
		   FROM case_sheets
		  WHERE DATE(visit_datetime) = CURDATE()"
	)->fetch(PDO::FETCH_ASSOC);
	if ($row) {
		$todayStats = array_map(fn($v) => (int)($v ?? 0), $row);
	}

	// Doctor's claimed cases
	if ($_userRole === 'DOCTOR') {
		$stmt = $pdo->prepare(
			'SELECT cs.case_sheet_id, cs.visit_type, cs.chief_complaint,
			        cs.visit_datetime, cs.updated_at,
			        p.first_name, p.last_name, p.patient_code, p.sex, p.age_years
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

	// Next upcoming event
	$nextEvent = $pdo->query(
		"SELECT title, start_datetime, event_type, location_name
		   FROM events WHERE is_active = 1 AND start_datetime >= NOW()
		   ORDER BY start_datetime LIMIT 1"
	)->fetch(PDO::FETCH_ASSOC);
}

$roleLabel = [
	'DOCTOR'             => 'Doctor',
	'TRIAGE_NURSE'       => 'Triage Nurse',
	'NURSE'              => 'Nurse',
	'SUPER_ADMIN'        => 'Super Admin',
	'ADMIN'              => 'Admin',
	'DATA_ENTRY_OPERATOR'=> 'Data Entry Operator',
	'GRIEVANCE_OFFICER'  => 'Grievance Officer',
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
			<li class="nav-item d-flex align-items-center mr-3">
				<div class="custom-control custom-switch theme-switch">
					<input type="checkbox" class="custom-control-input" id="themeToggleDashboard" data-theme-toggle />
					<label class="custom-control-label" for="themeToggleDashboard">Dark mode</label>
				</div>
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
					<span class="qa-tile qa-disabled">
						<i class="fas fa-envelope qa-icon text-info"></i>
						<span class="qa-label">Messages</span>
						<span class="qa-soon">Coming Soon</span>
					</span>
				</div>

				<div class="col-6 col-sm-4 col-md-2 mb-2">
					<span class="qa-tile qa-disabled">
						<i class="fas fa-vial qa-icon text-danger"></i>
						<span class="qa-label">Lab Results</span>
						<span class="qa-soon">Coming Soon</span>
					</span>
				</div>

				<div class="col-6 col-sm-4 col-md-2 mb-2">
					<span class="qa-tile qa-disabled">
						<i class="fas fa-chart-bar qa-icon text-secondary"></i>
						<span class="qa-label">Reports</span>
						<span class="qa-soon">Coming Soon</span>
					</span>
				</div>
			</div>

			<!-- ── Today's Stats ─────────────────────────────────────── -->
			<div class="row mb-4">
				<div class="col-6 col-md-<?= $_userRole === 'DOCTOR' ? '2' : '3' ?> mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between">
						<div>
							<div class="stat-num text-dark"><?= $todayStats['total_today'] ?></div>
							<div class="stat-lbl">Today's Patients</div>
						</div>
						<i class="fas fa-user-injured stat-icon text-primary"></i>
					</div>
				</div>
				<?php if ($_userRole !== 'DOCTOR'): ?>
				<div class="col-6 col-md-3 mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between">
						<div>
							<div class="stat-num text-info"><?= $todayStats['in_progress'] ?></div>
							<div class="stat-lbl">In Progress</div>
						</div>
						<i class="fas fa-hourglass-half stat-icon text-info"></i>
					</div>
				</div>
				<?php endif; ?>
				<div class="col-6 col-md-<?= $_userRole === 'DOCTOR' ? '2' : '3' ?> mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between">
						<div>
							<div class="stat-num text-warning"><?= $todayStats['ready'] ?></div>
							<div class="stat-lbl">Ready for Doctor</div>
						</div>
						<i class="fas fa-user-clock stat-icon text-warning"></i>
					</div>
				</div>
				<div class="col-6 col-md-<?= $_userRole === 'DOCTOR' ? '2' : '3' ?> mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between">
						<div>
							<div class="stat-num text-primary"><?= $todayStats['in_review'] ?></div>
							<div class="stat-lbl">In Review</div>
						</div>
						<i class="fas fa-notes-medical stat-icon text-primary"></i>
					</div>
				</div>
				<div class="col-6 col-md-<?= $_userRole === 'DOCTOR' ? '2' : '3' ?> mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between">
						<div>
							<div class="stat-num text-success"><?= $todayStats['closed_today'] ?></div>
							<div class="stat-lbl">Closed Today</div>
						</div>
						<i class="fas fa-check-circle stat-icon text-success"></i>
					</div>
				</div>
				<?php if ($_userRole === 'DOCTOR'): ?>
				<div class="col-6 col-md-2 mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between" style="border-color:#17a2b8">
						<div>
							<div class="stat-num text-info"><?= count($myActiveReviews) ?></div>
							<div class="stat-lbl">My Active</div>
						</div>
						<i class="fas fa-stethoscope stat-icon text-info"></i>
					</div>
				</div>
				<div class="col-6 col-md-2 mb-3">
					<div class="stat-card d-flex align-items-center justify-content-between">
						<div>
							<div class="stat-num text-info"><?= $todayStats['in_progress'] ?></div>
							<div class="stat-lbl">In Intake</div>
						</div>
						<i class="fas fa-hourglass-half stat-icon text-info"></i>
					</div>
				</div>
				<?php endif; ?>
			</div>

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
										<strong><?= htmlspecialchars($r['first_name'] . ' ' . ($r['last_name'] ?? '')) ?></strong>
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

			<!-- ── Bottom two-column section ──────────────────────────── -->
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
							</h3>
							<span class="badge badge-secondary">Coming Soon</span>
						</div>
						<div class="card-body p-0">
							<div class="coming-soon-banner">
								<i class="fas fa-clipboard-check"></i>
								<span>Task management coming soon</span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php else: ?>
			<!-- ── Non-clinical role landing ───────────────────────── -->
			<div class="row justify-content-center mt-4">
				<div class="col-md-6 text-center">
					<i class="fas fa-heartbeat fa-4x text-primary mb-3"></i>
					<h3>Welcome to CareSystem</h3>
					<p class="text-muted">Use the sidebar to navigate to your tools.</p>
					<a href="profile.php" class="btn btn-outline-primary mr-2"><i class="fas fa-user-circle mr-1"></i>My Profile</a>
					<a href="settings.php" class="btn btn-outline-secondary"><i class="fas fa-cog mr-1"></i>Settings</a>
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

/* ── Live patient queue ─────────────────────────────────────────── */
var CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
var USER_ROLE  = <?= json_encode($_userRole) ?>;
var POLL_MS    = 15000;
var pollTimer  = null;
var sortable   = null;

function statusBadge(status) {
	if (status === 'INTAKE_IN_PROGRESS') return '<span class="badge badge-status-in-progress text-white">In Progress</span>';
	if (status === 'INTAKE_COMPLETE')    return '<span class="badge badge-status-complete text-white">Ready</span>';
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
		html += '<td><strong>' + row.patient_name + '</strong><br><small class="text-muted">' + meta + '</small></td>';
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
}());
</script>
</body>
</html>
