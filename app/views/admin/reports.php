<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Reports | CareSystem</title>
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
					<input type="checkbox" class="custom-control-input" id="themeToggleReports" data-theme-toggle />
					<label class="custom-control-label" for="themeToggleReports">Dark mode</label>
				</div>
			</li>
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="admin.php" role="button">
					<i class="fas fa-arrow-left mr-1"></i>Admin Dashboard
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
						<h1 class="m-0 text-dark">Reports</h1>
						<p class="text-muted mb-0">Export and import database tables as CSV files.</p>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

				<?php if ($flashSuccess !== null): ?>
				<div class="alert alert-success alert-dismissible fade show" role="alert">
					<i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($flashSuccess) ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<?php endif; ?>

				<?php if ($formError !== null): ?>
				<div class="alert alert-danger alert-dismissible fade show" role="alert">
					<i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($formError) ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<?php endif; ?>

				<div class="card">
					<div class="card-header border-0">
						<h3 class="card-title mb-0">
							<i class="fas fa-database mr-2 text-primary"></i>Data Export &amp; Import
						</h3>
						<p class="card-text text-muted small mt-1 mb-0">
							<i class="fas fa-shield-alt mr-1"></i>A backup is automatically saved before each import.
						</p>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-hover mb-0">
								<thead class="thead-light">
									<tr>
										<th style="width: 50px;"></th>
										<th>Table</th>
										<th class="text-right">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($exportables as $tableName => $meta): ?>
									<tr>
										<td class="text-center align-middle">
											<i class="<?= htmlspecialchars($meta['icon']) ?> text-muted"></i>
										</td>
										<td class="align-middle">
											<strong><?= htmlspecialchars($meta['label']) ?></strong>
											<?php if (!empty($meta['exclude'])): ?>
											<br><small class="text-muted">Excludes: <?= htmlspecialchars(implode(', ', $meta['exclude'])) ?></small>
											<?php endif; ?>
										</td>
										<td class="text-right align-middle text-nowrap">
											<form method="POST" action="reports.php" class="d-inline">
												<input type="hidden" name="csrf_token"
													   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
												<input type="hidden" name="form_action" value="export" />
												<input type="hidden" name="table"
													   value="<?= htmlspecialchars($tableName) ?>" />
												<button type="submit" class="btn btn-primary btn-sm">
													<i class="fas fa-download mr-1"></i>Download
												</button>
											</form>
											<form method="POST" action="reports.php" enctype="multipart/form-data" class="d-inline ml-1">
												<input type="hidden" name="csrf_token"
													   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
												<input type="hidden" name="form_action" value="import" />
												<input type="hidden" name="table"
													   value="<?= htmlspecialchars($tableName) ?>" />
												<label class="btn btn-outline-success btn-sm mb-0" style="cursor:pointer;">
													<i class="fas fa-upload mr-1"></i>Upload
													<input type="file" name="csv_file" accept=".csv"
														   class="d-none" onchange="this.form.submit();" />
												</label>
											</form>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

			</div>
		</section>
	</div>

	<footer class="main-footer">
		<div class="float-right d-none d-sm-inline">CareSystem</div>
		<strong>Reports</strong>
	</footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
