<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Photos and press | CareSystem</title>
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
		<div>
			<span class="panel-label">Language</span>
			<div class="btn-group lang-btn-group" role="group" aria-label="Language">
				<button type="button" class="btn btn-sm <?= ($_SESSION['language'] ?? 'en') === 'en' ? 'btn-primary' : 'btn-outline-secondary' ?>" data-lang="en">English</button>
				<button type="button" class="btn btn-sm <?= ($_SESSION['language'] ?? 'en') === 'te' ? 'btn-primary' : 'btn-outline-secondary' ?>" data-lang="te">తెలుగు</button>
			</div>
		</div>
	</div>

	<?php require __DIR__ . '/_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2 align-items-center">
					<div class="col-sm-8">
						<h1 class="m-0 text-dark">Photos and press coverage</h1>
						<p class="text-muted mb-0">
							Public photographs and press links from the asset library.
							They can also be opened from Calendar when planning events.
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
				<?php
				$sectionMeta = [
					'Press Coverage' => ['icon' => 'fa-newspaper', 'hint' => 'News and media coverage of the organization.'],
					'Photos'         => ['icon' => 'fa-camera', 'hint' => 'Photographs reflecting clinic and camp operations.'],
					'Testimonials'   => ['icon' => 'fa-quote-left', 'hint' => 'Patient and partner testimonials.'],
				];
				$any = false;
				foreach ($grouped as $sectionItems) {
					if (!empty($sectionItems)) {
						$any = true;
						break;
					}
				}
				?>

				<?php if (!$any): ?>
				<div class="alert alert-info">
					No public press or photo assets have been added yet.
					<?php if (!empty($canManageAssets)): ?>
					Create an asset with category
					<strong>Press Coverage</strong>, <strong>Photos</strong>, or <strong>Testimonials</strong>,
					mark it public, and set an external URL (for example a Google Drive folder).
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<?php foreach ($grouped as $category => $sectionItems): ?>
					<?php if (empty($sectionItems)) {
						continue;
					} ?>
					<?php $meta = $sectionMeta[$category] ?? ['icon' => 'fa-folder', 'hint' => '']; ?>
					<h4 class="mt-3 mb-2">
						<i class="fas <?= htmlspecialchars($meta['icon']) ?> mr-2 text-primary"></i>
						<?= htmlspecialchars($category) ?>
					</h4>
					<?php if ($meta['hint'] !== ''): ?>
					<p class="text-muted small mb-3"><?= htmlspecialchars($meta['hint']) ?></p>
					<?php endif; ?>
					<div class="row">
					<?php foreach ($sectionItems as $item): ?>
						<div class="col-md-6 mb-3">
							<div class="card shadow-sm h-100">
								<div class="card-body">
									<span class="badge badge-secondary"><?= htmlspecialchars($item['asset_type']) ?></span>
									<h5 class="mt-2 mb-1"><?= htmlspecialchars($item['title']) ?></h5>
									<?php if (!empty($item['description'])): ?>
									<p class="text-muted mb-2"><?= htmlspecialchars($item['description']) ?></p>
									<?php endif; ?>
									<?php if (!empty($item['resource_url'])): ?>
									<a class="btn btn-sm btn-primary"
									   href="<?= htmlspecialchars($item['resource_url']) ?>"
									   target="_blank" rel="noopener noreferrer">
										<i class="fas fa-external-link-alt mr-1"></i>Open
									</a>
									<?php else: ?>
									<span class="text-muted small">No URL on this asset.</span>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
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
