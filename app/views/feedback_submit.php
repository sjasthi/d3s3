<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Submit Feedback | CareSystem</title>
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
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="feedback.php" role="button">
					<i class="fas fa-arrow-left mr-1"></i>All Feedback
				</a>
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
						<h1 class="m-0 text-dark">Submit Feedback</h1>
						<p class="text-muted mb-0">Log a new grievance or feedback record.</p>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">
				<div class="row justify-content-center">
					<div class="col-md-8">

						<?php if (isset($formError) && $formError !== null): ?>
						<div class="alert alert-danger alert-dismissible fade show" role="alert">
							<i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($formError) ?>
							<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						</div>
						<?php endif; ?>

						<div class="card">
							<div class="card-header">
								<h3 class="card-title"><i class="fas fa-comment-dots mr-2"></i>New Feedback</h3>
							</div>
							<div class="card-body">
								<form method="post" action="feedback.php?action=submit">
									<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

									<div class="form-group">
										<label for="subject">Subject <span class="text-danger">*</span></label>
										<input type="text" name="subject" id="subject" class="form-control"
										       maxlength="200" required
										       value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" />
									</div>

									<div class="form-group">
										<label for="description">Description <span class="text-danger">*</span></label>
										<textarea name="description" id="description" class="form-control" rows="6"
										          required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
									</div>

									<div class="d-flex justify-content-end">
										<a href="feedback.php" class="btn btn-secondary mr-2">Cancel</a>
										<button type="submit" class="btn btn-primary">
											<i class="fas fa-paper-plane mr-1"></i>Submit
										</button>
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
		<strong>D3S3 CareSystem</strong>
	</footer>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
