<?php
$pageTitle  = 'My Profile';
$activePage = 'profile';
require __DIR__ . '/_nav.php';
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

<h4 class="mb-4"><i class="fas fa-user-circle mr-2 text-primary"></i>My Profile</h4>

<div class="row">
	<!-- Demographics (read-only) -->
	<div class="col-md-6 mb-4">
		<div class="portal-card card h-100">
			<div class="card-header bg-white font-weight-bold">
				<i class="fas fa-id-card mr-2"></i>Personal Information
				<small class="text-muted font-weight-normal ml-1">(Contact clinic to update)</small>
			</div>
			<div class="card-body">
				<?php if ($patient): ?>
				<dl class="row mb-0">
					<dt class="col-sm-4 text-muted small">Full Name</dt>
					<dd class="col-sm-8">
						<?= htmlspecialchars(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '')) ?>
					</dd>

					<dt class="col-sm-4 text-muted small">Patient Code</dt>
					<dd class="col-sm-8"><code><?= htmlspecialchars($patient['patient_code']) ?></code></dd>

					<dt class="col-sm-4 text-muted small">Date of Birth</dt>
					<dd class="col-sm-8">
						<?= $patient['date_of_birth']
							? htmlspecialchars(date('d M Y', strtotime($patient['date_of_birth'])))
							: '<span class="text-muted">—</span>' ?>
					</dd>

					<dt class="col-sm-4 text-muted small">Sex</dt>
					<dd class="col-sm-8"><?= htmlspecialchars(ucfirst(strtolower($patient['sex'] ?? '—'))) ?></dd>

					<dt class="col-sm-4 text-muted small">Blood Group</dt>
					<dd class="col-sm-8">
						<?= $patient['blood_group']
							? '<strong>' . htmlspecialchars($patient['blood_group']) . '</strong>'
							: '<span class="text-muted">Not recorded</span>' ?>
					</dd>

					<dt class="col-sm-4 text-muted small">Phone</dt>
					<dd class="col-sm-8">
						<?= $patient['phone_e164']
							? htmlspecialchars($patient['phone_e164'])
							: '<span class="text-muted">—</span>' ?>
					</dd>

					<dt class="col-sm-4 text-muted small">Email</dt>
					<dd class="col-sm-8">
						<?= $patient['email'] ?? '<span class="text-muted">—</span>' ?>
					</dd>

					<?php if ($patient['address_line1']): ?>
					<dt class="col-sm-4 text-muted small">Address</dt>
					<dd class="col-sm-8">
						<?= htmlspecialchars($patient['address_line1']) ?>
						<?php if ($patient['city']): ?>, <?= htmlspecialchars($patient['city']) ?><?php endif; ?>
						<?php if ($patient['state_province']): ?>, <?= htmlspecialchars($patient['state_province']) ?><?php endif; ?>
					</dd>
					<?php endif; ?>

					<?php if ($patient['emergency_contact_name']): ?>
					<dt class="col-sm-4 text-muted small">Emergency Contact</dt>
					<dd class="col-sm-8">
						<?= htmlspecialchars($patient['emergency_contact_name']) ?>
						<?php if ($patient['emergency_contact_phone']): ?>
						&nbsp;(<?= htmlspecialchars($patient['emergency_contact_phone']) ?>)
						<?php endif; ?>
					</dd>
					<?php endif; ?>
				</dl>
				<?php else: ?>
				<p class="text-muted">Unable to load patient information.</p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Allergies (editable) -->
	<div class="col-md-6 mb-4">
		<div class="portal-card card">
			<div class="card-header bg-white font-weight-bold">
				<i class="fas fa-allergies mr-2 text-danger"></i>My Allergies
			</div>
			<div class="card-body">
				<p class="text-muted small mb-3">
					List any known allergies (medications, foods, environmental, etc.).
					Your care team will confirm this information during your next intake appointment.
				</p>

				<form method="POST" action="patient_portal.php">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
					<input type="hidden" name="action" value="update_profile" />

					<div class="form-group">
						<label for="allergies" class="font-weight-bold small">Known Allergies</label>
						<textarea name="allergies" id="allergies" class="form-control" rows="4"
						          maxlength="255"
						          placeholder="e.g. Penicillin (rash), Sulfa drugs, Peanuts…"><?= htmlspecialchars($patient['allergies'] ?? '') ?></textarea>
						<small class="text-muted">
							Enter your allergies separated by commas or on separate lines. Max 255 characters.
						</small>
					</div>

					<div class="d-flex align-items-center">
						<button type="submit" class="btn btn-danger">
							<i class="fas fa-save mr-1"></i> Save Allergies
						</button>
						<?php if (empty($patient['allergies'])): ?>
						<div class="custom-control custom-checkbox ml-3">
							<input type="checkbox" class="custom-control-input" id="noAllergies"
							       onchange="document.getElementById('allergies').value = this.checked ? 'No known allergies' : ''; document.getElementById('allergies').readOnly = this.checked;" />
							<label class="custom-control-label text-muted small" for="noAllergies">
								No known allergies
							</label>
						</div>
						<?php endif; ?>
					</div>
				</form>
			</div>
		</div>

		<div class="portal-card card mt-3">
			<div class="card-header bg-white font-weight-bold">
				<i class="fas fa-lock mr-2"></i>Account Security
			</div>
			<div class="card-body text-muted small">
				<p>To change your password or update your email address, please contact the clinic directly.</p>
				<p class="mb-0">
					<i class="fas fa-info-circle mr-1"></i>
					Account created by clinic staff. Contact the front desk if you need help accessing your account.
				</p>
			</div>
		</div>
	</div>
</div>

<?php require __DIR__ . '/_nav_close.php'; ?>
