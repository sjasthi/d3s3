<?php
$pageTitle            = 'Dashboard';
$activePage           = 'dashboard';
$portalUnreadCount    = $unreadMessages;
$resourcesUnreadCount = $newResources;
require __DIR__ . '/_nav.php';
?>

<?php if ($nextAppt): ?>
<div class="alert alert-info mb-4">
	<i class="fas fa-calendar-check mr-2"></i>
	<strong>Next appointment:</strong>
	<?= htmlspecialchars(date('D, d M Y', strtotime($nextAppt['scheduled_date']))) ?>
	<?php if ($nextAppt['scheduled_time']): ?>
		at <?= htmlspecialchars(date('g:i A', strtotime($nextAppt['scheduled_time']))) ?>
	<?php endif; ?>
	with Dr. <?= htmlspecialchars($nextAppt['doctor_first'] . ' ' . $nextAppt['doctor_last']) ?>
	&nbsp;<a href="patient_portal.php?page=appointments" class="alert-link">View all</a>
</div>
<?php endif; ?>

<?php if ($unreadMessages > 0): ?>
<div class="alert alert-warning mb-4">
	<i class="fas fa-envelope mr-2"></i>
	You have <strong><?= $unreadMessages ?></strong> unread
	<?= $unreadMessages === 1 ? 'message' : 'messages' ?> from your care team.
	<a href="patient_portal.php?page=messages" class="alert-link">View now</a>
</div>
<?php endif; ?>

<?php if ($newResources > 0): ?>
<div class="alert alert-success mb-4">
	<i class="fas fa-layer-group mr-2"></i>
	Your care team has sent you <strong><?= $newResources ?></strong> new
	<?= $newResources === 1 ? 'resource' : 'resources' ?>.
	<a href="patient_portal.php?page=resources" class="alert-link">View resources</a>
</div>
<?php endif; ?>

<div class="row">
	<!-- Quick links -->
	<div class="col-md-4 mb-4">
		<div class="portal-card card h-100">
			<div class="card-body text-center py-4">
				<i class="fas fa-calendar-check fa-2x text-primary mb-3"></i>
				<h5>Appointments</h5>
				<p class="text-muted small">View your upcoming and past appointments</p>
				<a href="patient_portal.php?page=appointments" class="btn btn-outline-primary btn-sm">View Appointments</a>
			</div>
		</div>
	</div>
	<div class="col-md-4 mb-4">
		<div class="portal-card card h-100">
			<div class="card-body text-center py-4">
				<i class="fas fa-file-medical fa-2x text-success mb-3"></i>
				<h5>Health Record</h5>
				<p class="text-muted small">Read your visit summaries and doctor notes</p>
				<a href="patient_portal.php?page=health_record" class="btn btn-outline-success btn-sm">View Record</a>
			</div>
		</div>
	</div>
	<div class="col-md-4 mb-4">
		<div class="portal-card card h-100">
			<div class="card-body text-center py-4">
				<i class="fas fa-vial fa-2x text-info mb-3"></i>
				<h5>Lab Results</h5>
				<p class="text-muted small">See results from ordered lab tests</p>
				<a href="patient_portal.php?page=lab_results" class="btn btn-outline-info btn-sm">View Results</a>
			</div>
		</div>
	</div>
	<div class="col-md-4 mb-4">
		<div class="portal-card card h-100">
			<div class="card-body text-center py-4">
				<i class="fas fa-envelope fa-2x text-warning mb-3"></i>
				<h5>Messages</h5>
				<p class="text-muted small">Contact your care team securely</p>
				<a href="patient_portal.php?page=messages" class="btn btn-outline-warning btn-sm">
					Open Messages
					<?php if ($unreadMessages > 0): ?>
					<span class="badge badge-danger ml-1"><?= $unreadMessages ?></span>
					<?php endif; ?>
				</a>
			</div>
		</div>
	</div>
	<div class="col-md-4 mb-4">
		<div class="portal-card card h-100">
			<div class="card-body text-center py-4">
				<i class="fas fa-comment-dots fa-2x text-secondary mb-3"></i>
				<h5>Feedback</h5>
				<p class="text-muted small">Submit a grievance, complaint, or positive feedback</p>
				<a href="patient_portal.php?page=feedback" class="btn btn-outline-secondary btn-sm">Give Feedback</a>
			</div>
		</div>
	</div>
	<div class="col-md-4 mb-4">
		<div class="portal-card card h-100">
			<div class="card-body text-center py-4">
				<i class="fas fa-layer-group fa-2x text-success mb-3"></i>
				<h5>Resources</h5>
				<p class="text-muted small">Documents, forms, and educational materials from your care team</p>
				<a href="patient_portal.php?page=resources" class="btn btn-outline-success btn-sm">
					View Resources
					<?php if ($newResources > 0): ?>
					<span class="badge badge-danger ml-1"><?= $newResources ?></span>
					<?php endif; ?>
				</a>
			</div>
		</div>
	</div>
	<div class="col-md-4 mb-4">
		<div class="portal-card card h-100">
			<div class="card-body text-center py-4">
				<i class="fas fa-allergies fa-2x text-danger mb-3"></i>
				<h5>My Profile</h5>
				<p class="text-muted small">Update your allergy information and view your details</p>
				<a href="patient_portal.php?page=profile" class="btn btn-outline-danger btn-sm">My Profile</a>
			</div>
		</div>
	</div>
</div>

<?php if (!empty($recentLabs)): ?>
<div class="portal-card card mb-4">
	<div class="card-header bg-white font-weight-bold">
		<i class="fas fa-vial mr-2 text-info"></i>Recent Lab Results
	</div>
	<div class="list-group list-group-flush">
		<?php foreach ($recentLabs as $lab): ?>
		<div class="list-group-item">
			<div class="d-flex justify-content-between align-items-start">
				<strong><?= htmlspecialchars($lab['test_name']) ?></strong>
				<span class="badge badge-success">Completed</span>
			</div>
			<?php if ($lab['result_notes']): ?>
			<small class="text-muted"><?= htmlspecialchars(mb_substr($lab['result_notes'], 0, 120)) . (mb_strlen($lab['result_notes']) > 120 ? '…' : '') ?></small>
			<?php endif; ?>
			<div class="text-muted small mt-1">
				<?= htmlspecialchars(date('d M Y', strtotime($lab['completed_at']))) ?>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
	<div class="card-footer bg-white text-right">
		<a href="patient_portal.php?page=lab_results" class="btn btn-sm btn-outline-info">All Lab Results</a>
	</div>
</div>
<?php endif; ?>

<?php if (!empty($recentFeedback)): ?>
<div class="portal-card card mb-4">
	<div class="card-header bg-white font-weight-bold">
		<i class="fas fa-comment-dots mr-2 text-secondary"></i>Recent Feedback
	</div>
	<div class="list-group list-group-flush">
		<?php foreach ($recentFeedback as $fb): ?>
		<div class="list-group-item">
			<div class="d-flex justify-content-between align-items-start">
				<strong><?= htmlspecialchars($fb['subject']) ?></strong>
				<?php
				$fbBadge = ['NEW' => 'secondary','REVIEWED' => 'info','ACTIONED' => 'warning','CLOSED' => 'success'];
				$fbBadgeClass = $fbBadge[$fb['status']] ?? 'secondary';
				?>
				<span class="badge badge-<?= $fbBadgeClass ?>"><?= htmlspecialchars($fb['status']) ?></span>
			</div>
			<small class="text-muted"><?= htmlspecialchars($fb['feedback_type']) ?> &middot;
				<?= htmlspecialchars(date('d M Y', strtotime($fb['created_at']))) ?>
			</small>
		</div>
		<?php endforeach; ?>
	</div>
	<div class="card-footer bg-white text-right">
		<a href="patient_portal.php?page=feedback" class="btn btn-sm btn-outline-secondary">All Feedback</a>
	</div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_nav_close.php'; ?>
