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
<body class="hold-transition sidebar-mini">
<div class="wrapper">
	<?php require __DIR__ . '/_sidebar.php'; ?>
	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<h1 class="m-0">Photos and press coverage</h1>
				<p class="text-muted">Public videos, photographs, and press links. These are also listed in the asset library and can be attached to an event.</p>
			</div>
		</div>
		<section class="content">
			<div class="container-fluid">
				<?php if (empty($items)): ?>
				<div class="alert alert-info">No public press or photo assets have been added yet.</div>
				<?php endif; ?>
				<div class="row">
				<?php foreach ($items as $item): ?>
					<div class="col-md-6 mb-3">
						<div class="card h-100">
							<div class="card-body">
								<span class="badge badge-info"><?= htmlspecialchars($item['category']) ?></span>
								<h5 class="mt-2"><?= htmlspecialchars($item['title']) ?></h5>
								<p class="text-muted"><?= htmlspecialchars($item['description'] ?? '') ?></p>
								<?php if (!empty($item['resource_url'])): ?>
								<a href="<?= htmlspecialchars($item['resource_url']) ?>" target="_blank" rel="noopener">Open</a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
				</div>
			</div>
		</section>
	</div>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
</body>
</html>
