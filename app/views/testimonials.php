<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Testimonials | CareSystem</title>
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
		</ul>
	</nav>

	<div id="settingsPanel" role="dialog" aria-label="Display settings">
		<span class="panel-label">Display settings</span>
		<div class="custom-control custom-switch mb-3">
			<input type="checkbox" class="custom-control-input" id="themeTogglePanel" data-theme-toggle />
			<label class="custom-control-label" for="themeTogglePanel">Dark mode</label>
		</div>
	</div>

	<?php require __DIR__ . '/_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2 align-items-center">
					<div class="col-sm-8">
						<h1 class="m-0 text-dark">Beneficiary testimonials</h1>
						<p class="text-muted mb-0">
							Public video assets tagged <strong>Testimonials</strong>.
							Videos stay on YouTube or Drive; CareSystem stores the link and tags
							(<code>is_public</code>, category).
						</p>
					</div>
					<?php if (!empty($canManageAssets)): ?>
					<div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
						<a href="assets.php?action=create" class="btn btn-outline-primary btn-sm">
							<i class="fas fa-plus mr-1"></i>Add in Asset Library
						</a>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">
				<?php if (empty($items)): ?>
				<div class="alert alert-info">
					No public testimonial assets yet.
					<?php if (!empty($canManageAssets)): ?>
					Create a <strong>VIDEO</strong> asset with category <strong>Testimonials</strong>,
					mark it public, and paste a YouTube URL.
					<?php endif; ?>
				</div>
				<?php else: ?>
				<div class="row">
				<?php foreach ($items as $item): ?>
					<?php
					$url = (string)($item['resource_url'] ?? '');
					$isYoutube = (bool)preg_match('~(youtube\.com|youtu\.be)~i', $url);
					?>
					<div class="col-md-6 col-lg-4 mb-3">
						<div class="card shadow-sm h-100">
							<div class="card-body">
								<span class="badge badge-info"><?= htmlspecialchars($item['asset_type']) ?></span>
								<span class="badge badge-success">Public</span>
								<span class="badge badge-secondary"><?= htmlspecialchars($item['category']) ?></span>
								<h5 class="mt-2 mb-1"><?= htmlspecialchars($item['title']) ?></h5>
								<?php if (!empty($item['description'])): ?>
								<p class="text-muted small mb-2"><?= htmlspecialchars($item['description']) ?></p>
								<?php endif; ?>
								<?php if ($url !== ''): ?>
								<a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($url) ?>"
								   target="_blank" rel="noopener noreferrer">
									<i class="fas <?= $isYoutube ? 'fa-play' : 'fa-external-link-alt' ?> mr-1"></i>
									<?= $isYoutube ? 'Watch on YouTube' : 'Open' ?>
								</a>
								<?php else: ?>
								<span class="text-muted small">No URL on this asset.</span>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
		</section>
	</div>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
