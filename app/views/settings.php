<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Settings | CareSystem</title>
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
			<li class="nav-item d-flex align-items-center">
				<button id="gearBtn" aria-label="Display settings" title="Display settings">
					<i class="fas fa-cog fa-lg"></i>
				</button>
			</li>
			<?php if (in_array($_SESSION['user_role'] ?? '', ['SUPER_ADMIN', 'ADMIN'], true)): ?>
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="admin.php" role="button">
					<i class="fas fa-arrow-left mr-1"></i>Admin Dashboard
				</a>
			</li>
			<?php else: ?>
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="dashboard.php" role="button">
					<i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
				</a>
			</li>
			<?php endif; ?>
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
						<h1 class="m-0 text-dark">Settings</h1>
						<p class="text-muted mb-0">Customize your experience.</p>
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

				<?php if ($formError !== null): ?>
				<div class="alert alert-danger" role="alert">
					<i class="fas fa-exclamation-circle mr-2"></i>
					<?= htmlspecialchars($formError) ?>
				</div>
				<?php endif; ?>

				<form method="POST" action="settings.php" novalidate>
					<input type="hidden" name="csrf_token"
						   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

					<!-- ── Appearance ────────────────────────────── -->
					<div class="card">
						<div class="card-header border-0">
							<h3 class="card-title mb-0">
								<i class="fas fa-palette mr-2 text-primary"></i>
								Appearance
							</h3>
						</div>
						<div class="card-body">
							<!-- Theme -->
							<div class="form-group">
								<label class="small font-weight-bold text-muted">Theme</label>
								<div>
									<div class="custom-control custom-radio custom-control-inline">
										<input type="radio" class="custom-control-input" id="theme_light"
											   name="theme" value="light"
											   <?= ($prefs['theme'] ?? 'system') === 'light' ? 'checked' : '' ?> />
										<label class="custom-control-label" for="theme_light">
											<i class="fas fa-sun mr-1 text-warning"></i>Light
										</label>
									</div>
									<div class="custom-control custom-radio custom-control-inline">
										<input type="radio" class="custom-control-input" id="theme_dark"
											   name="theme" value="dark"
											   <?= ($prefs['theme'] ?? 'system') === 'dark' ? 'checked' : '' ?> />
										<label class="custom-control-label" for="theme_dark">
											<i class="fas fa-moon mr-1 text-info"></i>Dark
										</label>
									</div>
									<div class="custom-control custom-radio custom-control-inline">
										<input type="radio" class="custom-control-input" id="theme_system"
											   name="theme" value="system"
											   <?= ($prefs['theme'] ?? 'system') === 'system' ? 'checked' : '' ?> />
										<label class="custom-control-label" for="theme_system">
											<i class="fas fa-desktop mr-1 text-secondary"></i>System Default
										</label>
									</div>
								</div>
								<small class="text-muted">Choose your preferred color scheme, or let it follow your device settings.</small>
							</div>

							<!-- Font Size -->
							<div class="form-group mb-0">
								<label class="small font-weight-bold text-muted">Font Size</label>
								<div>
									<div class="custom-control custom-radio custom-control-inline">
										<input type="radio" class="custom-control-input" id="font_normal"
											   name="font_size" value="normal"
											   <?= ($prefs['font_size'] ?? 'normal') === 'normal' ? 'checked' : '' ?> />
										<label class="custom-control-label" for="font_normal">Normal</label>
									</div>
									<div class="custom-control custom-radio custom-control-inline">
										<input type="radio" class="custom-control-input" id="font_large"
											   name="font_size" value="large"
											   <?= ($prefs['font_size'] ?? 'normal') === 'large' ? 'checked' : '' ?> />
										<label class="custom-control-label" for="font_large">Large</label>
									</div>
								</div>
								<small class="text-muted">Increase text size for better readability on tablets or in bright environments.</small>
							</div>
						</div>
					</div>

					<!-- ── Language & Region ────────────────────── -->
					<div class="card">
						<div class="card-header border-0">
							<h3 class="card-title mb-0">
								<i class="fas fa-globe mr-2 text-primary"></i>
								Language & Region
							</h3>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-sm-6">
									<div class="form-group">
										<label for="language" class="small font-weight-bold text-muted">Language</label>
										<select class="form-control" name="language" id="language">
											<option value="en" <?= ($prefs['language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
											<option value="te" <?= ($prefs['language'] ?? 'en') === 'te' ? 'selected' : '' ?>>Telugu</option>
										</select>
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<label for="date_format" class="small font-weight-bold text-muted">Date Format</label>
										<select class="form-control" name="date_format" id="date_format">
											<option value="DD/MM/YYYY" <?= ($prefs['date_format'] ?? 'DD/MM/YYYY') === 'DD/MM/YYYY' ? 'selected' : '' ?>>DD/MM/YYYY (31/12/2026)</option>
											<option value="MM/DD/YYYY" <?= ($prefs['date_format'] ?? 'DD/MM/YYYY') === 'MM/DD/YYYY' ? 'selected' : '' ?>>MM/DD/YYYY (12/31/2026)</option>
										</select>
										<small class="text-muted">How dates are displayed throughout the application.</small>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- ── Notifications & Session ──────────────── -->
					<div class="card">
						<div class="card-header border-0">
							<h3 class="card-title mb-0">
								<i class="fas fa-bell mr-2 text-primary"></i>
								Notifications & Session
							</h3>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-sm-6">
									<div class="form-group">
										<label class="small font-weight-bold text-muted d-block">Email Notifications</label>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="email_notifications"
												   name="email_notifications"
												   <?= ($prefs['email_notifications'] ?? 1) ? 'checked' : '' ?> />
											<label class="custom-control-label" for="email_notifications">
												Receive email alerts for messages and updates
											</label>
										</div>
										<small class="text-muted">Email notification system is being set up. Your preference will be saved.</small>
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<label for="session_timeout_minutes" class="small font-weight-bold text-muted">Session Timeout</label>
										<select class="form-control" name="session_timeout_minutes" id="session_timeout_minutes">
											<option value="15" <?= (int)($prefs['session_timeout_minutes'] ?? 30) === 15 ? 'selected' : '' ?>>15 minutes</option>
											<option value="30" <?= (int)($prefs['session_timeout_minutes'] ?? 30) === 30 ? 'selected' : '' ?>>30 minutes</option>
											<option value="60" <?= (int)($prefs['session_timeout_minutes'] ?? 30) === 60 ? 'selected' : '' ?>>60 minutes</option>
										</select>
										<small class="text-muted">How long before you are automatically logged out due to inactivity.</small>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- ── Save ──────────────────────────────────── -->
					<div class="mb-4">
						<button type="submit" class="btn btn-primary">
							<i class="fas fa-save mr-1"></i>Save Settings
						</button>
						<a href="dashboard.php" class="btn btn-link text-muted ml-2">Cancel</a>
					</div>
				</form>

			</div>
		</section>
	</div>

	<footer class="main-footer text-sm">
		<strong>CareSystem</strong> &middot; Personalize your experience.
	</footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
