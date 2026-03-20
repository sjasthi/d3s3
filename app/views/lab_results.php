<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Labwork | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed<?= ($_SESSION['font_size'] ?? 'normal') === 'large' ? ' font-size-large' : '' ?>"
      data-theme-server="<?= htmlspecialchars($_SESSION['theme'] ?? 'system') ?>">
<div class="wrapper">

	<!-- Navbar -->
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
				<a class="btn btn-sm btn-outline-secondary" href="dashboard.php" role="button">
					<i class="fas fa-arrow-left mr-1"></i>Dashboard
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

		<!-- Page header -->
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2 align-items-center">
					<div class="col-sm-6">
						<h1 class="m-0 text-dark"><i class="fas fa-flask mr-2 text-primary"></i>Labwork</h1>
						<p class="text-muted mb-0">Process pending lab orders and record results.</p>
					</div>
					<div class="col-sm-6 text-right">
						<span class="badge badge-primary px-3 py-2" style="font-size:.85rem;">
							<?= count($pendingOrders) ?> pending
						</span>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

				<!-- ── Pending Orders ──────────────────────────────────── -->
				<div class="card card-outline card-primary">
					<div class="card-header">
						<h5 class="card-title mb-0"><i class="fas fa-hourglass-half mr-2"></i>Pending Orders</h5>
					</div>
					<div class="card-body p-0">
						<?php if (empty($pendingOrders)): ?>
						<div class="text-center text-muted py-5">
							<i class="fas fa-check-circle fa-3x mb-3 text-success" style="opacity:.6"></i>
							<p class="mb-0">No pending lab orders &mdash; the queue is clear.</p>
						</div>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-hover mb-0" id="pendingTable">
								<thead class="thead-light">
									<tr>
										<th>Patient</th>
										<th>Test</th>
										<th>Order Notes</th>
										<th>Ordered By</th>
										<th>Ordered</th>
										<?php if (can($_SESSION['user_role'] ?? '', 'labwork', 'W')): ?>
										<th class="text-right">Action</th>
										<?php endif; ?>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($pendingOrders as $o): ?>
									<tr id="order-row-<?= $o['order_id'] ?>">
										<td>
											<strong><?= htmlspecialchars($o['first_name'] . ' ' . $o['last_name']) ?></strong>
											<br><small class="text-muted"><?= htmlspecialchars($o['patient_code']) ?></small>
										</td>
										<td><span class="font-weight-bold"><?= htmlspecialchars($o['test_name']) ?></span></td>
										<td class="text-muted"><?= $o['order_notes'] ? htmlspecialchars($o['order_notes']) : '&mdash;' ?></td>
										<td><?= htmlspecialchars(trim($o['ordered_by_first'] . ' ' . $o['ordered_by_last'])) ?></td>
										<td><small><?= htmlspecialchars(date('M j, Y g:i A', strtotime($o['ordered_at']))) ?></small></td>
										<?php if (can($_SESSION['user_role'] ?? '', 'labwork', 'W')): ?>
										<td class="text-right">
											<button type="button" class="btn btn-success btn-sm btn-complete-order"
											        data-order-id="<?= $o['order_id'] ?>"
											        data-test-name="<?= htmlspecialchars($o['test_name']) ?>"
											        data-patient="<?= htmlspecialchars($o['first_name'] . ' ' . $o['last_name']) ?>"
											        data-order-notes="<?= htmlspecialchars($o['order_notes'] ?? '') ?>">
												<i class="fas fa-check mr-1"></i>Complete
											</button>
										</td>
										<?php endif; ?>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- ── Recently Completed (last 48 h) ─────────────────── -->
				<div class="card card-outline card-success mt-3">
					<div class="card-header">
						<h5 class="card-title mb-0"><i class="fas fa-check-double mr-2"></i>Recently Completed <small class="text-muted font-weight-normal">(last 48 hours)</small></h5>
					</div>
					<div class="card-body p-0">
						<?php if (empty($recentCompleted)): ?>
						<div class="text-center text-muted py-4">
							<p class="mb-0">No completed orders in the last 48 hours.</p>
						</div>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-sm mb-0">
								<thead class="thead-light">
									<tr>
										<th>Patient</th>
										<th>Test</th>
										<th>Result Notes</th>
										<th>Completed By</th>
										<th>Completed</th>
									</tr>
								</thead>
								<tbody id="completedBody">
									<?php foreach ($recentCompleted as $o): ?>
									<tr>
										<td>
											<strong><?= htmlspecialchars($o['first_name'] . ' ' . $o['last_name']) ?></strong>
											<br><small class="text-muted"><?= htmlspecialchars($o['patient_code']) ?></small>
										</td>
										<td><?= htmlspecialchars($o['test_name']) ?></td>
										<td><?= $o['result_notes'] ? nl2br(htmlspecialchars($o['result_notes'])) : '<em class="text-muted">No notes</em>' ?></td>
										<td><?= htmlspecialchars(trim(($o['completed_by_first'] ?? '') . ' ' . ($o['completed_by_last'] ?? ''))) ?></td>
										<td><small><?= htmlspecialchars(date('M j, Y g:i A', strtotime($o['completed_at']))) ?></small></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php endif; ?>
					</div>
				</div>

			</div><!-- /.container-fluid -->
		</section>
	</div>

	<footer class="main-footer text-sm text-muted">
		<strong>D3S3 CareSystem</strong> &mdash; Labwork
	</footer>
</div>

<!-- ── Complete Order Modal ──────────────────────────────────────────── -->
<div class="modal fade" id="completeOrderModal" tabindex="-1" role="dialog" aria-labelledby="completeOrderModalTitle" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="completeOrderModalTitle"><i class="fas fa-flask mr-2"></i>Complete Lab Order</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<dl class="row mb-3">
					<dt class="col-sm-4">Patient</dt>
					<dd class="col-sm-8 mb-0" id="completePatientName"></dd>
					<dt class="col-sm-4">Test</dt>
					<dd class="col-sm-8 mb-0 font-weight-bold" id="completeTestName"></dd>
					<dt class="col-sm-4 mt-2" id="completeOrderNotesLabel" style="display:none">Order Notes</dt>
					<dd class="col-sm-8 mt-2 text-muted mb-0" id="completeOrderNotes" style="display:none"></dd>
				</dl>
				<div class="form-group mb-1">
					<label class="font-weight-bold" for="resultNotes">Result Notes <small class="font-weight-normal text-muted">(findings, values, observations)</small></label>
					<textarea class="form-control" id="resultNotes" rows="4" placeholder="Enter results, values, or any relevant observations&#8230;"></textarea>
				</div>
				<div class="alert alert-danger d-none mt-2 mb-0" id="completeOrderError"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-success" id="btnConfirmComplete">
					<i class="fas fa-check mr-1"></i>Complete
				</button>
			</div>
		</div>
	</div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script>
(function () {
	var csrfToken  = <?= json_encode($_SESSION['csrf_token']) ?>;
	var activeOrder = null;

	var $modal       = $('#completeOrderModal');
	var $patientName = $('#completePatientName');
	var $testName    = $('#completeTestName');
	var $orderNotes  = $('#completeOrderNotes');
	var $orderNotesLabel = $('#completeOrderNotesLabel');
	var $resultNotes = $('#resultNotes');
	var $error       = $('#completeOrderError');
	var $confirmBtn  = $('#btnConfirmComplete');

	// Open modal when "Complete" is clicked on any pending order row
	$(document).on('click', '.btn-complete-order', function () {
		activeOrder = {
			id:         $(this).data('order-id'),
			testName:   $(this).data('test-name'),
			patient:    $(this).data('patient'),
			orderNotes: $(this).data('order-notes') || ''
		};
		$patientName.text(activeOrder.patient);
		$testName.text(activeOrder.testName);
		if (activeOrder.orderNotes) {
			$orderNotes.text(activeOrder.orderNotes).parent().show();
			$orderNotesLabel.show();
		} else {
			$orderNotes.parent().hide();
			$orderNotesLabel.hide();
		}
		$resultNotes.val('');
		$error.addClass('d-none').text('');
		$confirmBtn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Complete');
		$modal.modal('show');
	});

	// Reset on close
	$modal.on('hidden.bs.modal', function () {
		activeOrder = null;
	});

	// Submit completion
	$confirmBtn.on('click', function () {
		if (!activeOrder) return;
		$error.addClass('d-none');
		$confirmBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Saving&hellip;');

		$.ajax({
			url: 'lab_results.php?action=complete',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				csrf_token:   csrfToken,
				order_id:     activeOrder.id,
				result_notes: $resultNotes.val().trim()
			}),
			dataType: 'json',
			success: function (r) {
				if (!r.success) {
					$error.text(r.message || 'Failed to complete order.').removeClass('d-none');
					$confirmBtn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Complete');
					return;
				}
				// Remove the row from the pending table
				$('#order-row-' + activeOrder.id).fadeOut(300, function () { $(this).remove(); });
				$modal.modal('hide');

				// Update the pending count badge
				var $badge = $('.badge.badge-primary');
				var cur = parseInt($badge.text()) || 0;
				if (cur > 0) { $badge.text((cur - 1) + ' pending'); }
				if (cur - 1 <= 0) {
					// Show empty state
					$('#pendingTable').closest('.table-responsive').replaceWith(
						'<div class="text-center text-muted py-5">' +
						'<i class="fas fa-check-circle fa-3x mb-3 text-success" style="opacity:.6"></i>' +
						'<p class="mb-0">No pending lab orders &mdash; the queue is clear.</p>' +
						'</div>'
					);
				}
			},
			error: function () {
				$error.text('Server error. Please try again.').removeClass('d-none');
				$confirmBtn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Complete');
			}
		});
	});
})();
</script>
</body>
</html>
