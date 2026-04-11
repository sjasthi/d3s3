<?php
$pageTitle  = 'My Appointments';
$activePage = 'appointments';
require __DIR__ . '/_nav.php';

$statusBadge = [
	'SCHEDULED'   => 'secondary',
	'CONFIRMED'   => 'primary',
	'IN_PROGRESS' => 'warning',
	'COMPLETED'   => 'success',
	'CANCELLED'   => 'danger',
	'NO_SHOW'     => 'dark',
];
$visitModeLabel = [
	'IN_PERSON' => 'In-Person',
	'REMOTE'    => 'Remote / Video',
	'CAMP'      => 'Medical Camp',
];
?>

<h4 class="mb-4"><i class="fas fa-calendar-check mr-2 text-primary"></i>My Appointments</h4>

<?php if (empty($appointments)): ?>
<div class="portal-card card">
	<div class="card-body text-center py-5 text-muted">
		<i class="fas fa-calendar fa-2x mb-3"></i>
		<p>No appointments on record.</p>
	</div>
</div>
<?php else: ?>

<?php
$upcoming = array_filter($appointments, fn($a) => in_array($a['status'], ['SCHEDULED','CONFIRMED','IN_PROGRESS']) && $a['scheduled_date'] >= date('Y-m-d'));
$past     = array_filter($appointments, fn($a) => !in_array($a, (array)$upcoming, true));
?>

<?php if (!empty($upcoming)): ?>
<h6 class="text-uppercase text-muted font-weight-bold mb-2">Upcoming</h6>
<?php foreach ($upcoming as $appt): ?>
<div class="portal-card card mb-3">
	<div class="card-body">
		<div class="d-flex justify-content-between align-items-start flex-wrap">
			<div>
				<h6 class="mb-1">
					<i class="fas fa-calendar-day mr-1 text-primary"></i>
					<?= htmlspecialchars(date('D, d M Y', strtotime($appt['scheduled_date']))) ?>
					<?php if ($appt['scheduled_time']): ?>
					at <?= htmlspecialchars(date('g:i A', strtotime($appt['scheduled_time']))) ?>
					<?php endif; ?>
				</h6>
				<div class="text-muted small">
					<?= htmlspecialchars($visitModeLabel[$appt['visit_mode']] ?? $appt['visit_mode']) ?>
					&nbsp;&bull;&nbsp;
					<?php if ($appt['doctor_first']): ?>
					Dr. <?= htmlspecialchars($appt['doctor_first'] . ' ' . $appt['doctor_last']) ?>
					<?php else: ?>
					Doctor TBD
					<?php endif; ?>
				</div>
				<?php if ($appt['chief_complaint']): ?>
				<div class="mt-1 text-muted small">
					Reason: <?= htmlspecialchars($appt['chief_complaint']) ?>
				</div>
				<?php endif; ?>
				<?php if ($appt['notes']): ?>
				<div class="mt-1 text-muted small">
					Notes: <?= htmlspecialchars($appt['notes']) ?>
				</div>
				<?php endif; ?>
			</div>
			<span class="badge badge-<?= $statusBadge[$appt['status']] ?? 'secondary' ?> mt-1">
				<?= htmlspecialchars(str_replace('_', ' ', $appt['status'])) ?>
			</span>
		</div>
	</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($past)): ?>
<h6 class="text-uppercase text-muted font-weight-bold mb-2 mt-4">Past Appointments</h6>
<div class="portal-card card">
	<ul class="list-group list-group-flush">
		<?php foreach ($past as $appt): ?>
		<li class="list-group-item">
			<div class="d-flex justify-content-between align-items-center">
				<div>
					<strong><?= htmlspecialchars(date('d M Y', strtotime($appt['scheduled_date']))) ?></strong>
					<span class="text-muted small ml-2">
						<?= htmlspecialchars($visitModeLabel[$appt['visit_mode']] ?? $appt['visit_mode']) ?>
						<?php if ($appt['doctor_first']): ?>
						&bull; Dr. <?= htmlspecialchars($appt['doctor_first'] . ' ' . $appt['doctor_last']) ?>
						<?php endif; ?>
					</span>
				</div>
				<span class="badge badge-<?= $statusBadge[$appt['status']] ?? 'secondary' ?>">
					<?= htmlspecialchars(str_replace('_', ' ', $appt['status'])) ?>
				</span>
			</div>
		</li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/_nav_close.php'; ?>
