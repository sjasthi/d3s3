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
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" />
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
					<input type="checkbox" class="custom-control-input" id="themeToggleCalendar" data-theme-toggle />
					<label class="custom-control-label" for="themeToggleCalendar">Dark mode</label>
				</div>
			</li>
		</ul>
	</nav>

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
					<div class="card-body">
						<div id="calendar"></div>
					</div>
				</div>
			</div>
		</section>
	</div>

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
				<div class="modal-body">
					<p><strong>Type:</strong> <span id="eventType"></span></p>
					<p><strong>When:</strong> <span id="eventWhen"></span></p>
					<p><strong>Location:</strong> <span id="eventLocation"></span></p>
					<p><strong>Status:</strong> <span id="eventStatus"></span></p>
					<p><strong>Description:</strong></p>
					<p id="eventDescription" class="text-muted"></p>
				</div>
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
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
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

	var calendarEl = document.getElementById('calendar');
	var calendar = new FullCalendar.Calendar(calendarEl, {
		initialView: 'dayGridMonth',
		headerToolbar: {
			left: 'prev,next today',
			center: 'title',
			right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
		},
		events: events,
		eventClick: function (info) {
			var e = info.event;
			document.getElementById('eventModalLabel').textContent = e.title;
			document.getElementById('eventType').textContent = typeLabels[e.extendedProps.eventType] || e.extendedProps.eventType;
			document.getElementById('eventLocation').textContent = e.extendedProps.location || 'Not specified';
			document.getElementById('eventStatus').textContent = e.extendedProps.status;
			document.getElementById('eventDescription').textContent = e.extendedProps.description || 'No description provided.';

			var start = e.start ? e.start.toLocaleString() : '';
			var end = e.end ? ' – ' + e.end.toLocaleString() : '';
			document.getElementById('eventWhen').textContent = start + end;

			$('#eventModal').modal('show');
		},
		height: 'auto'
	});
	calendar.render();
});
</script>
</body>
</html>
