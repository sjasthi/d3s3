<?php
require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/config/database.php';

$pdo = getDBConnection();
$calendarEvents = $pdo->query(
	'SELECT event_id, title, description, event_type, start_datetime, end_datetime, status, location_name
	   FROM events WHERE is_active = 1 ORDER BY start_datetime'
)->fetchAll();

$_userRole = $_SESSION['user_role'] ?? '';
$_isClinicalRole = in_array($_userRole, ['DOCTOR', 'TRIAGE_NURSE', 'NURSE'], true);

// Doctor's active reviews (cases already claimed by this doctor)
$myActiveReviews = [];
if ($_userRole === 'DOCTOR') {
	$stmt = $pdo->prepare(
		'SELECT cs.case_sheet_id, cs.patient_id, cs.visit_type, cs.chief_complaint,
		        cs.visit_datetime, cs.updated_at,
		        p.first_name, p.last_name, p.patient_code, p.sex, p.age_years
		   FROM case_sheets cs
		   JOIN patients p ON p.patient_id = cs.patient_id
		  WHERE cs.status = ?
		    AND cs.assigned_doctor_user_id = ?
		  ORDER BY cs.updated_at DESC'
	);
	$stmt->execute(['DOCTOR_REVIEW', $_SESSION['user_id']]);
	$myActiveReviews = $stmt->fetchAll();
}
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
		/* Queue card */
		.queue-row { cursor: grab; user-select: none; }
		.queue-row:active { cursor: grabbing; }
		.queue-row.sortable-ghost { opacity: 0.4; background: #e9ecef !important; }
		.queue-row.sortable-chosen { background: #fff3cd !important; }
		.queue-drag-handle { color: #adb5bd; cursor: grab; padding: 0 8px; font-size: 1.1rem; }
		.queue-drag-handle:hover { color: #495057; }
		.badge-status-in-progress { background-color: #17a2b8; }
		.badge-status-complete    { background-color: #fd7e14; }
		#queueLastUpdated { font-size: 0.75rem; color: #adb5bd; }
		.queue-empty { padding: 1rem 1.25rem; color: #6c757d; }
	</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed<?= ($_SESSION['font_size'] ?? 'normal') === 'large' ? ' font-size-large' : '' ?>"
      data-theme-server="<?= htmlspecialchars($_SESSION['theme'] ?? 'system') ?>">
<div class="wrapper">
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
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2 align-items-center">
					<div class="col-sm-8">
						<h1 class="m-0 text-dark">Clinical Workspace</h1>
						<p class="text-muted mb-0">Fast overview of patients, visits, and labs.</p>
					</div>
					<div class="col-sm-4 text-sm-right pt-3 pt-sm-0">
						<button class="btn btn-outline-primary btn-sm mr-2"><i class="fas fa-user-md mr-1"></i>On-call</button>
						<button class="btn btn-outline-secondary btn-sm"><i class="fas fa-bell mr-1"></i>Alerts</button>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

			<?php if ($_isClinicalRole): ?>
			<!-- ── Patient Queue (live, all clinical roles) ──────────────── -->
			<div class="card card-outline card-warning mb-4" id="queueCard">
				<div class="card-header d-flex align-items-center justify-content-between">
					<h3 class="card-title mb-0">
						<i class="fas fa-users mr-2"></i>
						<?= $_userRole === 'DOCTOR' ? 'Patients Ready for Review' : 'Patient Queue' ?>
						<span class="badge badge-warning ml-2" id="queueCount">…</span>
					</h3>
					<div class="d-flex align-items-center">
						<span id="queueLastUpdated" class="mr-3"></span>
						<span class="text-muted small mr-3" title="Drag rows to re-prioritize">
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
									<th>#</th>
									<th>Patient</th>
									<th>Status</th>
									<th>Visit Type</th>
									<th>Chief Complaint</th>
									<th>Time</th>
									<th>Intake By</th>
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
			<?php endif; ?>

			<?php if ($_userRole === 'DOCTOR' && !empty($myActiveReviews)): ?>
			<!-- ── Doctor: My Active Reviews ─────────────────────────────── -->
			<div class="card card-outline card-info mb-4">
				<div class="card-header d-flex align-items-center">
					<h3 class="card-title mb-0">
						<i class="fas fa-stethoscope mr-2"></i>My Active Reviews
						<span class="badge badge-info ml-2"><?= count($myActiveReviews) ?></span>
					</h3>
				</div>
				<div class="card-body p-0">
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
				</div>
			</div>
			<?php endif; ?>

				<div class="row">
					<div class="col-lg-7">
						<div class="card card-outline card-primary hero-card">
							<div class="card-body d-flex flex-column flex-md-row align-items-start align-items-md-center">
								<div class="hero-icon text-primary mr-md-4 mb-3 mb-md-0">
									<i class="fas fa-stethoscope"></i>
								</div>
								<div>
									<h3 class="card-title mb-1">Today at a glance</h3>
									<p class="mb-2 text-muted">Keep patient flow smooth, surface critical alerts, and stay coordinated across the care team.</p>
									<div class="d-flex flex-wrap">
										<span class="badge badge-primary mr-2 mb-2">Rounds</span>
										<span class="badge badge-secondary mr-2 mb-2">Lab watch</span>
										<span class="badge badge-light mr-2 mb-2">Med safety</span>
									</div>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-sm-6 mb-3">
								<div class="small-box bg-primary-light">
									<div class="inner">
										<h3>14</h3>
										<p>Patients in queue</p>
									</div>
									<div class="icon"><i class="fas fa-user-injured"></i></div>
									<a href="#" class="small-box-footer">View list <i class="fas fa-arrow-circle-right"></i></a>
								</div>
							</div>
							<div class="col-sm-6 mb-3">
								<div class="small-box bg-accent-light">
									<div class="inner">
										<h3>6</h3>
										<p>Labs to review</p>
									</div>
									<div class="icon"><i class="fas fa-vial"></i></div>
									<a href="#" class="small-box-footer">Open labs <i class="fas fa-arrow-circle-right"></i></a>
								</div>
							</div>
						</div>

						<div class="card shadow-sm">
							<div class="card-header border-0 d-flex align-items-center justify-content-between">
								<h3 class="card-title mb-0"><i class="fas fa-clipboard-list mr-2 text-primary"></i>Quick tasks</h3>
								<button class="btn btn-link btn-sm text-primary">See all</button>
							</div>
							<div class="card-body p-0">
								<ul class="list-group list-group-flush">
									<li class="list-group-item d-flex align-items-center">
										<span class="status-dot bg-success mr-3"></span>
										Review today's discharges
									</li>
									<li class="list-group-item d-flex align-items-center">
										<span class="status-dot bg-warning mr-3"></span>
										Verify medication changes
									</li>
									<li class="list-group-item d-flex align-items-center">
										<span class="status-dot bg-info mr-3"></span>
										Sign off imaging reports
									</li>
								</ul>
							</div>
						</div>
					</div>

					<div class="col-lg-5">
						<div class="card shadow-sm mb-3">
							<div class="card-header border-0">
								<h3 class="card-title mb-0"><i class="fas fa-heartbeat mr-2 text-primary"></i>Active alerts</h3>
							</div>
							<div class="card-body">
								<div class="alert alert-danger mb-3" role="alert">
									<strong>Critical:</strong> Potassium 6.2 mmol/L flagged for review.
								</div>
								<div class="alert alert-warning mb-0" role="alert">
									<strong>Reminder:</strong> Dr. Patel needs sign-off on MRI results.
								</div>
							</div>
						</div>

						<div class="card shadow-sm">
							<div class="card-header border-0 d-flex justify-content-between align-items-center">
								<h3 class="card-title mb-0"><i class="fas fa-calendar-alt mr-2 text-primary"></i>Upcoming</h3>
								<a href="calendar.php" class="btn btn-sm btn-outline-primary">View full calendar</a>
							</div>
							<div class="card-body p-2">
								<div id="clinicalCalendarWidget"></div>
							</div>
						</div>
					</div>
				</div>
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
<!-- SortableJS for drag-to-reorder -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
	/* ── Calendar ───────────────────────────────────────────────── */
	var typeColors = {
		'MEDICAL_CAMP': '#007bff',
		'EDUCATIONAL_SEMINAR': '#28a745',
		'TRAINING': '#17a2b8',
		'MEETING': '#ffc107',
		'OTHER': '#6c757d'
	};

	var events = <?= json_encode(array_map(function ($e) {
		return [
			'title' => $e['title'],
			'start' => $e['start_datetime'],
			'end'   => $e['end_datetime'],
			'color' => '',
			'type'  => $e['event_type'],
		];
	}, $calendarEvents), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

	events.forEach(function (e) {
		e.color = typeColors[e.type] || '#6c757d';
	});

	document.addEventListener('DOMContentLoaded', function () {
		var calEl = document.getElementById('clinicalCalendarWidget');
		if (calEl) {
			var cal = new FullCalendar.Calendar(calEl, {
				initialView: 'listWeek',
				headerToolbar: { left: 'prev,next', center: 'title', right: '' },
				events: events,
				height: 300,
				noEventsText: 'No upcoming events'
			});
			cal.render();
		}
	});

	/* ── Live Patient Queue ─────────────────────────────────────── */
	<?php if ($_isClinicalRole): ?>
	var CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
	var USER_ROLE  = <?= json_encode($_userRole) ?>;
	var POLL_MS    = 15000; // refresh every 15 seconds
	var pollTimer  = null;
	var sortable   = null;
	var queueData  = [];

	function statusBadge(status) {
		if (status === 'INTAKE_IN_PROGRESS') {
			return '<span class="badge badge-status-in-progress text-white">In Progress</span>';
		}
		if (status === 'INTAKE_COMPLETE') {
			return '<span class="badge badge-status-complete text-white">Ready</span>';
		}
		return '<span class="badge badge-secondary">' + status + '</span>';
	}

	function actionCell(row) {
		if (USER_ROLE === 'DOCTOR' && row.status === 'INTAKE_COMPLETE') {
			return '<form method="post" action="intake.php?action=claim" style="display:inline">' +
				'<input type="hidden" name="csrf_token" value="' + CSRF_TOKEN + '">' +
				'<input type="hidden" name="case_sheet_id" value="' + row.case_sheet_id + '">' +
				'<button type="submit" class="btn btn-sm btn-primary">' +
				'<i class="fas fa-stethoscope mr-1"></i>Review</button>' +
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
			// Destroy sortable if empty
			if (sortable) { sortable.destroy(); sortable = null; }
			return;
		}

		document.getElementById('queueCount').textContent = rows.length;

		var html = '';
		rows.forEach(function (row, idx) {
			var patientMeta = row.patient_code;
			if (row.sex)      patientMeta += ' &middot; ' + row.sex;
			if (row.age_years) patientMeta += ' &middot; ' + row.age_years + 'y';

			html += '<tr class="queue-row" data-id="' + row.case_sheet_id + '" data-pos="' + (row.queue_position || (idx + 1)) + '">';
			html += '<td class="queue-drag-handle"><i class="fas fa-grip-vertical"></i></td>';
			html += '<td class="text-muted">' + (idx + 1) + '</td>';
			html += '<td><strong>' + row.patient_name + '</strong><br><small class="text-muted">' + patientMeta + '</small></td>';
			html += '<td>' + statusBadge(row.status) + '</td>';
			html += '<td><span class="badge badge-info">' + (row.visit_type || '') + '</span></td>';
			html += '<td>' + (row.chief_complaint || '') + '</td>';
			html += '<td><small>' + row.visit_time + '</small></td>';
			html += '<td><small>' + (row.intake_by || '') + '</small></td>';
			html += '<td>' + actionCell(row) + '</td>';
			html += '</tr>';
		});
		tbody.innerHTML = html;

		// Reinitialize SortableJS
		if (sortable) { sortable.destroy(); }
		sortable = Sortable.create(tbody, {
			handle: '.queue-drag-handle',
			animation: 150,
			ghostClass: 'sortable-ghost',
			chosenClass: 'sortable-chosen',
			onEnd: function () {
				saveOrder();
			}
		});
	}

	function saveOrder() {
		// Collect new order
		var positions = [];
		var rows = document.querySelectorAll('#queueTbody .queue-row');
		rows.forEach(function (tr, idx) {
			positions.push({ case_sheet_id: parseInt(tr.dataset.id), position: idx + 1 });
			// Update rank column
			var cells = tr.querySelectorAll('td');
			if (cells[1]) cells[1].textContent = idx + 1;
		});

		fetch('queue_reorder.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ csrf_token: CSRF_TOKEN, positions: positions })
		}).catch(function (err) {
			console.error('Queue reorder failed:', err);
		});
	}

	function updateTimestamp(ts) {
		var el = document.getElementById('queueLastUpdated');
		if (!el) return;
		var d = new Date(ts * 1000);
		el.textContent = 'Updated ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
	}

	function pollQueue() {
		fetch('queue_poll.php')
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data.success) {
					queueData = data.rows;
					renderQueue(data.rows);
					updateTimestamp(data.updated_at);
				}
			})
			.catch(function (err) {
				console.error('Queue poll error:', err);
			});
	}

	function startPolling() {
		pollQueue(); // immediate first load
		pollTimer = setInterval(pollQueue, POLL_MS);
	}

	document.addEventListener('DOMContentLoaded', function () {
		startPolling();

		var btn = document.getElementById('queueRefreshBtn');
		if (btn) {
			btn.addEventListener('click', function () {
				// Spin the icon briefly
				var icon = btn.querySelector('i');
				icon.classList.add('fa-spin');
				pollQueue();
				setTimeout(function () { icon.classList.remove('fa-spin'); }, 800);

				// Reset the auto-poll timer
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
