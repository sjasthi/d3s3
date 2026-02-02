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
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
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
			<li class="nav-item d-none d-md-inline-block mr-3 text-muted small">
				<i class="fas fa-sun pr-1"></i>Optimized for outdoor tablet use
			</li>
			<li class="nav-item">
				<a class="btn btn-sm btn-primary" href="#" role="button">
					<i class="fas fa-plus-circle mr-1"></i>New record
				</a>
			</li>
		</ul>
	</nav>

	<?php include __DIR__ . '/public/_sidebar.php'; ?>

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
								<h3 class="card-title mb-0"><i class="fas fa-notes-medical mr-2 text-primary"></i>Upcoming</h3>
								<span class="badge badge-secondary">Today</span>
							</div>
							<div class="card-body">
								<div class="timeline">
									<div class="time-label"><span class="bg-primary">08:00</span></div>
									<div>
										<i class="fas fa-user-md bg-primary"></i>
										<div class="timeline-item">
											<span class="time"><i class="far fa-clock"></i> 30m</span>
											<h3 class="timeline-header">Morning rounds</h3>
											<div class="timeline-body">Team A | Cardiology</div>
										</div>
									</div>
									<div class="time-label"><span class="bg-secondary">11:00</span></div>
									<div>
										<i class="fas fa-vial bg-secondary"></i>
										<div class="timeline-item">
											<span class="time"><i class="far fa-clock"></i> 45m</span>
											<h3 class="timeline-header">Lab review</h3>
											<div class="timeline-body">CBC and metabolic panels</div>
										</div>
									</div>
									<div class="time-label"><span class="bg-info">14:00</span></div>
									<div>
										<i class="fas fa-clipboard-check bg-info"></i>
										<div class="timeline-item">
											<span class="time"><i class="far fa-clock"></i> 20m</span>
											<h3 class="timeline-header">Discharge planning</h3>
											<div class="timeline-body">Confirm follow-ups and scripts</div>
										</div>
									</div>
								</div>
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
</body>
</html>
