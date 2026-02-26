<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Permissions Management | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
		/* ── Table base ─────────────────────────────────────────────── */
		.perm-table th,
		.perm-table td { vertical-align: middle; white-space: nowrap; }
		.perm-table th.resource-header {
			font-size: .75rem; text-transform: uppercase;
			letter-spacing: .05em; text-align: center;
		}

		/* ── Radio pill buttons ─────────────────────────────────────── */
		.perm-radio-group { display: flex; gap: .3rem; justify-content: center; }
		.perm-radio-group label {
			display: flex; align-items: center; gap: .15rem;
			font-size: .78rem; font-weight: 500;
			padding: .12rem .3rem; border-radius: .25rem;
			border: 1px solid transparent; margin-bottom: 0;
			transition: border-color .1s, background .1s;
		}
		.perm-radio-group input[type="radio"] { display: none; }

		/* Selected state colours */
		.perm-radio-group label:has(input[value="R"]:checked)  { border-color: #007bff; background: #e8f1ff; color: #0056b3; }
		.perm-radio-group label:has(input[value="RW"]:checked) { border-color: #28a745; background: #e8f5ed; color: #155724; }
		.perm-radio-group label:has(input[value="N"]:checked)  { border-color: #6c757d; background: #f0f0f0; color: #495057; }

		/* ── Read-only mode: suppress hover / pointer ───────────────── */
		.perm-table:not(.edit-mode) .perm-radio-group label {
			cursor: default;
		}
		.perm-table:not(.edit-mode) .perm-radio-group label:hover {
			border-color: transparent !important;
			background: inherit !important;
		}

		/* ── Edit mode: hover feedback ──────────────────────────────── */
		.perm-table.edit-mode .perm-radio-group label:hover {
			border-color: #adb5bd;
		}

		/* ── Locked admin rows ──────────────────────────────────────── */
		tr.admin-row { background-color: rgba(0,0,0,.025); }
		.locked-badge { font-size: .7rem; }

		/* ── Changed cell highlight ─────────────────────────────────── */
		td.perm-changed {
			background-color: #fff8e1 !important;
			border-left: 3px solid #ffc107 !important;
		}

		/* ── Cell inner layout (radio group + undo button) ──────────── */
		.perm-cell-inner {
			display: flex; align-items: center;
			justify-content: center; gap: .35rem;
		}

		/* ── Undo button ────────────────────────────────────────────── */
		.perm-undo-btn {
			background: none; border: none;
			color: #adb5bd; cursor: pointer;
			padding: .1rem .2rem; font-size: .8rem; line-height: 1;
			border-radius: .2rem; transition: color .15s;
		}
		.perm-undo-btn:hover { color: #dc3545; }

		/* ── Edit mode banner colours (used in JS) ──────────────────── */
		.perm-R  { color: #0056b3; font-weight: 700; }
		.perm-RW { color: #155724; font-weight: 700; }
		.perm-N  { color: #495057; font-weight: 700; }

		/* ── Modal change list ──────────────────────────────────────── */
		#change-list { font-size: .88rem; max-height: 240px; overflow-y: auto; }
		#change-list li { padding: .18rem 0; }
		.arrow-icon { color: #6c757d; margin: 0 .2rem; }
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
			<li class="nav-item d-flex align-items-center mr-3">
				<div class="custom-control custom-switch theme-switch">
					<input type="checkbox" class="custom-control-input" id="themeTogglePermissions" data-theme-toggle />
					<label class="custom-control-label" for="themeTogglePermissions">Dark mode</label>
				</div>
			</li>
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="admin.php?page=panel" role="button">
					<i class="fas fa-arrow-left mr-1"></i>Admin Panel
				</a>
			</li>
		</ul>
	</nav>

	<?php require __DIR__ . '/../_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2 align-items-center">
					<div class="col-sm-7">
						<h1 class="m-0 text-dark">Permissions Management</h1>
						<p class="text-muted mb-0">Control which roles can read or write each resource. Admin roles are always full-access and cannot be changed.</p>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

				<?php if (isset($formError) && $formError !== null): ?>
				<div class="alert alert-danger alert-dismissible fade show" role="alert">
					<i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($formError) ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<?php endif; ?>

				<div class="card">
					<!-- ── Card header ─────────────────────────────────────── -->
					<div class="card-header border-0 d-flex align-items-center justify-content-between">
						<h3 class="card-title mb-0">
							<i class="fas fa-shield-alt mr-2"></i>Role &amp; Resource Access Matrix
						</h3>
						<div class="d-flex align-items-center">
							<!-- View-mode header controls -->
							<div id="view-header-controls">
								<span class="text-muted mr-3" style="font-size:.82rem;">
									<i class="fas fa-eye mr-1"></i>Read-only view
								</span>
								<button type="button" class="btn btn-warning btn-sm" id="edit-btn">
									<i class="fas fa-edit mr-1"></i>Edit Access Controls
								</button>
							</div>
							<!-- Edit-mode header controls (hidden initially) -->
							<div id="edit-header-controls" style="display:none;">
								<span class="badge badge-warning mr-3" style="font-size:.82rem; padding:.4rem .65rem;">
									<i class="fas fa-edit mr-1"></i>Editing
								</span>
								<button type="button" class="btn btn-outline-secondary btn-sm" id="cancel-edit-btn">
									<i class="fas fa-undo mr-1"></i>Cancel Editing
								</button>
							</div>
						</div>
					</div>

					<div class="card-body p-0">
						<!-- ── Edit mode info banner (hidden initially) ────── -->
						<div id="edit-banner" class="alert alert-warning rounded-0 border-0 border-bottom mb-0 py-2 px-4" style="display:none;">
							<i class="fas fa-info-circle mr-2"></i>
							<strong>Edit mode active.</strong>
							Changed cells are highlighted in amber. Click the
							<i class="fas fa-undo-alt text-danger"></i> undo arrow on any changed cell to revert it.
							&nbsp;&mdash;&nbsp;
							<span id="change-counter" class="font-weight-bold">No pending changes.</span>
						</div>

						<div class="table-responsive">
							<form id="permissions-form" method="post" action="permissions.php?action=save">
								<input type="hidden" name="csrf_token"
								       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
								<input type="hidden" name="confirm_password"
								       id="hidden-confirm-password" value="" />

								<table class="table table-bordered table-sm perm-table mb-0" id="perm-table">
									<thead class="thead-light">
										<tr>
											<th style="min-width:160px;">Role</th>
											<?php foreach ($resources as $res): ?>
											<th class="resource-header">
												<?= htmlspecialchars($resourceLabels[$res]) ?>
											</th>
											<?php endforeach; ?>
										</tr>
									</thead>
									<tbody>
										<!-- ── Locked admin rows ─────────────────── -->
										<?php foreach ($adminRoles as $adminRole): ?>
										<tr class="admin-row">
											<td>
												<strong><?= htmlspecialchars($roleLabels[$adminRole]) ?></strong>
												<span class="badge badge-secondary ml-1 locked-badge">locked</span>
											</td>
											<?php foreach ($resources as $res): ?>
											<td class="text-center">
												<span class="badge badge-success locked-badge">RW</span>
											</td>
											<?php endforeach; ?>
										</tr>
										<?php endforeach; ?>

										<!-- ── Editable role rows ─────────────────── -->
										<?php foreach ($roles as $role): ?>
										<tr>
											<td><strong><?= htmlspecialchars($roleLabels[$role]) ?></strong></td>
											<?php foreach ($resources as $res): ?>
											<?php $currentPerm = $matrix[$role][$res] ?? 'N'; ?>
											<td class="text-center perm-cell">
												<div class="perm-cell-inner">
													<div class="perm-radio-group">

														<label title="Read only">
															<input type="radio"
															       name="perm[<?= htmlspecialchars($role) ?>][<?= htmlspecialchars($res) ?>]"
															       value="R"
															       data-role="<?= htmlspecialchars($role) ?>"
															       data-resource="<?= htmlspecialchars($res) ?>"
															       data-original="<?= htmlspecialchars($currentPerm) ?>"
															       <?= $currentPerm === 'R'  ? 'checked' : '' ?>
															       disabled />
															<span>R</span>
														</label>

														<label title="Read &amp; Write">
															<input type="radio"
															       name="perm[<?= htmlspecialchars($role) ?>][<?= htmlspecialchars($res) ?>]"
															       value="RW"
															       data-role="<?= htmlspecialchars($role) ?>"
															       data-resource="<?= htmlspecialchars($res) ?>"
															       data-original="<?= htmlspecialchars($currentPerm) ?>"
															       <?= $currentPerm === 'RW' ? 'checked' : '' ?>
															       disabled />
															<span>RW</span>
														</label>

														<label title="No access">
															<input type="radio"
															       name="perm[<?= htmlspecialchars($role) ?>][<?= htmlspecialchars($res) ?>]"
															       value="N"
															       data-role="<?= htmlspecialchars($role) ?>"
															       data-resource="<?= htmlspecialchars($res) ?>"
															       data-original="<?= htmlspecialchars($currentPerm) ?>"
															       <?= $currentPerm === 'N'  ? 'checked' : '' ?>
															       disabled />
															<span>N</span>
														</label>

													</div><!-- /.perm-radio-group -->

													<!-- Per-cell undo button (hidden until cell changes) -->
													<button type="button"
													        class="perm-undo-btn"
													        title="Undo this change"
													        style="display:none;"
													        aria-label="Undo">
														<i class="fas fa-undo-alt"></i>
													</button>

												</div><!-- /.perm-cell-inner -->
											</td>
											<?php endforeach; ?>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</form>
						</div><!-- /.table-responsive -->
					</div><!-- /.card-body -->

					<!-- ── Card footer ──────────────────────────────────────── -->
					<div class="card-footer d-flex justify-content-between align-items-center">
						<div class="text-muted" style="font-size:.8rem;">
							<strong>R</strong> = Read only &nbsp;&bull;&nbsp;
							<strong>RW</strong> = Read &amp; Write &nbsp;&bull;&nbsp;
							<strong>N</strong> = No access
						</div>
						<div>
							<!-- View-mode footer: back link only -->
							<div id="view-footer-controls">
								<a href="admin.php?page=panel" class="btn btn-secondary btn-sm">
									<i class="fas fa-arrow-left mr-1"></i>Back to Admin Panel
								</a>
							</div>
							<!-- Edit-mode footer: save button (hidden initially) -->
							<div id="edit-footer-controls" style="display:none;">
								<button type="button" class="btn btn-primary btn-sm" id="save-btn">
									<i class="fas fa-save mr-1"></i>Save Changes
								</button>
							</div>
						</div>
					</div>
				</div><!-- /.card -->

			</div><!-- /.container-fluid -->
		</section>
	</div><!-- /.content-wrapper -->

	<footer class="main-footer">
		<div class="float-right d-none d-sm-inline">CareSystem</div>
		<strong>Permissions Management</strong>
	</footer>
</div><!-- /.wrapper -->

<!-- ── Confirm Changes Modal ──────────────────────────────────────────────── -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog"
     aria-labelledby="confirmModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="confirmModalLabel">
					<i class="fas fa-shield-alt mr-2 text-warning"></i>Confirm Permission Changes
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<p class="text-muted mb-2" style="font-size:.88rem;">
					The following changes will be applied:
				</p>
				<ul id="change-list" class="list-unstyled pl-2 mb-3"></ul>
				<hr class="mt-0" />
				<div class="form-group mb-0">
					<label for="modal-password" class="font-weight-bold">
						<i class="fas fa-lock mr-1"></i>Enter your password to authorise:
					</label>
					<input type="password" id="modal-password" class="form-control"
					       placeholder="Your current password"
					       autocomplete="current-password" />
					<small class="form-text text-muted">Required to confirm and save changes.</small>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">
					<i class="fas fa-times mr-1"></i>Cancel
				</button>
				<button type="button" class="btn btn-primary" id="confirm-submit-btn">
					<i class="fas fa-check mr-1"></i>Confirm &amp; Save
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
	'use strict';

	/* ── Session heartbeat ──────────────────────────────────────────
	 * Pings the server every 60 s to refresh the 5-minute idle timer
	 * while the admin is actively on this page.  When they navigate
	 * away the pings stop; after 5 minutes of silence the server marks
	 * the session expired and the next visit will show the gate again.
	 *────────────────────────────────────────────────────────────────*/
	var CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>';

	var heartbeatTimer = setInterval(function () {
		fetch('permissions.php?action=heartbeat', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: 'csrf_token=' + encodeURIComponent(CSRF_TOKEN)
		})
		.then(function (r) { return r.json(); })
		.then(function (data) {
			if (!data.ok) {
				// Session has expired server-side; stop pinging and redirect
				// so the gate form is shown on the next visit.
				clearInterval(heartbeatTimer);
				window.location.href = 'permissions.php';
			}
		})
		.catch(function () {
			// Network hiccup – silently ignore; the server-side idle check
			// will catch a genuine prolonged absence on the next page load.
		});
	}, 60000); // every 60 seconds

	/* ── Label maps for modal change list ──────────────────────────── */
	var RESOURCE_LABELS = {
		assets:       'Assets',
		case_sheets:  'Case Sheets',
		events:       'Events',
		patient_data: 'Patient Data',
		users:        'Users',
		feedback:     'Feedback',
		messages:     'Messages',
		tasks:        'Tasks'
	};

	var ROLE_LABELS = {
		DOCTOR:              'Doctor',
		TRIAGE_NURSE:        'Triage Nurse',
		NURSE:               'Nurse',
		PARAMEDIC:           'Paramedic',
		GRIEVANCE_OFFICER:   'Grievance Officer',
		EDUCATION_TEAM:      'Education Team',
		DATA_ENTRY_OPERATOR: 'Data Entry Operator'
	};

	/* ── Helpers ────────────────────────────────────────────────────── */
	function permBadge(val) {
		var cls = val === 'RW' ? 'perm-RW' : (val === 'R' ? 'perm-R' : 'perm-N');
		return '<span class="' + cls + '">' + val + '</span>';
	}

	function updateCounter() {
		var n = $('.perm-changed').length;
		$('#change-counter').text(
			n === 0
				? 'No pending changes.'
				: n + ' pending change' + (n === 1 ? '.' : 's.')
		);
	}

	function collectChanges() {
		var changes = [];
		$('#perm-table input[type="radio"]:checked[data-original]').each(function () {
			var $inp = $(this);
			if ($inp.val() !== $inp.data('original')) {
				changes.push({
					role:     $inp.data('role'),
					resource: $inp.data('resource'),
					from:     $inp.data('original'),
					to:       $inp.val()
				});
			}
		});
		return changes;
	}

	/* ── Cell change state ──────────────────────────────────────────── */
	function updateCell($td) {
		// Find the checked radio in this cell.
		var $checked  = $td.find('input[type="radio"]:checked');
		var $undoBtn  = $td.find('.perm-undo-btn');
		var original  = $checked.data('original'); // same for all radios in cell
		var current   = $checked.val();

		if (current !== original) {
			$td.addClass('perm-changed');
			$undoBtn.show();
		} else {
			$td.removeClass('perm-changed');
			$undoBtn.hide();
		}
		updateCounter();
	}

	/* ── Edit mode toggle ───────────────────────────────────────────── */
	$('#edit-btn').on('click', function () {
		// Enable all editable radio inputs.
		$('#perm-table input[type="radio"][data-original]').prop('disabled', false);

		// Add edit-mode class to table (activates hover CSS).
		$('#perm-table').addClass('edit-mode');

		// Swap header controls.
		$('#view-header-controls').hide();
		$('#edit-header-controls').show();

		// Swap footer controls.
		$('#view-footer-controls').hide();
		$('#edit-footer-controls').show();

		// Show info banner.
		$('#edit-banner').show();
		updateCounter();
	});

	/* ── Cancel editing ─────────────────────────────────────────────── */
	$('#cancel-edit-btn').on('click', function () {
		// Revert every editable cell to its original value.
		$('#perm-table input[type="radio"][data-original]').each(function () {
			var $inp = $(this);
			$inp.prop('checked', $inp.val() === $inp.data('original'));
		});

		// Clear all change indicators.
		$('.perm-changed').removeClass('perm-changed');
		$('.perm-undo-btn').hide();

		// Disable all editable radios.
		$('#perm-table input[type="radio"][data-original]').prop('disabled', true);

		// Remove edit-mode class.
		$('#perm-table').removeClass('edit-mode');

		// Swap controls back.
		$('#edit-header-controls').hide();
		$('#view-header-controls').show();
		$('#edit-footer-controls').hide();
		$('#view-footer-controls').show();

		// Hide banner.
		$('#edit-banner').hide();
		updateCounter();
	});

	/* ── Detect radio changes ───────────────────────────────────────── */
	$(document).on('change', '#perm-table input[type="radio"][data-original]', function () {
		updateCell($(this).closest('td'));
	});

	/* ── Per-cell undo ──────────────────────────────────────────────── */
	$(document).on('click', '.perm-undo-btn', function () {
		var $td      = $(this).closest('td');
		var original = $td.find('input[type="radio"]').first().data('original');

		// Re-check the radio whose value matches the original.
		$td.find('input[type="radio"]').each(function () {
			$(this).prop('checked', $(this).val() === original);
		});

		$td.removeClass('perm-changed');
		$(this).hide();
		updateCounter();
	});

	/* ── Save Changes button ────────────────────────────────────────── */
	$('#save-btn').on('click', function () {
		var changes = collectChanges();

		if (changes.length === 0) {
			alert('No changes to save. Modify at least one permission before saving.');
			return;
		}

		// Build change list HTML for modal.
		var $list = $('#change-list').empty();
		changes.forEach(function (c) {
			$list.append(
				'<li>' +
				'<strong>' + (ROLE_LABELS[c.role] || c.role) + '</strong>' +
				' &mdash; ' + (RESOURCE_LABELS[c.resource] || c.resource) + ': ' +
				permBadge(c.from) +
				' <i class="fas fa-arrow-right arrow-icon"></i> ' +
				permBadge(c.to) +
				'</li>'
			);
		});

		// Reset password field and show modal.
		$('#modal-password').val('').removeClass('is-invalid');
		$('#confirmModal').modal('show');
	});

	/* ── Auto-focus password field when modal opens ─────────────────── */
	$('#confirmModal').on('shown.bs.modal', function () {
		$('#modal-password').trigger('focus');
	});

	/* ── Allow Enter key to submit from password field ──────────────── */
	$('#modal-password').on('keydown', function (e) {
		if (e.key === 'Enter') {
			e.preventDefault();
			$('#confirm-submit-btn').trigger('click');
		}
	});

	/* ── Confirm & submit ───────────────────────────────────────────── */
	$('#confirm-submit-btn').on('click', function () {
		var pwd = $('#modal-password').val();
		if (!pwd) {
			$('#modal-password').addClass('is-invalid').trigger('focus');
			return;
		}
		$('#hidden-confirm-password').val(pwd);
		$('#confirmModal').modal('hide');
		$('#permissions-form').submit();
	});

	/* Clear invalid state while typing */
	$('#modal-password').on('input', function () {
		$(this).removeClass('is-invalid');
	});

}());
</script>
</body>
</html>
