<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Create Employee Account | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
</head>
<body class="hold-transition<?= isset($_SESSION['user_id']) ? ' sidebar-mini layout-fixed layout-navbar-fixed' : '' ?>">
<div class="wrapper">
	<nav class="main-header navbar navbar-expand navbar-white navbar-light">
		<ul class="navbar-nav">
			<?php if (isset($_SESSION['user_id'])): ?>
			<li class="nav-item">
				<a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Toggle sidebar">
					<i class="fas fa-bars"></i>
				</a>
			</li>
			<?php endif; ?>
			<li class="nav-item d-none d-sm-inline-block">
				<span class="navbar-brand mb-0 h6 text-primary">CareSystem</span>
			</li>
		</ul>
		<ul class="navbar-nav ml-auto">
			<li class="nav-item d-flex align-items-center mr-3">
				<div class="custom-control custom-switch theme-switch">
					<input type="checkbox" class="custom-control-input" id="themeToggleEmpRegister" data-theme-toggle />
					<label class="custom-control-label" for="themeToggleEmpRegister">Dark mode</label>
				</div>
			</li>
			<?php if (isset($_SESSION['user_id'])): ?>
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="admin.php" role="button">
					<i class="fas fa-arrow-left mr-1"></i>Admin Dashboard
				</a>
			</li>
			<?php endif; ?>
		</ul>
	</nav>

	<?php if (isset($_SESSION['user_id'])): ?>
	<?php require __DIR__ . '/../_sidebar.php'; ?>
	<?php endif; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-12">
						<h1 class="m-0 text-dark">Create Employee Account</h1>
						<p class="text-muted mb-0">Add a new employee to the system.</p>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">
				<div class="row">
					<div class="col-lg-8 col-md-10">

						<div class="card">
							<?php if ($formError !== null): ?>
							<div class="card-body pb-0">
								<div class="alert alert-danger" role="alert">
									<?= htmlspecialchars($formError) ?>
								</div>
							</div>
							<?php endif; ?>

							<div class="card-body">
								<form method="POST" action="emp_register.php" novalidate>
									<input type="hidden" name="csrf_token"
										   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

									<!-- Name row -->
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group">
												<label for="first_name" class="small font-weight-bold text-muted">First Name</label>
												<input type="text" name="first_name" id="first_name"
													   class="form-control"
													   placeholder="Jane"
													   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
													   required />
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group">
												<label for="last_name" class="small font-weight-bold text-muted">Last Name</label>
												<input type="text" name="last_name" id="last_name"
													   class="form-control"
													   placeholder="Smith"
													   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
													   required />
											</div>
										</div>
									</div>

									<!-- Email -->
									<div class="form-group">
										<label for="email" class="small font-weight-bold text-muted">Email</label>
										<div class="input-group">
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fas fa-envelope"></i></span>
											</div>
											<input type="email" name="email" id="email"
												   class="form-control"
												   placeholder="jane@example.com"
												   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
												   autocomplete="off" required />
										</div>
									</div>

									<!-- Username -->
									<div class="form-group">
										<label for="username" class="small font-weight-bold text-muted">Username</label>
										<div class="input-group">
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fas fa-user"></i></span>
											</div>
											<input type="text" name="username" id="username"
												   class="form-control"
												   placeholder="jsmith"
												   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
												   autocomplete="off" required />
										</div>
										<small class="text-muted">3–60 characters: letters, numbers, underscores, or hyphens.</small>
									</div>

									<!-- Password row -->
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group">
												<label for="password" class="small font-weight-bold text-muted">Password</label>
												<div class="input-group">
													<div class="input-group-prepend">
														<span class="input-group-text"><i class="fas fa-lock"></i></span>
													</div>
													<input type="password" name="password" id="password"
														   class="form-control"
														   placeholder="Min 8 characters"
														   autocomplete="off" required />
												</div>
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group">
												<label for="confirm_password" class="small font-weight-bold text-muted">Confirm Password</label>
												<div class="input-group">
													<div class="input-group-prepend">
														<span class="input-group-text"><i class="fas fa-lock"></i></span>
													</div>
													<input type="password" name="confirm_password" id="confirm_password"
														   class="form-control"
														   placeholder="Re-enter password"
														   autocomplete="off" required />
												</div>
											</div>
										</div>
									</div>

									<!-- Role notice -->
									<p class="text-muted small mb-3">
										<i class="fas fa-info-circle mr-1"></i>
										New accounts are assigned the <strong>DATA_ENTRY_OPERATOR</strong> role by default.
										Role can be updated later via User Management.
									</p>

									<!-- Actions -->
									<div class="mt-3">
										<button type="submit" class="btn btn-primary">
											<i class="fas fa-user-plus mr-1"></i>Create Account
										</button>
										<a href="admin.php" class="btn btn-link text-muted ml-2">Cancel</a>
									</div>
								</form>
							</div>
						</div>

					</div>
				</div>
			</div>
		</section>
	</div>

	<footer class="main-footer">
		<div class="float-right d-none d-sm-inline">CareSight</div>
		<strong>Create Employee Account</strong>
	</footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
