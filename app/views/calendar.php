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
					<?php if ($canWriteEvents): ?>
					<div class="card-header border-0 d-flex justify-content-end">
						<button class="btn btn-primary btn-sm" id="newEventBtn">
							<i class="fas fa-plus mr-1"></i>New Event
						</button>
					</div>
					<?php endif; ?>
					<div class="card-body">
						<div id="calendar"></div>
					</div>
				</div>
			</div>
		</section>
	</div>

	<!-- Create Event Modal -->
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
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

	<footer class="main-footer">
		<div class="float-right d-none d-sm-inline">CareSystem</div>
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
		'MEDICAL_CAMP': '#007bff',
		'EDUCATIONAL_SEMINAR': '#28a745',
		'TRAINING': '#17a2b8',
		'MEETING': '#ffc107',
		'OTHER': '#6c757d'
	};

	var typeLabels = {
		'MEDICAL_CAMP': 'Medical Camp',
		'EDUCATIONAL_SEMINAR': 'Educational Seminar',
		'TRAINING': 'Training',
		'MEETING': 'Meeting',
		'OTHER': 'Other'
	};

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

	// Appointment events
	var apptEvents = <?= json_encode(array_map(function ($a) {
		$start = $a['scheduled_date'];
		if (!empty($a['scheduled_time'])) {
			$start .= 'T' . $a['scheduled_time'];
		}
		$patient = trim($a['first_name'] . ' ' . ($a['last_name'] ?? ''));
		$doctor  = 'Dr. ' . trim($a['doc_first'] . ' ' . $a['doc_last']);
		return [
			'id'          => 'appt-' . $a['appointment_id'],
			'title'       => $patient . ' \u00b7 ' . $doctor,
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

	var calendarEl = document.getElementById('calendar');
	var calendar = new FullCalendar.Calendar(calendarEl, {
		initialView: 'dayGridMonth',
		initialDate: <?= json_encode($initialDate) ?>,
		headerToolbar: {
			left: 'prev,next today',
			center: 'title',
			right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
		},
		events: events,
		eventClick: function (info) {
			var e    = info.event;
			var ep   = e.extendedProps;
			var isAppt = (ep.eventType === 'APPOINTMENT');

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
				var statusLabel = {SCHEDULED:'Scheduled',CONFIRMED:'Confirmed',IN_PROGRESS:'In Progress'}[ep.apptStatus] || ep.apptStatus;
				var statusColor = {SCHEDULED:'info',CONFIRMED:'primary',IN_PROGRESS:'warning'}[ep.apptStatus] || 'secondary';
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
			$('#eventModal').modal('show');
		},
		height: 'auto'
	});
	calendar.render();

	<?php if ($canWriteEvents): ?>
	document.getElementById('newEventBtn').addEventListener('click', function () {
		document.getElementById('createEventForm').reset();
		document.getElementById('createEventError').classList.add('d-none');
		$('#createEventModal').modal('show');
	});

	document.getElementById('createEventSave').addEventListener('click', function () {
		var btn = this;
		var form = document.getElementById('createEventForm');
		var errEl = document.getElementById('createEventError');
		errEl.classList.add('d-none');

		var title = document.getElementById('evTitle').value.trim();
		var start = document.getElementById('evStart').value;
		if (!title || !start) {
			errEl.textContent = 'Title and start date/time are required.';
			errEl.classList.remove('d-none');
			return;
		}

		btn.disabled = true;
		var data = new FormData(form);
		data.append('csrf_token', <?= json_encode($_SESSION['csrf_token'] ?? '') ?>);

		fetch('calendar.php', { method: 'POST', body: data })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res.success) {
					errEl.textContent = res.message || 'Error saving event.';
					errEl.classList.remove('d-none');
					return;
				}
				var evType = form.event_type.value;
				calendar.addEvent({
					id:          res.event_id,
					title:       title,
					start:       start,
					end:         form.end_datetime.value || undefined,
					color:       typeColors[evType] || '#6c757d',
					extendedProps: {
						eventType:   evType,
						location:    form.location_name.value,
						description: form.description.value,
						status:      'SCHEDULED',
					},
				});
				$('#createEventModal').modal('hide');
			})
			.catch(function () {
				errEl.textContent = 'Network error. Please try again.';
				errEl.classList.remove('d-none');
			})
			.finally(function () { btn.disabled = false; });
	});
	<?php endif; ?>
});
</script>
</body>
</html>
