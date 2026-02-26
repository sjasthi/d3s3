<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Permissions – Identity Verification | CareSystem</title>
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
					<input type="checkbox" class="custom-control-input" id="themeTogglePermGate" data-theme-toggle />
					<label class="custom-control-label" for="themeTogglePermGate">Dark mode</label>
				</div>
			</li>
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="admin.php?page=panel" role="button">
					<i class="fas fa-arrow-left mr-1"></i>Admin Panel
				</a>
			</li>
		</ul>
	</nav>

	<?php require __DIR__ . '/../_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-12">
						<h1 class="m-0 text-dark">Permissions Management</h1>
						<p class="text-muted mb-0">Restricted – administrator access only.</p>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">
				<div class="row justify-content-center">
					<div class="col-md-5 col-lg-4 mt-3">

						<div class="card shadow-sm">
							<div class="card-header">
								<h3 class="card-title mb-0">
									<i class="fas fa-lock mr-2 text-warning"></i>Identity Verification Required
								</h3>
							</div>
							<div class="card-body">

								<p class="text-muted mb-3" style="font-size:.9rem;">
									The Permissions Management page contains sensitive access controls.
									Please verify your identity to continue.
								</p>

								<?php if (isset($gateError) && $gateError !== null): ?>
								<div class="alert alert-danger py-2" role="alert">
									<i class="fas fa-exclamation-circle mr-1"></i><?= htmlspecialchars($gateError) ?>
								</div>
								<?php endif; ?>

								<form method="post" action="permissions.php?action=gate-auth">
									<input type="hidden" name="csrf_token"
									       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

									<div class="form-group">
										<label for="gate-password" class="font-weight-bold">
											<i class="fas fa-key mr-1"></i>Your password
										</label>
										<input type="password"
										       id="gate-password"
										       name="gate_password"
										       class="form-control<?= isset($gateError) ? ' is-invalid' : '' ?>"
										       placeholder="Enter your current password"
										       autocomplete="current-password"
										       autofocus />
									</div>

									<div class="d-flex justify-content-between align-items-center">
										<a href="admin.php?page=panel" class="btn btn-outline-secondary">
											<i class="fas fa-times mr-1"></i>Cancel
										</a>
										<button type="submit" class="btn btn-primary">
											<i class="fas fa-arrow-right mr-1"></i>Continue
										</button>
									</div>
								</form>

							</div><!-- /.card-body -->
						</div><!-- /.card -->

					</div>
				</div>
			</div>
		</section>
	</div><!-- /.content-wrapper -->

	<footer class="main-footer">
		<div class="float-right d-none d-sm-inline">CareSystem</div>
		<strong>Permissions Management</strong>
	</footer>
</div><!-- /.wrapper -->

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
