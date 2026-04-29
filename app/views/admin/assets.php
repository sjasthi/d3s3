<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Asset Library | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
		.type-badge-VIDEO    { background:#17a2b8; }
		.type-badge-AUDIO    { background:#6f42c1; }
		.type-badge-PDF      { background:#dc3545; }
		.type-badge-IMAGE    { background:#28a745; }
		.type-badge-DOCUMENT { background:#fd7e14; }
		.type-badge-FORM     { background:#20c997; }
		.type-badge-OTHER    { background:#6c757d; }
		.filter-bar { background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px; padding:12px 16px; margin-bottom:16px; }
		.storage-url-fields  { display:none; }
		.storage-local-fields { display:none; }
		#patientSearchResults { position:absolute; z-index:1050; width:100%; max-height:260px; overflow-y:auto; background:#fff; border:1px solid #ced4da; border-top:none; border-radius:0 0 4px 4px; }
		#patientSearchResults .search-item { padding:8px 12px; cursor:pointer; border-bottom:1px solid #f0f0f0; }
		#patientSearchResults .search-item:hover { background:#f0f7ff; }
		#patientSearchResults .search-item small { color:#6c757d; }
		.send-count-badge { font-size:.7rem; }
		.file-info-existing { font-size:.8rem; color:#555; background:#f8f9fa; border-radius:3px; padding:4px 8px; margin-top:4px; }
	</style>
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
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
	</div>

	<?php require __DIR__ . '/../_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2 align-items-center">
					<div class="col-sm-6">
						<h1 class="m-0 text-dark">Asset Library</h1>
						<p class="text-muted mb-0">Documents, forms, links, and educational materials.</p>
					</div>
					<?php if ($action === 'list' && $canWrite): ?>
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
				<div class="alert alert-success alert-dismissible" role="alert">
					<i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($flashSuccess) ?>
					<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
				</div>
				<?php endif; ?>

				<?php if ($flashError !== null): ?>
				<div class="alert alert-danger alert-dismissible" role="alert">
					<i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($flashError) ?>
					<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
				</div>
				<?php endif; ?>

				<?php if ($action === 'edit' || $action === 'create'): ?>
				<!-- ══════════════════════════════════════════════════════
				     Create / Edit Form
				     ══════════════════════════════════════════════════ -->
				<?php
				$isCreate  = ($action === 'create');
				$formTitle = $isCreate
					? '<i class="fas fa-plus mr-2 text-primary"></i>New Asset'
					: '<i class="fas fa-pencil-alt mr-2 text-primary"></i>Edit: ' . htmlspecialchars($editAsset['title']);
				?>
				<div class="card">
					<div class="card-header border-0 d-flex align-items-center justify-content-between">
						<h3 class="card-title mb-0"><?= $formTitle ?></h3>
						<a href="assets.php" class="btn btn-sm btn-outline-secondary">
							<i class="fas fa-arrow-left mr-1"></i>Back to library
						</a>
					</div>

					<?php if ($formError !== null): ?>
					<div class="card-body pb-0">
						<div class="alert alert-danger" role="alert">
							<i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($formError) ?>
						</div>
					</div>
					<?php endif; ?>

					<div class="card-body">
						<form method="POST" action="assets.php"
						      enctype="multipart/form-data" novalidate id="assetForm">
							<input type="hidden" name="csrf_token"
							       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
							<input type="hidden" name="form_action"
							       value="<?= $isCreate ? 'create' : 'edit' ?>" />
							<?php if (!$isCreate): ?>
							<input type="hidden" name="asset_id"
							       value="<?= (int)$editAsset['asset_id'] ?>" />
							<?php endif; ?>

							<div class="row">
								<!-- Left column -->
								<div class="col-md-8">

									<!-- Title -->
									<div class="form-group">
										<label for="title" class="small font-weight-bold text-muted">
											Title <span class="text-danger">*</span>
										</label>
										<input type="text" name="title" id="title" class="form-control"
										       value="<?= htmlspecialchars($editAsset['title']) ?>" required />
									</div>

									<!-- Description -->
									<div class="form-group">
										<label for="description" class="small font-weight-bold text-muted">Description</label>
										<textarea name="description" id="description" class="form-control" rows="3"
										><?= htmlspecialchars($editAsset['description'] ?? '') ?></textarea>
									</div>

									<!-- Type + Category -->
									<div class="row">
										<div class="col-sm-6">
											<div class="form-group">
												<label for="asset_type" class="small font-weight-bold text-muted">Asset Type</label>
												<select class="form-control" name="asset_type" id="asset_type">
													<?php
													$types = [
														'PDF'      => 'PDF Document',
														'DOCUMENT' => 'Document (Word/Excel)',
														'FORM'     => 'Form',
														'IMAGE'    => 'Image',
														'VIDEO'    => 'Video (URL)',
														'AUDIO'    => 'Audio (URL)',
														'OTHER'    => 'Other',
													];
													foreach ($types as $val => $lbl):
													?>
													<option value="<?= $val ?>"
													        <?= ($editAsset['asset_type'] ?? '') === $val ? 'selected' : '' ?>>
														<?= $lbl ?>
													</option>
													<?php endforeach; ?>
												</select>
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group">
												<label for="category" class="small font-weight-bold text-muted">Category</label>
												<input type="text" name="category" id="category" class="form-control"
												       list="categoryList"
												       value="<?= htmlspecialchars($editAsset['category'] ?? '') ?>"
												       placeholder="e.g. Patient Education" />
												<datalist id="categoryList">
													<?php foreach ($categories as $cat): ?>
													<option value="<?= htmlspecialchars($cat) ?>">
													<?php endforeach; ?>
												</datalist>
											</div>
										</div>
									</div>

									<!-- Storage type -->
									<div class="form-group">
										<label class="small font-weight-bold text-muted d-block">Storage</label>
										<div class="btn-group btn-group-sm" id="storageToggle" role="group">
											<button type="button" class="btn btn-outline-secondary storage-btn"
											        data-storage="LOCAL">
												<i class="fas fa-upload mr-1"></i>Upload File
											</button>
											<button type="button" class="btn btn-outline-secondary storage-btn"
											        data-storage="URL">
												<i class="fas fa-link mr-1"></i>External URL
											</button>
											<button type="button" class="btn btn-outline-secondary storage-btn"
											        data-storage="OTHER">
												<i class="fas fa-ellipsis-h mr-1"></i>Other
											</button>
										</div>
										<input type="hidden" name="storage_type" id="storage_type"
										       value="<?= htmlspecialchars($editAsset['storage_type'] ?? 'LOCAL') ?>" />
									</div>

									<!-- LOCAL: file upload -->
									<div class="form-group storage-local-fields">
										<label for="asset_file" class="small font-weight-bold text-muted">Upload File</label>
										<div class="custom-file">
											<input type="file" class="custom-file-input" id="asset_file" name="asset_file"
											       accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.gif,.webp" />
											<label class="custom-file-label" for="asset_file">Choose file…</label>
										</div>
										<small class="text-muted">Max 20 MB. Accepted: PDF, Word, Excel, images (JPEG/PNG/GIF/WebP).</small>
										<?php if (!$isCreate && !empty($editAsset['file_name'])): ?>
										<div class="file-info-existing mt-1">
											<i class="fas fa-paperclip mr-1"></i>
											Current file: <strong><?= htmlspecialchars($editAsset['file_name']) ?></strong>
											<?php if ($editAsset['file_size_bytes']): ?>
											(<?= htmlspecialchars(number_format($editAsset['file_size_bytes'] / 1024, 1)) ?> KB)
											<?php endif; ?>
											— uploading a new file will replace it.
										</div>
										<?php endif; ?>
									</div>

									<!-- URL: external link -->
									<div class="form-group storage-url-fields">
										<label for="resource_url" class="small font-weight-bold text-muted">Resource URL</label>
										<div class="input-group">
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fas fa-link"></i></span>
											</div>
											<input type="url" name="resource_url" id="resource_url"
											       class="form-control"
											       value="<?= htmlspecialchars($editAsset['resource_url'] ?? '') ?>"
											       placeholder="https://…" />
										</div>
										<small class="text-muted">For videos, audio, or externally hosted documents.</small>
									</div>

									<!-- Notes -->
									<div class="form-group">
										<label for="notes" class="small font-weight-bold text-muted">Internal Notes</label>
										<textarea name="notes" id="notes" class="form-control" rows="2"
										          placeholder="Optional notes visible to staff only"><?= htmlspecialchars($editAsset['notes'] ?? '') ?></textarea>
									</div>

								</div>

								<!-- Right column: switches + submit -->
								<div class="col-md-4">
									<div class="card bg-light border-0 p-3">
										<h6 class="text-muted mb-3 small font-weight-bold text-uppercase">Visibility</h6>

										<div class="custom-control custom-switch mb-3">
											<input type="checkbox" class="custom-control-input" id="is_public" name="is_public"
											       <?= !empty($editAsset['is_public']) ? 'checked' : '' ?> />
											<label class="custom-control-label" for="is_public">
												<strong>Public</strong><br>
												<small class="text-muted">Visible to all patients in their portal</small>
											</label>
										</div>

										<div class="custom-control custom-switch mb-3">
											<input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
											       <?= !empty($editAsset['is_active']) ? 'checked' : '' ?> />
											<label class="custom-control-label" for="is_active">
												<strong>Active</strong><br>
												<small class="text-muted">Inactive assets are hidden from portal</small>
											</label>
										</div>

										<hr />
										<button type="submit" class="btn btn-primary btn-block">
											<i class="fas fa-save mr-1"></i><?= $isCreate ? 'Create Asset' : 'Save Changes' ?>
										</button>
										<a href="assets.php" class="btn btn-outline-secondary btn-block mt-2">Cancel</a>
									</div>
								</div>
							</div><!-- /.row -->
						</form>
					</div>
				</div>

				<?php else: ?>
				<!-- ══════════════════════════════════════════════════════
				     Filter Bar + Asset List
				     ══════════════════════════════════════════════════ -->

				<!-- Filter bar -->
				<form method="GET" action="assets.php" class="filter-bar">
					<div class="row align-items-end">
						<div class="col-sm-4 col-md-3 mb-2 mb-sm-0">
							<label class="small font-weight-bold text-muted mb-1 d-block">Search</label>
							<input type="text" name="search" class="form-control form-control-sm"
							       value="<?= htmlspecialchars($filterSearch) ?>"
							       placeholder="Title, description, category…" />
						</div>
						<div class="col-sm-3 col-md-2 mb-2 mb-sm-0">
							<label class="small font-weight-bold text-muted mb-1 d-block">Type</label>
							<select name="type" class="form-control form-control-sm">
								<option value="">All types</option>
								<?php
								$allTypes = ['VIDEO','AUDIO','PDF','IMAGE','DOCUMENT','FORM','OTHER'];
								foreach ($allTypes as $t):
								?>
								<option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= $t ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-sm-3 col-md-2 mb-2 mb-sm-0">
							<label class="small font-weight-bold text-muted mb-1 d-block">Category</label>
							<select name="category" class="form-control form-control-sm">
								<option value="">All categories</option>
								<?php foreach ($categories as $cat): ?>
								<option value="<?= htmlspecialchars($cat) ?>"
								        <?= $filterCategory === $cat ? 'selected' : '' ?>>
									<?= htmlspecialchars($cat) ?>
								</option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-sm-3 col-md-2 mb-2 mb-sm-0">
							<label class="small font-weight-bold text-muted mb-1 d-block">Visibility</label>
							<select name="public" class="form-control form-control-sm">
								<option value="">All</option>
								<option value="1" <?= $filterPublic === '1' ? 'selected' : '' ?>>Public only</option>
								<option value="0" <?= $filterPublic === '0' ? 'selected' : '' ?>>Staff only</option>
							</select>
						</div>
						<div class="col-sm-auto">
							<button type="submit" class="btn btn-sm btn-primary">
								<i class="fas fa-search mr-1"></i>Filter
							</button>
							<a href="assets.php" class="btn btn-sm btn-outline-secondary ml-1">Reset</a>
						</div>
					</div>
				</form>

				<!-- Asset table -->
				<div class="card">
					<div class="card-header border-0 d-flex justify-content-between align-items-center">
						<h3 class="card-title mb-0">
							<i class="fas fa-layer-group mr-2 text-primary"></i>
							<?php
							$totalCount = count($assetsList);
							echo $totalCount . ' asset' . ($totalCount !== 1 ? 's' : '');
							if ($filterSearch !== '' || $filterType !== '' || $filterCategory !== '' || $filterPublic !== '') {
								echo ' <small class="text-muted">(filtered)</small>';
							}
							?>
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
										<th>Patients</th>
										<th>Added by</th>
										<th class="text-right">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($assetsList as $asset): ?>
									<?php
									$typeBg = 'type-badge-' . $asset['asset_type'];
									$typeIcon = [
										'VIDEO'    => 'fa-video',
										'AUDIO'    => 'fa-music',
										'PDF'      => 'fa-file-pdf',
										'IMAGE'    => 'fa-image',
										'DOCUMENT' => 'fa-file-word',
										'FORM'     => 'fa-file-alt',
										'OTHER'    => 'fa-file',
									][$asset['asset_type']] ?? 'fa-file';
									?>
									<tr>
										<td>
											<strong><?= htmlspecialchars($asset['title']) ?></strong>
											<?php if (!empty($asset['resource_url'])): ?>
											<a href="<?= htmlspecialchars($asset['resource_url']) ?>"
											   target="_blank" rel="noopener noreferrer"
											   class="ml-1 small text-info" title="Open external URL">
												<i class="fas fa-external-link-alt"></i>
											</a>
											<?php elseif (!empty($asset['local_file_path'])): ?>
											<a href="assets.php?action=download&id=<?= (int)$asset['asset_id'] ?>"
											   target="_blank" class="ml-1 small text-success" title="Download / view file">
												<i class="fas fa-download"></i>
											</a>
											<?php endif; ?>
											<?php if (!empty($asset['description'])): ?>
											<br><small class="text-muted"><?= htmlspecialchars(mb_substr($asset['description'], 0, 80)) . (mb_strlen($asset['description']) > 80 ? '…' : '') ?></small>
											<?php endif; ?>
										</td>
										<td>
											<span class="badge text-white <?= $typeBg ?>" style="font-size:.75rem;">
												<i class="fas <?= $typeIcon ?> mr-1"></i><?= htmlspecialchars($asset['asset_type']) ?>
											</span>
										</td>
										<td class="small text-muted"><?= htmlspecialchars($asset['category'] ?? '—') ?></td>
										<td class="small">
											<?php if ($asset['storage_type'] === 'LOCAL'): ?>
											<span class="text-success"><i class="fas fa-hdd mr-1"></i>Local</span>
											<?php elseif ($asset['storage_type'] === 'URL'): ?>
											<span class="text-info"><i class="fas fa-link mr-1"></i>URL</span>
											<?php else: ?>
											<span class="text-muted"><?= htmlspecialchars($asset['storage_type']) ?></span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($asset['is_public']): ?>
											<span class="badge badge-success">Public</span>
											<?php else: ?>
											<span class="badge badge-secondary">Staff only</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($asset['is_active']): ?>
											<span class="badge badge-success">Active</span>
											<?php else: ?>
											<span class="badge badge-secondary">Inactive</span>
											<?php endif; ?>
										</td>
										<td class="text-center">
											<?php if ($asset['send_count'] > 0): ?>
											<span class="badge badge-info send-count-badge"
											      title="Sent to <?= (int)$asset['send_count'] ?> patient(s)">
												<i class="fas fa-user-injured mr-1"></i><?= (int)$asset['send_count'] ?>
											</span>
											<?php else: ?>
											<span class="text-muted small">—</span>
											<?php endif; ?>
										</td>
										<td class="small text-muted">
											<?= isset($asset['first_name'])
												? htmlspecialchars($asset['first_name'] . ' ' . $asset['last_name'])
												: '—' ?>
											<br><small><?= htmlspecialchars(date('d M Y', strtotime($asset['created_at']))) ?></small>
										</td>
										<td class="text-right text-nowrap">
											<?php if ($canSendToPatient): ?>
											<button type="button" class="btn btn-sm btn-outline-success mr-1"
											        title="Send to patient"
											        data-toggle="modal" data-target="#sendModal"
											        data-asset-id="<?= (int)$asset['asset_id'] ?>"
											        data-asset-title="<?= htmlspecialchars($asset['title']) ?>">
												<i class="fas fa-paper-plane"></i>
											</button>
											<?php endif; ?>
											<?php if ($canWrite): ?>
											<a href="assets.php?action=edit&id=<?= (int)$asset['asset_id'] ?>"
											   class="btn btn-sm btn-outline-primary mr-1" title="Edit">
												<i class="fas fa-pencil-alt"></i>
											</a>
											<button type="button" class="btn btn-sm btn-outline-danger"
											        data-toggle="modal" data-target="#deleteModal"
											        data-asset-id="<?= (int)$asset['asset_id'] ?>"
											        data-asset-title="<?= htmlspecialchars($asset['title']) ?>"
											        title="Delete">
												<i class="fas fa-trash-alt"></i>
											</button>
											<?php endif; ?>
										</td>
									</tr>
									<?php endforeach; ?>
									<?php if (empty($assetsList)): ?>
									<tr>
										<td colspan="9" class="text-center text-muted py-5">
											<i class="fas fa-layer-group fa-2x mb-2 d-block text-muted"></i>
											<?php if ($filterSearch !== '' || $filterType !== '' || $filterCategory !== '' || $filterPublic !== ''): ?>
											No assets match your filters.
											<a href="assets.php">Clear filters</a>
											<?php else: ?>
											No assets yet.
											<?php if ($canWrite): ?>
											<a href="assets.php?action=create">Add the first one</a>.
											<?php endif; ?>
											<?php endif; ?>
										</td>
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
		<div class="float-right d-none d-sm-inline">CareSystem <span class="badge badge-warning" style="font-size:.65rem;vertical-align:middle">Alpha</span></div>
		<strong>Asset Library</strong>
	</footer>
</div>

<!-- ── Delete Modal ─────────────────────────────────────────────────────── -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">
					<i class="fas fa-exclamation-triangle text-danger mr-2"></i>Confirm Delete
				</h5>
				<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
			</div>
			<div class="modal-body">
				Delete <strong id="deleteAssetName"></strong>? This also removes the file and any patient assignments. This cannot be undone.
			</div>
			<div class="modal-footer">
				<form method="POST" action="assets.php">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
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

<!-- ── Send to Patient Modal ───────────────────────────────────────────── -->
<?php if ($canSendToPatient): ?>
<div class="modal fade" id="sendModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">
					<i class="fas fa-paper-plane text-success mr-2"></i>Send to Patient Portal
				</h5>
				<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
			</div>
			<form method="POST" action="assets.php">
				<div class="modal-body">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
					<input type="hidden" name="form_action" value="send_to_patient" />
					<input type="hidden" name="asset_id" id="sendAssetId" value="" />
					<input type="hidden" name="patient_id" id="sendPatientId" value="" />

					<p class="mb-3">
						Sending: <strong id="sendAssetTitle"></strong>
					</p>

					<!-- Patient search -->
					<div class="form-group" style="position:relative;">
						<label for="patientSearchInput" class="small font-weight-bold text-muted">
							Find Patient <span class="text-danger">*</span>
						</label>
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text"><i class="fas fa-search"></i></span>
							</div>
							<input type="text" id="patientSearchInput" class="form-control"
							       placeholder="Type patient name to search…"
							       autocomplete="off" />
						</div>
						<div id="patientSearchResults"></div>
						<div id="selectedPatientDisplay" class="mt-2" style="display:none;">
							<span class="badge badge-success p-2">
								<i class="fas fa-user-injured mr-1"></i>
								<span id="selectedPatientName"></span>
								<button type="button" class="ml-2 btn btn-xs p-0 text-white" id="clearPatient" title="Clear">
									<i class="fas fa-times"></i>
								</button>
							</span>
						</div>
					</div>

					<!-- Note -->
					<div class="form-group">
						<label for="sendNote" class="small font-weight-bold text-muted">Note to patient (optional)</label>
						<textarea name="note" id="sendNote" class="form-control" rows="2"
						          placeholder="e.g. Please review this before your next visit"></textarea>
					</div>

					<div class="alert alert-info small py-2 mb-0">
						<i class="fas fa-info-circle mr-1"></i>
						The patient can view this in their portal under <strong>Resources</strong>.
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-success" id="sendSubmitBtn" disabled>
						<i class="fas fa-paper-plane mr-1"></i>Send
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
<?php endif; ?>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script>
// ── Custom file input label ──────────────────────────────────────────────
$(document).on('change', '.custom-file-input', function () {
	var name = $(this).val().split('\\').pop();
	$(this).siblings('.custom-file-label').text(name || 'Choose file…');
});

// ── Storage type toggle ──────────────────────────────────────────────────
var URL_TYPES = ['VIDEO', 'AUDIO'];

function applyStorageType(val) {
	$('#storage_type').val(val);
	$('.storage-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
	$('.storage-btn[data-storage="' + val + '"]').removeClass('btn-outline-secondary').addClass('btn-primary');
	$('.storage-local-fields').toggle(val === 'LOCAL');
	$('.storage-url-fields').toggle(val === 'URL' || val === 'OTHER');
}

// On asset type change, auto-set sensible storage default
$('#asset_type').on('change', function () {
	var assetType = $(this).val();
	if (URL_TYPES.indexOf(assetType) !== -1) {
		applyStorageType('URL');
	} else if ($('#storage_type').val() === 'URL' && URL_TYPES.indexOf(assetType) === -1) {
		applyStorageType('LOCAL');
	}
});

$('.storage-btn').on('click', function () {
	applyStorageType($(this).data('storage'));
});

// Initialise on page load
$(function () {
	applyStorageType($('#storage_type').val() || 'LOCAL');
});

// ── Delete modal ─────────────────────────────────────────────────────────
$('#deleteModal').on('show.bs.modal', function (e) {
	var btn = $(e.relatedTarget);
	$('#deleteAssetId').val(btn.data('asset-id'));
	$('#deleteAssetName').text(btn.data('asset-title'));
});

<?php if ($canSendToPatient): ?>
// ── Send to patient modal ─────────────────────────────────────────────────
$('#sendModal').on('show.bs.modal', function (e) {
	var btn = $(e.relatedTarget);
	$('#sendAssetId').val(btn.data('asset-id'));
	$('#sendAssetTitle').text(btn.data('asset-title'));
	// Reset patient selection
	$('#sendPatientId').val('');
	$('#patientSearchInput').val('').prop('disabled', false);
	$('#patientSearchResults').empty();
	$('#selectedPatientDisplay').hide();
	$('#sendSubmitBtn').prop('disabled', true);
	$('#sendNote').val('');
});

// Patient search autocomplete
var searchTimer = null;
$('#patientSearchInput').on('input', function () {
	clearTimeout(searchTimer);
	var q = $.trim($(this).val());
	$('#patientSearchResults').empty();
	if (q.length < 2) return;
	searchTimer = setTimeout(function () {
		$.getJSON('patients.php', { action: 'search', name: q }, function (data) {
			var $results = $('#patientSearchResults');
			$results.empty();
			if (!data.length) {
				$results.append('<div class="search-item text-muted">No patients found.</div>');
				return;
			}
			$.each(data.slice(0, 15), function (i, p) {
				var fullName = $.trim((p.first_name || '') + ' ' + (p.last_name || ''));
				var info     = [p.patient_code, p.city].filter(Boolean).join(' · ');
				$('<div class="search-item" tabindex="0">')
					.append('<strong>' + $('<span>').text(fullName).html() + '</strong>')
					.append(info ? ' <small>' + $('<span>').text(info).html() + '</small>' : '')
					.data('patient', p)
					.on('click', function () { selectPatient($(this).data('patient')); })
					.on('keydown', function (ev) {
						if (ev.key === 'Enter') selectPatient($(this).data('patient'));
					})
					.appendTo($results);
			});
		});
	}, 280);
});

function selectPatient(p) {
	var fullName = $.trim((p.first_name || '') + ' ' + (p.last_name || ''));
	$('#sendPatientId').val(p.patient_id);
	$('#selectedPatientName').text(fullName + ' (' + p.patient_code + ')');
	$('#selectedPatientDisplay').show();
	$('#patientSearchInput').val('').prop('disabled', true);
	$('#patientSearchResults').empty();
	$('#sendSubmitBtn').prop('disabled', false);
}

$('#clearPatient').on('click', function () {
	$('#sendPatientId').val('');
	$('#patientSearchInput').prop('disabled', false).focus();
	$('#selectedPatientDisplay').hide();
	$('#sendSubmitBtn').prop('disabled', true);
});
<?php endif; ?>
</script>
</body>
</html>
