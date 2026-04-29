<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Calendar | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<link rel="stylesheet" href="assets/css/fullcalendar.min.css" />
	<style>
	/* ── FullCalendar – tablet-polished, brand-integrated ─────────────────── */

	/* Toolbar */
	.fc .fc-toolbar { gap: .5rem; padding-bottom: .75rem; flex-wrap: wrap; }
	.fc .fc-toolbar-title {
		font-size: 1.25rem; font-weight: 700; letter-spacing: -.01em;
		color: var(--text-strong);
	}
	.fc .fc-button-primary {
		background: var(--brand-primary); border-color: var(--brand-primary);
		border-radius: 8px; padding: .5rem 1rem; font-size: .875rem;
		font-weight: 500; min-height: 44px; min-width: 44px;
		box-shadow: 0 1px 4px rgba(15,143,169,.25);
		transition: background .15s, box-shadow .15s;
	}
	.fc .fc-button-primary:not(:disabled):hover,
	.fc .fc-button-primary:not(:disabled):active,
	.fc .fc-button-primary:not(:disabled).fc-button-active {
		background: var(--brand-secondary); border-color: var(--brand-secondary);
	}
	.fc .fc-button-primary:focus {
		box-shadow: 0 0 0 3px rgba(15,143,169,.3);
	}

	/* Column headers */
	.fc .fc-col-header-cell-cushion {
		font-size: .78rem; font-weight: 700; text-transform: uppercase;
		letter-spacing: .05em; color: var(--text-muted);
		padding: 10px 4px; text-decoration: none;
	}

	/* Day numbers */
	.fc .fc-daygrid-day-number {
		font-size: .95rem; font-weight: 500; padding: 6px 10px;
		color: var(--text-strong); text-decoration: none;
	}
	.fc .fc-day-other .fc-daygrid-day-number { opacity: .35; }

	/* Grid borders */
	.fc td, .fc th { border-color: var(--border-soft) !important; }
	.fc .fc-scrollgrid { border-color: var(--border-soft) !important; }

	/* Today highlight */
	.fc .fc-day-today { background: rgba(15,143,169,.07) !important; }
	.fc .fc-day-today .fc-daygrid-day-number {
		background: var(--brand-primary); color: #fff; border-radius: 50%;
		width: 32px; height: 32px; display: flex; align-items: center;
		justify-content: center; margin: 4px; padding: 0;
	}

	/* Clickable day hint (write roles only) */
	.cal-clickable-days .fc-daygrid-day:not(.fc-day-other):hover {
		background: rgba(15,143,169,.05) !important;
		cursor: pointer;
	}

	/* Event pills */
	.fc .fc-daygrid-event {
		border-radius: 6px; border: none !important; margin-bottom: 2px;
		box-shadow: 0 1px 3px rgba(0,0,0,.12);
		transition: transform .1s, box-shadow .1s;
	}
	.fc .fc-daygrid-event:active { transform: scale(.97); }
	.fc-custom-event {
		display: flex; align-items: center; gap: 5px;
		padding: 4px 7px; min-height: 26px; overflow: hidden;
	}
	.fc-custom-event i { flex-shrink: 0; font-size: .72rem; opacity: .9; }
	.fc-custom-event .fc-ev-title {
		overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
		font-size: .8rem; font-weight: 500;
	}
	.fc-custom-event .fc-ev-time {
		font-size: .72rem; opacity: .8; flex-shrink: 0;
	}
	.fc .fc-daygrid-more-link {
		font-size: .78rem; font-weight: 600; color: var(--brand-primary);
	}

	/* Now-indicator */
	.fc .fc-timegrid-now-indicator-line  { border-color: var(--brand-primary); }
	.fc .fc-timegrid-now-indicator-arrow {
		border-top-color: var(--brand-primary);
		border-bottom-color: var(--brand-primary);
	}

	/* Time grid */
	.fc .fc-timegrid-slot { height: 44px; }
	.fc .fc-timegrid-slot-label {
		font-size: .78rem; color: var(--text-muted);
		vertical-align: top; padding-top: 4px;
	}

	/* List view */
	.fc .fc-list-day-cushion { background: var(--surface) !important; padding: 10px 16px; }
	.fc .fc-list-day-text,
	.fc .fc-list-day-side-text {
		font-size: .875rem; font-weight: 700;
		color: var(--brand-primary) !important; text-decoration: none !important;
	}
	.fc .fc-list-event:hover td { background: rgba(15,143,169,.06) !important; }
	.fc .fc-list-event-dot { border-color: currentColor; }
	.fc .fc-list-event-time {
		color: var(--text-muted); font-size: .85rem;
		padding: 12px 14px; white-space: nowrap;
	}
	.fc .fc-list-event-title { font-size: .9rem; padding: 12px 14px; }
	.fc .fc-list-empty-cushion { color: var(--text-muted); font-size: .9rem; }

	/* ── Type filter bar ───────────────────────────────────── */
	.cal-filter-btn {
		border-radius: 20px; font-size: .73rem; font-weight: 600;
		padding: 3px 11px; border: 2px solid; line-height: 1.7;
		white-space: nowrap; transition: opacity .18s;
	}
	.cal-filter-btn.inactive { opacity: .32; }

	/* Dark-mode overrides */
	body.dark-mode .fc .fc-toolbar-title,
	body.dark-mode .fc .fc-daygrid-day-number  { color: var(--text-strong); }
	body.dark-mode .fc .fc-col-header-cell-cushion,
	body.dark-mode .fc .fc-timegrid-slot-label,
	body.dark-mode .fc .fc-list-event-time     { color: var(--text-muted); }
	body.dark-mode .fc td,
	body.dark-mode .fc th,
	body.dark-mode .fc .fc-scrollgrid          { border-color: var(--border-soft) !important; }
	body.dark-mode .fc .fc-day-today           { background: rgba(15,143,169,.14) !important; }
	body.dark-mode .fc .fc-list-day-cushion    { background: var(--surface-card) !important; }
	body.dark-mode .fc-list-event td           { background: var(--surface-card); border-color: var(--border-soft); }
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
			<li class="nav-item d-flex align-items-center">
				<button id="gearBtn" aria-label="Display settings" title="Display settings">
					<i class="fas fa-cog fa-lg"></i>
				</button>
			</li>
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

	<?php require __DIR__ . '/_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-12">
						<h1 class="m-0 text-dark"><i class="fas fa-calendar-alt mr-2"></i>Calendar</h1>
						<p class="text-muted mb-0">View upcoming events and activities.</p>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">
				<div class="card shadow-sm">
					<div class="card-header border-0 py-2">
						<div class="d-flex flex-wrap align-items-center" style="gap:.4rem;">
							<div id="calFilterBar" class="d-flex flex-wrap" style="gap:.35rem;">
								<small class="text-muted align-self-center mr-1" style="white-space:nowrap;">Show:</small>
								<button type="button" class="btn cal-filter-btn"
								        data-type="MEDICAL_CAMP" data-color="#007bff"
								        style="background:#007bff;border-color:#007bff;color:#fff;">
									<i class="fas fa-briefcase-medical mr-1"></i>Medical Camp
								</button>
								<button type="button" class="btn cal-filter-btn"
								        data-type="EDUCATIONAL_SEMINAR" data-color="#28a745"
								        style="background:#28a745;border-color:#28a745;color:#fff;">
									<i class="fas fa-graduation-cap mr-1"></i>Seminar
								</button>
								<button type="button" class="btn cal-filter-btn"
								        data-type="TRAINING" data-color="#17a2b8"
								        style="background:#17a2b8;border-color:#17a2b8;color:#fff;">
									<i class="fas fa-chalkboard-teacher mr-1"></i>Training
								</button>
								<button type="button" class="btn cal-filter-btn"
								        data-type="MEETING" data-color="#e0a800"
								        style="background:#ffc107;border-color:#e0a800;color:#212529;">
									<i class="fas fa-users mr-1"></i>Meeting
								</button>
								<button type="button" class="btn cal-filter-btn"
								        data-type="OTHER" data-color="#6c757d"
								        style="background:#6c757d;border-color:#6c757d;color:#fff;">
									<i class="fas fa-calendar-alt mr-1"></i>Other
								</button>
								<?php if (can($_SESSION['user_role'] ?? '', 'appointments')): ?>
								<button type="button" class="btn cal-filter-btn"
								        data-type="APPOINTMENT" data-color="#6f42c1"
								        style="background:#6f42c1;border-color:#6f42c1;color:#fff;">
									<i class="fas fa-calendar-check mr-1"></i>Appointments
								</button>
								<?php endif; ?>
							</div>
							<?php if ($canWriteEvents): ?>
							<button class="btn btn-primary btn-sm ml-auto flex-shrink-0" id="newEventBtn">
								<i class="fas fa-plus mr-1"></i>New Event
							</button>
							<?php endif; ?>
						</div>
					</div>
					<div class="card-body pt-2">
						<div id="calendar"></div>
					</div>
				</div>
			</div>
		</section>
	</div>

	<!-- Create / Edit Event Modal -->
	<?php if ($canWriteEvents): ?>
	<div class="modal fade" id="createEventModal" tabindex="-1" role="dialog" aria-labelledby="createEventModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="createEventModalLabel"><i class="fas fa-calendar-plus mr-2"></i>New Event</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<div id="createEventError" class="alert alert-danger d-none"></div>
					<form id="createEventForm">
						<input type="hidden" id="evId">
						<div class="form-group">
							<label for="evTitle">Title <span class="text-danger">*</span></label>
							<input type="text" class="form-control" id="evTitle" name="title" required maxlength="255">
						</div>
						<div class="form-group">
							<label for="evType">Type</label>
							<select class="form-control" id="evType" name="event_type">
								<option value="MEDICAL_CAMP">Medical Camp</option>
								<option value="EDUCATIONAL_SEMINAR">Educational Seminar</option>
								<option value="TRAINING">Training</option>
								<option value="MEETING">Meeting</option>
								<option value="OTHER" selected>Other</option>
							</select>
						</div>
						<div class="form-row">
							<div class="form-group col-md-6">
								<label for="evStart">Start <span class="text-danger">*</span></label>
								<input type="datetime-local" class="form-control" id="evStart" name="start_datetime" required>
							</div>
							<div class="form-group col-md-6">
								<label for="evEnd">End</label>
								<input type="datetime-local" class="form-control" id="evEnd" name="end_datetime">
							</div>
						</div>
						<div class="form-group">
							<label for="evLocation">Location</label>
							<input type="text" class="form-control" id="evLocation" name="location_name" maxlength="255">
						</div>
						<div class="form-group">
							<label for="evDesc">Description</label>
							<textarea class="form-control" id="evDesc" name="description" rows="3" maxlength="2000"></textarea>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" id="createEventSave">Save Event</button>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<!-- Event Detail Modal -->
	<div class="modal fade" id="eventModal" tabindex="-1" role="dialog" aria-labelledby="eventModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="eventModalLabel">Event Details</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body"></div>
				<div class="modal-footer">
					<?php if ($canWriteEvents): ?>
					<button type="button" class="btn btn-outline-danger btn-sm mr-auto d-none" id="eventDeleteBtn">
						<i class="fas fa-trash mr-1"></i>Delete
					</button>
					<button type="button" class="btn btn-outline-primary btn-sm d-none" id="eventEditBtn">
						<i class="fas fa-edit mr-1"></i>Edit
					</button>
					<?php endif; ?>
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

	<footer class="main-footer">
		<div class="float-right d-none d-sm-inline">CareSystem <span class="badge badge-warning" style="font-size:.65rem;vertical-align:middle">Alpha</span></div>
		<strong>Calendar</strong>
	</footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script src="assets/js/fullcalendar.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

	var typeColors = {
		'MEDICAL_CAMP':        '#007bff',
		'EDUCATIONAL_SEMINAR': '#28a745',
		'TRAINING':            '#17a2b8',
		'MEETING':             '#ffc107',
		'OTHER':               '#6c757d',
		'APPOINTMENT':         '#6f42c1'
	};

	var typeLabels = {
		'MEDICAL_CAMP':        'Medical Camp',
		'EDUCATIONAL_SEMINAR': 'Educational Seminar',
		'TRAINING':            'Training',
		'MEETING':             'Meeting',
		'OTHER':               'Other'
	};

	var canWrite   = <?= $canWriteEvents ? 'true' : 'false' ?>;
	var csrfToken  = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;

	// ── Event data ──────────────────────────────────────────────
	var events = <?= json_encode(array_map(function ($e) {
		return [
			'id'          => $e['event_id'],
			'title'       => $e['title'],
			'start'       => $e['start_datetime'],
			'end'         => $e['end_datetime'],
			'description' => $e['description'] ?? '',
			'eventType'   => $e['event_type'],
			'location'    => $e['location_name'] ?? '',
			'status'      => $e['status'],
		];
	}, $events), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

	events.forEach(function (e) {
		e.color = typeColors[e.eventType] || '#6c757d';
	});

	var apptEvents = <?= json_encode(array_map(function ($a) {
		$start = $a['scheduled_date'];
		if (!empty($a['scheduled_time'])) {
			$start .= 'T' . $a['scheduled_time'];
		}
		$patient = trim($a['first_name'] . ' ' . ($a['last_name'] ?? ''));
		$doctor  = 'Dr. ' . trim($a['doc_first'] . ' ' . $a['doc_last']);
		return [
			'id'          => 'appt-' . $a['appointment_id'],
			'title'       => $patient . ' · ' . $doctor,
			'start'       => $start,
			'color'       => '#6f42c1',
			'eventType'   => 'APPOINTMENT',
			'patient'     => $patient,
			'patientCode' => $a['patient_code'],
			'doctor'      => $doctor,
			'apptStatus'  => $a['status'],
		];
	}, $appointments), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

	events = events.concat(apptEvents);

	// ── Filter state ────────────────────────────────────────────
	var hiddenTypes = {};

	// ── Current event reference (for edit / delete) ─────────────
	var currentFCEvent = null;

	// ── Helpers ─────────────────────────────────────────────────
	function formatDatetimeLocal(date) {
		if (!date) return '';
		var d = (date instanceof Date) ? date : new Date(date);
		var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
		return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
		       'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
	}

	// Normalise a FullCalendar dateStr that may include seconds: "2026-04-08T10:00:00" → "2026-04-08T10:00"
	function normDateStr(str) {
		return str ? str.substring(0, 16) : '';
	}

	// Open the create/edit modal.
	// eventData = FullCalendar Event object for edit, null for create.
	// prefillStart = "YYYY-MM-DDTHH:MM" string to pre-fill start when creating via dateClick.
	function openEventModal(eventData, prefillStart) {
		if (!canWrite) return;
		var form   = document.getElementById('createEventForm');
		var errEl  = document.getElementById('createEventError');
		form.reset();
		document.getElementById('evId').value = '';
		errEl.classList.add('d-none');

		if (eventData) {
			document.getElementById('createEventModalLabel').innerHTML =
				'<i class="fas fa-calendar-edit mr-2"></i>Edit Event';
			document.getElementById('evId').value    = eventData.id;
			document.getElementById('evTitle').value = eventData.title;
			document.getElementById('evType').value  = eventData.extendedProps.eventType || 'OTHER';
			document.getElementById('evStart').value = formatDatetimeLocal(eventData.start);
			document.getElementById('evEnd').value   = eventData.end ? formatDatetimeLocal(eventData.end) : '';
			document.getElementById('evLocation').value = eventData.extendedProps.location || '';
			document.getElementById('evDesc').value     = eventData.extendedProps.description || '';
		} else {
			document.getElementById('createEventModalLabel').innerHTML =
				'<i class="fas fa-calendar-plus mr-2"></i>New Event';
			if (prefillStart) {
				document.getElementById('evStart').value = normDateStr(prefillStart);
			}
		}
		$('#createEventModal').modal('show');
	}

	// ── FullCalendar ─────────────────────────────────────────────
	var calendarEl  = document.getElementById('calendar');
	var evTypeIcons = {
		'MEDICAL_CAMP':        'fa-briefcase-medical',
		'EDUCATIONAL_SEMINAR': 'fa-graduation-cap',
		'TRAINING':            'fa-chalkboard-teacher',
		'MEETING':             'fa-users',
		'APPOINTMENT':         'fa-calendar-check',
		'OTHER':               'fa-calendar-alt'
	};

	var calendar = new FullCalendar.Calendar(calendarEl, {
		initialView: 'dayGridMonth',
		initialDate: <?= json_encode($initialDate) ?>,
		headerToolbar: {
			left:   'prev,next today',
			center: 'title',
			right:  'dayGridMonth,timeGridWeek,listMonth'
		},
		fixedWeekCount: false,
		dayMaxEvents:   3,
		nowIndicator:   true,
		eventDisplay:   'block',
		eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
		eventContent: function (arg) {
			var et   = arg.event.extendedProps.eventType || 'OTHER';
			var icon = evTypeIcons[et] || 'fa-calendar-alt';
			var time = arg.timeText
				? '<span class="fc-ev-time">' + arg.timeText + '</span>'
				: '';
			return { html: '<div class="fc-custom-event">'
				+ '<i class="fas ' + icon + '"></i>'
				+ time
				+ '<span class="fc-ev-title">' + arg.event.title + '</span>'
				+ '</div>' };
		},
		events: events,

		// Feature 1 – click an empty day to open create modal with date pre-filled
		dateClick: function (info) {
			if (!canWrite) return;
			var prefill = info.allDay
				? info.dateStr + 'T09:00'
				: info.dateStr;
			openEventModal(null, prefill);
		},

		eventClick: function (info) {
			var e    = info.event;
			var ep   = e.extendedProps;
			var isAppt = (ep.eventType === 'APPOINTMENT');

			currentFCEvent = e;

			var fmtDate = { dateStyle: 'medium' };
			var fmtTime = { timeStyle: 'short' };
			var start = e.start
				? e.start.toLocaleDateString([], fmtDate) + (e.allDay ? '' : ' ' + e.start.toLocaleTimeString([], fmtTime))
				: '';
			var end = (!e.allDay && e.end) ? ' \u2013 ' + e.end.toLocaleTimeString([], fmtTime) : '';
			var when = start + end;

			var esc = function (s) { return $('<span>').text(s || '').html(); };

			var bodyHtml;
			if (isAppt) {
				var statusLabel = { SCHEDULED: 'Scheduled', CONFIRMED: 'Confirmed', IN_PROGRESS: 'In Progress' }[ep.apptStatus] || ep.apptStatus;
				var statusColor = { SCHEDULED: 'info', CONFIRMED: 'primary', IN_PROGRESS: 'warning' }[ep.apptStatus] || 'secondary';
				bodyHtml =
					'<p><strong>Patient:</strong> ' + esc(ep.patient) +
					' <small class="text-muted">(' + esc(ep.patientCode) + ')</small></p>' +
					'<p><strong>Doctor:</strong> ' + esc(ep.doctor) + '</p>' +
					'<p><strong>When:</strong> ' + esc(when) + '</p>' +
					'<p><strong>Status:</strong> <span class="badge badge-' + statusColor + '">' + esc(statusLabel) + '</span></p>' +
					'<div class="mt-3"><a href="appointments.php" class="btn btn-sm btn-primary">' +
					'<i class="fas fa-calendar-alt mr-1"></i>Go to Appointments</a></div>';
				document.getElementById('eventModalLabel').textContent = ep.patient;
			} else {
				bodyHtml =
					'<p><strong>Type:</strong> ' + esc(typeLabels[ep.eventType] || ep.eventType) + '</p>' +
					'<p><strong>When:</strong> ' + esc(when) + '</p>' +
					'<p><strong>Location:</strong> ' + esc(ep.location || 'Not specified') + '</p>' +
					'<p><strong>Status:</strong> ' + esc(ep.status) + '</p>' +
					'<p class="mb-1"><strong>Description:</strong></p>' +
					'<p class="text-muted">' + esc(ep.description || 'No description provided.') + '</p>';
				document.getElementById('eventModalLabel').textContent = e.title;
			}

			document.querySelector('#eventModal .modal-body').innerHTML = bodyHtml;

			// Feature 3 – show Edit / Delete only for non-appointment events (write roles)
			var editBtn   = document.getElementById('eventEditBtn');
			var deleteBtn = document.getElementById('eventDeleteBtn');
			if (editBtn) {
				editBtn.classList.toggle('d-none', isAppt);
				deleteBtn.classList.toggle('d-none', isAppt);
			}

			$('#eventModal').modal('show');
		},
		height: 'auto'
	});

	// Add clickable-day hint class when user has write access
	if (canWrite) {
		calendarEl.classList.add('cal-clickable-days');
	}

	calendar.render();

	// ── Feature 5 – type filter bar ──────────────────────────────
	document.getElementById('calFilterBar').addEventListener('click', function (e) {
		var btn = e.target.closest('.cal-filter-btn');
		if (!btn) return;
		var type = btn.dataset.type;
		var hiding = !hiddenTypes[type];
		hiddenTypes[type] = hiding;

		if (hiding) {
			btn.style.background    = '';
			btn.style.color         = btn.dataset.color;
			btn.style.borderColor   = btn.dataset.color;
			btn.classList.add('inactive');
		} else {
			var isMeeting = (type === 'MEETING');
			btn.style.background    = btn.dataset.color;
			btn.style.color         = isMeeting ? '#212529' : '#fff';
			btn.style.borderColor   = btn.dataset.color;
			btn.classList.remove('inactive');
		}

		calendar.getEvents().forEach(function (ev) {
			var et = ev.extendedProps.eventType || 'OTHER';
			if (et === type) {
				ev.setProp('display', hiding ? 'none' : 'auto');
			}
		});
	});

	// ── New Event button ─────────────────────────────────────────
	var newEventBtn = document.getElementById('newEventBtn');
	if (newEventBtn) {
		newEventBtn.addEventListener('click', function () {
			openEventModal(null, null);
		});
	}

	// ── Save button (create or update) ──────────────────────────
	var saveBtn = document.getElementById('createEventSave');
	if (saveBtn) {
		saveBtn.addEventListener('click', function () {
			var btn   = this;
			var form  = document.getElementById('createEventForm');
			var errEl = document.getElementById('createEventError');
			errEl.classList.add('d-none');

			var title  = document.getElementById('evTitle').value.trim();
			var start  = document.getElementById('evStart').value;
			var evId   = document.getElementById('evId').value;
			var evType = document.getElementById('evType').value;

			if (!title || !start) {
				errEl.textContent = 'Title and start date/time are required.';
				errEl.classList.remove('d-none');
				return;
			}

			btn.disabled = true;
			var data = new FormData(form);
			data.append('csrf_token', csrfToken);

			if (evId) {
				data.append('action', 'update');
				data.append('event_id', evId);
			}
			// no action field → server defaults to 'create'

			fetch('calendar.php', { method: 'POST', body: data })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res.success) {
						errEl.textContent = res.message || 'Error saving event.';
						errEl.classList.remove('d-none');
						return;
					}
					var color = typeColors[evType] || '#6c757d';
					if (evId) {
						// Update existing event in calendar
						var ev = calendar.getEventById(evId);
						if (ev) {
							ev.setProp('title', title);
							ev.setStart(start);
							ev.setEnd(form.end_datetime.value || null);
							ev.setExtendedProp('eventType',   evType);
							ev.setExtendedProp('location',    form.location_name.value);
							ev.setExtendedProp('description', form.description.value);
							ev.setProp('color', color);
							if (hiddenTypes[evType]) ev.setProp('display', 'none');
						}
					} else {
						// Add new event and respect active filter state
						var newEv = calendar.addEvent({
							id:    res.event_id,
							title: title,
							start: start,
							end:   form.end_datetime.value || undefined,
							color: color,
							extendedProps: {
								eventType:   evType,
								location:    form.location_name.value,
								description: form.description.value,
								status:      'SCHEDULED',
							},
						});
						if (newEv && hiddenTypes[evType]) {
							newEv.setProp('display', 'none');
						}
					}
					$('#createEventModal').modal('hide');
				})
				.catch(function () {
					errEl.textContent = 'Network error. Please try again.';
					errEl.classList.remove('d-none');
				})
				.finally(function () { btn.disabled = false; });
		});
	}

	// ── Feature 3 – Edit button in detail modal ──────────────────
	var editBtn = document.getElementById('eventEditBtn');
	if (editBtn) {
		editBtn.addEventListener('click', function () {
			$('#eventModal').modal('hide');
			// Wait for detail modal to finish closing before opening edit modal
			$('#eventModal').one('hidden.bs.modal', function () {
				openEventModal(currentFCEvent, null);
			});
		});
	}

	// ── Feature 3 – Delete button in detail modal ────────────────
	var deleteBtn = document.getElementById('eventDeleteBtn');
	if (deleteBtn) {
		deleteBtn.addEventListener('click', function () {
			if (!currentFCEvent) return;
			var title = currentFCEvent.title;
			if (!confirm('Delete "' + title + '"?\n\nThis cannot be undone.')) return;

			var data = new FormData();
			data.append('action',     'delete');
			data.append('event_id',   currentFCEvent.id);
			data.append('csrf_token', csrfToken);

			fetch('calendar.php', { method: 'POST', body: data })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res.success) {
						alert(res.message || 'Error deleting event.');
						return;
					}
					currentFCEvent.remove();
					$('#eventModal').modal('hide');
				})
				.catch(function () {
					alert('Network error. Please try again.');
				});
		});
	}
});
</script>
</body>
</html>
