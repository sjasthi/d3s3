<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Admin Panel | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
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
					<input type="checkbox" class="custom-control-input" id="themeToggleAdminPanel" data-theme-toggle />
					<label class="custom-control-label" for="themeToggleAdminPanel">Dark mode</label>
				</div>
			</li>
		</ul>
	</nav>

	<?php require __DIR__ . '/../_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-12">
						<h1 class="m-0 text-dark">Admin Panel</h1>
						<p class="text-muted mb-0">Manage system settings and administration.</p>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

				<?php if (isset($flashSuccess) && $flashSuccess !== null): ?>
				<div class="alert alert-success alert-dismissible fade show" role="alert">
					<i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($flashSuccess) ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<?php endif; ?>

				<div class="row">
					<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
						<a href="users.php" class="admin-tile">
							<div class="admin-tile-icon">
								<i class="fas fa-users"></i>
							</div>
							<div class="admin-tile-label">Users</div>
						</a>
					</div>
					<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
						<a href="assets.php" class="admin-tile">
							<div class="admin-tile-icon">
								<i class="fas fa-boxes"></i>
							</div>
							<div class="admin-tile-label">Assets</div>
						</a>
					</div>
					<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
						<a href="#" class="admin-tile">
							<div class="admin-tile-icon">
								<i class="fas fa-user-injured"></i>
							</div>
							<div class="admin-tile-label">Patient Management</div>
						</a>
					</div>
					<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
						<a href="#" class="admin-tile">
							<div class="admin-tile-icon">
								<i class="fas fa-envelope"></i>
							</div>
							<div class="admin-tile-label">Messages</div>
						</a>
					</div>
					<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
						<a href="reports.php" class="admin-tile">
							<div class="admin-tile-icon">
								<i class="fas fa-chart-line"></i>
							</div>
							<div class="admin-tile-label">Reports</div>
						</a>
					</div>
					<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
						<a href="calendar.php" class="admin-tile">
							<div class="admin-tile-icon">
								<i class="fas fa-calendar-alt"></i>
							</div>
							<div class="admin-tile-label">Calendar</div>
						</a>
					</div>
					<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
						<a href="profile.php" class="admin-tile">
							<div class="admin-tile-icon">
								<i class="fas fa-user-circle"></i>
							</div>
							<div class="admin-tile-label">Profile</div>
						</a>
					</div>
					<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
						<a href="settings.php" class="admin-tile">
							<div class="admin-tile-icon">
								<i class="fas fa-cog"></i>
							</div>
							<div class="admin-tile-label">Settings</div>
						</a>
					</div>
					<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
						<a href="permissions.php" class="admin-tile">
							<div class="admin-tile-icon">
								<i class="fas fa-shield-alt"></i>
							</div>
							<div class="admin-tile-label">Permissions</div>
						</a>
					</div>
					<div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
						<a href="#" class="admin-tile">
							<div class="admin-tile-icon">
								<i class="fas fa-question-circle"></i>
							</div>
							<div class="admin-tile-label">Help</div>
						</a>
					</div>
				</div>
			</div>
		</section>
	</div>

	<footer class="main-footer">
		<div class="float-right d-none d-sm-inline">CareSystem</div>
		<strong>Admin Panel</strong>
	</footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
