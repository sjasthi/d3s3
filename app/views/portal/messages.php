<?php
$pageTitle         = 'Messages';
$activePage        = 'messages';
$portalUnreadCount = 0;  // already on this page
require __DIR__ . '/_nav.php';

$isCompose = isset($_GET['compose']);
?>

<?php if ($flashSuccess): ?>
<div class="alert alert-success alert-dismissible fade show">
	<?= htmlspecialchars($flashSuccess) ?>
	<button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="alert alert-danger alert-dismissible fade show">
	<?= htmlspecialchars($flashError) ?>
	<button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
	<h4 class="mb-0"><i class="fas fa-envelope mr-2 text-primary"></i>Messages</h4>
	<a href="patient_portal.php?page=messages&compose=1" class="btn btn-primary btn-sm">
		<i class="fas fa-edit mr-1"></i> New Message
	</a>
</div>

<div class="row">
	<!-- Thread list -->
	<div class="col-md-4 mb-4">
		<div class="portal-card card h-100">
			<div class="card-header bg-white font-weight-bold small text-muted text-uppercase">
				Conversations
			</div>
			<?php if (empty($threads)): ?>
			<div class="card-body text-center text-muted py-4">
				<i class="fas fa-inbox fa-2x mb-2"></i>
				<p class="small">No messages yet.</p>
			</div>
			<?php else: ?>
			<div class="list-group list-group-flush">
				<?php foreach ($threads as $t): ?>
				<a href="patient_portal.php?page=messages&thread=<?= (int)$t['thread_id'] ?>"
				   class="list-group-item list-group-item-action thread-item py-2
				          <?= ($activeThread && (int)$activeThread['thread_id'] === (int)$t['thread_id']) ? 'active-thread' : '' ?>">
					<div class="d-flex justify-content-between">
						<strong class="small"><?= htmlspecialchars(mb_substr($t['subject'], 0, 40)) ?></strong>
						<?php if ($t['patient_unread']): ?>
						<span class="badge-unread">New</span>
						<?php endif; ?>
					</div>
					<div class="text-muted small"><?= htmlspecialchars(date('d M Y', strtotime($t['last_message_at']))) ?></div>
				</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Right panel: compose or thread view -->
	<div class="col-md-8 mb-4">
		<?php if ($isCompose || (empty($threads) && !$activeThread)): ?>
		<!-- Compose -->
		<div class="portal-card card">
			<div class="card-header bg-white font-weight-bold">
				<i class="fas fa-edit mr-2"></i>New Message to Care Team
			</div>
			<div class="card-body">
				<form method="POST" action="patient_portal.php">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
					<input type="hidden" name="action" value="send_message" />
					<input type="hidden" name="msg_action" value="new" />

					<div class="form-group">
						<label for="subject" class="small font-weight-bold">Subject</label>
						<input type="text" name="subject" id="subject" class="form-control"
							   maxlength="200" placeholder="e.g. Question about my prescription" required />
					</div>
					<div class="form-group">
						<label for="body" class="small font-weight-bold">Message</label>
						<textarea name="body" id="body" class="form-control" rows="6"
						          maxlength="5000" placeholder="Type your message here…" required></textarea>
						<small class="text-muted">Max 5,000 characters</small>
					</div>
					<button type="submit" class="btn btn-primary">
						<i class="fas fa-paper-plane mr-1"></i> Send Message
					</button>
					<?php if (!empty($threads)): ?>
					<a href="patient_portal.php?page=messages" class="btn btn-outline-secondary ml-2">Cancel</a>
					<?php endif; ?>
				</form>
			</div>
		</div>

		<?php elseif ($activeThread): ?>
		<!-- Thread view -->
		<div class="portal-card card d-flex flex-column" style="min-height:400px;">
			<div class="card-header bg-white d-flex justify-content-between align-items-center">
				<strong><?= htmlspecialchars($activeThread['subject']) ?></strong>
				<a href="patient_portal.php?page=messages" class="btn btn-sm btn-outline-secondary">
					<i class="fas fa-arrow-left"></i>
				</a>
			</div>
			<div class="card-body flex-grow-1" style="overflow-y:auto;">
				<?php if (empty($threadMessages)): ?>
				<p class="text-muted text-center">No messages in this thread.</p>
				<?php endif; ?>
				<?php foreach ($threadMessages as $msg): ?>
				<div class="d-flex mb-3 <?= $msg['sender_type'] === 'PATIENT' ? 'justify-content-end' : '' ?>">
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
			<div class="card-footer bg-white">
				<form method="POST" action="patient_portal.php">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
					<input type="hidden" name="action" value="send_message" />
					<input type="hidden" name="msg_action" value="reply" />
					<input type="hidden" name="thread_id" value="<?= (int)$activeThread['thread_id'] ?>" />
					<div class="input-group">
						<textarea name="body" class="form-control" rows="2"
						          maxlength="5000" placeholder="Type a reply…" required></textarea>
						<div class="input-group-append">
							<button type="submit" class="btn btn-primary">
								<i class="fas fa-paper-plane"></i>
							</button>
						</div>
					</div>
				</form>
			</div>
		</div>

		<?php elseif (!empty($threads)): ?>
		<!-- No thread selected yet -->
		<div class="portal-card card">
			<div class="card-body text-center text-muted py-5">
				<i class="fas fa-comments fa-2x mb-2"></i>
				<p>Select a conversation on the left.</p>
				<a href="patient_portal.php?page=messages&compose=1" class="btn btn-primary btn-sm">
					<i class="fas fa-edit mr-1"></i> New Message
				</a>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>

<?php require __DIR__ . '/_nav_close.php'; ?>
