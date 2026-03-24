<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Admin Dashboard | CareSystem</title>
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

	<?php require __DIR__ . '/../_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-12">
						<h1 class="m-0 text-dark">Admin Dashboard</h1>
						<p class="text-muted mb-0">Manage system settings and administration.</p>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">
				<!-- Shortcuts -->
				<div class="row mb-4">
					<div class="col-md-4 col-sm-6 mb-3">
						<a href="emp_register.php" class="btn btn-primary btn-lg btn-block py-4">
							<i class="fas fa-user-plus fa-2x mb-2 d-block"></i>
							New User
						</a>
					</div>
					<div class="col-md-4 col-sm-6 mb-3">
						<a href="assets.php" class="btn btn-info btn-lg btn-block py-4">
							<i class="fas fa-boxes fa-2x mb-2 d-block"></i>
							Assets
						</a>
					</div>
					<div class="col-md-4 col-sm-6 mb-3">
						<a href="reports.php" class="btn btn-success btn-lg btn-block py-4">
							<i class="fas fa-chart-line fa-2x mb-2 d-block"></i>
							Reports
						</a>
					</div>
				</div>

				<div class="row">
					<!-- Messages -->
					<div class="col-lg-6 mb-4">
						<a href="messages.php" class="text-decoration-none">
							<div class="card shadow-sm h-100">
								<div class="card-header border-0 d-flex justify-content-between align-items-center">
									<h3 class="card-title mb-0"><i class="fas fa-envelope mr-2 text-primary"></i>Messages</h3>
									<?php if ($unreadMessages > 0): ?>
									<span class="badge badge-danger badge-pill"><?= $unreadMessages ?> unread</span>
									<?php else: ?>
									<span class="badge badge-secondary badge-pill">No new messages</span>
									<?php endif; ?>
								</div>
								<div class="card-body text-center py-4">
									<?php if ($unreadMessages > 0): ?>
									<i class="fas fa-envelope fa-3x mb-3 d-block text-danger"></i>
									<p class="mb-0 font-weight-bold text-danger">
										You have <?= $unreadMessages ?> unread message<?= $unreadMessages !== 1 ? 's' : '' ?>
									</p>
									<?php else: ?>
									<i class="fas fa-envelope-open-text fa-3x mb-3 d-block text-muted"></i>
									<p class="mb-0 text-muted">Your inbox is up to date</p>
									<?php endif; ?>
								</div>
							</div>
						</a>
					</div>

					<!-- Calendar -->
					<div class="col-lg-6 mb-4">
						<div class="card shadow-sm h-100">
							<div class="card-header border-0 d-flex justify-content-between align-items-center">
								<h3 class="card-title mb-0"><i class="fas fa-calendar-alt mr-2 text-primary"></i>Calendar</h3>
								<a href="calendar.php" class="btn btn-sm btn-outline-primary">View full calendar</a>
							</div>
							<div class="card-body p-2">
								<div id="adminCalendarWidget"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>

	<footer class="main-footer">
		<div class="float-right d-none d-sm-inline">CareSystem</div>
		<strong>Admin Dashboard</strong>
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

	var events = <?= json_encode(array_map(function ($e) {
		return [
			'title' => $e['title'],
			'start' => $e['start_datetime'],
			'end'   => $e['end_datetime'],
			'color' => '',
			'type'  => $e['event_type'],
		];
	}, $events), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

	events.forEach(function (e) {
		e.color = typeColors[e.type] || '#6c757d';
	});

	var cal = new FullCalendar.Calendar(document.getElementById('adminCalendarWidget'), {
		initialView: 'listMonth',
		initialDate: <?= json_encode($dashboardInitDate) ?>,
		headerToolbar: {
			left: 'prev,next',
			center: 'title',
			right: ''
		},
		events: events,
		height: 260,
		noEventsText: 'No events this month'
	});
	cal.render();
});
</script>
</body>
</html>
