<?php
$pageTitle  = 'My Health Record';
$activePage = 'health_record';
require __DIR__ . '/_nav.php';
?>

<h4 class="mb-4"><i class="fas fa-file-medical mr-2 text-success"></i>My Health Record</h4>

<?php if ($patient): ?>
<div class="portal-card card mb-4">
	<div class="card-header bg-white font-weight-bold">Personal Information</div>
	<div class="card-body">
		<div class="row">
			<div class="col-sm-6">
				<p class="mb-1"><small class="text-muted">Name</small><br />
					<strong><?= htmlspecialchars(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '')) ?></strong>
				</p>
				<p class="mb-1"><small class="text-muted">Patient Code</small><br />
					<code><?= htmlspecialchars($patient['patient_code']) ?></code>
				</p>
				<p class="mb-1"><small class="text-muted">Date of Birth</small><br />
					<?= $patient['date_of_birth'] ? htmlspecialchars(date('d M Y', strtotime($patient['date_of_birth']))) : '<span class="text-muted">—</span>' ?>
				</p>
			</div>
			<div class="col-sm-6">
				<p class="mb-1"><small class="text-muted">Blood Group</small><br />
					<?= $patient['blood_group'] ? '<strong>' . htmlspecialchars($patient['blood_group']) . '</strong>' : '<span class="text-muted">Not recorded</span>' ?>
				</p>
				<p class="mb-1"><small class="text-muted">Allergies</small><br />
					<?php if ($patient['allergies']): ?>
					<span class="badge badge-danger"><?= htmlspecialchars($patient['allergies']) ?></span>
					<?php else: ?>
					<span class="text-muted">None recorded — <a href="patient_portal.php?page=profile">update in profile</a></span>
					<?php endif; ?>
				</p>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<h5 class="mb-3">Visit History</h5>

<?php if (empty($caseSheets)): ?>
<div class="portal-card card">
	<div class="card-body text-center py-5 text-muted">
		<i class="fas fa-notes-medical fa-2x mb-3"></i>
		<p>No completed visits on record yet.</p>
	</div>
</div>
<?php else: ?>

<?php foreach ($caseSheets as $cs):
	$vitals        = [];
	$assessmentData = [];
	if ($cs['assessment']) {
		$assessmentData = json_decode($cs['assessment'], true) ?: [];
	}
?>
<div class="portal-card card mb-3">
	<div class="card-header bg-white d-flex justify-content-between align-items-center"
	     data-toggle="collapse" data-target="#cs<?= $cs['case_sheet_id'] ?>"
	     style="cursor:pointer;">
		<span>
			<i class="fas fa-notes-medical mr-2 text-muted"></i>
			<strong><?= htmlspecialchars(date('d M Y', strtotime($cs['visit_datetime']))) ?></strong>
			<span class="text-muted small ml-2"><?= htmlspecialchars(str_replace('_', ' ', $cs['visit_type'])) ?></span>
			<?php if ($cs['chief_complaint']): ?>
			&nbsp;&mdash; <?= htmlspecialchars(mb_substr($cs['chief_complaint'], 0, 60)) ?>
			<?php endif; ?>
		</span>
		<i class="fas fa-chevron-down text-muted small"></i>
	</div>
	<div class="collapse" id="cs<?= $cs['case_sheet_id'] ?>">
		<div class="card-body">
			<?php if ($cs['doctor_first']): ?>
			<p class="text-muted small mb-3">
				<i class="fas fa-user-md mr-1"></i>
				Dr. <?= htmlspecialchars($cs['doctor_first'] . ' ' . $cs['doctor_last']) ?>
			</p>
			<?php endif; ?>

			<?php if ($cs['chief_complaint']): ?>
			<div class="mb-3">
				<label class="small font-weight-bold text-muted">Chief Complaint</label>
				<p class="mb-0"><?= htmlspecialchars($cs['chief_complaint']) ?></p>
			</div>
			<?php endif; ?>

			<?php
			$doctorDiagnosis = $cs['doctor_diagnosis'] ?: $cs['diagnosis'];
			$doctorPlan      = $cs['doctor_plan_notes'] ?: $cs['plan_notes'];
			?>

			<?php if ($doctorDiagnosis): ?>
			<div class="mb-3">
				<label class="small font-weight-bold text-muted">Diagnosis</label>
				<p class="mb-0"><?= nl2br(htmlspecialchars($doctorDiagnosis)) ?></p>
			</div>
			<?php endif; ?>

			<?php if ($doctorPlan): ?>
			<div class="mb-3">
				<label class="small font-weight-bold text-muted">Treatment Plan</label>
				<p class="mb-0"><?= nl2br(htmlspecialchars($doctorPlan)) ?></p>
			</div>
			<?php endif; ?>

			<?php if ($cs['prescriptions']): ?>
			<div class="mb-3">
				<label class="small font-weight-bold text-muted">Prescriptions</label>
				<p class="mb-0"><?= nl2br(htmlspecialchars($cs['prescriptions'])) ?></p>
			</div>
			<?php endif; ?>

			<?php if ($cs['advice']): ?>
			<div class="mb-3">
				<label class="small font-weight-bold text-muted">Advice</label>
				<p class="mb-0"><?= nl2br(htmlspecialchars($cs['advice'])) ?></p>
			</div>
			<?php endif; ?>

			<?php if ($cs['follow_up_date']): ?>
			<div class="mb-0">
				<label class="small font-weight-bold text-muted">Follow-up Date</label>
				<p class="mb-0"><?= htmlspecialchars(date('d M Y', strtotime($cs['follow_up_date']))) ?></p>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<script>
// Auto-expand first card
document.addEventListener('DOMContentLoaded', function() {
	var first = document.querySelector('.portal-card .card-header[data-toggle="collapse"]');
	if (first) {
		var target = document.querySelector(first.getAttribute('data-target'));
		if (target) target.classList.add('show');
	}
});
</script>

<?php require __DIR__ . '/_nav_close.php'; ?>
