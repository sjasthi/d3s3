<?php
/**
 * app/views/portal_messages.php
 *
 * Staff-side view for patient portal messages.
 * Shows all patient-initiated threads; staff can view and reply.
 * Requires staff session (patient_data read) — enforced by PatientPortalController::staffMessages().
 */
if (!function_exists('can')) {
	require_once __DIR__ . '/../config/permissions.php';
}
if (!function_exists('__')) {
	require_once __DIR__ . '/../../app/config/lang.php';
	load_language($_SESSION['language'] ?? 'en');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Patient Messages | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
		.thread-row a { text-decoration:none; color:inherit; display:block; }
		.thread-row:hover { background:#f0f4ff; }
		.thread-row.active-thread { background:#e8f0fe; border-left:3px solid #007bff; }
		.msg-bubble { border-radius:12px; padding:.6rem 1rem; max-width:80%; }
		.msg-bubble.patient { background:#007bff; color:#fff; }
		.msg-bubble.staff   { background:#e9ecef; color:#212529; margin-left:auto; }
		.msg-scroll { overflow-y:auto; max-height:50vh; }
	</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed<?= ($_SESSION['font_size'] ?? 'normal') === 'large' ? ' font-size-large' : '' ?>"
      data-theme-server="<?= htmlspecialchars($_SESSION['theme'] ?? 'system') ?>">
<div class="wrapper">

	<nav class="main-header navbar navbar-expand navbar-white navbar-light">
		<ul class="navbar-nav">
			<li class="nav-item">
				<a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
			</li>
		</ul>
		<ul class="navbar-nav ml-auto">
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="dashboard.php">
					<i class="fas fa-arrow-left mr-1"></i>Dashboard
				</a>
			</li>
		</ul>
	</nav>

	<?php require __DIR__ . '/_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-6">
						<h1 class="m-0">
							<i class="fas fa-comments mr-2"></i>Patient Messages
						</h1>
					</div>
					<div class="col-sm-6">
						<ol class="breadcrumb float-sm-right">
							<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
							<li class="breadcrumb-item active">Patient Messages</li>
						</ol>
					</div>
				</div>
			</div>
		</div>

		<div class="content">
			<div class="container-fluid">

				<?php if ($flashSuccess): ?>
				<div class="alert alert-success alert-dismissible fade show">
					<?= htmlspecialchars($flashSuccess) ?>
					<button type="button" class="close" data-dismiss="alert">&times;</button>
				</div>
				<?php endif; ?>

				<div class="row">
					<!-- Thread list -->
					<div class="col-md-4">
						<div class="card">
							<div class="card-header">
								<h5 class="card-title mb-0">
									<i class="fas fa-inbox mr-1"></i> Patient Threads
									<?php
									$unreadThreadCount = count(array_filter($threads ?? [], fn($t) => $t['staff_unread']));
									if ($unreadThreadCount > 0):
									?>
									<span class="badge badge-danger ml-1"><?= $unreadThreadCount ?></span>
									<?php endif; ?>
								</h5>
							</div>
							<div class="card-body p-0">
								<?php if (empty($threads)): ?>
								<div class="text-center text-muted py-5">
									<i class="fas fa-inbox fa-2x mb-2"></i>
									<p class="small">No patient messages yet.</p>
								</div>
								<?php else: ?>
								<ul class="list-group list-group-flush">
									<?php foreach ($threads as $t): ?>
									<li class="list-group-item p-0 thread-row
									    <?= ($activeThread && (int)$activeThread['thread_id'] === (int)$t['thread_id']) ? 'active-thread' : '' ?>">
										<a href="portal_messages.php?thread=<?= (int)$t['thread_id'] ?>"
										   class="px-3 py-2">
											<div class="d-flex justify-content-between align-items-start">
												<div class="overflow-hidden mr-2">
													<div class="font-weight-bold small">
														<?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?>
														<code class="ml-1 text-muted" style="font-size:.7rem"><?= htmlspecialchars($t['patient_code']) ?></code>
													</div>
													<div class="text-muted small text-truncate">
														<?= htmlspecialchars($t['subject']) ?>
													</div>
												</div>
												<div class="text-right flex-shrink-0">
													<small class="text-muted"><?= date('d M', strtotime($t['last_message_at'])) ?></small>
													<?php if ($t['staff_unread']): ?>
													<br /><span class="badge badge-danger">New</span>
													<?php endif; ?>
												</div>
											</div>
										</a>
									</li>
									<?php endforeach; ?>
								</ul>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<!-- Thread detail + reply -->
					<div class="col-md-8">
						<?php if ($activeThread): ?>

						<div class="card d-flex flex-column">
							<div class="card-header d-flex justify-content-between align-items-center">
								<div>
									<strong><?= htmlspecialchars($activeThread['subject']) ?></strong>
									<div class="small text-muted">
										<?= htmlspecialchars($activeThread['first_name'] . ' ' . $activeThread['last_name']) ?>
										&nbsp;<code><?= htmlspecialchars($activeThread['patient_code']) ?></code>
										<?php if (!empty($activeThread['patient_id'])): ?>
										&nbsp;&mdash;&nbsp;
										<a href="patients.php?action=view&id=<?= (int)$activeThread['patient_id'] ?>">View Patient Record</a>
										<?php endif; ?>
									</div>
								</div>
								<a href="portal_messages.php" class="btn btn-sm btn-outline-secondary">
									<i class="fas fa-arrow-left"></i>
								</a>
							</div>
							<div class="card-body msg-scroll">
								<?php if (empty($threadMessages)): ?>
								<p class="text-muted text-center">No messages in this thread.</p>
								<?php endif; ?>
								<?php foreach ($threadMessages as $msg): ?>
								<div class="d-flex mb-3 <?= $msg['sender_type'] === 'STAFF' ? 'justify-content-end' : '' ?>">
									<div class="msg-bubble <?= $msg['sender_type'] === 'PATIENT' ? 'patient' : 'staff' ?>">
										<?php if ($msg['sender_type'] === 'STAFF' && $msg['staff_first']): ?>
										<div class="small mb-1 font-weight-bold">
											<?= htmlspecialchars($msg['staff_first'] . ' ' . $msg['staff_last']) ?>
										</div>
										<?php endif; ?>
										<?= nl2br(htmlspecialchars($msg['body'])) ?>
										<div class="small mt-1 <?= $msg['sender_type'] === 'PATIENT' ? 'text-white-50' : 'text-muted' ?>">
											<?= htmlspecialchars(date('d M Y, g:i A', strtotime($msg['sent_at']))) ?>
										</div>
									</div>
								</div>
								<?php endforeach; ?>
							</div>
							<div class="card-footer">
								<form method="POST" action="portal_messages.php?thread=<?= (int)$activeThread['thread_id'] ?>">
									<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
									<input type="hidden" name="action" value="staff_reply" />
									<input type="hidden" name="thread_id" value="<?= (int)$activeThread['thread_id'] ?>" />
									<div class="input-group">
										<textarea name="body" class="form-control" rows="2"
										          maxlength="5000" placeholder="Type your reply to the patient…" required></textarea>
										<div class="input-group-append">
											<button type="submit" class="btn btn-primary">
												<i class="fas fa-paper-plane mr-1"></i>Send
											</button>
										</div>
									</div>
									<small class="text-muted">This reply will appear in the patient's portal inbox.</small>
								</form>
							</div>
						</div>

						<?php else: ?>
						<div class="card">
							<div class="card-body text-center text-muted py-5">
								<i class="fas fa-comments fa-2x mb-2"></i>
								<p>Select a patient conversation on the left to view messages and reply.</p>
							</div>
						</div>
						<?php endif; ?>
					</div>

				</div><!-- /.row -->

			</div><!-- /.container-fluid -->
		</div><!-- /.content -->
	</div><!-- /.content-wrapper -->

</div><!-- /.wrapper -->

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme.js"></script>
</body>
</html>
