<?php
$pageTitle  = 'Lab Results';
$activePage = 'lab_results';
require __DIR__ . '/_nav.php';
?>

<h4 class="mb-4"><i class="fas fa-vial mr-2 text-info"></i>Lab Results</h4>

<?php if (empty($labOrders)): ?>
<div class="portal-card card">
	<div class="card-body text-center py-5 text-muted">
		<i class="fas fa-flask fa-2x mb-3"></i>
		<p>No lab tests have been ordered for you yet.</p>
	</div>
</div>
<?php else: ?>

<?php
$pending   = array_filter($labOrders, fn($l) => $l['status'] === 'PENDING');
$completed = array_filter($labOrders, fn($l) => $l['status'] === 'COMPLETED');
?>

<?php if (!empty($completed)): ?>
<h6 class="text-uppercase text-muted font-weight-bold mb-2">Completed Results</h6>
<?php foreach ($completed as $lab): ?>
<div class="portal-card card mb-3">
	<div class="card-body">
		<div class="d-flex justify-content-between align-items-start flex-wrap">
			<div class="mr-3">
				<h6 class="mb-1"><i class="fas fa-vial mr-1 text-info"></i><?= htmlspecialchars($lab['test_name']) ?></h6>
				<?php if ($lab['chief_complaint']): ?>
				<small class="text-muted">Visit: <?= htmlspecialchars($lab['chief_complaint']) ?></small><br />
				<?php endif; ?>
				<small class="text-muted">
					Ordered: <?= htmlspecialchars(date('d M Y', strtotime($lab['ordered_at']))) ?>
					&bull; Completed: <?= htmlspecialchars(date('d M Y', strtotime($lab['completed_at']))) ?>
				</small>
			</div>
			<span class="badge badge-success mt-1">Completed</span>
		</div>
		<?php if ($lab['result_notes']): ?>
		<div class="mt-3 p-3 bg-light rounded">
			<label class="small font-weight-bold text-muted d-block mb-1">Result Notes</label>
			<?= nl2br(htmlspecialchars($lab['result_notes'])) ?>
		</div>
		<?php endif; ?>
		<?php if ($lab['order_notes']): ?>
		<div class="mt-2">
			<small class="text-muted">Test notes: <?= htmlspecialchars($lab['order_notes']) ?></small>
		</div>
		<?php endif; ?>
	</div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($pending)): ?>
<h6 class="text-uppercase text-muted font-weight-bold mb-2 mt-4">Pending Tests</h6>
<div class="portal-card card">
	<ul class="list-group list-group-flush">
		<?php foreach ($pending as $lab): ?>
		<li class="list-group-item">
			<div class="d-flex justify-content-between align-items-center">
				<div>
					<strong><?= htmlspecialchars($lab['test_name']) ?></strong>
					<?php if ($lab['order_notes']): ?>
					<div class="text-muted small"><?= htmlspecialchars($lab['order_notes']) ?></div>
					<?php endif; ?>
					<div class="text-muted small">Ordered: <?= htmlspecialchars(date('d M Y', strtotime($lab['ordered_at']))) ?></div>
				</div>
				<span class="badge badge-warning">Pending</span>
			</div>
		</li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/_nav_close.php'; ?>
