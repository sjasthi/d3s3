<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Profile | CareSystem</title>
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
			<li class="nav-item d-flex align-items-center mr-3">
				<div class="custom-control custom-switch theme-switch">
					<input type="checkbox" class="custom-control-input" id="themeToggleProfile" data-theme-toggle />
					<label class="custom-control-label" for="themeToggleProfile">Dark mode</label>
				</div>
			</li>
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="dashboard.php" role="button">
					<i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
				</a>
			</li>
		</ul>
	</nav>

	<?php require __DIR__ . '/_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-12">
						<h1 class="m-0 text-dark">My Profile</h1>
						<p class="text-muted mb-0">Update your personal details and password.</p>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

				<?php if ($flashSuccess !== null): ?>
				<div class="alert alert-success" role="alert">
					<i class="fas fa-check-circle mr-2"></i>
					<?= htmlspecialchars($flashSuccess) ?>
				</div>
				<?php endif; ?>

				<div class="card">
					<div class="card-header border-0 d-flex align-items-center justify-content-between">
						<h3 class="card-title mb-0">
							<i class="fas fa-user-circle mr-2 text-primary"></i>
							Profile Details
						</h3>
						<div class="text-muted small">
							<?= htmlspecialchars($profile['role'] ?? 'User') ?>
						</div>
					</div>

					<?php if ($formError !== null): ?>
					<div class="card-body pb-0">
						<div class="alert alert-danger" role="alert">
							<?= htmlspecialchars($formError) ?>
						</div>
					</div>
					<?php endif; ?>

					<div class="card-body">
						<form method="POST" action="profile.php" novalidate>
							<input type="hidden" name="csrf_token"
								   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

							<div class="row">
								<div class="col-sm-6">
									<div class="form-group">
										<label for="first_name" class="small font-weight-bold text-muted">First Name</label>
										<input type="text" name="first_name" id="first_name"
											   class="form-control"
											   value="<?= htmlspecialchars($profile['first_name'] ?? '') ?>"
											   required />
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<label for="last_name" class="small font-weight-bold text-muted">Last Name</label>
										<input type="text" name="last_name" id="last_name"
											   class="form-control"
											   value="<?= htmlspecialchars($profile['last_name'] ?? '') ?>"
											   required />
									</div>
								</div>
							</div>

							<div class="form-group">
								<label for="email" class="small font-weight-bold text-muted">Email</label>
								<div class="input-group">
									<div class="input-group-prepend">
										<span class="input-group-text"><i class="fas fa-envelope"></i></span>
									</div>
									<input type="email" name="email" id="email"
										   class="form-control"
										   value="<?= htmlspecialchars($profile['email'] ?? '') ?>"
										   autocomplete="off" required />
								</div>
							</div>

							<div class="form-group">
								<label for="username" class="small font-weight-bold text-muted">Username</label>
								<div class="input-group">
									<div class="input-group-prepend">
										<span class="input-group-text"><i class="fas fa-user"></i></span>
									</div>
									<input type="text" name="username" id="username"
										   class="form-control"
										   value="<?= htmlspecialchars($profile['username'] ?? '') ?>"
										   autocomplete="off" required />
								</div>
								<small class="text-muted">3–60 characters: letters, numbers, underscores, or hyphens.</small>
							</div>

							<hr />
							<p class="small text-muted mb-3">
								<i class="fas fa-lock mr-1"></i>
								Leave both fields blank to keep the current password.
							</p>
							<div class="row">
								<div class="col-sm-6">
									<div class="form-group">
										<label for="new_password" class="small font-weight-bold text-muted">New Password</label>
										<div class="input-group">
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fas fa-lock"></i></span>
											</div>
											<input type="password" name="new_password" id="new_password"
												   class="form-control"
												   placeholder="Min 8 characters"
												   autocomplete="off" />
										</div>
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<label for="confirm_password" class="small font-weight-bold text-muted">Confirm New Password</label>
										<div class="input-group">
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fas fa-lock"></i></span>
											</div>
											<input type="password" name="confirm_password" id="confirm_password"
												   class="form-control"
												   placeholder="Re-enter password"
												   autocomplete="off" />
										</div>
									</div>
								</div>
							</div>

							<div class="mt-3">
								<button type="submit" class="btn btn-primary">
									<i class="fas fa-save mr-1"></i>Save Changes
								</button>
								<a href="dashboard.php" class="btn btn-link text-muted ml-2">Cancel</a>
							</div>
						</form>
					</div>

					<div class="card-footer text-muted small">
						<?php if (!empty($profile['last_login_at'])): ?>
							Last login: <?= htmlspecialchars($profile['last_login_at']) ?>
						<?php else: ?>
							Last login: N/A
						<?php endif; ?>
					</div>
				</div>

			</div>
		</section>
	</div>

	<footer class="main-footer text-sm">
		<strong>CareSystem</strong> &middot; Manage your account details.
	</footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
