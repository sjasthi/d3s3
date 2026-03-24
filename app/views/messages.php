<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Messages | CareSystem</title>
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
						<h1 class="m-0 text-dark">
							Messages
							<?php if (!empty($unreadCount)): ?>
							<span class="badge badge-danger ml-2"><?= (int)$unreadCount ?></span>
							<?php endif; ?>
						</h1>
					</div>
					<div class="col-sm-6 text-right">
						<a href="messages.php?action=compose" class="btn btn-primary btn-sm">
							<i class="fas fa-pen mr-1"></i>Compose
						</a>
					</div>
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

				<?php if (isset($flashError) && $flashError !== null): ?>
				<div class="alert alert-danger alert-dismissible fade show" role="alert">
					<i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($flashError) ?>
					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<?php endif; ?>

				<?php if ($view === 'compose'): ?>
				<!-- ── Compose ─────────────────────────────── -->
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
								<h3 class="card-title"><i class="fas fa-pen mr-2"></i>New Message</h3>
							</div>
							<div class="card-body">
								<form method="post" action="messages.php?action=compose">
									<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

									<div class="form-group">
										<label>To <span class="text-danger">*</span></label>
										<?php
										$roleLabels = ['SUPER_ADMIN'=>'Super Admin','ADMIN'=>'Admin','DOCTOR'=>'Doctor','TRIAGE_NURSE'=>'Triage Nurse','NURSE'=>'Nurse','PARAMEDIC'=>'Paramedic','GRIEVANCE_OFFICER'=>'Grievance Officer','EDUCATION_TEAM'=>'Education Team','DATA_ENTRY_OPERATOR'=>'Data Entry Operator'];
										?>
										<select name="recipient_user_ids[]" id="recipient_user_ids"
										        class="form-control" multiple size="7" required>
											<?php foreach ($recipients as $r): ?>
											<option value="<?= (int)$r['user_id'] ?>"
												<?= in_array((int)$r['user_id'], $preselectedIds, true) ? 'selected' : '' ?>>
												<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>
												(<?= htmlspecialchars($roleLabels[$r['role']] ?? $r['role']) ?>)
											</option>
											<?php endforeach; ?>
										</select>
										<small class="form-text text-muted">
											Hold <kbd>Ctrl</kbd> (Windows/Linux) or <kbd>⌘</kbd> (Mac) to select multiple recipients.
										</small>
									</div>

									<div class="form-group">
										<label for="subject">Subject <span class="text-danger">*</span></label>
										<input type="text" name="subject" id="subject" class="form-control"
										       maxlength="200" required
										       value="<?= htmlspecialchars($_POST['subject'] ?? $prefillSubject) ?>" />
									</div>

									<div class="form-group">
										<label for="body">Message <span class="text-danger">*</span></label>
										<textarea name="body" id="body" class="form-control" rows="8"
										          required><?= htmlspecialchars($_POST['body'] ?? '') ?></textarea>
									</div>

									<div class="d-flex justify-content-end">
										<a href="messages.php" class="btn btn-secondary mr-2">Cancel</a>
										<button type="submit" class="btn btn-primary">
											<i class="fas fa-paper-plane mr-1"></i>Send
										</button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>

				<?php elseif ($view === 'view'): ?>
				<!-- ── View single message ─────────────────── -->
				<div class="row justify-content-center">
					<div class="col-md-8">
						<div class="card">
							<div class="card-header">
								<h3 class="card-title">
									<i class="fas fa-envelope-open mr-2"></i><?= htmlspecialchars($message['subject']) ?>
								</h3>
							</div>
							<div class="card-body">
								<dl class="row small text-muted mb-3">
									<dt class="col-sm-2">From</dt>
									<dd class="col-sm-10"><?= htmlspecialchars($message['sender_first'] . ' ' . $message['sender_last']) ?></dd>
									<dt class="col-sm-2">To</dt>
									<dd class="col-sm-10">
										<?php if (!empty($threadRecipients)): ?>
											<?= htmlspecialchars(implode(', ', array_map(
												fn($r) => $r['first_name'] . ' ' . $r['last_name'],
												$threadRecipients
											))) ?>
										<?php else: ?>
											<?= htmlspecialchars($message['recipient_first'] . ' ' . $message['recipient_last']) ?>
										<?php endif; ?>
									</dd>
									<dt class="col-sm-2">Date</dt>
									<dd class="col-sm-10"><?= htmlspecialchars(date('d M Y H:i', strtotime($message['sent_at']))) ?></dd>
								</dl>
								<hr />
								<p class="mb-0"><?= nl2br(htmlspecialchars($message['body'])) ?></p>
							</div>
							<div class="card-footer d-flex justify-content-between">
								<a href="messages.php" class="btn btn-sm btn-outline-secondary">
									<i class="fas fa-arrow-left mr-1"></i>Back to Inbox
								</a>
								<?php if ((int)$message['recipient_user_id'] === (int)$_SESSION['user_id']): ?>
								<div>
									<a href="messages.php?action=compose&reply_to=<?= (int)$message['sender_user_id'] ?>&reply_subject=<?= urlencode('Re: ' . $message['subject']) ?>"
									   class="btn btn-sm btn-outline-primary mr-1">
										<i class="fas fa-reply mr-1"></i>Reply
									</a>
									<?php if (count($threadRecipients) > 1): ?>
									<a href="messages.php?action=compose&reply_all_thread=<?= urlencode($message['thread_id']) ?>&reply_subject=<?= urlencode('Re: ' . $message['subject']) ?>"
									   class="btn btn-sm btn-primary">
										<i class="fas fa-reply-all mr-1"></i>Reply All
									</a>
									<?php endif; ?>
								</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>

				<?php else: ?>
				<!-- ── Inbox / Sent tabs ───────────────────── -->
				<ul class="nav nav-tabs mb-3">
					<li class="nav-item">
						<a class="nav-link <?= $view === 'inbox' ? 'active' : '' ?>" href="messages.php">
							<i class="fas fa-inbox mr-1"></i>Inbox
							<?php if (!empty($unreadCount)): ?>
							<span class="badge badge-danger ml-1"><?= (int)$unreadCount ?></span>
							<?php endif; ?>
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?= $view === 'sent' ? 'active' : '' ?>" href="messages.php?action=sent">
							<i class="fas fa-paper-plane mr-1"></i>Sent
						</a>
					</li>
				</ul>

				<div class="card">
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-hover mb-0">
								<thead class="thead-light">
									<tr>
										<th style="width:1.5rem"></th>
										<th><?= $view === 'inbox' ? 'From' : 'To' ?></th>
										<th>Subject</th>
										<th>Date</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($messages as $msg): ?>
									<?php $isUnread = ($view === 'inbox' && !$msg['is_read']); ?>
									<tr class="<?= $isUnread ? 'font-weight-bold' : '' ?>"
									    style="cursor:pointer"
									    onclick="window.location='messages.php?action=view&id=<?= (int)$msg['message_id'] ?>'">
										<td class="text-center">
											<?php if ($isUnread): ?>
											<i class="fas fa-circle text-primary" style="font-size:.5rem;vertical-align:middle" title="Unread"></i>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($view === 'inbox'): ?>
											<?= htmlspecialchars($msg['sender_first'] . ' ' . $msg['sender_last']) ?>
											<?php else: ?>
											<?= htmlspecialchars($msg['recipients_list']) ?>
											<?php if (($msg['recipient_count'] ?? 1) > 1): ?>
											<span class="badge badge-secondary ml-1"><?= (int)$msg['recipient_count'] ?></span>
											<?php endif; ?>
											<?php endif; ?>
										</td>
										<td><?= htmlspecialchars($msg['subject']) ?></td>
										<td class="text-muted small"><?= htmlspecialchars(date('d M Y H:i', strtotime($msg['sent_at']))) ?></td>
									</tr>
									<?php endforeach; ?>
									<?php if (empty($messages)): ?>
									<tr>
										<td colspan="4" class="text-center text-muted py-4">
											<?= $view === 'inbox' ? 'Your inbox is empty.' : 'No sent messages.' ?>
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
		<strong>D3S3 CareSystem</strong>
	</footer>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
