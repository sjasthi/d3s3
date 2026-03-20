<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Feedback Detail | CareSystem</title>
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
						<h1 class="m-0 text-dark">Feedback #<?= (int)$feedback['feedback_id'] ?></h1>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

				<?php if ($flashSuccess !== null): ?>
				<div class="alert alert-success alert-dismissible fade show" role="alert">
					<i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($flashSuccess) ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<?php endif; ?>

				<?php if ($flashError !== null): ?>
				<div class="alert alert-danger alert-dismissible fade show" role="alert">
					<i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($flashError) ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<?php endif; ?>

				<?php
				$statusColors = ['OPEN'=>'badge-danger','UNDER_REVIEW'=>'badge-warning','RESOLVED'=>'badge-success','CLOSED'=>'badge-secondary'];
				$statusLabels = ['OPEN'=>'Open','UNDER_REVIEW'=>'Under Review','RESOLVED'=>'Resolved','CLOSED'=>'Closed'];
				?>

				<div class="row">
					<div class="col-md-8">
						<div class="card">
							<div class="card-header">
								<h3 class="card-title">
									<i class="fas fa-comment-dots mr-2"></i><?= htmlspecialchars($feedback['subject']) ?>
								</h3>
								<div class="card-tools">
									<span class="badge <?= $statusColors[$feedback['status']] ?? 'badge-secondary' ?> badge-lg">
										<?= $statusLabels[$feedback['status']] ?? htmlspecialchars($feedback['status']) ?>
									</span>
								</div>
							</div>
							<div class="card-body">
								<p class="mb-0"><?= nl2br(htmlspecialchars($feedback['description'])) ?></p>
							</div>
							<div class="card-footer text-muted small">
								Submitted by <?= htmlspecialchars($feedback['submitter_first'] . ' ' . $feedback['submitter_last']) ?>
								on <?= htmlspecialchars(date('d M Y H:i', strtotime($feedback['created_at']))) ?>
							</div>
						</div>
					</div>

					<?php if ($canWrite): ?>
					<div class="col-md-4">
						<div class="card">
							<div class="card-header">
								<h3 class="card-title"><i class="fas fa-edit mr-2"></i>Update</h3>
							</div>
							<div class="card-body">
								<form method="post" action="feedback.php?action=view&amp;id=<?= (int)$feedback['feedback_id'] ?>">
									<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
									<input type="hidden" name="action" value="update_status" />

									<div class="form-group">
										<label for="status">Status</label>
										<select name="status" id="status" class="form-control form-control-sm">
											<?php foreach ($statusLabels as $val => $label): ?>
											<option value="<?= $val ?>" <?= $feedback['status'] === $val ? 'selected' : '' ?>>
												<?= $label ?>
											</option>
											<?php endforeach; ?>
										</select>
									</div>

									<?php if (!empty($grievanceUsers)): ?>
									<div class="form-group">
										<label for="assigned_to_user_id">Assign To</label>
										<select name="assigned_to_user_id" id="assigned_to_user_id" class="form-control form-control-sm">
											<option value="">— Unassigned —</option>
											<?php foreach ($grievanceUsers as $u): ?>
											<option value="<?= (int)$u['user_id'] ?>"
												<?= (int)($feedback['assigned_to_user_id'] ?? 0) === (int)$u['user_id'] ? 'selected' : '' ?>>
												<?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
											</option>
											<?php endforeach; ?>
										</select>
									</div>
									<?php endif; ?>

									<button type="submit" class="btn btn-primary btn-block btn-sm">
										<i class="fas fa-save mr-1"></i>Save Changes
									</button>
								</form>
							</div>
						</div>

						<?php if (!empty($feedback['assignee_first'])): ?>
						<div class="card card-outline card-info">
							<div class="card-body small">
								<strong>Assigned to:</strong><br />
								<?= htmlspecialchars($feedback['assignee_first'] . ' ' . $feedback['assignee_last']) ?>
							</div>
						</div>
						<?php endif; ?>
					</div>
					<?php endif; ?>
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
