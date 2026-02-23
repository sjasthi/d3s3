<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>User Management | CareSystem</title>
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
					<input type="checkbox" class="custom-control-input" id="themeToggleUsers" data-theme-toggle />
					<label class="custom-control-label" for="themeToggleUsers">Dark mode</label>
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
						<h1 class="m-0 text-dark">User Management</h1>
						<p class="text-muted mb-0">View and manage employee accounts.</p>
					</div>
					<?php if ($action !== 'edit' && in_array($_SESSION['user_role'] ?? '', ['SUPER_ADMIN', 'ADMIN'], true)): ?>
					<div class="col-sm-6 text-right mt-2 mt-sm-0">
						<button type="button" class="btn btn-outline-info mr-1"
								data-toggle="modal" data-target="#regCodeModal">
							<i class="fas fa-key mr-1"></i>Registration Code
						</button>
						<a href="emp_register.php" class="btn btn-primary">
							<i class="fas fa-user-plus mr-1"></i>Create User
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

				<?php if ($action === 'edit' && $editUser !== null): ?>
				<!-- ── Edit User ──────────────────────────────────── -->
				<div class="card">
					<div class="card-header border-0 d-flex align-items-center justify-content-between">
						<h3 class="card-title mb-0">
							<i class="fas fa-pencil-alt mr-2 text-primary"></i>
							Edit: <?= htmlspecialchars($editUser['first_name'] . ' ' . $editUser['last_name']) ?>
						</h3>
						<a href="users.php" class="btn btn-sm btn-outline-secondary">
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
						<form method="POST" action="users.php" novalidate>
							<input type="hidden" name="csrf_token"
								   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
							<input type="hidden" name="user_id"
								   value="<?= htmlspecialchars((string)$editUser['user_id']) ?>" />

							<!-- Name row -->
							<div class="row">
								<div class="col-sm-6">
									<div class="form-group">
										<label for="first_name" class="small font-weight-bold text-muted">First Name</label>
										<input type="text" name="first_name" id="first_name"
											   class="form-control"
											   value="<?= htmlspecialchars($editUser['first_name']) ?>"
											   required />
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<label for="last_name" class="small font-weight-bold text-muted">Last Name</label>
										<input type="text" name="last_name" id="last_name"
											   class="form-control"
											   value="<?= htmlspecialchars($editUser['last_name']) ?>"
											   required />
									</div>
								</div>
							</div>

							<!-- Email -->
							<div class="form-group">
								<label for="email" class="small font-weight-bold text-muted">Email</label>
								<div class="input-group">
									<div class="input-group-prepend">
										<span class="input-group-text"><i class="fas fa-envelope"></i></span>
									</div>
									<input type="email" name="email" id="email"
										   class="form-control"
										   value="<?= htmlspecialchars($editUser['email']) ?>"
										   autocomplete="off" required />
								</div>
							</div>

							<!-- Username -->
							<div class="form-group">
								<label for="username" class="small font-weight-bold text-muted">Username</label>
								<div class="input-group">
									<div class="input-group-prepend">
										<span class="input-group-text"><i class="fas fa-user"></i></span>
									</div>
									<input type="text" name="username" id="username"
										   class="form-control"
										   value="<?= htmlspecialchars($editUser['username']) ?>"
										   autocomplete="off" required />
								</div>
								<small class="text-muted">3–60 characters: letters, numbers, underscores, or hyphens.</small>
							</div>

							<!-- Role + Active row -->
							<div class="row">
								<div class="col-sm-6">
									<div class="form-group">
										<label for="role" class="small font-weight-bold text-muted">Role</label>
										<select class="form-control" name="role" id="role">
											<option value="DATA_ENTRY_OPERATOR" <?= $editUser['role'] === 'DATA_ENTRY_OPERATOR' ? 'selected' : '' ?>>Data Entry Operator</option>
											<option value="DOCTOR"              <?= $editUser['role'] === 'DOCTOR'              ? 'selected' : '' ?>>Doctor</option>
											<option value="TRIAGE_NURSE"        <?= $editUser['role'] === 'TRIAGE_NURSE'        ? 'selected' : '' ?>>Triage Nurse</option>
											<option value="NURSE"               <?= $editUser['role'] === 'NURSE'               ? 'selected' : '' ?>>Nurse</option>
											<option value="PARAMEDIC"           <?= $editUser['role'] === 'PARAMEDIC'           ? 'selected' : '' ?>>Paramedic</option>
											<option value="GRIEVANCE_OFFICER"   <?= $editUser['role'] === 'GRIEVANCE_OFFICER'   ? 'selected' : '' ?>>Grievance Officer</option>
											<option value="EDUCATION_TEAM"      <?= $editUser['role'] === 'EDUCATION_TEAM'      ? 'selected' : '' ?>>Education Team</option>
											<option value="ADMIN"               <?= $editUser['role'] === 'ADMIN'               ? 'selected' : '' ?>>Administrator</option>
											<option value="SUPER_ADMIN"         <?= $editUser['role'] === 'SUPER_ADMIN'         ? 'selected' : '' ?>>Super Administrator</option>
										</select>
									</div>
								</div>
								<div class="col-sm-6 d-flex align-items-end pb-3">
									<div class="custom-control custom-switch">
										<input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
											   <?= $editUser['is_active'] ? 'checked' : '' ?> />
										<label class="custom-control-label" for="is_active">Account Active</label>
									</div>
								</div>
							</div>

							<!-- Password Reset (optional) -->
							<hr />
							<p class="small text-muted mb-3">
								<i class="fas fa-lock mr-1"></i>
								Leave both fields blank to keep the current password.
							</p>
							<div class="row">
								<div class="col-sm-6">
									<div class="form-group">
										<label for="new_password" class="small font-weight-bold text-muted">New Password</label>
										<div class="input-group">
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fas fa-lock"></i></span>
											</div>
											<input type="password" name="new_password" id="new_password"
												   class="form-control"
												   placeholder="Min 8 characters"
												   autocomplete="off" />
										</div>
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<label for="confirm_password" class="small font-weight-bold text-muted">Confirm New Password</label>
										<div class="input-group">
											<div class="input-group-prepend">
												<span class="input-group-text"><i class="fas fa-lock"></i></span>
											</div>
											<input type="password" name="confirm_password" id="confirm_password"
												   class="form-control"
												   placeholder="Re-enter password"
												   autocomplete="off" />
										</div>
									</div>
								</div>
							</div>

							<!-- Actions -->
							<div class="mt-3">
								<button type="submit" class="btn btn-primary">
									<i class="fas fa-save mr-1"></i>Save Changes
								</button>
								<a href="users.php" class="btn btn-link text-muted ml-2">Cancel</a>
							</div>
						</form>
					</div>
				</div>

				<?php else: ?>
				<!-- ── User List ──────────────────────────────────── -->
				<?php
				$roleLabels  = ['SUPER_ADMIN' => 'Super Admin', 'ADMIN' => 'Admin', 'DOCTOR' => 'Doctor', 'TRIAGE_NURSE' => 'Triage Nurse', 'NURSE' => 'Nurse', 'PARAMEDIC' => 'Paramedic', 'GRIEVANCE_OFFICER' => 'Grievance Officer', 'EDUCATION_TEAM' => 'Education Team', 'DATA_ENTRY_OPERATOR' => 'Data Entry'];
				$roleClasses = ['SUPER_ADMIN' => 'badge-danger', 'ADMIN' => 'badge-warning', 'DOCTOR' => 'badge-info', 'TRIAGE_NURSE' => 'badge-primary', 'NURSE' => 'badge-success', 'PARAMEDIC' => 'badge-primary', 'GRIEVANCE_OFFICER' => 'badge-dark', 'EDUCATION_TEAM' => 'badge-light', 'DATA_ENTRY_OPERATOR' => 'badge-secondary'];
				?>
				<div class="card">
					<div class="card-header border-0">
						<h3 class="card-title mb-0">
							<i class="fas fa-users mr-2 text-primary"></i>All Users
						</h3>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-hover mb-0">
								<thead class="thead-light">
									<tr>
										<th>Name</th>
										<th>Email</th>
										<th>Username</th>
										<th>Role</th>
										<th>Status</th>
										<th>Last Login</th>
										<th class="text-right">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($users as $user): ?>
									<tr>
										<td><strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong></td>
										<td class="small"><?= htmlspecialchars($user['email']) ?></td>
										<td class="small text-muted"><?= htmlspecialchars($user['username']) ?></td>
										<td>
											<span class="badge <?= htmlspecialchars($roleClasses[$user['role']] ?? 'badge-secondary') ?>">
												<?= htmlspecialchars($roleLabels[$user['role']] ?? $user['role']) ?>
											</span>
										</td>
										<td>
											<?php if ($user['is_active']): ?>
											<span class="badge badge-success">Active</span>
											<?php else: ?>
											<span class="badge badge-secondary">Inactive</span>
											<?php endif; ?>
										</td>
										<td class="small text-muted">
											<?= $user['last_login_at'] !== null ? htmlspecialchars($user['last_login_at']) : 'Never' ?>
										</td>
										<td class="text-right">
											<a href="users.php?action=edit&amp;id=<?= (int)$user['user_id'] ?>"
											   class="btn btn-sm btn-outline-primary">
												<i class="fas fa-pencil-alt"></i>
											</a>
										</td>
									</tr>
									<?php endforeach; ?>
									<?php if (empty($users)): ?>
									<tr>
										<td colspan="7" class="text-center text-muted py-4">No users found.</td>
									</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<?php
				// TODO: Registration Code management card (Super Admin only).
				// Lets a Super Admin view/change the REGISTRATION_CODE from this page
				// instead of editing .env via FTP.
				//
				// To enable: make .env writable by the web server (chmod 664 .env),
				// then uncomment this block AND the matching code in AdminController::users().
				//
				// if ($_SESSION['user_role'] === 'SUPER_ADMIN'):
				?>
				<!--
				<div class="card mt-4">
					<div class="card-header border-0">
						<h3 class="card-title mb-0">
							<i class="fas fa-key mr-2 text-primary"></i>Registration Code
						</h3>
					</div>

					<?php if (isset($regCodeError) && $regCodeError !== null): ?>
					<div class="card-body pb-0">
						<div class="alert alert-danger" role="alert">
							<?= htmlspecialchars($regCodeError) ?>
						</div>
					</div>
					<?php endif; ?>

					<div class="card-body">
						<p class="text-muted small mb-3">
							<i class="fas fa-info-circle mr-1"></i>
							This code is required when creating a new account. Share it only with authorized staff.
						</p>
						<form method="POST" action="users.php" class="form-inline" novalidate>
							<input type="hidden" name="csrf_token"
								   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
							<input type="hidden" name="form_action" value="update_registration_code" />
							<div class="input-group mr-3" style="max-width: 350px;">
								<div class="input-group-prepend">
									<span class="input-group-text"><i class="fas fa-key"></i></span>
								</div>
								<input type="text" name="registration_code" id="registration_code"
									   class="form-control"
									   value="<?= htmlspecialchars($registrationCode) ?>"
									   required />
							</div>
							<button type="submit" class="btn btn-primary">
								<i class="fas fa-save mr-1"></i>Update Code
							</button>
						</form>
					</div>
				</div>
				<?php // endif; ?>
				-->

			</div>
		</section>
	</div>

	<footer class="main-footer">
		<div class="float-right d-none d-sm-inline">CareSystem</div>
		<strong>User Management</strong>
	</footer>
</div>

<?php if (in_array($_SESSION['user_role'] ?? '', ['SUPER_ADMIN', 'ADMIN'], true)): ?>
<div class="modal fade" id="regCodeModal" tabindex="-1" role="dialog" aria-labelledby="regCodeModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="regCodeModalLabel">
					<i class="fas fa-key mr-2"></i>Registration Code
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body text-center">
				<div class="input-group">
					<input type="text" class="form-control text-center font-weight-bold"
						   id="regCodeDisplay"
						   value="<?= htmlspecialchars($registrationCode) ?>"
						   readonly />
					<div class="input-group-append">
						<button class="btn btn-outline-secondary" type="button"
								id="copyRegCodeBtn" title="Copy to clipboard">
							<i class="fas fa-copy"></i>
						</button>
					</div>
				</div>
				<small class="text-muted mt-2 d-block">Share only with authorized staff.</small>
			</div>
			<div class="modal-footer justify-content-center">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script>
document.getElementById('copyRegCodeBtn') && document.getElementById('copyRegCodeBtn').addEventListener('click', function() {
	var input = document.getElementById('regCodeDisplay');
	input.select();
	document.execCommand('copy');
	var icon = this.querySelector('i');
	icon.className = 'fas fa-check text-success';
	setTimeout(function() { icon.className = 'fas fa-copy'; }, 1500);
});
</script>
</body>
</html>
