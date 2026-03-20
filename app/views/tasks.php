<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Tasks | CareSystem</title>
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
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2 align-items-center">
					<div class="col-sm-6">
						<h1 class="m-0 text-dark">Tasks</h1>
						<p class="text-muted mb-0">Manage your to-do items.</p>
					</div>
					<?php if (($view ?? 'list') !== 'create'): ?>
					<div class="col-sm-6 text-right">
						<a href="tasks.php?action=create" class="btn btn-primary btn-sm">
							<i class="fas fa-plus mr-1"></i>New Task
						</a>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

				<?php if (isset($flashSuccess) && $flashSuccess !== null): ?>
				<div class="alert alert-success alert-dismissible fade show" role="alert">
					<i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($flashSuccess) ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<?php endif; ?>

				<?php if (($view ?? 'list') === 'create'): ?>
				<!-- ── Create form ───────────────────────────── -->
				<div class="row justify-content-center">
					<div class="col-md-8">

						<?php if (isset($formError) && $formError !== null): ?>
						<div class="alert alert-danger alert-dismissible fade show" role="alert">
							<i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($formError) ?>
							<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						</div>
						<?php endif; ?>

						<div class="card">
							<div class="card-header">
								<h3 class="card-title"><i class="fas fa-plus mr-2"></i>New Task</h3>
							</div>
							<div class="card-body">
								<form method="post" action="tasks.php?action=create">
									<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

									<div class="form-group">
										<label for="title">Title <span class="text-danger">*</span></label>
										<input type="text" name="title" id="title" class="form-control"
										       maxlength="200" required
										       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" />
									</div>

									<div class="form-group">
										<label for="description">Description</label>
										<textarea name="description" id="description" class="form-control" rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
									</div>

									<div class="row">
										<div class="col-md-4">
											<div class="form-group">
												<label for="priority">Priority</label>
												<select name="priority" id="priority" class="form-control">
													<option value="LOW"    <?= ($_POST['priority'] ?? '') === 'LOW'    ? 'selected' : '' ?>>Low</option>
													<option value="MEDIUM" <?= ($_POST['priority'] ?? 'MEDIUM') === 'MEDIUM' ? 'selected' : '' ?>>Medium</option>
													<option value="HIGH"   <?= ($_POST['priority'] ?? '') === 'HIGH'   ? 'selected' : '' ?>>High</option>
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label for="due_date">Due Date</label>
												<input type="date" name="due_date" id="due_date" class="form-control"
												       value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>" />
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label for="assigned_to_user_id">Assign To</label>
												<select name="assigned_to_user_id" id="assigned_to_user_id" class="form-control">
													<option value="">— Myself —</option>
													<?php
													$roleLabels = ['SUPER_ADMIN'=>'Super Admin','ADMIN'=>'Admin','DOCTOR'=>'Doctor','TRIAGE_NURSE'=>'Triage Nurse','NURSE'=>'Nurse','PARAMEDIC'=>'Paramedic','GRIEVANCE_OFFICER'=>'Grievance Officer','EDUCATION_TEAM'=>'Education Team','DATA_ENTRY_OPERATOR'=>'Data Entry Operator'];
													foreach ($allUsers as $u):
													?>
													<option value="<?= (int)$u['user_id'] ?>"
														<?= (int)($_POST['assigned_to_user_id'] ?? 0) === (int)$u['user_id'] ? 'selected' : '' ?>>
														<?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
													</option>
													<?php endforeach; ?>
												</select>
											</div>
										</div>
									</div>

									<div class="d-flex justify-content-end">
										<a href="tasks.php" class="btn btn-secondary mr-2">Cancel</a>
										<button type="submit" class="btn btn-primary">
											<i class="fas fa-save mr-1"></i>Create Task
										</button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>

				<?php else: ?>
				<!-- ── Task list ─────────────────────────────── -->
				<?php if ($isAdmin): ?>
				<ul class="nav nav-tabs mb-3">
					<li class="nav-item">
						<a class="nav-link <?= ($tab ?? 'mine') === 'mine' ? 'active' : '' ?>" href="tasks.php?tab=mine">
							<i class="fas fa-user mr-1"></i>My Tasks
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?= ($tab ?? '') === 'all' ? 'active' : '' ?>" href="tasks.php?tab=all">
							<i class="fas fa-list mr-1"></i>All Tasks
						</a>
					</li>
				</ul>
				<?php endif; ?>

				<?php
				$statusColors   = ['PENDING'=>'badge-secondary','IN_PROGRESS'=>'badge-warning','DONE'=>'badge-success'];
				$statusLabels   = ['PENDING'=>'Pending','IN_PROGRESS'=>'In Progress','DONE'=>'Done'];
				$priorityColors = ['LOW'=>'text-muted','MEDIUM'=>'text-info','HIGH'=>'text-danger'];
				$priorityLabels = ['LOW'=>'Low','MEDIUM'=>'Medium','HIGH'=>'High'];
				$userId         = (int)$_SESSION['user_id'];
				?>

				<div class="card">
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-hover mb-0">
								<thead class="thead-light">
									<tr>
										<th>Title</th>
										<th>Priority</th>
										<th>Status</th>
										<?php if ($isAdmin && ($tab ?? '') === 'all'): ?>
										<th>Created By</th>
										<?php endif; ?>
										<th>Assigned To</th>
										<th>Due</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($tasks as $task): ?>
									<?php $canEdit = $isAdmin || (int)$task['created_by_user_id'] === $userId || (int)($task['assigned_to_user_id'] ?? 0) === $userId; ?>
									<tr class="<?= $task['status'] === 'DONE' ? 'text-muted' : '' ?>">
										<td>
											<?= htmlspecialchars($task['title']) ?>
											<?php if (!empty($task['description'])): ?>
											<br /><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($task['description'], 0, 80, '…')) ?></small>
											<?php endif; ?>
										</td>
										<td>
											<span class="<?= $priorityColors[$task['priority']] ?? '' ?>">
												<i class="fas fa-circle mr-1" style="font-size:.6rem"></i><?= $priorityLabels[$task['priority']] ?? htmlspecialchars($task['priority']) ?>
											</span>
										</td>
										<td>
											<span class="badge <?= $statusColors[$task['status']] ?? 'badge-secondary' ?>">
												<?= $statusLabels[$task['status']] ?? htmlspecialchars($task['status']) ?>
											</span>
										</td>
										<?php if ($isAdmin && ($tab ?? '') === 'all'): ?>
										<td class="small"><?= htmlspecialchars($task['creator_first'] . ' ' . $task['creator_last']) ?></td>
										<?php endif; ?>
										<td class="small">
											<?= !empty($task['assignee_first'])
												? htmlspecialchars($task['assignee_first'] . ' ' . $task['assignee_last'])
												: '<span class="text-muted">—</span>' ?>
										</td>
										<td class="small">
											<?= !empty($task['due_date'])
												? htmlspecialchars(date('d M Y', strtotime($task['due_date'])))
												: '<span class="text-muted">—</span>' ?>
										</td>
										<td>
											<?php if ($canEdit && $task['status'] !== 'DONE'): ?>
											<form method="post" action="tasks.php?action=update" class="d-inline">
												<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
												<input type="hidden" name="task_id" value="<?= (int)$task['task_id'] ?>" />
												<?php if ($task['status'] === 'PENDING'): ?>
												<input type="hidden" name="status" value="IN_PROGRESS" />
												<button type="submit" class="btn btn-sm btn-outline-warning" title="Start">
													<i class="fas fa-play"></i>
												</button>
												<?php else: ?>
												<input type="hidden" name="status" value="DONE" />
												<button type="submit" class="btn btn-sm btn-outline-success" title="Mark Done">
													<i class="fas fa-check"></i>
												</button>
												<?php endif; ?>
											</form>
											<?php endif; ?>
											<?php if ($canEdit): ?>
											<form method="post" action="tasks.php?action=delete" class="d-inline"
											      onsubmit="return confirm('Delete this task?')">
												<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
												<input type="hidden" name="task_id" value="<?= (int)$task['task_id'] ?>" />
												<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
													<i class="fas fa-trash-alt"></i>
												</button>
											</form>
											<?php endif; ?>
										</td>
									</tr>
									<?php endforeach; ?>
									<?php if (empty($tasks)): ?>
									<tr>
										<td colspan="7" class="text-center text-muted py-4">No tasks found. Click "New Task" to create one.</td>
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
		<strong>D3S3 CareSystem</strong>
	</footer>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
