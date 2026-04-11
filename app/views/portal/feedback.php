<?php
$pageTitle  = 'Feedback';
$activePage = 'feedback';
require __DIR__ . '/_nav.php';

$typeLabels = [
	'GRIEVANCE'  => ['label' => 'Grievance',   'class' => 'danger'],
	'COMPLAINT'  => ['label' => 'Complaint',   'class' => 'warning'],
	'POSITIVE'   => ['label' => 'Positive',    'class' => 'success'],
	'SUGGESTION' => ['label' => 'Suggestion',  'class' => 'info'],
];
$statusBadge = [
	'NEW'      => 'secondary',
	'REVIEWED' => 'info',
	'ACTIONED' => 'warning',
	'CLOSED'   => 'success',
];
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

<div class="row">
	<!-- Submit form -->
	<div class="col-md-5 mb-4">
		<div class="portal-card card">
			<div class="card-header bg-white font-weight-bold">
				<i class="fas fa-comment-dots mr-2 text-secondary"></i>Submit Feedback
			</div>
			<div class="card-body">
				<form method="POST" action="patient_portal.php">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
					<input type="hidden" name="action" value="submit_feedback" />

					<div class="form-group">
						<label class="small font-weight-bold">Type <span class="text-danger">*</span></label>
						<select name="feedback_type" class="form-control" required>
							<option value="">— Select type —</option>
							<option value="POSITIVE">Positive feedback / Compliment</option>
							<option value="SUGGESTION">Suggestion</option>
							<option value="COMPLAINT">Complaint</option>
							<option value="GRIEVANCE">Grievance</option>
						</select>
					</div>

					<div class="form-group">
						<label class="small font-weight-bold">Subject <span class="text-danger">*</span></label>
						<input type="text" name="subject" class="form-control" maxlength="200"
							   placeholder="Brief subject…" required />
					</div>

					<div class="form-group">
						<label class="small font-weight-bold">Description <span class="text-danger">*</span></label>
						<textarea name="description" class="form-control" rows="5"
						          maxlength="5000" placeholder="Please describe your feedback in detail…" required></textarea>
						<small class="text-muted">Max 5,000 characters</small>
					</div>

					<div class="form-group">
						<label class="small font-weight-bold">Regarding a specific staff member?</label>
						<select name="related_user_id" class="form-control">
							<option value="">— Not specific to any one person —</option>
							<?php foreach ($staffList as $staff): ?>
							<option value="<?= (int)$staff['user_id'] ?>">
								<?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?>
								(<?= htmlspecialchars(str_replace('_', ' ', $staff['role'])) ?>)
							</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="form-group">
						<label class="small font-weight-bold">Overall Rating (optional)</label>
						<div class="d-flex" id="starRating">
							<?php for ($i = 1; $i <= 5; $i++): ?>
							<label class="mr-2 mb-0" style="cursor:pointer;" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">
								<input type="radio" name="rating" value="<?= $i ?>" class="d-none star-input" />
								<i class="fas fa-star fa-lg text-muted star-icon" data-val="<?= $i ?>"></i>
							</label>
							<?php endfor; ?>
						</div>
					</div>

					<button type="submit" class="btn btn-primary btn-block">
						<i class="fas fa-paper-plane mr-1"></i> Submit Feedback
					</button>
				</form>
			</div>
		</div>
	</div>

	<!-- My feedback list -->
	<div class="col-md-7 mb-4">
		<div class="portal-card card">
			<div class="card-header bg-white font-weight-bold">
				<i class="fas fa-history mr-2"></i>My Submitted Feedback
			</div>
			<?php if (empty($myFeedback)): ?>
			<div class="card-body text-center py-5 text-muted">
				<i class="fas fa-comment-slash fa-2x mb-3"></i>
				<p>No feedback submitted yet.</p>
			</div>
			<?php else: ?>
			<ul class="list-group list-group-flush">
				<?php foreach ($myFeedback as $fb):
					$typeInfo = $typeLabels[$fb['feedback_type']] ?? ['label' => $fb['feedback_type'], 'class' => 'secondary'];
				?>
				<li class="list-group-item">
					<div class="d-flex justify-content-between align-items-start">
						<div class="mr-3">
							<strong><?= htmlspecialchars($fb['subject']) ?></strong>
							<div class="mt-1">
								<span class="badge badge-<?= $typeInfo['class'] ?>"><?= $typeInfo['label'] ?></span>
								<?php if ($fb['rating']): ?>
								<span class="ml-1 text-warning">
									<?php for ($s = 1; $s <= 5; $s++): ?>
									<i class="fas fa-star<?= $s > $fb['rating'] ? '' : '' ?>"
									   style="color:<?= $s <= $fb['rating'] ? '#f6c23e' : '#dee2e6' ?>"></i>
									<?php endfor; ?>
								</span>
								<?php endif; ?>
							</div>
							<?php if ($fb['related_first']): ?>
							<div class="text-muted small mt-1">
								Re: <?= htmlspecialchars($fb['related_first'] . ' ' . $fb['related_last']) ?>
							</div>
							<?php endif; ?>
							<div class="text-muted small">
								<?= htmlspecialchars(date('d M Y', strtotime($fb['created_at']))) ?>
							</div>
						</div>
						<span class="badge badge-<?= $statusBadge[$fb['status']] ?? 'secondary' ?> mt-1">
							<?= htmlspecialchars($fb['status']) ?>
						</span>
					</div>
				</li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</div>
	</div>
</div>

<script>
// Star rating highlight
document.querySelectorAll('.star-input').forEach(function(radio) {
	radio.addEventListener('change', function() {
		var val = parseInt(this.value);
		document.querySelectorAll('.star-icon').forEach(function(icon) {
			var v = parseInt(icon.getAttribute('data-val'));
			icon.style.color = v <= val ? '#f6c23e' : '#dee2e6';
		});
	});
});
</script>

<?php require __DIR__ . '/_nav_close.php'; ?>
