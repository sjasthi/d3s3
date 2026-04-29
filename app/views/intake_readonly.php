<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Case Sheet – <?= htmlspecialchars(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '')) ?> | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
		.ro-label { font-size: .75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .15rem; }
		.ro-value { font-size: .95rem; color: inherit; }
		.ro-empty { color: #adb5bd; font-style: italic; }
		.section-card .card-header { background: transparent; border-bottom: 1px solid rgba(0,0,0,.075); }
		.vitals-box { background: #f8f9fa; border-radius: .4rem; padding: .6rem 1rem; text-align: center; }
		.vitals-box .val { font-size: 1.25rem; font-weight: 700; line-height: 1.2; }
		.vitals-box .unit { font-size: .7rem; color: #6c757d; }
		.cond-badge { font-size: .8rem; }
		.audit-table td, .audit-table th { font-size: .8rem; vertical-align: middle; }
		.audit-old { color: #dc3545; }
		.audit-new { color: #28a745; }
		body.dark-mode .vitals-box { background: rgba(255,255,255,.05); }
		body.dark-mode .ro-label { color: #adb5bd; }
	</style>
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
				<?php
					$v    = !empty($caseSheet['vitals_json']) ? (json_decode($caseSheet['vitals_json'], true) ?: []) : [];
					$hist = !empty($caseSheet['assessment'])  ? (json_decode($caseSheet['assessment'],  true) ?: []) : [];
					$exam = !empty($caseSheet['exam_notes'])  ? (json_decode($caseSheet['exam_notes'],  true) ?: []) : [];
					$plan = !empty($caseSheet['plan_notes'])  ? (json_decode($caseSheet['plan_notes'],  true) ?: []) : [];

					$statusLabels = [
						'INTAKE_IN_PROGRESS' => ['label' => 'In Progress',       'cls' => 'badge-warning'],
						'INTAKE_COMPLETE'    => ['label' => 'Ready for Doctor',   'cls' => 'badge-info'],
						'DOCTOR_REVIEW'      => ['label' => 'In Review',          'cls' => 'badge-primary'],
						'CLOSED'             => ['label' => 'Closed',             'cls' => 'badge-success'],
						'SCHEDULED'          => ['label' => 'Scheduled',          'cls' => 'badge-success'],
					];
					$statusInfo = $statusLabels[$caseSheet['status']] ?? ['label' => $caseSheet['status'], 'cls' => 'badge-secondary'];

					$patientName = htmlspecialchars(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
					$doctorName  = $caseSheet['doctor_first'] ? htmlspecialchars('Dr. ' . $caseSheet['doctor_first'] . ' ' . $caseSheet['doctor_last']) : null;
					$createdBy   = $caseSheet['created_first'] ? htmlspecialchars($caseSheet['created_first'] . ' ' . $caseSheet['created_last']) : null;

					$canAmend = can($_SESSION['user_role'] ?? '', 'case_sheets', 'W')
					         && ($_SESSION['user_role'] ?? '') !== 'DOCTOR'
					         && $caseSheet['status'] === 'INTAKE_COMPLETE';
				?>
				<div class="row align-items-center mb-2">
					<div class="col">
						<h1 class="m-0 text-dark">
							<?= $patientName ?>
							<span class="badge <?= $statusInfo['cls'] ?> ml-2 font-weight-normal" style="font-size:.6em;vertical-align:middle"><?= $statusInfo['label'] ?></span>
						</h1>
						<p class="text-muted mb-0 small">
							<?= htmlspecialchars($patient['patient_code'] ?? '') ?>
							<?php if ($patient['sex']): ?>
							&middot; <?= htmlspecialchars($patient['sex']) ?>
							<?php endif; ?>
							<?php if ($patient['age_years']): ?>
							&middot; <?= (int)$patient['age_years'] ?>y
							<?php endif; ?>
							&middot; Case Sheet #<?= (int)$caseSheet['case_sheet_id'] ?>
							<?php if ($caseSheet['visit_datetime']): ?>
							&middot; <?= htmlspecialchars(date('d M Y, g:i A', strtotime($caseSheet['visit_datetime']))) ?>
							<?php endif; ?>
						</p>
					</div>
					<div class="col-auto">
						<a href="patients.php?action=view&id=<?= (int)$patient['patient_id'] ?>" class="btn btn-sm btn-outline-secondary mr-2">
							<i class="fas fa-user mr-1"></i>Patient Profile
						</a>
						<?php if ($canAmend): ?>
						<form method="post" action="intake.php?action=amend" style="display:inline">
							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
							<input type="hidden" name="case_sheet_id" value="<?= (int)$caseSheet['case_sheet_id'] ?>">
							<button type="submit" class="btn btn-sm btn-warning">
								<i class="fas fa-pencil-alt mr-1"></i>Amend Case Sheet
							</button>
						</form>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

				<!-- ── Visit Details ──────────────────────────────────────────── -->
				<div class="card section-card mb-3">
					<div class="card-header">
						<h3 class="card-title mb-0"><i class="fas fa-clipboard mr-2 text-primary"></i>Visit Details</h3>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-sm-4 mb-3">
								<div class="ro-label">Visit Type</div>
								<div class="ro-value"><?= htmlspecialchars($caseSheet['visit_type'] ?? '') ?: '<span class="ro-empty">—</span>' ?></div>
							</div>
							<div class="col-sm-4 mb-3">
								<div class="ro-label">Assigned Doctor</div>
								<div class="ro-value"><?= $doctorName ?? '<span class="ro-empty">Not assigned</span>' ?></div>
							</div>
							<div class="col-sm-4 mb-3">
								<div class="ro-label">Created By</div>
								<div class="ro-value"><?= $createdBy ?? '<span class="ro-empty">—</span>' ?></div>
							</div>
							<div class="col-sm-4 mb-3">
								<div class="ro-label">Medicine Source</div>
								<div class="ro-value"><?= htmlspecialchars(ucfirst(strtolower($v['medicine_sources'] ?? ''))) ?: '<span class="ro-empty">—</span>' ?></div>
							</div>
							<div class="col-sm-4 mb-3">
								<div class="ro-label">Occupation</div>
								<div class="ro-value"><?= htmlspecialchars($v['occupation'] ?? '') ?: '<span class="ro-empty">—</span>' ?></div>
							</div>
							<div class="col-sm-4 mb-3">
								<div class="ro-label">Education</div>
								<div class="ro-value"><?= htmlspecialchars($v['education'] ?? '') ?: '<span class="ro-empty">—</span>' ?></div>
							</div>
							<div class="col-sm-12 mb-3">
								<div class="ro-label">Chief Complaint</div>
								<div class="ro-value"><?= htmlspecialchars($caseSheet['chief_complaint'] ?? '') ?: '<span class="ro-empty">—</span>' ?></div>
							</div>
							<?php if (!empty($caseSheet['history_present_illness'])): ?>
							<div class="col-sm-12 mb-0">
								<div class="ro-label">History of Present Illness</div>
								<div class="ro-value" style="white-space:pre-wrap"><?= htmlspecialchars($caseSheet['history_present_illness']) ?></div>
							</div>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- ── Vitals ──────────────────────────────────────────────────── -->
				<?php
					$bpSys  = $v['general_bp_systolic']  ?? $v['bp_systolic']  ?? '';
					$bpDia  = $v['general_bp_diastolic'] ?? $v['bp_diastolic'] ?? '';
					$pulse  = $v['general_pulse']         ?? $v['pulse']        ?? '';
					$temp   = $v['general_temperature']   ?? $v['temperature']  ?? '';
					$wt     = $v['general_weight']        ?? $v['weight_kg']    ?? '';
					$ht     = $v['general_height']        ?? $v['height_cm']    ?? '';
					$bmi    = $v['general_bmi']           ?? $v['bmi']          ?? '';
					$spo2   = $v['general_spo2']          ?? $v['spo2']         ?? '';
					$hasVitals = $bpSys || $pulse || $temp || $wt || $ht || $bmi || $spo2;
				?>
				<?php if ($hasVitals): ?>
				<div class="card section-card mb-3">
					<div class="card-header">
						<h3 class="card-title mb-0"><i class="fas fa-heartbeat mr-2 text-danger"></i>Vitals</h3>
					</div>
					<div class="card-body">
						<div class="row">
							<?php if ($bpSys || $bpDia): ?>
							<div class="col-6 col-sm-3 col-md-2 mb-3">
								<div class="vitals-box">
									<div class="val"><?= htmlspecialchars($bpSys ?: '—') ?><?= $bpDia ? '/' . htmlspecialchars($bpDia) : '' ?></div>
									<div class="unit">mmHg · BP</div>
								</div>
							</div>
							<?php endif; ?>
							<?php if ($pulse): ?>
							<div class="col-6 col-sm-3 col-md-2 mb-3">
								<div class="vitals-box">
									<div class="val"><?= htmlspecialchars($pulse) ?></div>
									<div class="unit">bpm · Pulse</div>
								</div>
							</div>
							<?php endif; ?>
							<?php if ($temp): ?>
							<div class="col-6 col-sm-3 col-md-2 mb-3">
								<div class="vitals-box">
									<div class="val"><?= htmlspecialchars($temp) ?></div>
									<div class="unit">°F · Temp</div>
								</div>
							</div>
							<?php endif; ?>
							<?php if ($spo2): ?>
							<div class="col-6 col-sm-3 col-md-2 mb-3">
								<div class="vitals-box">
									<div class="val"><?= htmlspecialchars($spo2) ?>%</div>
									<div class="unit">SpO₂</div>
								</div>
							</div>
							<?php endif; ?>
							<?php if ($wt): ?>
							<div class="col-6 col-sm-3 col-md-2 mb-3">
								<div class="vitals-box">
									<div class="val"><?= htmlspecialchars($wt) ?></div>
									<div class="unit">kg · Weight</div>
								</div>
							</div>
							<?php endif; ?>
							<?php if ($ht): ?>
							<div class="col-6 col-sm-3 col-md-2 mb-3">
								<div class="vitals-box">
									<div class="val"><?= htmlspecialchars($ht) ?></div>
									<div class="unit">cm · Height</div>
								</div>
							</div>
							<?php endif; ?>
							<?php if ($bmi): ?>
							<div class="col-6 col-sm-3 col-md-2 mb-3">
								<div class="vitals-box">
									<div class="val"><?= htmlspecialchars($bmi) ?></div>
									<div class="unit">BMI</div>
								</div>
							</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<!-- ── Medical History ───────────────────────────────────────── -->
				<?php
					$condLabels = [
						'condition_dm'           => 'Diabetes Mellitus',
						'condition_htn'          => 'Hypertension',
						'condition_tsh'          => 'Thyroid (TSH)',
						'condition_heart_disease'=> 'Heart Disease',
						'condition_others'       => 'Other Conditions',
					];
					$fhLabels = [
						'family_history_diabetes'    => 'Diabetes',
						'family_history_bp'          => 'Blood Pressure',
						'family_history_cancer'      => 'Cancer',
						'family_history_tuberculosis'=> 'Tuberculosis',
						'family_history_thyroid'     => 'Thyroid',
						'family_history_other'       => 'Other',
					];
					$condColors = ['CURRENT' => 'badge-danger', 'PAST' => 'badge-warning', 'NO' => 'badge-secondary'];
					$hasMedHistory = array_filter(array_keys($condLabels), fn($k) => !empty($hist[$k]));
					$hasFamilyHist = array_filter(array_keys($fhLabels), fn($k) => !empty($hist[$k]) && $hist[$k] !== '0');
					$allergyRows   = !empty($hist['allergies_json']) ? (json_decode($hist['allergies_json'], true) ?: []) : [];
					$noKnownAllergies = !empty($hist['no_known_allergies']);
				?>
				<?php if ($hasMedHistory || $hasFamilyHist || $allergyRows || $noKnownAllergies || !empty($patient['allergies'])): ?>
				<div class="card section-card mb-3">
					<div class="card-header">
						<h3 class="card-title mb-0"><i class="fas fa-notes-medical mr-2 text-info"></i>Medical History</h3>
					</div>
					<div class="card-body">
						<?php if ($hasMedHistory): ?>
						<div class="mb-3">
							<div class="ro-label mb-2">Conditions</div>
							<div class="d-flex flex-wrap" style="gap:.4rem">
								<?php foreach ($condLabels as $key => $label): ?>
									<?php if (!empty($hist[$key])): ?>
									<span>
										<span class="badge badge-pill <?= $condColors[$hist[$key]] ?? 'badge-secondary' ?> cond-badge">
											<?= htmlspecialchars($label) ?>: <?= htmlspecialchars($hist[$key]) ?>
										</span>
									</span>
									<?php if ($key === 'condition_others' && is_string($hist[$key]) && strlen($hist[$key]) > 7): ?>
									<span class="small text-muted align-self-center"><?= htmlspecialchars($hist[$key]) ?></span>
									<?php endif; ?>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
						<?php endif; ?>

						<?php if ($hasFamilyHist): ?>
						<div class="mb-3">
							<div class="ro-label mb-2">Family History</div>
							<div class="d-flex flex-wrap" style="gap:.4rem">
								<?php foreach ($fhLabels as $key => $label): ?>
									<?php if (!empty($hist[$key]) && $hist[$key] !== '0'): ?>
									<span class="badge badge-pill badge-light border cond-badge"><?= htmlspecialchars($label) ?></span>
									<?php endif; ?>
								<?php endforeach; ?>
								<?php if (!empty($hist['family_history_other']) && $hist['family_history_other'] !== '0' && $hist['family_history_other'] !== '1'): ?>
								<span class="small text-muted align-self-center">(<?= htmlspecialchars($hist['family_history_other']) ?>)</span>
								<?php endif; ?>
							</div>
						</div>
						<?php endif; ?>

						<!-- Allergies -->
						<div class="ro-label mb-2">Allergies</div>
						<?php if ($noKnownAllergies): ?>
							<p class="text-muted mb-0"><i class="fas fa-check-circle text-success mr-1"></i>No known allergies</p>
						<?php elseif (!empty($allergyRows)): ?>
							<div class="table-responsive">
								<table class="table table-sm table-bordered mb-0">
									<thead><tr><th>Allergen</th><th>Type</th><th>Reaction</th><th>Severity</th></tr></thead>
									<tbody>
									<?php foreach ($allergyRows as $ar): ?>
										<tr>
											<td><?= htmlspecialchars($ar['allergen'] ?? '') ?></td>
											<td><?= htmlspecialchars($ar['type'] ?? '') ?></td>
											<td><?= htmlspecialchars($ar['reaction'] ?? '') ?></td>
											<td><?= htmlspecialchars($ar['severity'] ?? '') ?></td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php elseif (!empty($patient['allergies'])): ?>
							<p class="ro-value"><?= htmlspecialchars($patient['allergies']) ?></p>
						<?php else: ?>
							<p class="ro-empty mb-0">None recorded</p>
						<?php endif; ?>
					</div>
				</div>
				<?php endif; ?>

				<!-- ── Examination Notes ─────────────────────────────────────── -->
				<?php
					$examFields = [
						'exam_mouth'     => 'Oral Cavity',
						'exam_teeth'     => 'Teeth',
						'exam_tongue'    => 'Tongue',
						'exam_throat'    => 'Throat',
						'exam_breast_left'  => 'Breast (L)',
						'exam_breast_right' => 'Breast (R)',
						'exam_pelvic_cervix'=> 'Pelvic / Cervix',
						'exam_gynae_pv'     => 'Gynaecology (PV)',
						'exam_gynae_via'    => 'Gynaecology (VIA)',
					];
					$hasExam = array_filter(array_keys($examFields), fn($k) => !empty($exam[$k]));
				?>
				<?php if ($hasExam): ?>
				<div class="card section-card mb-3">
					<div class="card-header">
						<h3 class="card-title mb-0"><i class="fas fa-stethoscope mr-2 text-success"></i>Examination Notes</h3>
					</div>
					<div class="card-body">
						<div class="row">
							<?php foreach ($examFields as $key => $label): ?>
								<?php if (!empty($exam[$key])): ?>
								<div class="col-sm-6 col-md-4 mb-3">
									<div class="ro-label"><?= htmlspecialchars($label) ?></div>
									<div class="ro-value"><?= htmlspecialchars($exam[$key]) ?></div>
								</div>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<!-- ── Lab Orders ───────────────────────────────────────────── -->
				<?php if (!empty($labOrders)): ?>
				<div class="card section-card mb-3">
					<div class="card-header">
						<h3 class="card-title mb-0"><i class="fas fa-flask mr-2 text-warning"></i>Lab Orders</h3>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-sm table-bordered mb-0">
								<thead>
									<tr>
										<th>Test</th>
										<th>Notes</th>
										<th>Ordered By</th>
										<th>Ordered At</th>
										<th>Status</th>
									</tr>
								</thead>
								<tbody>
								<?php foreach ($labOrders as $lo): ?>
									<tr>
										<td><?= htmlspecialchars($lo['test_name']) ?></td>
										<td><?= htmlspecialchars($lo['order_notes'] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
										<td><?= htmlspecialchars(($lo['ordered_by_first'] ?? '') . ' ' . ($lo['ordered_by_last'] ?? '')) ?></td>
										<td class="text-nowrap small"><?= htmlspecialchars(date('d M y, g:i A', strtotime($lo['ordered_at']))) ?></td>
										<td>
											<?php if ($lo['status'] === 'COMPLETED'): ?>
												<span class="badge badge-success">Completed</span>
											<?php else: ?>
												<span class="badge badge-warning">Pending</span>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<!-- ── Summary / Plan ───────────────────────────────────────── -->
				<?php $hasPlan = !empty($plan['summary_risk_level']) || !empty($plan['summary_referral']) || !empty($plan['summary_patient_acceptance']) || !empty($plan['summary_doctor_summary']); ?>
				<?php if ($hasPlan): ?>
				<div class="card section-card mb-3">
					<div class="card-header">
						<h3 class="card-title mb-0"><i class="fas fa-clipboard-check mr-2 text-secondary"></i>Summary / Disposition</h3>
					</div>
					<div class="card-body">
						<div class="row">
							<?php if (!empty($plan['summary_risk_level'])): ?>
							<div class="col-sm-6 mb-3">
								<div class="ro-label">Risk Level / Assessment</div>
								<div class="ro-value" style="white-space:pre-wrap"><?= htmlspecialchars($plan['summary_risk_level']) ?></div>
							</div>
							<?php endif; ?>
							<?php if (!empty($plan['summary_referral'])): ?>
							<div class="col-sm-6 mb-3">
								<div class="ro-label">Referral</div>
								<div class="ro-value"><?= htmlspecialchars($plan['summary_referral']) ?></div>
							</div>
							<?php endif; ?>
							<?php if (!empty($plan['summary_patient_acceptance'])): ?>
							<div class="col-sm-6 mb-3">
								<div class="ro-label">Patient Acceptance</div>
								<div class="ro-value"><?= htmlspecialchars($plan['summary_patient_acceptance']) ?></div>
							</div>
							<?php endif; ?>
							<?php if (!empty($plan['summary_doctor_summary'])): ?>
							<div class="col-sm-12 mb-0">
								<div class="ro-label">Doctor Summary</div>
								<div class="ro-value" style="white-space:pre-wrap"><?= htmlspecialchars($plan['summary_doctor_summary']) ?></div>
							</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<!-- ── Amendment / Audit History ─────────────────────────────── -->
				<?php if (!empty($auditLog)): ?>
				<div class="card section-card mb-4">
					<div class="card-header" id="auditHeading">
						<h3 class="card-title mb-0">
							<button class="btn btn-link text-dark p-0 collapsed" type="button"
							        data-toggle="collapse" data-target="#auditCollapse"
							        aria-expanded="false" aria-controls="auditCollapse">
								<i class="fas fa-history mr-2 text-muted"></i>Change History
								<span class="badge badge-secondary ml-2"><?= count($auditLog) ?></span>
							</button>
						</h3>
					</div>
					<div id="auditCollapse" class="collapse">
						<div class="card-body p-0">
							<div class="table-responsive">
								<table class="table table-sm table-bordered mb-0 audit-table">
									<thead>
										<tr>
											<th>When</th>
											<th>By</th>
											<th>Field</th>
											<th>Previous Value</th>
											<th>New Value</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach ($auditLog as $al): ?>
										<tr>
											<td class="text-nowrap"><?= htmlspecialchars(date('d M y, g:i A', strtotime($al['changed_at']))) ?></td>
											<td><?= htmlspecialchars($al['changed_by_name'] ?? (($al['user_first'] ?? '') . ' ' . ($al['user_last'] ?? ''))) ?></td>
											<td><code><?= htmlspecialchars($al['field_name']) ?></code></td>
											<td class="audit-old"><?= htmlspecialchars(mb_strimwidth($al['old_value'] ?? '', 0, 120, '…')) ?: '<span class="text-muted">—</span>' ?></td>
											<td class="audit-new"><?= htmlspecialchars(mb_strimwidth($al['new_value'] ?? '', 0, 120, '…')) ?: '<span class="text-muted">—</span>' ?></td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
				<?php endif; ?>

			</div>
		</section>
	</div>

	<footer class="main-footer text-sm">
		<strong>CareSystem</strong> <span class="badge badge-warning" style="font-size:.65rem;vertical-align:middle">Alpha</span> &middot; Case Sheet #<?= (int)$caseSheet['case_sheet_id'] ?> &middot; Read-only view
	</footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
</body>
</html>
