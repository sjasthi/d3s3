<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Asset Management | CareSystem</title>
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
					<input type="checkbox" class="custom-control-input" id="themeToggleAssets" data-theme-toggle />
					<label class="custom-control-label" for="themeToggleAssets">Dark mode</label>
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
				<div class="row mb-2 align-items-center">
					<div class="col-sm-6">
						<h1 class="m-0 text-dark">Asset Management</h1>
						<p class="text-muted mb-0">View and manage system assets.</p>
					</div>
					<?php if ($action === 'list'): ?>
					<div class="col-sm-6 text-right mt-2 mt-sm-0">
						<a href="assets.php?action=create" class="btn btn-primary">
							<i class="fas fa-plus mr-1"></i>Add Asset
						</a>
					</div>
					<?php endif; ?>
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

				<?php if (($action === 'edit' || $action === 'create') && $editAsset !== null): ?>
				<!-- ── Edit / Create Asset ────────────────────────── -->
				<?php
				$isCreate = ($action === 'create');
				$formTitle = $isCreate
					? '<i class="fas fa-plus mr-2 text-primary"></i>New Asset'
					: '<i class="fas fa-pencil-alt mr-2 text-primary"></i>Edit: ' . htmlspecialchars($editAsset['title']);
				?>
				<div class="card">
					<div class="card-header border-0 d-flex align-items-center justify-content-between">
						<h3 class="card-title mb-0">
							<?= $formTitle ?>
						</h3>
						<a href="assets.php" class="btn btn-sm btn-outline-secondary">
							<i class="fas fa-arrow-left mr-1"></i>Back to list
						</a>
					</div>

					<?php if ($formError !== null): ?>
					<div class="card-body pb-0">
						<div class="alert alert-danger" role="alert">
							<?= htmlspecialchars($formError) ?>
						</div>
					</div>
					<?php endif; ?>

					<div class="card-body">
						<form method="POST" action="assets.php" novalidate>
							<input type="hidden" name="csrf_token"
								   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
							<input type="hidden" name="form_action"
								   value="<?= $isCreate ? 'create' : 'edit' ?>" />
							<?php if (!$isCreate): ?>
							<input type="hidden" name="asset_id"
								   value="<?= htmlspecialchars((string)$editAsset['asset_id']) ?>" />
							<?php endif; ?>

							<!-- Title -->
							<div class="form-group">
								<label for="title" class="small font-weight-bold text-muted">Title <span class="text-danger">*</span></label>
								<input type="text" name="title" id="title"
									   class="form-control"
									   value="<?= htmlspecialchars($editAsset['title']) ?>"
									   required />
							</div>

							<!-- Description -->
							<div class="form-group">
								<label for="description" class="small font-weight-bold text-muted">Description</label>
								<textarea name="description" id="description"
										  class="form-control" rows="3"><?= htmlspecialchars($editAsset['description'] ?? '') ?></textarea>
							</div>

							<!-- Type + Category row -->
							<div class="row">
								<div class="col-sm-6">
									<div class="form-group">
										<label for="asset_type" class="small font-weight-bold text-muted">Asset Type</label>
										<select class="form-control" name="asset_type" id="asset_type">
											<?php
											$types = ['VIDEO' => 'Video', 'PDF' => 'PDF', 'IMAGE' => 'Image', 'DOCUMENT' => 'Document', 'OTHER' => 'Other'];
											foreach ($types as $val => $lbl):
											?>
											<option value="<?= $val ?>" <?= ($editAsset['asset_type'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<label for="category" class="small font-weight-bold text-muted">Category</label>
										<input type="text" name="category" id="category"
											   class="form-control"
											   value="<?= htmlspecialchars($editAsset['category'] ?? '') ?>" />
									</div>
								</div>
							</div>

							<!-- Storage Type + Resource URL row -->
							<div class="row">
								<div class="col-sm-4">
									<div class="form-group">
										<label for="storage_type" class="small font-weight-bold text-muted">Storage Type</label>
										<select class="form-control" name="storage_type" id="storage_type">
											<?php
											$storageTypes = ['URL' => 'URL', 'LOCAL' => 'Local', 'S3' => 'S3', 'OTHER' => 'Other'];
											foreach ($storageTypes as $val => $lbl):
											?>
											<option value="<?= $val ?>" <?= ($editAsset['storage_type'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
								<div class="col-sm-8">
									<div class="form-group">
										<label for="resource_url" class="small font-weight-bold text-muted">Resource URL</label>
										<div class="input-group">
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fas fa-link"></i></span>
											</div>
											<input type="url" name="resource_url" id="resource_url"
												   class="form-control"
												   value="<?= htmlspecialchars($editAsset['resource_url'] ?? '') ?>"
												   placeholder="https://..." />
										</div>
									</div>
								</div>
							</div>

							<!-- File Name + File Size row -->
							<div class="row">
								<div class="col-sm-8">
									<div class="form-group">
										<label for="file_name" class="small font-weight-bold text-muted">File Name</label>
										<input type="text" name="file_name" id="file_name"
											   class="form-control"
											   value="<?= htmlspecialchars($editAsset['file_name'] ?? '') ?>" />
									</div>
								</div>
								<div class="col-sm-4">
									<div class="form-group">
										<label for="file_size_bytes" class="small font-weight-bold text-muted">File Size (bytes)</label>
										<input type="number" name="file_size_bytes" id="file_size_bytes"
											   class="form-control" min="0"
											   value="<?= htmlspecialchars((string)($editAsset['file_size_bytes'] ?? '')) ?>" />
									</div>
								</div>
							</div>

							<!-- Public + Active switches -->
							<div class="row mt-2">
								<div class="col-sm-6 d-flex align-items-end pb-3">
									<div class="custom-control custom-switch">
										<input type="checkbox" class="custom-control-input" id="is_public" name="is_public"
											   <?= !empty($editAsset['is_public']) ? 'checked' : '' ?> />
										<label class="custom-control-label" for="is_public">Public</label>
									</div>
								</div>
								<div class="col-sm-6 d-flex align-items-end pb-3">
									<div class="custom-control custom-switch">
										<input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
											   <?= !empty($editAsset['is_active']) ? 'checked' : '' ?> />
										<label class="custom-control-label" for="is_active">Active</label>
									</div>
								</div>
							</div>

							<!-- Actions -->
							<div class="mt-3">
								<button type="submit" class="btn btn-primary">
									<i class="fas fa-save mr-1"></i><?= $isCreate ? 'Create Asset' : 'Save Changes' ?>
								</button>
								<a href="assets.php" class="btn btn-link text-muted ml-2">Cancel</a>
							</div>
						</form>
					</div>
				</div>

				<?php else: ?>
				<!-- ── Asset List ─────────────────────────────────── -->
				<?php
				$typeBadges = [
					'VIDEO'    => 'badge-info',
					'PDF'      => 'badge-danger',
					'IMAGE'    => 'badge-success',
					'DOCUMENT' => 'badge-warning',
					'OTHER'    => 'badge-secondary',
				];
				?>
				<div class="card">
					<div class="card-header border-0">
						<h3 class="card-title mb-0">
							<i class="fas fa-boxes mr-2 text-primary"></i>All Assets
						</h3>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-hover mb-0">
								<thead class="thead-light">
									<tr>
										<th>Title</th>
										<th>Type</th>
										<th>Category</th>
										<th>Storage</th>
										<th>Public</th>
										<th>Active</th>
										<th>Uploaded By</th>
										<th>Created</th>
										<th class="text-right">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($assetsList as $asset): ?>
									<tr>
										<td>
											<strong><?= htmlspecialchars($asset['title']) ?></strong>
											<?php if (!empty($asset['resource_url'])): ?>
											<a href="<?= htmlspecialchars($asset['resource_url']) ?>" target="_blank" rel="noopener noreferrer" class="ml-1 small" title="Open resource">
												<i class="fas fa-external-link-alt"></i>
											</a>
											<?php endif; ?>
										</td>
										<td>
											<span class="badge <?= htmlspecialchars($typeBadges[$asset['asset_type']] ?? 'badge-secondary') ?>">
												<?= htmlspecialchars($asset['asset_type']) ?>
											</span>
										</td>
										<td class="small text-muted"><?= htmlspecialchars($asset['category'] ?? '—') ?></td>
										<td class="small"><?= htmlspecialchars($asset['storage_type']) ?></td>
										<td>
											<?php if ($asset['is_public']): ?>
											<span class="badge badge-success">Yes</span>
											<?php else: ?>
											<span class="badge badge-secondary">No</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($asset['is_active']): ?>
											<span class="badge badge-success">Active</span>
											<?php else: ?>
											<span class="badge badge-secondary">Inactive</span>
											<?php endif; ?>
										</td>
										<td class="small text-muted">
											<?= isset($asset['first_name']) ? htmlspecialchars($asset['first_name'] . ' ' . $asset['last_name']) : '—' ?>
										</td>
										<td class="small text-muted"><?= htmlspecialchars($asset['created_at']) ?></td>
										<td class="text-right text-nowrap">
											<a href="assets.php?action=edit&amp;id=<?= (int)$asset['asset_id'] ?>"
											   class="btn btn-sm btn-outline-primary mr-1" title="Edit">
												<i class="fas fa-pencil-alt"></i>
											</a>
											<button type="button" class="btn btn-sm btn-outline-danger"
													data-toggle="modal"
													data-target="#deleteModal"
													data-asset-id="<?= (int)$asset['asset_id'] ?>"
													data-asset-title="<?= htmlspecialchars($asset['title']) ?>"
													title="Delete">
												<i class="fas fa-trash-alt"></i>
											</button>
										</td>
									</tr>
									<?php endforeach; ?>
									<?php if (empty($assetsList)): ?>
									<tr>
										<td colspan="9" class="text-center text-muted py-4">No assets found. Click "Add Asset" to create one.</td>
									</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<?php endif; ?>

			</div>
		</section>
	</div>

	<footer class="main-footer">
		<div class="float-right d-none d-sm-inline">CareSystem</div>
		<strong>Asset Management</strong>
	</footer>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="deleteModalLabel">
					<i class="fas fa-exclamation-triangle text-danger mr-2"></i>Confirm Delete
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				Are you sure you want to delete <strong id="deleteAssetName"></strong>? This action cannot be undone.
			</div>
			<div class="modal-footer">
				<form method="POST" action="assets.php" id="deleteForm">
					<input type="hidden" name="csrf_token"
						   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
					<input type="hidden" name="form_action" value="delete" />
					<input type="hidden" name="asset_id" id="deleteAssetId" value="" />
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-danger">
						<i class="fas fa-trash-alt mr-1"></i>Delete
					</button>
				</form>
			</div>
		</div>
	</div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script>
$('#deleteModal').on('show.bs.modal', function (e) {
	var btn = $(e.relatedTarget);
	$('#deleteAssetId').val(btn.data('asset-id'));
	$('#deleteAssetName').text(btn.data('asset-title'));
});
</script>
</body>
</html>
