<?php
$pageTitle           = 'Resources';
$activePage          = 'resources';
$portalUnreadCount   = 0;  // messages badge – not needed on this page
$resourcesUnreadCount = 0; // we're on this page, badge not relevant
require __DIR__ . '/_nav.php';

// ── Type helper maps ────────────────────────────────────────────────────────
$typeIcons = [
	'VIDEO'    => 'fa-video',
	'AUDIO'    => 'fa-music',
	'PDF'      => 'fa-file-pdf',
	'IMAGE'    => 'fa-image',
	'DOCUMENT' => 'fa-file-word',
	'FORM'     => 'fa-file-alt',
	'OTHER'    => 'fa-file',
];
$typeBg = [
	'VIDEO'    => '#17a2b8',
	'AUDIO'    => '#6f42c1',
	'PDF'      => '#dc3545',
	'IMAGE'    => '#28a745',
	'DOCUMENT' => '#fd7e14',
	'FORM'     => '#20c997',
	'OTHER'    => '#6c757d',
];

function renderAssetLink(array $asset, bool $isSent = false): string {
	$id    = (int)$asset['asset_id'];
	$title = htmlspecialchars($asset['title']);

	if (!empty($asset['resource_url'])) {
		$url  = htmlspecialchars($asset['resource_url']);
		$icon = '<i class="fas fa-external-link-alt mr-1"></i>';
		$attrs = 'href="' . $url . '" target="_blank" rel="noopener noreferrer"';
	} elseif ($asset['storage_type'] === 'LOCAL' && !empty($asset['local_file_path'])) {
		$attrs = 'href="assets.php?action=download&id=' . $id . '" target="_blank"';
		$icon  = '<i class="fas fa-download mr-1"></i>';
	} else {
		return '<span class="text-muted small">No file available</span>';
	}

	// Mark-read form for sent (unread) assets
	$markRead = '';
	if ($isSent && empty($asset['is_read'])) {
		$markRead = ' <form method="POST" action="patient_portal.php" class="d-inline ml-1">'
		          . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">'
		          . '<input type="hidden" name="action" value="mark_asset_read">'
		          . '<input type="hidden" name="asset_id" value="' . $id . '">'
		          . '<button type="submit" class="btn btn-xs btn-outline-secondary py-0 px-1 small">'
		          . '<i class="fas fa-check mr-1"></i>Mark read</button></form>';
	}

	return '<a ' . $attrs . ' class="btn btn-sm btn-outline-primary">'
	       . $icon . $title . '</a>' . $markRead;
}
?>

<?php if ($flashSuccess !== null): ?>
<div class="alert alert-success" role="alert">
	<i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($flashSuccess) ?>
</div>
<?php endif; ?>

<h4 class="mb-1 text-primary"><i class="fas fa-layer-group mr-2"></i>Resources</h4>
<p class="text-muted mb-4">Documents, forms, and links sent to you by your care team, plus public educational materials.</p>

<!-- ── Sent to you ──────────────────────────────────────────────────────── -->
<div class="portal-card card mb-4">
	<div class="card-header bg-white d-flex align-items-center justify-content-between">
		<h5 class="mb-0 font-weight-bold">
			<i class="fas fa-inbox text-success mr-2"></i>Sent to You
		</h5>
		<?php
		$unreadSent = array_filter($sentAssets, fn($a) => !$a['is_read']);
		if (count($unreadSent) > 0):
		?>
		<span class="badge badge-danger"><?= count($unreadSent) ?> new</span>
		<?php endif; ?>
	</div>

	<?php if (empty($sentAssets)): ?>
	<div class="card-body text-center text-muted py-4">
		<i class="fas fa-inbox fa-2x mb-2 d-block"></i>
		Nothing has been sent to you yet. Your care team can share documents and resources through this section.
	</div>
	<?php else: ?>
	<div class="list-group list-group-flush">
		<?php foreach ($sentAssets as $asset): ?>
		<?php
		$bg       = $typeBg[$asset['asset_type']] ?? '#6c757d';
		$icon     = $typeIcons[$asset['asset_type']] ?? 'fa-file';
		$unread   = !$asset['is_read'];
		$rowClass = $unread ? 'bg-light' : '';
		?>
		<div class="list-group-item <?= $rowClass ?>">
			<div class="d-flex align-items-start">
				<div class="mr-3 text-center" style="width:36px;">
					<span class="badge text-white" style="background:<?= $bg ?>;font-size:.85rem;padding:6px 8px;">
						<i class="fas <?= $icon ?>"></i>
					</span>
				</div>
				<div class="flex-grow-1">
					<div class="d-flex justify-content-between align-items-start flex-wrap">
						<div>
							<strong class="<?= $unread ? 'text-dark' : '' ?>">
								<?= htmlspecialchars($asset['title']) ?>
							</strong>
							<?php if ($unread): ?>
							<span class="badge badge-danger ml-1" style="font-size:.65rem;">New</span>
							<?php endif; ?>
							<span class="badge ml-1 text-white" style="background:<?= $bg ?>;font-size:.65rem;">
								<?= htmlspecialchars($asset['asset_type']) ?>
							</span>
						</div>
						<small class="text-muted ml-2 text-nowrap">
							<?= htmlspecialchars(date('d M Y', strtotime($asset['sent_at']))) ?>
						</small>
					</div>

					<?php if (!empty($asset['description'])): ?>
					<p class="text-muted small mb-1 mt-1"><?= htmlspecialchars($asset['description']) ?></p>
					<?php endif; ?>

					<?php if (!empty($asset['note'])): ?>
					<div class="alert alert-light small py-1 px-2 mb-2 mt-1">
						<i class="fas fa-comment-medical mr-1 text-muted"></i>
						<em><?= htmlspecialchars($asset['note']) ?></em>
						<?php if (!empty($asset['sender_first'])): ?>
						&mdash; <?= htmlspecialchars(trim($asset['sender_first'] . ' ' . $asset['sender_last'])) ?>
						<?php endif; ?>
					</div>
					<?php endif; ?>

					<div class="mt-1">
						<?= renderAssetLink($asset, true) ?>
					</div>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</div>

<!-- ── Public library ───────────────────────────────────────────────────── -->
<?php if (!empty($publicAssets)): ?>
<?php
// Group by type
$grouped = [];
foreach ($publicAssets as $a) {
	$grouped[$a['asset_type']][] = $a;
}
$typeLabels = [
	'VIDEO'    => 'Videos',
	'AUDIO'    => 'Audio',
	'PDF'      => 'PDFs',
	'IMAGE'    => 'Images',
	'DOCUMENT' => 'Documents',
	'FORM'     => 'Forms',
	'OTHER'    => 'Other',
];
?>
<div class="portal-card card mb-4">
	<div class="card-header bg-white">
		<h5 class="mb-0 font-weight-bold">
			<i class="fas fa-book-open text-primary mr-2"></i>Public Library
		</h5>
		<small class="text-muted">General educational materials available to all patients.</small>
	</div>

	<?php foreach ($grouped as $type => $typeAssets): ?>
	<div class="card-body pb-0">
		<h6 class="text-muted small font-weight-bold text-uppercase mb-2">
			<i class="fas <?= $typeIcons[$type] ?? 'fa-file' ?> mr-1"
			   style="color:<?= $typeBg[$type] ?? '#6c757d' ?>"></i>
			<?= htmlspecialchars($typeLabels[$type] ?? $type) ?>
		</h6>
		<div class="row">
			<?php foreach ($typeAssets as $asset): ?>
			<div class="col-sm-6 col-lg-4 mb-3">
				<div class="border rounded p-2 h-100">
					<p class="mb-1 font-weight-bold small">
						<?= htmlspecialchars($asset['title']) ?>
					</p>
					<?php if (!empty($asset['description'])): ?>
					<p class="text-muted small mb-2"><?= htmlspecialchars(mb_substr($asset['description'], 0, 100)) . (mb_strlen($asset['description']) > 100 ? '…' : '') ?></p>
					<?php endif; ?>
					<?= renderAssetLink($asset, false) ?>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endforeach; ?>
	<div class="card-footer bg-white"></div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_nav_close.php'; ?>
