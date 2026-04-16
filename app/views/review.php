<?php
require_once __DIR__ . '/../config/lang.php';
load_language($_SESSION['language'] ?? 'en');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Doctor Review | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
		/* Scrollable sticky tab bar */
		.case-tab-bar {
			position: sticky;
			top: 0;
			z-index: 100;
			background: #fff;
			border-bottom: 2px solid #dee2e6;
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
			scrollbar-width: thin;
			scrollbar-color: #ced4da transparent;
			box-shadow: 0 2px 4px rgba(0,0,0,.05);
		}
		.case-tab-bar::-webkit-scrollbar { height: 3px; }
		.case-tab-bar::-webkit-scrollbar-thumb { background: #ced4da; border-radius: 2px; }
		.case-tab-bar .nav { flex-wrap: nowrap; padding: 0 16px; min-width: max-content; }
		.case-tab-bar .nav-link {
			color: #495057;
			background-color: transparent;
			border-radius: 0;
			font-size: 0.82rem;
			padding: 8px 14px;
			white-space: nowrap;
			border-bottom: 3px solid transparent;
			transition: color .15s, border-color .15s, background-color .15s;
		}
		.case-tab-bar .nav-link:hover { color: #007bff; background-color: #f8f9fa; }
		.case-tab-bar .nav-link.active {
			color: #007bff;
			background-color: transparent;
			border-bottom-color: #007bff;
			font-weight: 600;
		}
		.tab-navigation { margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6; display: flex; justify-content: space-between; }
		.auto-save-indicator {
			position: fixed; top: 70px; right: 20px; padding: 10px 20px;
			background-color: #28a745; color: white; border-radius: 4px;
			display: none; z-index: 1000;
		}
		.auto-save-indicator.saving { background-color: #ffc107; color: #000; }
		.auto-save-indicator.error { background-color: #dc3545; }
		.intake-summary-label { font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 2px; }
		.intake-summary-value { font-size: 0.95rem; color: #212529; margin-bottom: 0; }
		.intake-summary-empty { color: #adb5bd; font-style: italic; }
		.summary-section { border: 1px solid rgba(0,0,0,.125); border-radius: 0.25rem; margin-bottom: 1.5rem; padding-bottom: 0.75rem; }
		.summary-section h6 { color: #495057; font-weight: 700; margin: 0 0 0.75rem; padding: 0.6rem 1rem; background-color: rgba(0,0,0,.03); border-bottom: 1px solid rgba(0,0,0,.125); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; }
		.audit-row-changed td { background-color: rgba(255, 193, 7, 0.08); }
		/* Diagram editor */
		.diagram-preview-img { max-width: 300px; max-height: 220px; cursor: pointer; }
		.diagram-canvas-container { overflow: auto; max-height: 65vh; background: #f8f9fa; padding: 16px; border: 2px solid #dee2e6; border-radius: 6px; text-align: center; }
		#diagramCanvas { border: 1px solid #adb5bd; background: white; cursor: crosshair; touch-action: none; }
		.modal-xl { max-width: 92%; }
		/* Vitals comparison panel */
		.vitals-compare-table th.col-prev { background-color: #f8f9fa; }
		.vitals-compare-prev { color: #6c757d; font-size: 0.9rem; }
		.vitals-compare-drastic { color: #dc3545; font-weight: 600; }
		.vitals-compare-no-record { color: #adb5bd; font-style: italic; font-size: 0.85rem; }
	</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed<?= ($_SESSION['font_size'] ?? 'normal') === 'large' ? ' font-size-large' : '' ?>"
      data-theme-server="<?= htmlspecialchars($_SESSION['theme'] ?? 'system') ?>">
<div class="wrapper">

	<nav class="main-header navbar navbar-expand navbar-white navbar-light">
		<ul class="navbar-nav">
			<li class="nav-item">
				<a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></a>
			</li>
			<li class="nav-item d-none d-sm-inline-block">
				<span class="navbar-brand mb-0 h6 text-primary">CareSystem</span>
			</li>
			<li class="nav-item d-none d-md-inline-block">
				<span class="navbar-text px-2 ml-1" style="border-left:1px solid #dee2e6;">
					<strong class="text-dark"><?= htmlspecialchars($patient['first_name'] . ' ' . ($patient['last_name'] ?? '')) ?></strong>
					<span class="badge badge-secondary ml-1"><?= htmlspecialchars($patient['patient_code']) ?></span>
				</span>
			</li>
		</ul>

		<ul class="navbar-nav ml-auto">
			<li class="nav-item d-flex align-items-center">
				<button id="gearBtn" aria-label="<?= __('display_settings') ?>" title="<?= __('display_settings') ?>">
					<i class="fas fa-cog fa-lg"></i>
				</button>
			</li>
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="dashboard.php" role="button">
					<i class="fas fa-arrow-left mr-1"></i><?= __('dashboard') ?>
				</a>
			</li>
		</ul>
	</nav>

	<!-- Slide-down display settings panel -->
	<div id="settingsPanel" role="dialog" aria-label="<?= __('display_settings') ?>">
		<span class="panel-label"><?= __('display_settings') ?></span>
		<div class="custom-control custom-switch mb-3">
			<input type="checkbox" class="custom-control-input" id="themeTogglePanel" data-theme-toggle />
			<label class="custom-control-label" for="themeTogglePanel"><?= __('dark_mode') ?></label>
		</div>
		<div>
			<span class="panel-label"><?= __('language') ?></span>
			<div class="btn-group lang-btn-group" role="group" aria-label="<?= __('language') ?>">
				<button type="button" class="btn btn-sm <?= ($_SESSION['language'] ?? 'en') === 'en' ? 'btn-primary' : 'btn-outline-secondary' ?>" data-lang="en">English</button>
				<button type="button" class="btn btn-sm <?= ($_SESSION['language'] ?? 'en') === 'te' ? 'btn-primary' : 'btn-outline-secondary' ?>" data-lang="te">తెలుగు</button>
			</div>
		</div>
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
	</div>

	<?php require __DIR__ . '/_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="case-tab-bar">
			<ul class="nav nav-pills" id="reviewTabs" role="tablist">
				<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-patient"><?= __('tab_patient') ?></a></li>
				<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-medical-history"><?= __('tab_history_records') ?></a></li>
				<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-general"><?= __('tab_general') ?></a></li>
				<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-examinations"><?= __('tab_examinations') ?></a></li>
				<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-labs"><?= __('tab_labs') ?></a></li>
				<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-assessment"><?= __('tab_assessment') ?></a></li>
				<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-plan"><?= __('tab_plan') ?></a></li>
				<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-followup"><?= __('tab_followup') ?></a></li>
				<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-audit"><?= __('tab_audit') ?></a></li>
			</ul>
		</div>
		<?php
			$cs         = $caseSheet;
			$p          = $patient;
			$csId       = (int)$cs['case_sheet_id'];
			$vitals     = !empty($cs['vitals_json']) ? json_decode($cs['vitals_json'], true) : [];
			$examData   = !empty($cs['exam_notes']) ? json_decode($cs['exam_notes'], true) : [];
			$histData   = !empty($cs['assessment']) ? json_decode($cs['assessment'], true) : [];
			$labData    = !empty($cs['diagnosis']) ? json_decode($cs['diagnosis'], true) : [];
			$summData   = !empty($cs['plan_notes']) ? json_decode($cs['plan_notes'], true) : [];
			if (!is_array($examData))  $examData  = [];
			if (!is_array($histData))  $histData  = [];
			if (!is_array($labData))   $labData   = [];
			if (!is_array($summData))  $summData  = [];
			if (!is_array($vitals))    $vitals    = [];

			// prevLabData is pre-decoded by the controller from the most recent
			// closed case sheet that has lab data — default to empty array if not set
			if (!isset($prevLabData) || !is_array($prevLabData)) {
				$prevLabData = [];
			}

			// Helper: display a value or a placeholder
			function rv($val, $placeholder = '—') {
				$v = trim((string)($val ?? ''));
				if ($v === '') return '<span class="intake-summary-empty">' . $placeholder . '</span>';
				return htmlspecialchars($v);
			}

			// Helper: compare a vital between visits and return display data.
			// Returns an array with keys: prev (string), curr (string), drastic (bool), direction ('up'|'down'|null)
			function vitalsCompare($prevVitals, $currVitals, $field, $threshold, $aliases = []) {
				// Support legacy field aliases (e.g. bp_systolic vs general_bp_systolic)
				$currVal = null;
				foreach (array_merge([$field], $aliases) as $key) {
					if (isset($currVitals[$key]) && $currVitals[$key] !== '') {
						$currVal = $currVitals[$key];
						break;
					}
				}
				$prevVal = null;
				foreach (array_merge([$field], $aliases) as $key) {
					if (isset($prevVitals[$key]) && $prevVitals[$key] !== '') {
						$prevVal = $prevVitals[$key];
						break;
					}
				}
				$drastic   = false;
				$direction = null;
				if ($prevVal !== null && $currVal !== null && is_numeric($prevVal) && is_numeric($currVal)) {
					$delta = (float)$currVal - (float)$prevVal;
					if (abs($delta) >= $threshold) {
						$drastic   = true;
						$direction = $delta > 0 ? 'up' : 'down';
					}
				}
				return [
					'prev'      => $prevVal,
					'curr'      => $currVal,
					'drastic'   => $drastic,
					'direction' => $direction,
				];
			}
		?>

		<div class="content-header">
			<div class="container-fluid">
				<div class="row align-items-center">
					<div class="col">
						<h1 class="m-0 text-dark">
							<?= htmlspecialchars($p['first_name'] . ' ' . ($p['last_name'] ?? '')) ?>
							<small class="text-muted ml-1"><?= htmlspecialchars($p['patient_code']) ?></small>
							<span class="badge badge-warning ml-2" style="font-size:0.6rem;vertical-align:middle;"><?= strtoupper(__('doctor_review')) ?></span>
						</h1>
						<p class="text-muted mb-0 small">
							<?= __('intake_by') ?> <?= htmlspecialchars(!empty($cs['created_by_name']) ? $cs['created_by_name'] : (($intakeUser['first_name'] ?? '') . ' ' . ($intakeUser['last_name'] ?? ''))) ?>
							&middot; <?= date('M j, Y g:i A', strtotime($cs['visit_datetime'])) ?>
							&middot; <strong><?= htmlspecialchars(['CAMP'=>__('visit_camp'),'CLINIC'=>__('visit_clinic'),'FOLLOW_UP'=>__('visit_follow_up'),'EMERGENCY'=>__('visit_emergency'),'OTHER'=>__('other')][$cs['visit_type']] ?? htmlspecialchars($cs['visit_type'])) ?></strong>
						</p>
					</div>
				</div>
			</div>
		</div>

		<!-- Auto-save indicator -->
		<div id="autoSaveIndicator" class="auto-save-indicator"><i class="fas fa-check-circle"></i> <?= __('saved') ?></div>

		<?php if (!empty($flashError)): ?>
		<div class="container-fluid">
			<div class="alert alert-danger alert-dismissible fade show" role="alert">
				<i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($flashError) ?>
				<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
			</div>
		</div>
		<?php endif; ?>

		<section class="content">
			<div class="container-fluid">
				<div class="card shadow-sm">
					<div class="card-body">
						<!-- ── This Visit (persistent, shown above all tabs) ── -->
					<div class="card bg-light border-0 mb-3">
						<div class="card-body py-2 px-3">
							<div class="row align-items-center">
								<div class="col-auto">
									<span class="text-muted small text-uppercase font-weight-bold" style="letter-spacing:.05em;">
										<i class="fas fa-notes-medical mr-1"></i><?= __('this_visit') ?>
									</span>
								</div>
								<div class="col-auto">
									<span class="text-muted small mr-1"><?= __('type_label') ?>:</span>
									<strong class="small"><?= htmlspecialchars(['CAMP'=>__('visit_camp'),'CLINIC'=>__('visit_clinic'),'FOLLOW_UP'=>__('visit_follow_up'),'EMERGENCY'=>__('visit_emergency'),'OTHER'=>__('other')][$cs['visit_type']] ?? htmlspecialchars($cs['visit_type'])) ?></strong>
								</div>
								<div class="col-auto">
									<span class="text-muted small mr-1"><?= __('chief_complaint') ?>:</span>
									<strong class="small"><?= htmlspecialchars($cs['chief_complaint'] ?? '—') ?></strong>
								</div>
								<?php if (!empty($vitals['symptoms_complaints'])): ?>
								<div class="col-auto">
									<span class="text-muted small mr-1"><?= __('symptoms_label') ?>:</span>
									<span class="small"><?= htmlspecialchars($vitals['symptoms_complaints']) ?></span>
									<?php if (!empty($vitals['duration_of_symptoms'])): ?>
									<span class="text-muted small ml-1">(<?= htmlspecialchars($vitals['duration_of_symptoms']) ?>)</span>
									<?php endif; ?>
								</div>
								<?php endif; ?>
								<div class="col-auto ml-auto">
									<span class="text-muted small"><?= date('M j, Y g:i A', strtotime($cs['visit_datetime'])) ?></span>
								</div>
							</div>
						</div>
					</div>

					<div class="tab-content" id="reviewTabContent">

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 1: PATIENT HISTORY                            -->
							<!-- ══════════════════════════════════════════════════ -->
<!-- ══════════════════════════════════════════════════ -->
<!-- TAB 1: PATIENT                                    -->
<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade show active" id="tab-patient" role="tabpanel">

								<div class="d-flex align-items-center justify-content-between mb-4">
									<h4 class="mb-0"><?= __('tab_patient') ?></h4>
									<div>
										<button type="button" id="btnEditPatientReview" class="btn btn-sm btn-outline-primary">
											<i class="fas fa-edit mr-1"></i> <?= __('edit_patient_info') ?>
										</button>
										<button type="button" id="btnLockPatientReview" class="btn btn-sm btn-warning ml-2" style="display:none;">
											<i class="fas fa-lock mr-1"></i> <?= __('lock_editing') ?>
										</button>
									</div>
								</div>

								<div id="patientReviewAlert" class="alert alert-warning alert-dismissible mb-3" style="display:none;" role="alert">
									<i class="fas fa-exclamation-triangle mr-2"></i>
									<strong><?= __('editing_patient_record') ?></strong> <?= __('auto_save_audit_note') ?>
									<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
								</div>

								<div id="patientSaveError" class="alert alert-danger mb-3" style="display:none;" role="alert"></div>

								<!-- ── Identity ──────────────────────────────────── -->
								<div class="card card-outline card-primary mb-3">
									<div class="card-header py-2">
										<h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><?= __('identity') ?></h6>
									</div>
									<div class="card-body py-3">
										<div class="row">
											<div class="col-md-6 mb-3">
												<label class="small font-weight-bold"><?= __('first_name') ?> <span class="text-danger">*</span></label>
												<input type="text" class="form-control patient-review-field" id="pr-first_name" name="first_name"
												       value="<?= htmlspecialchars($p['first_name'] ?? '') ?>" readonly />
											</div>
											<div class="col-md-6 mb-3">
												<label class="small font-weight-bold"><?= __('last_name') ?></label>
												<input type="text" class="form-control patient-review-field" id="pr-last_name" name="last_name"
												       value="<?= htmlspecialchars($p['last_name'] ?? '') ?>" readonly />
											</div>
											<div class="col-md-3 mb-3">
												<label class="small font-weight-bold"><?= __('sex') ?></label>
												<select class="form-control patient-review-field" id="pr-sex" name="sex" disabled>
													<option value="MALE"    <?= ($p['sex'] ?? '') === 'MALE'    ? 'selected' : '' ?>><?= __('sex_male') ?></option>
													<option value="FEMALE"  <?= ($p['sex'] ?? '') === 'FEMALE'  ? 'selected' : '' ?>><?= __('sex_female') ?></option>
													<option value="OTHER"   <?= ($p['sex'] ?? '') === 'OTHER'   ? 'selected' : '' ?>><?= __('sex_other') ?></option>
													<option value="UNKNOWN" <?= ($p['sex'] ?? '') === 'UNKNOWN' ? 'selected' : '' ?>><?= __('sex_unknown') ?></option>
												</select>
											</div>
											<div class="col-md-3 mb-3">
												<label class="small font-weight-bold"><?= __('date_of_birth') ?></label>
												<input type="date" class="form-control patient-review-field" id="pr-date_of_birth" name="date_of_birth"
												       value="<?= htmlspecialchars($p['date_of_birth'] ?? '') ?>" readonly />
											</div>
											<div class="col-md-3 mb-3">
												<label class="small font-weight-bold"><?= __('age_years') ?></label>
												<input type="number" class="form-control patient-review-field" id="pr-age_years" name="age_years"
												       min="0" max="150" value="<?= htmlspecialchars($p['age_years'] ?? '') ?>" readonly />
											</div>
											<div class="col-md-3 mb-0">
												<label class="small font-weight-bold"><?= __('blood_group') ?></label>
												<select class="form-control patient-review-field" id="pr-blood_group" name="blood_group" disabled>
													<option value=""><?= __('select_placeholder') ?></option>
													<?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
													<option value="<?= $bg ?>" <?= ($p['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
													<?php endforeach; ?>
												</select>
											</div>
										</div>
									</div>
								</div>

								<!-- ── Contact ───────────────────────────────────── -->
								<div class="card card-outline card-info mb-3">
									<div class="card-header py-2">
										<h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><?= __('contact') ?></h6>
									</div>
									<div class="card-body py-3">
										<div class="row">
											<div class="col-md-4 mb-3">
												<label class="small font-weight-bold"><?= __('phone') ?></label>
												<input type="text" class="form-control patient-review-field" id="pr-phone_e164" name="phone_e164"
												       value="<?= htmlspecialchars($p['phone_e164'] ?? '') ?>" placeholder="+91 98765 43210" readonly />
											</div>
											<div class="col-md-5 mb-3">
												<label class="small font-weight-bold"><?= __('email') ?></label>
												<input type="email" class="form-control patient-review-field" id="pr-email" name="email"
												       value="<?= htmlspecialchars($p['email'] ?? '') ?>" readonly />
											</div>
										</div>
									</div>
								</div>

								<!-- ── Address ───────────────────────────────────── -->
								<div class="card card-outline card-secondary mb-3">
									<div class="card-header py-2">
										<h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><?= __('address') ?></h6>
									</div>
									<div class="card-body py-3">
										<div class="row">
											<div class="col-md-6 mb-3">
												<label class="small font-weight-bold"><?= __('street_address') ?></label>
												<input type="text" class="form-control patient-review-field" id="pr-address_line1" name="address_line1"
												       value="<?= htmlspecialchars($p['address_line1'] ?? '') ?>" readonly />
											</div>
											<div class="col-md-3 mb-3">
												<label class="small font-weight-bold"><?= __('city') ?></label>
												<input type="text" class="form-control patient-review-field" id="pr-city" name="city"
												       value="<?= htmlspecialchars($p['city'] ?? '') ?>" readonly />
											</div>
											<div class="col-md-2 mb-3">
												<label class="small font-weight-bold"><?= __('state') ?></label>
												<input type="text" class="form-control patient-review-field" id="pr-state_province" name="state_province"
												       value="<?= htmlspecialchars($p['state_province'] ?? '') ?>" readonly />
											</div>
											<div class="col-md-2 mb-0">
												<label class="small font-weight-bold"><?= __('pin_code') ?></label>
												<input type="text" class="form-control patient-review-field" id="pr-postal_code" name="postal_code"
												       value="<?= htmlspecialchars($p['postal_code'] ?? '') ?>" readonly />
											</div>
										</div>
									</div>
								</div>

								<!-- ── Emergency Contact ─────────────────────────── -->
								<div class="card card-outline card-warning mb-3">
									<div class="card-header py-2">
										<h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><?= __('emergency_contact') ?></h6>
									</div>
									<div class="card-body py-3">
										<div class="row">
											<div class="col-md-5 mb-3 mb-md-0">
												<label class="small font-weight-bold"><?= __('name') ?></label>
												<input type="text" class="form-control patient-review-field" id="pr-emergency_contact_name" name="emergency_contact_name"
												       value="<?= htmlspecialchars($p['emergency_contact_name'] ?? '') ?>" readonly />
											</div>
											<div class="col-md-4 mb-0">
												<label class="small font-weight-bold"><?= __('phone') ?></label>
												<input type="text" class="form-control patient-review-field" id="pr-emergency_contact_phone" name="emergency_contact_phone"
												       value="<?= htmlspecialchars($p['emergency_contact_phone'] ?? '') ?>" readonly />
											</div>
										</div>
									</div>
								</div>

								<!-- ── Allergies ─────────────────────────────────── -->
								<div class="card card-outline card-danger mb-3">
									<div class="card-header py-2">
										<h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;">
											<?= __('allergies') ?>
											<small class="text-muted font-weight-normal ml-2" style="font-size:.8rem;text-transform:none;letter-spacing:0;">
												<?= __('synced_with_history') ?>
											</small>
										</h6>
									</div>
									<div class="card-body py-3">
										<div class="mb-3">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="pt-noKnownAllergies"
												       <?= !empty($histData['no_known_allergies']) ? 'checked' : '' ?> />
												<label class="custom-control-label font-weight-bold text-danger" for="pt-noKnownAllergies">
													<?= __('no_known_allergies') ?>
												</label>
											</div>
										</div>
										<div id="pt-allergySection"<?= !empty($histData['no_known_allergies']) ? ' style="display:none;"' : '' ?>>
											<div class="d-flex font-weight-bold small text-muted mb-1 px-1">
												<span class="flex-fill mr-2"><?= __('allergy_substance') ?></span>
												<span class="flex-fill mr-2"><?= __('allergy_reaction') ?></span>
												<span style="width:42px;"></span>
											</div>
											<div id="pt-allergyRows"></div>
											<button type="button" id="pt-btnAddAllergy" class="btn btn-sm btn-outline-secondary mt-2">
												<i class="fas fa-plus mr-1"></i> <?= __('add_allergy') ?>
											</button>
										</div>
									</div>
								</div>

								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-patient-history"><i class="fas fa-chevron-left"></i> <?= __('patient_history_heading') ?></button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-medical-history"><?= __('tab_history') ?> <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>


<!-- ══════════════════════════════════════════════════ -->
<!-- TAB 2: HISTORY & PATIENT RECORDS                  -->
<!-- ══════════════════════════════════════════════════ -->
							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 3: HISTORY                                    -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-medical-history" role="tabpanel">
								<h4 class="mb-4"><?= __('tab_history') ?></h4>

								<form class="doctor-auto-save">

								<!-- ── Medical Conditions ───────────────────────── -->
								<div class="card card-outline card-warning mb-3">
									<div class="card-header">
										<h5 class="card-title mb-0"><i class="fas fa-file-medical-alt mr-2"></i><?= __('medical_conditions') ?></h5>
									</div>
									<div class="card-body">
										<div class="row">
											<div class="col-md-3 mb-3">
												<label><?= __('condition_dm') ?></label>
												<select class="form-control" name="condition_dm" data-field="condition_dm">
													<option value="NO"      <?= ($histData['condition_dm'] ?? 'NO') === 'NO'      ? 'selected' : '' ?>><?= __('cond_no') ?></option>
													<option value="CURRENT" <?= ($histData['condition_dm'] ?? '') === 'CURRENT' ? 'selected' : '' ?>><?= __('cond_current') ?></option>
													<option value="PAST"    <?= ($histData['condition_dm'] ?? '') === 'PAST'    ? 'selected' : '' ?>><?= __('cond_past') ?></option>
												</select>
											</div>
											<div class="col-md-3 mb-3">
												<label><?= __('condition_htn') ?></label>
												<select class="form-control" name="condition_htn" data-field="condition_htn">
													<option value="NO"      <?= ($histData['condition_htn'] ?? 'NO') === 'NO'      ? 'selected' : '' ?>><?= __('cond_no') ?></option>
													<option value="CURRENT" <?= ($histData['condition_htn'] ?? '') === 'CURRENT' ? 'selected' : '' ?>><?= __('cond_current') ?></option>
													<option value="PAST"    <?= ($histData['condition_htn'] ?? '') === 'PAST'    ? 'selected' : '' ?>><?= __('cond_past') ?></option>
												</select>
											</div>
											<div class="col-md-3 mb-3">
												<label><?= __('condition_tsh') ?></label>
												<select class="form-control" name="condition_tsh" data-field="condition_tsh">
													<option value="NO"      <?= ($histData['condition_tsh'] ?? 'NO') === 'NO'      ? 'selected' : '' ?>><?= __('cond_no') ?></option>
													<option value="CURRENT" <?= ($histData['condition_tsh'] ?? '') === 'CURRENT' ? 'selected' : '' ?>><?= __('cond_current') ?></option>
													<option value="PAST"    <?= ($histData['condition_tsh'] ?? '') === 'PAST'    ? 'selected' : '' ?>><?= __('cond_past') ?></option>
												</select>
											</div>
											<div class="col-md-3 mb-3">
												<label><?= __('condition_heart_disease') ?></label>
												<select class="form-control" name="condition_heart_disease" data-field="condition_heart_disease">
													<option value="NO"      <?= ($histData['condition_heart_disease'] ?? 'NO') === 'NO'      ? 'selected' : '' ?>><?= __('cond_no') ?></option>
													<option value="CURRENT" <?= ($histData['condition_heart_disease'] ?? '') === 'CURRENT' ? 'selected' : '' ?>><?= __('cond_current') ?></option>
													<option value="PAST"    <?= ($histData['condition_heart_disease'] ?? '') === 'PAST'    ? 'selected' : '' ?>><?= __('cond_past') ?></option>
												</select>
											</div>
										</div>
										<div class="row">
											<div class="col-md-6 mb-3">
												<label><?= __('other_conditions') ?></label>
												<textarea class="form-control" name="condition_others" data-field="condition_others" rows="2"><?= htmlspecialchars($histData['condition_others'] ?? '') ?></textarea>
											</div>
											<div class="col-md-6 mb-0">
												<label><?= __('surgical_history') ?></label>
												<textarea class="form-control" name="surgical_history" data-field="surgical_history" rows="2"><?= htmlspecialchars($histData['surgical_history'] ?? '') ?></textarea>
											</div>
										</div>
									</div>
								</div>

								<!-- ── Allergies ────────────────────────────────── -->
								<div class="card card-outline card-danger mb-3">
									<div class="card-header">
										<h5 class="card-title mb-0"><i class="fas fa-allergies mr-2"></i><?= __('allergies') ?></h5>
									</div>
									<div class="card-body">
										<div class="mb-3">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="rv-noKnownAllergies"
												       name="no_known_allergies" data-field="no_known_allergies" value="1"
												       <?= !empty($histData['no_known_allergies']) ? 'checked' : '' ?> />
												<label class="custom-control-label font-weight-bold text-danger" for="rv-noKnownAllergies"><?= __('no_known_allergies') ?></label>
												<small class="text-muted ml-2"><?= __('no_known_allergies_hint') ?></small>
											</div>
										</div>
										<div id="rv-allergySection"<?= !empty($histData['no_known_allergies']) ? ' style="display:none;"' : '' ?>>
											<div class="d-flex font-weight-bold small text-muted mb-1 px-1">
												<span class="flex-fill mr-2"><?= __('allergy_substance') ?></span>
												<span class="flex-fill mr-2"><?= __('allergy_reaction') ?></span>
												<span style="width:42px;"></span>
											</div>
											<div id="rv-allergyRows"></div>
											<button type="button" id="rv-btnAddAllergy" class="btn btn-sm btn-outline-secondary mt-2">
												<i class="fas fa-plus mr-1"></i> <?= __('add_allergy') ?>
											</button>
										</div>
									</div>
								</div>

								<!-- ── Family History ───────────────────────────── -->
								<div class="card card-outline card-success mb-3">
									<div class="card-header">
										<h5 class="card-title mb-0"><i class="fas fa-users mr-2"></i><?= __('family_history') ?></h5>
									</div>
									<div class="card-body">
										<div class="row">
											<div class="col-md-4 mb-3">
												<div class="custom-control custom-checkbox">
													<input type="checkbox" class="custom-control-input" id="rv-fh_cancer"
													       name="family_history_cancer" data-field="family_history_cancer" value="1"
													       <?= !empty($histData['family_history_cancer']) ? 'checked' : '' ?> />
													<label class="custom-control-label" for="rv-fh_cancer"><?= __('fh_cancer') ?></label>
												</div>
											</div>
											<div class="col-md-4 mb-3">
												<div class="custom-control custom-checkbox">
													<input type="checkbox" class="custom-control-input" id="rv-fh_tb"
													       name="family_history_tuberculosis" data-field="family_history_tuberculosis" value="1"
													       <?= !empty($histData['family_history_tuberculosis']) ? 'checked' : '' ?> />
													<label class="custom-control-label" for="rv-fh_tb"><?= __('fh_tuberculosis') ?></label>
												</div>
											</div>
											<div class="col-md-4 mb-3">
												<div class="custom-control custom-checkbox">
													<input type="checkbox" class="custom-control-input" id="rv-fh_diabetes"
													       name="family_history_diabetes" data-field="family_history_diabetes" value="1"
													       <?= !empty($histData['family_history_diabetes']) ? 'checked' : '' ?> />
													<label class="custom-control-label" for="rv-fh_diabetes"><?= __('fh_diabetes') ?></label>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-4 mb-3">
												<div class="custom-control custom-checkbox">
													<input type="checkbox" class="custom-control-input" id="rv-fh_bp"
													       name="family_history_bp" data-field="family_history_bp" value="1"
													       <?= !empty($histData['family_history_bp']) ? 'checked' : '' ?> />
													<label class="custom-control-label" for="rv-fh_bp"><?= __('fh_bp') ?></label>
												</div>
											</div>
											<div class="col-md-4 mb-3">
												<div class="custom-control custom-checkbox">
													<input type="checkbox" class="custom-control-input" id="rv-fh_thyroid"
													       name="family_history_thyroid" data-field="family_history_thyroid" value="1"
													       <?= !empty($histData['family_history_thyroid']) ? 'checked' : '' ?> />
													<label class="custom-control-label" for="rv-fh_thyroid"><?= __('fh_thyroid') ?></label>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="col-md-12 mb-0">
												<label><?= __('other_family_history') ?></label>
												<input type="text" class="form-control" name="family_history_other" data-field="family_history_other"
												       value="<?= htmlspecialchars($histData['family_history_other'] ?? '') ?>" />
											</div>
										</div>
									</div>
								</div>

								<small class="text-muted"><?= __('auto_saved_note') ?></small>
								</form>


								<hr class="my-4">
								<h4 class="mb-1"><?= __('patient_history_heading') ?></h4>
								<p class="text-muted mb-4"><?= __('prior_visits_subtitle') ?></p>

								<?php if (empty($priorCaseSheets)): ?>
								<div class="alert alert-light border">
									<i class="fas fa-info-circle mr-2 text-muted"></i><?= __('no_previous_visits') ?>
								</div>
								<?php else: ?>
								<?php
								$_phClosureLabels = [
									'DISCHARGED' => __('closure_discharged'),
									'FOLLOW_UP'  => __('visit_follow_up'),
									'REFERRAL'   => __('closure_referred'),
									'CANCELLED'  => __('closure_cancelled'),
								];
								$_phClosureColors = [
									'DISCHARGED' => 'success',
									'FOLLOW_UP'  => 'info',
									'REFERRAL'   => 'warning',
									'CANCELLED'  => 'secondary',
								];
								$_phVisitLabels = [
									'CAMP'      => __('visit_camp'),
									'CLINIC'    => __('visit_clinic'),
									'FOLLOW_UP' => __('visit_follow_up'),
									'EMERGENCY' => __('visit_emergency'),
									'OTHER'     => __('other'),
								];
								?>
								<div id="priorCaseSheetsAccordion">
								<?php foreach ($priorCaseSheets as $i => $prior):
									$_phVitals   = !empty($prior['vitals_json']) ? (json_decode($prior['vitals_json'], true) ?: []) : [];
									$_phHist     = !empty($prior['assessment'])  ? (json_decode($prior['assessment'],  true) ?: []) : [];
									$_phLab      = !empty($prior['diagnosis'])   ? (json_decode($prior['diagnosis'],   true) ?: []) : [];
									$_phDoctorName = trim(($prior['doctor_first'] ?? '') . ' ' . ($prior['doctor_last'] ?? ''));
									$_phAssessment = $prior['doctor_assessment'] ?? null;
									$_phDiagnosis  = $prior['doctor_diagnosis']  ?? null;
									$_phPlan       = $prior['doctor_plan_notes'] ?? null;
									$_phClosure    = $prior['closure_type'] ?? 'PENDING';
									$_phHasBody    = !empty($prior['chief_complaint']) || !empty($_phVitals)
										           || !empty($_phAssessment) || !empty($_phDiagnosis) || !empty($_phPlan)
										           || !empty($prior['prescriptions']) || !empty($prior['follow_up_notes'])
										           || !empty($_phHist) || !empty($_phLab);
								?>
								<div class="card mb-2 border-left-<?= $_phClosureColors[$_phClosure] ?? 'secondary' ?>">
									<div class="card-header p-0 bg-white">
										<button class="btn btn-link btn-block text-left px-3 py-2<?= $_phHasBody ? '' : ' disabled' ?>"
										        type="button" data-toggle="collapse"
										        data-target="#prior-<?= $i ?>" aria-expanded="false">
											<div class="d-flex align-items-center justify-content-between">
												<div>
													<span class="font-weight-bold"><?= date('d M Y', strtotime($prior['visit_datetime'])) ?></span>
													<span class="badge badge-light border ml-2"><?= htmlspecialchars($_phVisitLabels[$prior['visit_type']] ?? $prior['visit_type']) ?></span>
													<?php if (isset($_phClosureLabels[$_phClosure])): ?>
													<span class="badge badge-<?= $_phClosureColors[$_phClosure] ?? 'secondary' ?> ml-1"><?= $_phClosureLabels[$_phClosure] ?></span>
													<?php endif; ?>
													<?php if (!empty($prior['chief_complaint'])): ?>
													<span class="text-muted small ml-2">&mdash; <?= htmlspecialchars($prior['chief_complaint']) ?></span>
													<?php endif; ?>
												</div>
												<div class="text-right flex-shrink-0 ml-2">
													<?php if ($_phDoctorName): ?>
													<small class="text-muted d-block"><i class="fas fa-user-md mr-1"></i>Dr. <?= htmlspecialchars($_phDoctorName) ?></small>
													<?php endif; ?>
													<?php if ($_phHasBody): ?><i class="fas fa-chevron-down small text-muted"></i><?php endif; ?>
												</div>
											</div>
										</button>
									</div>
									<?php if ($_phHasBody): ?>
									<div id="prior-<?= $i ?>" class="collapse" data-parent="#priorCaseSheetsAccordion">
										<div class="card-body pt-3">
											<div class="row">

											<?php if (!empty($_phVitals)): ?>
											<div class="col-12 mb-3">
												<div class="intake-summary-label mb-2"><i class="fas fa-heartbeat mr-1"></i><?= __('vital_signs') ?></div>
												<div class="row">
													<div class="col-6 col-md-2 mb-1"><small class="text-muted d-block"><?= __('pulse') ?></small><?= rv($_phVitals['general_pulse'] ?? $_phVitals['pulse'] ?? null) ?> <small>/mt</small></div>
													<div class="col-6 col-md-2 mb-1"><small class="text-muted d-block"><?= __('bp') ?></small><?= rv($_phVitals['general_bp_systolic'] ?? null) ?>/<?= rv($_phVitals['general_bp_diastolic'] ?? null) ?> <small>mmHg</small></div>
													<div class="col-6 col-md-2 mb-1"><small class="text-muted d-block"><?= __('spo2') ?></small><?= rv($_phVitals['spo2'] ?? null) ?> <small>%</small></div>
													<div class="col-6 col-md-2 mb-1"><small class="text-muted d-block"><?= __('weight') ?></small><?= rv($_phVitals['general_weight'] ?? $_phVitals['weight_kg'] ?? null) ?> <small>kg</small></div>
													<div class="col-6 col-md-2 mb-1"><small class="text-muted d-block"><?= __('bmi') ?></small><?= rv($_phVitals['general_bmi'] ?? null) ?></div>
												</div>
											</div>
											<?php endif; ?>

											<?php if (!empty($_phAssessment)): ?>
											<div class="col-md-6 mb-3">
												<div class="intake-summary-label"><?= __('assessment_label') ?></div>
												<div><?= nl2br(htmlspecialchars($_phAssessment)) ?></div>
											</div>
											<?php endif; ?>

											<?php if (!empty($_phDiagnosis)): ?>
											<div class="col-md-6 mb-3">
												<div class="intake-summary-label"><?= __('diagnosis_label') ?></div>
												<div><?= nl2br(htmlspecialchars($_phDiagnosis)) ?></div>
											</div>
											<?php endif; ?>

											<?php if (!empty($_phPlan)): ?>
											<div class="col-md-6 mb-3">
												<div class="intake-summary-label"><?= __('treatment_plan_label') ?></div>
												<div><?= nl2br(htmlspecialchars($_phPlan)) ?></div>
											</div>
											<?php endif; ?>

											<?php if (!empty($prior['prescriptions'])): ?>
											<div class="col-md-6 mb-3">
												<div class="intake-summary-label"><?= __('prescriptions_label') ?></div>
												<div><?= nl2br(htmlspecialchars($prior['prescriptions'])) ?></div>
											</div>
											<?php endif; ?>

											<?php if (!empty($prior['follow_up_notes'])): ?>
											<div class="col-md-6 mb-3">
												<div class="intake-summary-label"><?= __('follow_up_notes_label') ?></div>
												<div><?= nl2br(htmlspecialchars($prior['follow_up_notes'])) ?></div>
											</div>
											<?php endif; ?>

											<?php
											$_phConditions = [];
											foreach (['condition_dm'=>__('condition_dm'),'condition_htn'=>__('condition_htn'),'condition_tsh'=>__('condition_tsh'),'condition_heart_disease'=>__('condition_heart_disease')] as $_k=>$_l) {
												if (!empty($_phHist[$_k]) && $_phHist[$_k] !== 'NO') $_phConditions[] = $_l . ': ' . (__($_phHist[$_k] === 'CURRENT' ? 'cond_current' : 'cond_past'));
											}
											?>
											<?php if (!empty($_phConditions) || !empty($_phHist['surgical_history'])): ?>
											<div class="col-12 mb-3">
												<div class="intake-summary-label"><?= __('medical_conditions') ?></div>
												<div><?= implode(', ', array_map('htmlspecialchars', $_phConditions)) ?>
												<?php if (!empty($_phHist['surgical_history'])): ?>
												<div class="mt-1"><small class="text-muted"><?= __('surgical_label') ?>: </small><?= htmlspecialchars($_phHist['surgical_history']) ?></div>
												<?php endif; ?>
												</div>
											</div>
											<?php endif; ?>

											<?php if (!empty($_phLab)): ?>
											<div class="col-12 mb-3">
												<div class="intake-summary-label mb-1"><i class="fas fa-vial mr-1"></i><?= __('tab_labs') ?></div>
												<div class="row">
												<?php foreach (['lab_hb_gms'=>__('lab_hb').' (gms)','lab_hb_percentage'=>__('lab_hb').' (%)','lab_fbs'=>__('lab_fbs'),'lab_tsh'=>__('lab_tsh'),'lab_sr_creatinine'=>__('sr_creatinine')] as $_lk=>$_ll): ?>
												<?php if (!empty($_phLab[$_lk])): ?>
												<div class="col-6 col-md-2 mb-1"><small class="text-muted d-block"><?= $_ll ?></small><?= rv($_phLab[$_lk]) ?></div>
												<?php endif; ?>
												<?php endforeach; ?>
												</div>
											</div>
											<?php endif; ?>

											</div><!-- /row -->
										</div><!-- /card-body -->
									</div><!-- /collapse -->
									<?php endif; ?>
								</div><!-- /card -->
								<?php endforeach; ?>
								</div><!-- /accordion -->
								<?php endif; ?>

								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-patient"><i class="fas fa-chevron-left"></i> <?= __('tab_patient') ?></button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-general"><?= __('tab_general') ?> <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 4: GENERAL                                    -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-general" role="tabpanel">

								<?php if (!empty($prevCaseSheet)): ?>
								<!-- ── Vitals Comparison Panel ──────────────────── -->
								<div class="card shadow-sm mb-4">
									<div class="card-header bg-light py-2">
										<span class="font-weight-bold text-secondary" style="font-size:0.85rem;text-transform:uppercase;letter-spacing:0.05em;">
											<i class="fas fa-exchange-alt mr-2"></i><?= __('cmp_vitals_heading_short') ?>
										</span>
										<span class="text-muted ml-2 small">
											<?= __('cmp_previous_visit_label') ?> <?= date('M j, Y', strtotime($prevCaseSheet['visit_datetime'])) ?>
										</span>
										<span class="text-muted float-right small"><span style="color:#dc3545;">&#9632;</span> <?= __('cmp_threshold_legend') ?></span>
									</div>
									<div class="card-body p-0">
										<table class="table table-sm table-bordered mb-0 vitals-compare-table">
											<thead class="thead-light">
												<tr>
													<th style="width:160px;"><?= __('cmp_col_vital') ?></th>
													<th class="col-prev" style="width:160px;"><?= __('cmp_col_prev_visit') ?></th>
													<th style="width:160px;"><?= __('cmp_col_this_visit') ?></th>
												</tr>
											</thead>
											<tbody>
												<?php
												// [field, aliases, label, unit, threshold]
												$compareRows = [
													['general_bp_systolic', ['bp_systolic'],  __('cmp_bp_systolic'),  'mmHg', 20],
													['general_bp_diastolic',['bp_diastolic'], __('cmp_bp_diastolic'), 'mmHg', 10],
													['general_pulse',       ['pulse'],         __('pulse'),            '/mt',  20],
													['spo2',                [],                __('spo2'),             '%',    5],
													['temperature',         [],                __('temperature'),      '°F',   1],
													['general_height',      ['height_cm'],     __('height'),           'cm',   5],
													['general_weight',      ['weight_kg'],     __('weight'),           'kg',   5],
													['general_bmi',         [],                __('bmi'),              '',     3],
												];
												foreach ($compareRows as [$field, $aliases, $label, $unit, $threshold]):
													$cmp = vitalsCompare($prevVitals, $vitals, $field, $threshold, $aliases);
												?>
												<tr data-compare-field="<?= $field ?>"
												    data-compare-prev="<?= htmlspecialchars((string)($cmp['prev'] ?? '')) ?>"
												    data-compare-threshold="<?= $threshold ?>"
												    data-compare-unit="<?= htmlspecialchars($unit) ?>">
													<td class="text-secondary small font-weight-bold"><?= $label ?></td>
													<td class="vitals-compare-prev">
														<?php if ($cmp['prev'] !== null): ?>
															<?= htmlspecialchars($cmp['prev']) ?>
															<?php if ($unit): ?><small class="text-muted ml-1"><?= htmlspecialchars($unit) ?></small><?php endif; ?>
														<?php else: ?>
															<span class="vitals-compare-no-record"><?= __('cmp_no_prior_record') ?></span>
														<?php endif; ?>
													</td>
													<td class="compare-curr-td<?= $cmp['drastic'] ? ' vitals-compare-drastic' : '' ?>">
														<span class="compare-curr-val"><?= $cmp['curr'] !== null ? htmlspecialchars((string)$cmp['curr']) : '' ?></span>
														<?php if ($unit && $cmp['curr'] !== null): ?><small class="compare-curr-unit<?= (!$cmp['drastic']) ? ' text-muted' : '' ?> ml-1"><?= htmlspecialchars($unit) ?></small><?php endif; ?>
														<span class="compare-arrow"><?= $cmp['drastic'] ? ($cmp['direction'] === 'up' ? ' ↑' : ' ↓') : '' ?></span>
														<?php if ($cmp['curr'] === null): ?><span class="vitals-compare-no-record"><?= __('cmp_not_recorded') ?></span><?php endif; ?>
													</td>
												</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>

								<?php
								// Menstrual comparison — only show if patient has a uterus and prior menstrual data exists
								$_showMenstrual = ($vitals['has_uterus'] ?? '1') === '1';
								$_prevMenstrual = array_filter([
									'menstrual_cycle_frequency'  => $prevVitals['menstrual_cycle_frequency']  ?? null,
									'menstrual_duration_of_flow' => $prevVitals['menstrual_duration_of_flow'] ?? null,
									'menstrual_lmp'              => $prevVitals['menstrual_lmp']              ?? null,
									'menstrual_mh'               => $prevVitals['menstrual_mh']               ?? null,
								]);
								?>
								<?php if ($_showMenstrual && (!empty($_prevMenstrual) || !empty($vitals['menstrual_cycle_frequency']))): ?>
								<div class="card shadow-sm mb-4">
									<div class="card-header bg-light py-2">
										<span class="font-weight-bold text-secondary" style="font-size:0.85rem;text-transform:uppercase;letter-spacing:0.05em;">
											<i class="fas fa-exchange-alt mr-2"></i><?= __('cmp_menstrual_heading_short') ?>
										</span>
										<?php if (!empty($prevCaseSheet)): ?>
										<span class="text-muted ml-2 small"><?= __('cmp_previous_visit_label') ?> <?= date('M j, Y', strtotime($prevCaseSheet['visit_datetime'])) ?></span>
										<?php endif; ?>
									</div>
									<div class="card-body p-0">
										<table class="table table-sm table-bordered mb-0 vitals-compare-table">
											<thead class="thead-light">
												<tr>
													<th style="width:160px;"><?= __('cmp_col_field') ?></th>
													<th style="width:160px;"><?= __('cmp_col_prev_visit') ?></th>
													<th style="width:160px;"><?= __('cmp_col_this_visit') ?></th>
												</tr>
											</thead>
											<tbody>
											<?php
											$_menstrualRows = [
												['menstrual_cycle_frequency',  __('cycle_frequency'), 'days', 7],
												['menstrual_duration_of_flow', __('duration_of_flow'),'days', 3],
												['menstrual_lmp',              __('lmp'),             '',    0],
												['menstrual_mh',               __('mh'),              '',    0],
											];
													$_mhEnumMap = ['REGULAR' => __('regular'), 'IRREGULAR' => __('irregular')];
											foreach ($_menstrualRows as [$_mf, $_ml, $_mu, $_mt]):
												$_mcmp = vitalsCompare($prevVitals, $vitals, $_mf, $_mt > 0 ? $_mt : 99999);
											?>
											<tr data-compare-field="<?= $_mf ?>"
											    data-compare-prev="<?= htmlspecialchars((string)($_mcmp['prev'] ?? '')) ?>"
											    data-compare-threshold="<?= $_mt ?>"
											    data-compare-unit="<?= htmlspecialchars($_mu) ?>">
												<td class="text-secondary small font-weight-bold"><?= $_ml ?></td>
												<td class="vitals-compare-prev">
													<?php if ($_mcmp['prev'] !== null): ?>
														<?= htmlspecialchars($_mf === 'menstrual_mh' ? ($_mhEnumMap[$_mcmp['prev']] ?? $_mcmp['prev']) : $_mcmp['prev']) ?>
														<?php if ($_mu): ?><small class="text-muted ml-1"><?= $_mu ?></small><?php endif; ?>
													<?php else: ?>
														<span class="vitals-compare-no-record"><?= __('cmp_no_prior_record') ?></span>
													<?php endif; ?>
												</td>
												<td class="compare-curr-td<?= ($_mt > 0 && $_mcmp['drastic']) ? ' vitals-compare-drastic' : '' ?>">
													<span class="compare-curr-val"><?= $_mcmp['curr'] !== null ? htmlspecialchars($_mf === 'menstrual_mh' ? ($_mhEnumMap[$_mcmp['curr']] ?? (string)$_mcmp['curr']) : (string)$_mcmp['curr']) : '' ?></span>
													<?php if ($_mu && $_mcmp['curr'] !== null): ?><small class="compare-curr-unit text-muted ml-1"><?= $_mu ?></small><?php endif; ?>
													<?php if ($_mcmp['curr'] === null): ?><span class="vitals-compare-no-record"><?= __('cmp_not_recorded') ?></span><?php endif; ?>
												</td>
											</tr>
											<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>
								<?php endif; ?>

								<?php endif; ?>

								<!-- ── Editable Vital Signs ─────────────────────── -->
								<form class="doctor-auto-save">
									<div class="card card-outline card-danger mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-heartbeat mr-2"></i><?= __('vital_signs') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-3 mb-3">
													<label><?= __('pulse') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="general_pulse" data-field="general_pulse" min="0" max="300"
														       value="<?= htmlspecialchars($vitals['general_pulse'] ?? $vitals['pulse'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">/mt</span></div>
													</div>
												</div>
												<div class="col-md-4 mb-3">
													<label><?= __('bp') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="general_bp_systolic" data-field="general_bp_systolic" min="0" max="300" placeholder="Sys"
														       value="<?= htmlspecialchars($vitals['general_bp_systolic'] ?? $vitals['bp_systolic'] ?? '') ?>" />
														<div class="input-group-append input-group-prepend"><span class="input-group-text">/</span></div>
														<input type="number" class="form-control" name="general_bp_diastolic" data-field="general_bp_diastolic" min="0" max="200" placeholder="Dia"
														       value="<?= htmlspecialchars($vitals['general_bp_diastolic'] ?? $vitals['bp_diastolic'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">mmHg</span></div>
													</div>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('spo2') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="spo2" data-field="spo2" min="50" max="100"
														       value="<?= htmlspecialchars($vitals['spo2'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">%</span></div>
													</div>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('temperature') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="temperature" data-field="temperature" min="90" max="110" step="0.1"
														       value="<?= htmlspecialchars($vitals['temperature'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">&deg;F</span></div>
													</div>
												</div>
											</div>
										</div>
									</div>

									<!-- ── Physical Examination ───────────────────── -->
									<div class="card card-outline card-primary mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-user-md mr-2"></i><?= __('physical_examination') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-6 mb-3">
													<label><?= __('heart') ?></label>
													<input type="text" class="form-control" name="general_heart" data-field="general_heart"
													       value="<?= htmlspecialchars($vitals['general_heart'] ?? '') ?>" />
												</div>
												<div class="col-md-6 mb-3">
													<label><?= __('lungs') ?></label>
													<input type="text" class="form-control" name="general_lungs" data-field="general_lungs"
													       value="<?= htmlspecialchars($vitals['general_lungs'] ?? '') ?>" />
												</div>
											</div>
											<div class="row">
												<div class="col-md-6 mb-3">
													<label><?= __('liver') ?></label>
													<input type="text" class="form-control" name="general_liver" data-field="general_liver"
													       value="<?= htmlspecialchars($vitals['general_liver'] ?? '') ?>" />
												</div>
												<div class="col-md-6 mb-3">
													<label><?= __('spleen') ?></label>
													<input type="text" class="form-control" name="general_spleen" data-field="general_spleen"
													       value="<?= htmlspecialchars($vitals['general_spleen'] ?? '') ?>" />
												</div>
											</div>
											<div class="row">
												<div class="col-md-6 mb-0">
													<label><?= __('lymph_glands') ?></label>
													<input type="text" class="form-control" name="general_lymph_glands" data-field="general_lymph_glands"
													       value="<?= htmlspecialchars($vitals['general_lymph_glands'] ?? '') ?>" />
												</div>
											</div>
										</div>
									</div>

									<!-- ── Anthropometric Measurements ────────────── -->
									<div class="card card-outline card-success mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-weight mr-2"></i><?= __('anthropometric_measurements') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-3 mb-3">
													<label><?= __('height') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="general_height" data-field="general_height" min="0" max="300"
														       value="<?= htmlspecialchars($vitals['general_height'] ?? $vitals['height_cm'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">cm</span></div>
													</div>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('weight') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="general_weight" data-field="general_weight" min="0" max="500" step="0.1"
														       value="<?= htmlspecialchars($vitals['general_weight'] ?? $vitals['weight_kg'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">kg</span></div>
													</div>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('bmi') ?></label>
													<input type="number" class="form-control" name="general_bmi" data-field="general_bmi" min="0" max="100" step="0.1"
													       value="<?= htmlspecialchars($vitals['general_bmi'] ?? '') ?>" />
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('obesity_overweight') ?></label>
													<select class="form-control" name="general_obesity_overweight" data-field="general_obesity_overweight">
														<option value="0" <?= ($vitals['general_obesity_overweight'] ?? '0') == '0' ? 'selected' : '' ?>><?= __('no') ?></option>
														<option value="1" <?= ($vitals['general_obesity_overweight'] ?? '0') == '1' ? 'selected' : '' ?>><?= __('yes') ?></option>
													</select>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('has_uterus_label') ?></label>
													<select class="form-control" name="has_uterus" id="rv_has_uterus" data-field="has_uterus">
														<option value="1" <?= ($vitals['has_uterus'] ?? '1') == '1' ? 'selected' : '' ?>><?= __('yes') ?></option>
														<option value="0" <?= ($vitals['has_uterus'] ?? '1') == '0' ? 'selected' : '' ?>><?= __('no') ?></option>
													</select>
												</div>
											</div>
										</div>
									</div>


									<!-- ── Menstrual Details ───────────────────────── -->
									<div id="rvMenstrualSection" style="display:none;">
									<div class="card card-outline card-info mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-calendar-alt mr-2"></i><?= __('menstrual_details') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-3 mb-3"><label><?= __('age_of_onset') ?></label><div class="input-group"><input type="number" class="form-control" name="menstrual_age_of_onset" data-field="menstrual_age_of_onset" min="0" max="30" value="<?= htmlspecialchars($vitals['menstrual_age_of_onset'] ?? '') ?>" /><div class="input-group-append"><span class="input-group-text">yrs</span></div></div></div>
												<div class="col-md-3 mb-3"><label><?= __('cycle_frequency') ?></label><div class="input-group"><input type="number" class="form-control" name="menstrual_cycle_frequency" data-field="menstrual_cycle_frequency" min="0" max="90" value="<?= htmlspecialchars($vitals['menstrual_cycle_frequency'] ?? '') ?>" /><div class="input-group-append"><span class="input-group-text">days</span></div></div></div>
												<div class="col-md-3 mb-3"><label><?= __('duration_of_flow') ?></label><div class="input-group"><input type="number" class="form-control" name="menstrual_duration_of_flow" data-field="menstrual_duration_of_flow" min="0" max="30" value="<?= htmlspecialchars($vitals['menstrual_duration_of_flow'] ?? '') ?>" /><div class="input-group-append"><span class="input-group-text">days</span></div></div></div>
												<div class="col-md-3 mb-3"><label><?= __('lmp') ?></label><input type="date" class="form-control" name="menstrual_lmp" data-field="menstrual_lmp" value="<?= htmlspecialchars($vitals['menstrual_lmp'] ?? '') ?>" /></div>
											</div>
											<div class="row">
												<div class="col-md-3 mb-0"><label><?= __('mh') ?></label><select class="form-control" name="menstrual_mh" data-field="menstrual_mh"><option value=""><?= __('select_option') ?></option><option value="REGULAR" <?= ($vitals['menstrual_mh'] ?? '') === 'REGULAR' ? 'selected' : '' ?>><?= __('regular') ?></option><option value="IRREGULAR" <?= ($vitals['menstrual_mh'] ?? '') === 'IRREGULAR' ? 'selected' : '' ?>><?= __('irregular') ?></option></select></div>
											</div>
										</div>
									</div>
									</div>

									<small class="text-muted"><?= __('auto_saved_note') ?></small>
								</form>

								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-medical-history"><i class="fas fa-chevron-left"></i> <?= __('tab_history') ?></button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-examinations"><?= __('tab_examinations') ?> <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 5: EXAMINATIONS                               -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-examinations" role="tabpanel">
								<h4 class="mb-4"><?= __('tab_examinations') ?></h4>

								<form class="doctor-auto-save">

									<!-- ── Head &amp; Neck ───────────────────────────── -->
									<div class="card card-outline card-secondary mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-head-side-cough mr-2"></i><?= __('head_and_neck') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-4 mb-3"><label><?= __('mouth') ?></label><input type="text" class="form-control" name="exam_mouth" data-field="exam_mouth" value="<?= htmlspecialchars($examData['exam_mouth'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label><?= __('lips') ?></label><input type="text" class="form-control" name="exam_lips" data-field="exam_lips" value="<?= htmlspecialchars($examData['exam_lips'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label><?= __('buccal_mucosa') ?></label><input type="text" class="form-control" name="exam_buccal_mucosa" data-field="exam_buccal_mucosa" value="<?= htmlspecialchars($examData['exam_buccal_mucosa'] ?? '') ?>" /></div>
											</div>
											<div class="row">
												<div class="col-md-4 mb-3"><label><?= __('teeth') ?></label><input type="text" class="form-control" name="exam_teeth" data-field="exam_teeth" value="<?= htmlspecialchars($examData['exam_teeth'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label><?= __('tongue') ?></label><input type="text" class="form-control" name="exam_tongue" data-field="exam_tongue" value="<?= htmlspecialchars($examData['exam_tongue'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label><?= __('oropharynx') ?></label><input type="text" class="form-control" name="exam_oropharynx" data-field="exam_oropharynx" value="<?= htmlspecialchars($examData['exam_oropharynx'] ?? '') ?>" /></div>
											</div>
											<div class="row">
												<div class="col-md-4 mb-3"><label><?= __('hypopharynx') ?></label><input type="text" class="form-control" name="exam_hypo" data-field="exam_hypo" value="<?= htmlspecialchars($examData['exam_hypo'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label><?= __('naso_pharynx') ?></label><input type="text" class="form-control" name="exam_naso_pharynx" data-field="exam_naso_pharynx" value="<?= htmlspecialchars($examData['exam_naso_pharynx'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label><?= __('larynx') ?></label><input type="text" class="form-control" name="exam_larynx" data-field="exam_larynx" value="<?= htmlspecialchars($examData['exam_larynx'] ?? '') ?>" /></div>
											</div>
											<div class="row">
												<div class="col-md-4 mb-3"><label><?= __('nose') ?></label><input type="text" class="form-control" name="exam_nose" data-field="exam_nose" value="<?= htmlspecialchars($examData['exam_nose'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label><?= __('ears') ?></label><input type="text" class="form-control" name="exam_ears" data-field="exam_ears" value="<?= htmlspecialchars($examData['exam_ears'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label><?= __('neck') ?></label><input type="text" class="form-control" name="exam_neck" data-field="exam_neck" value="<?= htmlspecialchars($examData['exam_neck'] ?? '') ?>" /></div>
											</div>
											<div class="row">
												<div class="col-md-6 mb-3"><label><?= __('bones_joints') ?></label><input type="text" class="form-control" name="exam_bones_joints" data-field="exam_bones_joints" value="<?= htmlspecialchars($examData['exam_bones_joints'] ?? '') ?>" /></div>
												<div class="col-md-6 mb-0"><label><?= __('abdomen_genital') ?></label><input type="text" class="form-control" name="exam_abdomen_genital" data-field="exam_abdomen_genital" value="<?= htmlspecialchars($examData['exam_abdomen_genital'] ?? '') ?>" /></div>
											</div>
										</div>
									</div>

									<!-- ── Breast Examination ────────────────────── -->
									<div class="card card-outline card-warning mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-search mr-2"></i><?= __('breasts') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-4 mb-3"><label><?= __('left_breast') ?></label><input type="text" class="form-control" name="exam_breast_left" data-field="exam_breast_left" value="<?= htmlspecialchars($examData['exam_breast_left'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label><?= __('right_breast') ?></label><input type="text" class="form-control" name="exam_breast_right" data-field="exam_breast_right" value="<?= htmlspecialchars($examData['exam_breast_right'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label><?= __('axillary_nodes') ?></label><input type="text" class="form-control" name="exam_breast_axillary_nodes" data-field="exam_breast_axillary_nodes" value="<?= htmlspecialchars($examData['exam_breast_axillary_nodes'] ?? '') ?>" /></div>
											</div>
											<div class="row">
												<div class="col-12">
													<label class="d-block"><?= __('breast_diagram') ?></label>
													<button type="button" class="btn btn-outline-primary btn-sm" onclick="openDiagram('breast','diag_breast','breastDiagramPreview')">
														<i class="fas fa-draw-polygon mr-1"></i><?= !empty($cs['diag_breast']) ? __('edit') : __('draw') ?> <?= __('breast_diagram') ?>
													</button>
													<div id="breastDiagramPreview" class="mt-2<?= empty($cs['diag_breast']) ? ' d-none' : '' ?>">
														<img src="" data-diag-field="diag_breast" data-diag-type="breast" alt="Breast Examination Diagram" class="img-thumbnail diagram-preview-img">
													</div>
													<input type="hidden" id="diag_breast" value="<?= htmlspecialchars($cs['diag_breast'] ?? '') ?>">
												</div>
											</div>
										</div>
									</div>

									<!-- ── Pelvic Examination ────────────────────── -->
									<div class="card card-outline card-info mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-female mr-2"></i><?= __('pelvic_examination_label') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-3 mb-3"><label><?= __('cervix') ?></label><input type="text" class="form-control" name="exam_pelvic_cervix" data-field="exam_pelvic_cervix" value="<?= htmlspecialchars($examData['exam_pelvic_cervix'] ?? '') ?>" /></div>
												<div class="col-md-3 mb-3"><label><?= __('uterus') ?></label><input type="text" class="form-control" name="exam_pelvic_uterus" data-field="exam_pelvic_uterus" value="<?= htmlspecialchars($examData['exam_pelvic_uterus'] ?? '') ?>" /></div>
												<div class="col-md-3 mb-3"><label><?= __('ovaries') ?></label><input type="text" class="form-control" name="exam_pelvic_ovaries" data-field="exam_pelvic_ovaries" value="<?= htmlspecialchars($examData['exam_pelvic_ovaries'] ?? '') ?>" /></div>
												<div class="col-md-3 mb-3"><label><?= __('adnexa') ?></label><input type="text" class="form-control" name="exam_pelvic_adnexa" data-field="exam_pelvic_adnexa" value="<?= htmlspecialchars($examData['exam_pelvic_adnexa'] ?? '') ?>" /></div>
											</div>
											<div class="row">
												<div class="col-12">
													<label class="d-block"><?= __('pelvic_diagram') ?></label>
													<button type="button" class="btn btn-outline-primary btn-sm" onclick="openDiagram('pelvic','diag_pelvic','pelvicDiagramPreview')">
														<i class="fas fa-draw-polygon mr-1"></i><?= !empty($cs['diag_pelvic']) ? __('edit') : __('draw') ?> <?= __('pelvic_diagram') ?>
													</button>
													<div id="pelvicDiagramPreview" class="mt-2<?= empty($cs['diag_pelvic']) ? ' d-none' : '' ?>">
														<img src="" data-diag-field="diag_pelvic" data-diag-type="pelvic" alt="Pelvic Examination Diagram" class="img-thumbnail diagram-preview-img">
													</div>
													<input type="hidden" id="diag_pelvic" value="<?= htmlspecialchars($cs['diag_pelvic'] ?? '') ?>">
												</div>
											</div>
										</div>
									</div>

									<!-- ── Rectal Examination ────────────────────── -->
									<div class="card card-outline card-secondary mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-notes-medical mr-2"></i><?= __('rectal_examination') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-6 mb-3"><label><?= __('rectal_skin') ?></label><input type="text" class="form-control" name="exam_rectal_skin" data-field="exam_rectal_skin" value="<?= htmlspecialchars($examData['exam_rectal_skin'] ?? '') ?>" /></div>
												<div class="col-md-6 mb-0"><label><?= __('rectal_remarks') ?></label><input type="text" class="form-control" name="exam_rectal_remarks" data-field="exam_rectal_remarks" value="<?= htmlspecialchars($examData['exam_rectal_remarks'] ?? '') ?>" /></div>
											</div>
										</div>
									</div>

									<!-- ── Gynaecological Examination ─────────────── -->
									<div class="card card-outline card-primary mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-diagnoses mr-2"></i><?= __('gynae_examination') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-6 mb-3"><label><?= __('gynae_ps') ?></label><input type="text" class="form-control" name="exam_gynae_ps" data-field="exam_gynae_ps" value="<?= htmlspecialchars($examData['exam_gynae_ps'] ?? '') ?>" /></div>
												<div class="col-md-6 mb-3"><label><?= __('gynae_pv') ?></label><input type="text" class="form-control" name="exam_gynae_pv" data-field="exam_gynae_pv" value="<?= htmlspecialchars($examData['exam_gynae_pv'] ?? '') ?>" /></div>
											</div>
											<div class="row">
												<div class="col-md-6 mb-3">
													<label><?= __('gynae_via') ?></label>
													<input type="text" class="form-control mb-2" name="exam_gynae_via" data-field="exam_gynae_via" value="<?= htmlspecialchars($examData['exam_gynae_via'] ?? '') ?>" />
													<button type="button" class="btn btn-outline-primary btn-sm" onclick="openDiagram('via','diag_via','viaDiagramPreview')">
														<i class="fas fa-draw-polygon mr-1"></i><?= !empty($cs['diag_via']) ? __('edit') : __('draw') ?> <?= __('via_diagram') ?>
													</button>
													<div id="viaDiagramPreview" class="mt-2<?= empty($cs['diag_via']) ? ' d-none' : '' ?>">
														<img src="" data-diag-field="diag_via" data-diag-type="via" alt="VIA Diagram" class="img-thumbnail diagram-preview-img">
													</div>
													<input type="hidden" id="diag_via" value="<?= htmlspecialchars($cs['diag_via'] ?? '') ?>">
												</div>
												<div class="col-md-6 mb-3">
													<label><?= __('gynae_vili') ?></label>
													<input type="text" class="form-control mb-2" name="exam_gynae_vili" data-field="exam_gynae_vili" value="<?= htmlspecialchars($examData['exam_gynae_vili'] ?? '') ?>" />
													<button type="button" class="btn btn-outline-primary btn-sm" onclick="openDiagram('vili','diag_vili','viliDiagramPreview')">
														<i class="fas fa-draw-polygon mr-1"></i><?= !empty($cs['diag_vili']) ? __('edit') : __('draw') ?> <?= __('vili_diagram') ?>
													</button>
													<div id="viliDiagramPreview" class="mt-2<?= empty($cs['diag_vili']) ? ' d-none' : '' ?>">
														<img src="" data-diag-field="diag_vili" data-diag-type="vili" alt="VILI Diagram" class="img-thumbnail diagram-preview-img">
													</div>
													<input type="hidden" id="diag_vili" value="<?= htmlspecialchars($cs['diag_vili'] ?? '') ?>">
												</div>
											</div>
										</div>
									</div>

									<small class="text-muted"><?= __('auto_saved_note') ?></small>
								</form>

								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-general"><i class="fas fa-chevron-left"></i> <?= __('tab_general') ?></button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-labs"><?= __('tab_labs') ?> <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 6: LABS                                       -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-labs" role="tabpanel">
								<h4 class="mb-4"><?= __('tab_labs') ?></h4>

								<?php if (!empty($prevCaseSheet)): ?>
								<!-- ── Lab Results Comparison Panel ────────────────── -->
								<div class="card shadow-sm mb-4">
									<div class="card-header bg-light py-2">
										<span class="font-weight-bold text-secondary" style="font-size:0.85rem;text-transform:uppercase;letter-spacing:0.05em;">
											<i class="fas fa-exchange-alt mr-2"></i><?= __('cmp_lab_heading') ?>
										</span>
										<span class="text-muted ml-2 small">
											<?= __('cmp_previous_visit_label') ?> <?= date('M j, Y', strtotime($prevCaseSheet['visit_datetime'])) ?>
										</span>
										<span class="text-muted float-right small"><span style="color:#dc3545;">&#9632;</span> <?= __('cmp_threshold_legend') ?></span>
									</div>
									<div class="card-body p-0">
										<table class="table table-sm table-bordered mb-0 vitals-compare-table">
											<thead class="thead-light">
												<tr>
													<th style="width:180px;"><?= __('cmp_col_investigation') ?></th>
													<th class="col-prev" style="width:160px;"><?= __('cmp_col_prev_visit') ?></th>
													<th style="width:160px;"><?= __('cmp_col_this_visit') ?></th>
												</tr>
											</thead>
											<tbody>
												<?php
												$labCompareRows = [
													['lab_hb_gms',       'Hb',          'gms', 2],
													['lab_hb_percentage','Hb',          '%',   5],
													['lab_fbs',          'FBS',         'mg/dl', 30],
													['lab_tsh',          'TSH',         'mIU/L', 2],
													['lab_sr_creatinine','Sr. Creatinine','mg/dL', 0.3],
												];
												foreach ($labCompareRows as [$field, $label, $unit, $threshold]):
													$cmp = vitalsCompare($prevLabData, $labData, $field, $threshold);
												?>
												<tr data-compare-field="<?= $field ?>"
												    data-compare-prev="<?= htmlspecialchars((string)($cmp['prev'] ?? '')) ?>"
												    data-compare-threshold="<?= $threshold ?>"
												    data-compare-unit="<?= htmlspecialchars($unit) ?>">
													<td class="text-secondary small font-weight-bold">
														<?= $label ?> <small class="text-muted font-weight-normal">(<?= $unit ?>)</small>
													</td>
													<td class="vitals-compare-prev">
														<?php if ($cmp['prev'] !== null): ?>
															<?= htmlspecialchars($cmp['prev']) ?> <small class="text-muted"><?= htmlspecialchars($unit) ?></small>
														<?php else: ?>
															<span class="vitals-compare-no-record"><?= __('cmp_no_prior_record') ?></span>
														<?php endif; ?>
													</td>
													<td class="compare-curr-td<?= $cmp['drastic'] ? ' vitals-compare-drastic' : '' ?>">
														<span class="compare-curr-val"><?= $cmp['curr'] !== null ? htmlspecialchars((string)$cmp['curr']) : '' ?></span>
														<?php if ($unit && $cmp['curr'] !== null): ?><small class="compare-curr-unit<?= !$cmp['drastic'] ? ' text-muted' : '' ?> ml-1"><?= htmlspecialchars($unit) ?></small><?php endif; ?>
														<span class="compare-arrow"><?= $cmp['drastic'] ? ($cmp['direction'] === 'up' ? ' ↑' : ' ↓') : '' ?></span>
														<?php if ($cmp['curr'] === null): ?><span class="vitals-compare-no-record"><?= __('cmp_not_recorded') ?></span><?php endif; ?>
													</td>
												</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>
								<?php endif; ?>

								<form class="doctor-auto-save">

									<!-- ── Investigations ──────────────────────────────── -->
									<div class="card card-outline card-success mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-vial mr-2"></i><?= __('investigations') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-3 mb-3">
													<label><?= __('lab_hb') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="lab_hb_percentage" data-field="lab_hb_percentage" min="0" max="100" value="<?= htmlspecialchars($labData['lab_hb_percentage'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">%</span></div>
														<input type="number" class="form-control" name="lab_hb_gms" data-field="lab_hb_gms" min="0" max="30" step="0.1" value="<?= htmlspecialchars($labData['lab_hb_gms'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">gms</span></div>
													</div>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('lab_fbs') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="lab_fbs" data-field="lab_fbs" min="0" max="1000" step="0.1" value="<?= htmlspecialchars($labData['lab_fbs'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">mg/dl</span></div>
													</div>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('lab_tsh') ?></label>
													<input type="number" class="form-control" name="lab_tsh" data-field="lab_tsh" min="0" max="100" step="0.01" value="<?= htmlspecialchars($labData['lab_tsh'] ?? '') ?>" />
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('sr_creatinine') ?></label>
													<input type="number" class="form-control" name="lab_sr_creatinine" data-field="lab_sr_creatinine" min="0" max="20" step="0.01" value="<?= htmlspecialchars($labData['lab_sr_creatinine'] ?? '') ?>" />
												</div>
											</div>
											<div class="form-group mb-0">
												<label><?= __('others') ?></label>
												<textarea class="form-control" name="lab_others" data-field="lab_others" rows="2"><?= htmlspecialchars($labData['lab_others'] ?? '') ?></textarea>
											</div>
										</div>
									</div>

									<!-- ── Cytology Report ─────────────────────────── -->
									<div class="card card-outline card-info mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-microscope mr-2"></i><?= __('cytology_report') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-6 mb-3">
													<label><?= __('papsmear') ?></label>
													<select class="form-control" name="cytology_papsmear" data-field="cytology_papsmear">
														<option value="NONE"    <?= ($labData['cytology_papsmear'] ?? 'NONE') === 'NONE'    ? 'selected' : '' ?>><?= __('none') ?></option>
														<option value="DONE"    <?= ($labData['cytology_papsmear'] ?? '') === 'DONE'    ? 'selected' : '' ?>><?= __('done') ?></option>
														<option value="ADVISED" <?= ($labData['cytology_papsmear'] ?? '') === 'ADVISED' ? 'selected' : '' ?>><?= __('advised') ?></option>
													</select>
												</div>
												<div class="col-md-6 mb-3">
													<label><?= __('papsmear_notes') ?></label>
													<textarea class="form-control" name="cytology_papsmear_notes" data-field="cytology_papsmear_notes" rows="2"><?= htmlspecialchars($labData['cytology_papsmear_notes'] ?? '') ?></textarea>
												</div>
											</div>
											<div class="row">
												<div class="col-md-6 mb-3">
													<label><?= __('colposcopy') ?></label>
													<select class="form-control" name="cytology_colposcopy" data-field="cytology_colposcopy">
														<option value="NONE"    <?= ($labData['cytology_colposcopy'] ?? 'NONE') === 'NONE'    ? 'selected' : '' ?>><?= __('none') ?></option>
														<option value="DONE"    <?= ($labData['cytology_colposcopy'] ?? '') === 'DONE'    ? 'selected' : '' ?>><?= __('done') ?></option>
														<option value="ADVISED" <?= ($labData['cytology_colposcopy'] ?? '') === 'ADVISED' ? 'selected' : '' ?>><?= __('advised') ?></option>
													</select>
												</div>
												<div class="col-md-6 mb-3">
													<label><?= __('colposcopy_notes') ?></label>
													<textarea class="form-control" name="cytology_colposcopy_notes" data-field="cytology_colposcopy_notes" rows="2"><?= htmlspecialchars($labData['cytology_colposcopy_notes'] ?? '') ?></textarea>
												</div>
											</div>
											<div class="row">
												<div class="col-md-6 mb-3">
													<label><?= __('biopsy') ?></label>
													<select class="form-control" name="cytology_biopsy" data-field="cytology_biopsy">
														<option value="NONE"    <?= ($labData['cytology_biopsy'] ?? 'NONE') === 'NONE'    ? 'selected' : '' ?>><?= __('none') ?></option>
														<option value="DONE"    <?= ($labData['cytology_biopsy'] ?? '') === 'DONE'    ? 'selected' : '' ?>><?= __('done') ?></option>
														<option value="ADVISED" <?= ($labData['cytology_biopsy'] ?? '') === 'ADVISED' ? 'selected' : '' ?>><?= __('advised') ?></option>
													</select>
												</div>
												<div class="col-md-6 mb-3">
													<label><?= __('biopsy_notes') ?></label>
													<textarea class="form-control" name="cytology_biopsy_notes" data-field="cytology_biopsy_notes" rows="2"><?= htmlspecialchars($labData['cytology_biopsy_notes'] ?? '') ?></textarea>
												</div>
											</div>
										</div>
									</div>

									<small class="text-muted"><?= __('auto_saved_note') ?></small>
								</form>

								<!-- ── Lab Orders ────────────────────────────────── -->
								<div class="card card-outline card-primary mt-3 mb-3">
									<div class="card-header d-flex justify-content-between align-items-center">
										<h5 class="card-title mb-0"><i class="fas fa-flask mr-2"></i><?= __('lab_orders') ?></h5>
										<?php if (can($_SESSION['user_role'] ?? '', 'labwork', 'W')): ?>
										<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#labOrderModal">
											<i class="fas fa-plus mr-1"></i><?= __('order_lab_test') ?>
										</button>
										<?php endif; ?>
									</div>
									<div class="card-body p-0">
										<div class="text-center text-muted py-4" id="rvNoLabOrders" style="display:none;">
											<i class="fas fa-flask fa-2x mb-2 d-block" style="opacity:.4"></i><?= __('no_lab_orders') ?>
										</div>
										<table class="table table-sm mb-0" id="rvLabOrdersTable" style="display:none;">
											<thead class="thead-light">
												<tr>
													<th><?= __('col_test') ?></th>
													<th><?= __('col_notes') ?></th>
													<th><?= __('col_ordered_by') ?></th>
													<th><?= __('col_status') ?></th>
													<th><?= __('col_ordered') ?></th>
												</tr>
											</thead>
											<tbody id="rvLabOrdersBody"></tbody>
										</table>
									</div>
								</div>

								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-examinations"><i class="fas fa-chevron-left"></i> <?= __('tab_examinations') ?></button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-assessment"><?= __('tab_assessment') ?> <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 7: ASSESSMENT & DIAGNOSIS (doctor)            -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-assessment" role="tabpanel">
								<div class="card card-outline card-warning mb-3">
									<div class="card-header">
										<h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i><?= __('tab_assessment') ?></h3>
									</div>
									<div class="card-body">
										<p class="text-muted mb-3"><?= __('assessment_diagnosis_subtitle') ?></p>
										<form class="doctor-auto-save">
											<div class="form-group">
												<label for="doctor_assessment"><?= __('clinical_assessment') ?></label>
												<textarea class="form-control" id="doctor_assessment" name="doctor_assessment"
												          data-field="doctor_assessment" rows="6"
												          placeholder="Your clinical impression and assessment of the patient's condition..."
												><?= htmlspecialchars($cs['doctor_assessment'] ?? '') ?></textarea>
											</div>
											<div class="form-group">
												<label for="doctor_diagnosis"><?= __('diagnosis_label') ?></label>
												<textarea class="form-control" id="doctor_diagnosis" name="doctor_diagnosis"
												          data-field="doctor_diagnosis" rows="6"
												          placeholder="Formal diagnosis (primary and secondary diagnoses, ICD codes if applicable)..."
												><?= htmlspecialchars($cs['doctor_diagnosis'] ?? '') ?></textarea>
											</div>
											<small class="text-muted"><?= __('auto_saved_note') ?></small>
										</form>
									</div>
								</div>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-labs"><i class="fas fa-chevron-left"></i> <?= __('tab_labs') ?></button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-plan"><?= __('tab_plan') ?> <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 8: TREATMENT PLAN (doctor)                    -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-plan" role="tabpanel">
								<div class="card card-outline card-success mb-3">
									<div class="card-header">
										<h3 class="card-title"><i class="fas fa-prescription-bottle mr-2"></i><?= __('tab_plan') ?></h3>
									</div>
									<div class="card-body">
										<p class="text-muted mb-3"><?= __('treatment_plan_subtitle') ?></p>
										<form class="doctor-auto-save">
											<div class="form-group">
												<label for="doctor_plan_notes"><?= __('treatment_plan_label') ?> / Clinical Notes</label>
												<textarea class="form-control" id="doctor_plan_notes" name="doctor_plan_notes"
												          data-field="doctor_plan_notes" rows="5"
												          placeholder="Overall treatment plan and any additional clinical notes..."
												><?= htmlspecialchars($cs['doctor_plan_notes'] ?? '') ?></textarea>
											</div>
											<div class="form-group">
												<label for="prescriptions"><?= __('prescriptions_label') ?></label>
												<textarea class="form-control" id="prescriptions" name="prescriptions"
												          data-field="prescriptions" rows="5"
												          placeholder="List medications, dosage, frequency, and duration..."
												><?= htmlspecialchars($cs['prescriptions'] ?? '') ?></textarea>
											</div>
											<div class="form-group">
												<label for="advice"><?= __("advice_instructions") ?></label>
												<textarea class="form-control" id="advice" name="advice"
												          data-field="advice" rows="4"
												          placeholder="Dietary advice, lifestyle changes, warning signs to watch for..."
												><?= htmlspecialchars($cs['advice'] ?? '') ?></textarea>
											</div>
											<small class="text-muted"><?= __('auto_saved_note') ?></small>
										</form>
									</div>
								</div>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-assessment"><i class="fas fa-chevron-left"></i> <?= __('tab_assessment') ?></button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-followup"><?= __('tab_followup') ?> <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 9: FOLLOW-UP & REFERRALS + CLOSE CHART        -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-followup" role="tabpanel">
								<div class="card card-outline card-info mb-4">
									<div class="card-header">
										<h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i><?= __('tab_followup') ?></h3>
									</div>
									<div class="card-body">
										<p class="text-muted mb-3"><?= __('followup_subtitle') ?></p>
										<form class="doctor-auto-save">
											<h5 class="mb-3"><?= __('visit_follow_up') ?></h5>
											<div class="row">
												<div class="col-md-4 mb-3">
													<label for="follow_up_date"><?= __('follow_up_date_label') ?></label>
													<input type="date" class="form-control" id="follow_up_date" name="follow_up_date"
													       data-field="follow_up_date"
													       value="<?= htmlspecialchars($cs['follow_up_date'] ?? '') ?>" />
												</div>
												<div class="col-md-8 mb-3">
													<label for="follow_up_notes"><?= __('follow_up_instructions_label') ?></label>
													<textarea class="form-control" id="follow_up_notes" name="follow_up_notes"
													          data-field="follow_up_notes" rows="3"
													          placeholder="Who to follow up with, what to monitor, what to bring to next appointment..."
													><?= htmlspecialchars($cs['follow_up_notes'] ?? '') ?></textarea>
												</div>
											</div>

											<h5 class="mb-3"><?= __('referrals_heading') ?></h5>
											<div class="row">
												<div class="col-md-4 mb-3">
													<label for="referral_to"><?= __('referral_to_label') ?></label>
													<input type="text" class="form-control" id="referral_to" name="referral_to"
													       data-field="referral_to"
													       value="<?= htmlspecialchars($cs['referral_to'] ?? '') ?>"
													       placeholder="Doctor name, specialty, or facility..." />
												</div>
												<div class="col-md-8 mb-3">
													<label for="referral_reason"><?= __('referral_reason_label') ?></label>
													<textarea class="form-control" id="referral_reason" name="referral_reason"
													          data-field="referral_reason" rows="3"
													          placeholder="Reason for referral and any relevant clinical information to include..."
													><?= htmlspecialchars($cs['referral_reason'] ?? '') ?></textarea>
												</div>
											</div>

											<h5 class="mb-3"><?= __('hpi_heading') ?></h5>
											<div class="mb-3">
												<label for="history_present_illness"><?= __('hpi_narrative_label') ?></label>
												<textarea class="form-control" id="history_present_illness" name="history_present_illness"
												          data-field="history_present_illness" rows="4"
												          placeholder="Narrative history of the present illness as obtained during consultation..."
												><?= htmlspecialchars($cs['history_present_illness'] ?? '') ?></textarea>
											</div>

											<div class="row">
												<div class="col-md-6 mb-3">
													<label for="disposition"><?= __('disposition_label') ?></label>
													<input type="text" class="form-control" id="disposition" name="disposition"
													       data-field="disposition"
													       value="<?= htmlspecialchars($cs['disposition'] ?? '') ?>"
													       placeholder="e.g. Discharged home, Admitted, Transfer..." />
												</div>
											</div>
											<small class="text-muted"><?= __('auto_saved_note') ?></small>
										</form>
									</div>
								</div>

								<!-- ─── Close Chart ─────────────────────────────── -->
								<div class="card card-outline card-danger mt-5">
									<div class="card-header">
										<h3 class="card-title"><i class="fas fa-folder-minus mr-2"></i><?= __('close_chart') ?></h3>
									</div>
									<div class="card-body">
										<p class="text-muted"><?= __('close_chart_warning') ?></p>
										<form method="post" action="review.php?action=close" id="closeChartForm">
											<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
											<input type="hidden" name="case_sheet_id" value="<?= $csId ?>" />
											<div class="row align-items-end">
												<div class="col-md-4 mb-3">
													<label for="closure_type"><?= __('closure_type_label') ?> <span class="text-danger">*</span></label>
													<select class="form-control" id="closure_type" name="closure_type" required>
														<option value=""><?= __('select_placeholder') ?></option>
														<option value="DISCHARGED" <?= ($cs['closure_type'] ?? '') === 'DISCHARGED' ? 'selected' : '' ?>><?= __('closure_discharged') ?></option>
														<option value="FOLLOW_UP"  <?= ($cs['closure_type'] ?? '') === 'FOLLOW_UP'  ? 'selected' : '' ?>><?= __('closure_follow_up_scheduled') ?></option>
														<option value="REFERRAL"   <?= ($cs['closure_type'] ?? '') === 'REFERRAL'   ? 'selected' : '' ?>><?= __('closure_referred') ?></option>
														<option value="PENDING"    <?= ($cs['closure_type'] ?? '') === 'PENDING'    ? 'selected' : '' ?>><?= __('closure_pending') ?></option>
													</select>
												</div>
												<div class="col-md-8 mb-3">
													<button type="submit" class="btn btn-danger btn-lg" id="closeChartBtn">
														<i class="fas fa-folder-minus mr-1"></i><?= __('close_chart') ?>
													</button>
													<small class="d-block text-muted mt-1">
														<i class="fas fa-lock mr-1"></i><?= __("close_chart_locked_note") ?> <?= htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) ?> <?= __("close_chart_at_submission") ?>
													</small>
												</div>
											</div>
										</form>
									</div>
								</div>

								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-plan"><i class="fas fa-chevron-left"></i> <?= __('tab_plan') ?></button>
									<button type="button" class="btn btn-outline-secondary btn-next-tab" data-target="#tab-audit"><?= __('view_audit_history') ?> <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 10: AUDIT HISTORY                             -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-audit" role="tabpanel">
								<h4 class="mb-1"><?= __('tab_audit') ?></h4>
								<p class="text-muted mb-4"><?= __('audit_subtitle') ?></p>
								<?php if (empty($auditLog)): ?>
								<div class="alert alert-light border"><?= __('audit_no_changes') ?></div>
								<?php else: ?>
								<div class="table-responsive">
									<table class="table table-sm table-hover table-bordered mb-0">
										<thead class="thead-light">
											<tr>
												<th style="width:160px"><?= __('col_when') ?></th>
												<th style="width:140px"><?= __('col_by') ?></th>
												<th style="width:160px"><?= __('col_field') ?></th>
												<th><?= __('col_previous_value') ?></th>
												<th><?= __('col_new_value') ?></th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($auditLog as $entry): ?>
											<tr class="<?= ($entry['old_value'] !== $entry['new_value'] && $entry['old_value'] !== null) ? 'audit-row-changed' : '' ?>">
												<td class="text-nowrap small"><?= date('M j, Y g:i:s A', strtotime($entry['changed_at'])) ?></td>
												<td class="small"><?= htmlspecialchars(!empty($entry['changed_by_name']) ? $entry['changed_by_name'] : trim(($entry['first_name'] ?? '') . ' ' . ($entry['last_name'] ?? ''))) ?></td>
												<td><code class="small"><?= htmlspecialchars($entry['field_name']) ?></code></td>
												<td class="small text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($entry['old_value'] ?? '') ?>">
													<?= htmlspecialchars(substr($entry['old_value'] ?? '—', 0, 120)) ?>
												</td>
												<td class="small" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($entry['new_value'] ?? '') ?>">
													<?= htmlspecialchars(substr($entry['new_value'] ?? '—', 0, 120)) ?>
												</td>
											</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
								<?php endif; ?>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-followup"><i class="fas fa-chevron-left"></i> <?= __('tab_followup') ?></button>
									<button type="button" class="btn btn-secondary" disabled><?= __("end_label") ?> <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

						</div><!-- /tab-content -->
					</div>
				</div>
			</div>
		</section>
	</div>

	<footer class="main-footer text-sm"><strong>CareSystem</strong> &middot; <?= __('doctor_review') ?></footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script>
(function () {
	var csrfToken   = <?= json_encode($_SESSION['csrf_token']) ?>;
	var caseSheetId = <?= $csId ?>;

	// ── Tab navigation ──────────────────────────────────────
	$(document).on('click', '.btn-next-tab', function () {
		var target = $(this).data('target');
		$('#reviewTabs a[href="' + target + '"]').tab('show');
		window.scrollTo(0, 0);
	});

	// ── Unsaved-navigation guard ────────────────────────────
	var formDirty   = false;
	var pendingSaves = 0;

	$('.doctor-auto-save').on('change input', 'input, select, textarea', function () {
		formDirty = true;
	});

	$(window).on('beforeunload', function (e) {
		if (formDirty) {
			e.preventDefault();
			return '';
		}
	});

	$('#closeChartForm').on('submit', function () {
		formDirty = false;
	});

	// ── Auto-save ───────────────────────────────────────────
	var saveTimeout = {};
	var $indicator  = $('#autoSaveIndicator');

	function autoSave(field, value) {
		pendingSaves++;
		$indicator.removeClass('error').addClass('saving').text('<?= addslashes(__('saving_label')) ?>').fadeIn();
		$.ajax({
			url: 'update_case_sheet.php',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({ case_sheet_id: caseSheetId, field: field, value: value, csrf_token: csrfToken }),
			dataType: 'json',
			success: function (r) {
				pendingSaves--;
				if (r.success) {
					$indicator.removeClass('saving').html('<i class="fas fa-check-circle"></i> <?= addslashes(__('saved')) ?>').fadeIn();
					setTimeout(function () { $indicator.fadeOut(); }, 1500);
					if (pendingSaves <= 0) { pendingSaves = 0; formDirty = false; }
				} else {
					$indicator.removeClass('saving').addClass('error').html('<i class="fas fa-times-circle"></i> ' + (r.message || 'Error')).fadeIn();
				}
			},
			error: function () {
				pendingSaves--;
				$indicator.removeClass('saving').addClass('error').html('<i class="fas fa-times-circle"></i> Save failed').fadeIn();
			}
		});
	}

	$('.doctor-auto-save').on('change', 'input, select', function () {
		var field = $(this).data('field');
		if (!field) return;
		clearTimeout(saveTimeout[field]);
		saveTimeout[field] = setTimeout(function () { autoSave(field, arguments[0]); }.bind(null, $(this).val()), 400);
	});

	$('.doctor-auto-save').on('input', 'textarea', function () {
		var field = $(this).data('field');
		if (!field) return;
		var val = $(this).val();
		clearTimeout(saveTimeout[field]);
		saveTimeout[field] = setTimeout(function () { autoSave(field, val); }, 1000);
	});

	// ── Unified allergy management — synced across Patient & History tabs ────
	// Single source of truth: allergyData array.
	// Both #rv-allergyRows (History) and #pt-allergyRows (Patient) render from it.
	// Any mutation re-renders both containers and saves to:
	//   1. update_case_sheet.php → allergies_json (assessment JSON)
	//   2. intake.php?action=update-patient → allergies (plain text summary on patients table)
	(function () {
		var allergyData = [];

		function escHtml(s) {
			return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
		}

		// Save to both endpoints
		function saveAllergies() {
			var clean = allergyData.filter(function(r) { return r.allergy || r.reaction; });
			// 1. Case sheet allergies_json
			autoSave('allergies_json', JSON.stringify(clean));
			// 2. Patient table plain text summary (substance names joined)
			var summary = clean.map(function(r) { return r.allergy; }).filter(Boolean).join(', ');
			$.ajax({
				url: 'intake.php?action=update-patient',
				method: 'POST',
				contentType: 'application/json',
				data: JSON.stringify({
					csrf_token:    csrfToken,
					patient_id:    <?= (int)$p['patient_id'] ?>,
					case_sheet_id: caseSheetId,
					allergies:     summary
				}),
				dataType: 'json'
			});
		}

		// Render all rows into a container element
		function renderContainer(containerId) {
			var container = document.getElementById(containerId);
			if (!container) return;
			container.innerHTML = '';
			allergyData.forEach(function (rowData, idx) {
				var div = document.createElement('div');
				div.className = 'allergy-sync-row d-flex align-items-center mb-2';
				div.dataset.idx = idx;
				div.innerHTML =
					'<input type="text" class="form-control allergy-name-input mr-2" placeholder="<?= addslashes(__('allergy_substance')) ?>" value="' + escHtml(rowData.allergy || '') + '">' +
					'<input type="text" class="form-control allergy-reaction-input mr-2" placeholder="<?= addslashes(__('allergy_reaction')) ?>" value="' + escHtml(rowData.reaction || '') + '">' +
					'<button type="button" class="btn btn-sm btn-outline-danger allergy-remove-btn" title="Remove"><i class="fas fa-times"></i></button>';
				container.appendChild(div);
				wireRow(div, idx);
			});
		}

		// Re-render both containers
		function renderBoth() {
			renderContainer('rv-allergyRows');
			renderContainer('pt-allergyRows');
		}

		// Wire events on a row in either container
		function wireRow(div, idx) {
			var nameInp = div.querySelector('.allergy-name-input');
			var rxInp   = div.querySelector('.allergy-reaction-input');
			var removeBtn = div.querySelector('.allergy-remove-btn');

			function onNameChange() {
				allergyData[idx].allergy = nameInp.value.trim();
				// Mirror to the other container's same row without full re-render
				document.querySelectorAll('.allergy-sync-row[data-idx="' + idx + '"] .allergy-name-input').forEach(function(el) {
					if (el !== nameInp) el.value = allergyData[idx].allergy;
				});
				saveAllergies();
			}
			function onRxChange() {
				allergyData[idx].reaction = rxInp.value.trim();
				document.querySelectorAll('.allergy-sync-row[data-idx="' + idx + '"] .allergy-reaction-input').forEach(function(el) {
					if (el !== rxInp) el.value = allergyData[idx].reaction;
				});
				saveAllergies();
			}

			nameInp.addEventListener('change', onNameChange);
			nameInp.addEventListener('input', function() { clearTimeout(nameInp._st); nameInp._st = setTimeout(onNameChange, 600); });
			rxInp.addEventListener('change', onRxChange);
			rxInp.addEventListener('input', function() { clearTimeout(rxInp._st); rxInp._st = setTimeout(onRxChange, 600); });

			removeBtn.addEventListener('click', function () {
				allergyData.splice(idx, 1);
				renderBoth();
				saveAllergies();
			});
		}

		function addRow(allergy, reaction) {
			allergyData.push({ allergy: allergy || '', reaction: reaction || '' });
			renderBoth();
		}

		// Initialise from saved JSON
		(function () {
			var raw = <?= json_encode(!empty($histData['allergies_json']) ? $histData['allergies_json'] : '') ?>;
			var rows = [];
			if (raw) { try { rows = JSON.parse(raw); } catch (e) {} }
			if (rows.length > 0) {
				rows.forEach(function (r) { allergyData.push({ allergy: r.allergy || '', reaction: r.reaction || '' }); });
			} else {
				allergyData.push({ allergy: '', reaction: '' });
			}
			renderBoth();
		})();

		// Add row buttons
		document.getElementById('rv-btnAddAllergy').addEventListener('click', function () { addRow('', ''); });
		document.getElementById('pt-btnAddAllergy').addEventListener('click', function () { addRow('', ''); });

		// No known allergies toggle — both checkboxes mirror each other
		function setNoKnownAllergies(checked) {
			document.getElementById('rv-noKnownAllergies').checked = checked;
			document.getElementById('pt-noKnownAllergies').checked = checked;
			document.getElementById('rv-allergySection').style.display = checked ? 'none' : '';
			document.getElementById('pt-allergySection').style.display = checked ? 'none' : '';
			autoSave('no_known_allergies', checked ? '1' : '0');
		}
		document.getElementById('rv-noKnownAllergies').addEventListener('change', function () { setNoKnownAllergies(this.checked); });
		document.getElementById('pt-noKnownAllergies').addEventListener('change', function () { setNoKnownAllergies(this.checked); });
	})();

	// ── Labs tab: load lab orders via AJAX when tab activates ───────────
	$('a[data-toggle="tab"][href="#tab-labs"]').on('shown.bs.tab', function () {
		loadLabOrders();
	});

	// ── Lab order modal ──────────────────────────────────────────────────
	(function () {
		var $modal     = $('#labOrderModal');
		var $rows      = $('#labTestRows');
		var $error     = $('#labOrderError');
		var $submitBtn = $('#btnSubmitLabOrder');

		var rowTemplate =
			'<div class="lab-test-row mb-3">' +
			'<div class="d-flex align-items-center mb-1">' +
			'<span class="font-weight-bold small mr-2" style="min-width:3rem;">Test</span>' +
			'<input type="text" class="form-control form-control-sm lab-test-name" placeholder="e.g. Complete Blood Count (CBC)" autocomplete="off" />' +
			'<button type="button" class="btn btn-outline-danger btn-sm btn-remove-row ml-2" aria-label="Remove test"><i class="fas fa-times"></i></button>' +
			'</div>' +
			'<div class="d-flex align-items-center">' +
			'<span class="text-muted small mr-2" style="min-width:3rem;">Notes</span>' +
			'<input type="text" class="form-control form-control-sm lab-test-notes" placeholder="Optional notes for this test" />' +
			'</div>' +
			'</div>';

		$('#btnAddLabRow').on('click', function () {
			$rows.append(rowTemplate);
			$rows.find('.btn-remove-row').show();
			$rows.find('.lab-test-name').last().focus();
		});

		$rows.on('click', '.btn-remove-row', function () {
			$(this).closest('.lab-test-row').remove();
			if ($rows.find('.lab-test-row').length === 1) {
				$rows.find('.btn-remove-row').hide();
			}
		});

		$modal.on('hidden.bs.modal', function () {
			$rows.html(
				'<div class="lab-test-row mb-3">' +
				'<div class="d-flex align-items-center mb-1">' +
				'<span class="font-weight-bold small mr-2" style="min-width:3rem;">Test</span>' +
				'<input type="text" class="form-control form-control-sm lab-test-name" placeholder="e.g. Complete Blood Count (CBC)" autocomplete="off" />' +
				'<button type="button" class="btn btn-outline-danger btn-sm btn-remove-row ml-2" style="display:none;" aria-label="Remove test"><i class="fas fa-times"></i></button>' +
				'</div>' +
				'<div class="d-flex align-items-center">' +
				'<span class="text-muted small mr-2" style="min-width:3rem;">Notes</span>' +
				'<input type="text" class="form-control form-control-sm lab-test-notes" placeholder="Optional notes for this test" />' +
				'</div>' +
				'</div>'
			);
			$error.addClass('d-none').text('');
			$submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i><?= __('submit_order') ?>');
		});

		$submitBtn.on('click', function () {
			var tests = [];
			$rows.find('.lab-test-row').each(function () {
				var name  = $(this).find('.lab-test-name').val().trim();
				var notes = $(this).find('.lab-test-notes').val().trim();
				if (name !== '') {
					tests.push({ test_name: name, notes: notes });
				}
			});
			if (!tests.length) {
				$error.text('Please enter at least one test name.').removeClass('d-none');
				return;
			}
			$error.addClass('d-none');
			$submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Submitting…');

			$.ajax({
				url: 'intake.php?action=order-lab-test',
				method: 'POST',
				contentType: 'application/json',
				data: JSON.stringify({
					csrf_token: csrfToken,
					case_sheet_id: caseSheetId,
					tests: tests
				}),
				dataType: 'json',
				success: function (r) {
					if (!r.success) {
						$error.text(r.message || 'Failed to submit.').removeClass('d-none');
						$submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i><?= __('submit_order') ?>');
						return;
					}
					$modal.modal('hide');
					loadLabOrders();
				},
				error: function () {
					$error.text('Server error. Please try again.').removeClass('d-none');
					$submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i><?= __('submit_order') ?>');
				}
			});
		});
	})();

	function loadLabOrders() {
		$.ajax({
			url: 'intake.php?action=get-lab-orders&case_sheet_id=' + caseSheetId,
			method: 'GET',
			dataType: 'json',
			success: function (r) {
				if (!r.success) return;
				var orders = r.orders || [];
				var $body  = $('#rvLabOrdersBody');
				var $table = $('#rvLabOrdersTable');
				var $none  = $('#rvNoLabOrders');
				$body.empty();
				if (orders.length === 0) {
					$table.hide(); $none.show();
				} else {
					$none.hide(); $table.show();
					orders.forEach(function (lo) {
						var statusBadge = lo.status === 'COMPLETED'
							? '<span class="badge badge-success"><?= addslashes(__('status_completed')) ?></span>'
							: '<span class="badge badge-warning"><?= addslashes(__('status_pending')) ?></span>';
						var orderedBy = (lo.ordered_by_first + ' ' + lo.ordered_by_last).trim();
						var orderedAt = lo.ordered_at ? new Date(lo.ordered_at).toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
						$body.append(
							'<tr>' +
							'<td>' + $('<span>').text(lo.test_name).html() + '</td>' +
							'<td class="text-muted">' + (lo.order_notes ? $('<span>').text(lo.order_notes).html() : '—') + '</td>' +
							'<td>' + $('<span>').text(orderedBy).html() + '</td>' +
							'<td>' + statusBadge + '</td>' +
							'<td>' + orderedAt + '</td>' +
							'</tr>'
						);
					});
				}
			}
		});
	}

	// ── Patient tab: lock / unlock toggle ────────────────────
	var patientEditActive = false;

	function setPatientEditMode(enabled) {
		patientEditActive = enabled;
		$('.patient-review-field').each(function () {
			if ($(this).is('select')) {
				$(this).prop('disabled', !enabled);
			} else {
				$(this).prop('readonly', !enabled);
			}
		});
		$('#btnEditPatientReview').toggle(!enabled);
		$('#btnLockPatientReview').toggle(enabled);
		$('#patientReviewAlert')[enabled ? 'slideDown' : 'slideUp']();
	}

	$('#btnEditPatientReview').on('click', function () { setPatientEditMode(true); });
	$('#btnLockPatientReview').on('click', function () { setPatientEditMode(false); });

	// Auto-lock when navigating away from the Patient tab
	$('#reviewTabs a[data-toggle="tab"]').on('show.bs.tab', function (e) {
		var target = $(e.target).attr('href');
		if (target !== '#tab-patient' && patientEditActive) {
			setPatientEditMode(false);
		}
	});

	// ── Patient tab: per-field auto-save ───────────────────────
	var patientSaveTimeout = {};

	function savePatientField(fieldName, value) {
		$('#patientSaveError').hide();
		pendingSaves++;
		$indicator.removeClass('error').addClass('saving').text('<?= addslashes(__("saving_label")) ?>').fadeIn();
		$.ajax({
			url: 'intake.php?action=update-patient',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				csrf_token:    csrfToken,
				patient_id:    <?= (int)$p['patient_id'] ?>,
				case_sheet_id: caseSheetId,
				[fieldName]:   value
			}),
			dataType: 'json',
			success: function (r) {
				pendingSaves--;
				if (r.success) {
					$indicator.removeClass('saving').html('<i class="fas fa-check-circle"></i> <?= addslashes(__("saved")) ?>').fadeIn();
					setTimeout(function () { $indicator.fadeOut(); }, 1500);
				} else {
					$indicator.removeClass('saving').addClass('error').html('<i class="fas fa-times-circle"></i> Save failed').fadeIn();
					$('#patientSaveError').text(r.message || 'Save failed.').show();
				}
			},
			error: function () {
				pendingSaves--;
				$indicator.removeClass('saving').addClass('error').html('<i class="fas fa-times-circle"></i> Save failed').fadeIn();
				$('#patientSaveError').text('A network error occurred. Please try again.').show();
			}
		});
	}

	$('#tab-patient').on('change', '.patient-review-field', function () {
		if (!patientEditActive) return;
		var fieldName = $(this).attr('name');
		var value     = $(this).val();
		if (!fieldName) return;
		clearTimeout(patientSaveTimeout[fieldName]);
		patientSaveTimeout[fieldName] = setTimeout(function () {
			savePatientField(fieldName, value);
		}, 400);
	});

	$('#tab-patient').on('input', '.patient-review-field', function () {
		if (!patientEditActive) return;
		var fieldName = $(this).attr('name');
		var value     = $(this).val();
		if (!fieldName) return;
		clearTimeout(patientSaveTimeout[fieldName]);
		patientSaveTimeout[fieldName] = setTimeout(function () {
			savePatientField(fieldName, value);
		}, 800);
	});

	// DOB → age auto-calculation
	$('#pr-date_of_birth').on('change', function () {
		var dob = this.value;
		if (!dob) return;
		var today = new Date();
		var birth = new Date(dob);
		var age = today.getFullYear() - birth.getFullYear();
		var m = today.getMonth() - birth.getMonth();
		if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
		if (age >= 0 && age <= 150) {
			$('#pr-age_years').val(age);
			clearTimeout(patientSaveTimeout['age_years']);
			patientSaveTimeout['age_years'] = setTimeout(function () {
				savePatientField('age_years', String(age));
			}, 400);
		}
	});

	// ── Live vitals comparison update ──────────────────────
	// When a doctor edits a value in the General tab, the comparison
	// table updates immediately without waiting for a page reload.

	// Shared function to update a single comparison row given a field name and new value.
	function updateCompareRow(field, newVal) {
		var $row = $('tr[data-compare-field="' + field + '"]');
		if (!$row.length) return;

		var prevVal   = parseFloat($row.data('compare-prev'));
		var threshold = parseFloat($row.data('compare-threshold'));
		var unit      = $row.data('compare-unit') || '';
		var $currTd   = $row.find('.compare-curr-td');
		var $val      = $currTd.find('.compare-curr-val');
		var $unitEl   = $currTd.find('.compare-curr-unit');
		var $arrow    = $currTd.find('.compare-arrow');
		var $noRec    = $currTd.find('.vitals-compare-no-record');
		var numVal    = parseFloat(newVal);

		if (newVal === '' || isNaN(numVal)) {
			$val.text('');
			$unitEl.text('').hide();
			$arrow.text('');
			$currTd.removeClass('vitals-compare-drastic');
			if ($noRec.length) { $noRec.show(); } else { $currTd.append('<span class="vitals-compare-no-record"><?= addslashes(__('cmp_not_recorded')) ?></span>'); }
			return;
		}

		$noRec.hide();

		var hasPrev   = !isNaN(prevVal) && String($row.data('compare-prev')) !== '';
		var drastic   = false;
		var direction = '';

		if (hasPrev) {
			var delta = numVal - prevVal;
			if (Math.abs(delta) >= threshold) {
				drastic   = true;
				direction = delta > 0 ? '↑' : '↓';
			}
		}

		$val.text(newVal);
		if (unit) {
			$unitEl.text(unit).show();
			if (drastic) { $unitEl.removeClass('text-muted'); } else { $unitEl.addClass('text-muted'); }
		}
		$arrow.text(drastic ? ' ' + direction : '');
		if (drastic) { $currTd.addClass('vitals-compare-drastic'); } else { $currTd.removeClass('vitals-compare-drastic'); }
	}

	// Auto-recalculate and update BMI when height or weight changes.
	function recalcBmi() {
		var h = parseFloat($('input[data-field="general_height"]').val());
		var w = parseFloat($('input[data-field="general_weight"]').val());
		if (h > 0 && w > 0) {
			var bmi = (w / ((h / 100) * (h / 100))).toFixed(1);
			$('input[data-field="general_bmi"]').val(bmi);
			updateCompareRow('general_bmi', bmi);
			// Delay longer than the height/weight save (400ms) so the JSON
			// column is not read before their writes complete, preventing BMI
			// from being silently overwritten by the racing save.
			clearTimeout(saveTimeout['general_bmi']);
			saveTimeout['general_bmi'] = setTimeout(function () {
				autoSave('general_bmi', bmi);
			}, 1200);
		}
	}

	// ── Menstrual section toggle (mirrors intake.php logic) ───
	function rvToggleMenstrual() {
		$('#rvMenstrualSection')[$('#rv_has_uterus').val() === '1' ? 'slideDown' : 'slideUp']();
	}
	rvToggleMenstrual(); // run on page load
	$('#rv_has_uterus').on('change', rvToggleMenstrual);

	$('#tab-general').on('input change', 'input[data-field], select[data-field]', function () {
		var field  = $(this).data('field');
		var newVal = $(this).val().trim();
		updateCompareRow(field, newVal);
		// Trigger BMI recalc whenever height or weight changes
		if (field === 'general_height' || field === 'general_weight') {
			recalcBmi();
		}
		// Re-evaluate menstrual section visibility when has_uterus changes
		if (field === 'has_uterus') {
			rvToggleMenstrual();
		}
	});

	// ── Labs tab: live comparison update on input ──────────────
	$('#tab-labs').on('input change', 'input[data-field], select[data-field]', function () {
		updateCompareRow($(this).data('field'), $(this).val().trim());
	});

	// ── Close chart confirmation ────────────────────────────
	$('#closeChartForm').on('submit', function (e) {
		if (!$('#closure_type').val()) {
			e.preventDefault();
			alert('Please select a closure type before closing the chart.');
			return;
		}
		if (!confirm('Close this chart? This action is permanent and will lock the record.')) {
			e.preventDefault();
		}
	});
})();
</script>
<!-- ── Diagram Editor Modal ──────────────────────────────────────── -->
<div class="modal fade" id="diagramEditorModal" tabindex="-1" role="dialog" aria-labelledby="diagramEditorTitle" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="diagramEditorTitle"><?= __("diagram_editor") ?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="p-3 bg-light border rounded mb-3">
					<div class="row align-items-end">
						<div class="col-sm-5 mb-2 mb-sm-0">
							<label class="mb-1 small font-weight-bold"><?= __("drawing_tool") ?></label>
							<div class="btn-group btn-group-sm" role="group">
								<button type="button" class="btn btn-outline-dark active" data-tool="pen" data-color="#000000"><i class="fas fa-pen mr-1"></i><?= __("color_black") ?></button>
								<button type="button" class="btn btn-outline-danger" data-tool="pen" data-color="#e63946"><i class="fas fa-pen mr-1"></i><?= __("color_red") ?></button>
								<button type="button" class="btn btn-outline-secondary" data-tool="eraser"><i class="fas fa-eraser mr-1"></i><?= __("eraser") ?></button>
							</div>
						</div>
						<div class="col-sm-3 mb-2 mb-sm-0">
							<label for="diagLineThickness" class="mb-1 small font-weight-bold"><?= __("thickness") ?></label>
							<select class="form-control form-control-sm" id="diagLineThickness">
								<option value="2"><?= __("thickness_fine") ?></option>
								<option value="4" selected><?= __("thickness_normal") ?></option>
								<option value="6"><?= __("thickness_medium") ?></option>
								<option value="8"><?= __("thickness_thick") ?></option>
								<option value="12"><?= __("thickness_very_thick") ?></option>
							</select>
						</div>
						<div class="col-sm-4 text-sm-right">
							<button type="button" class="btn btn-sm btn-warning" id="diagUndoBtn"><i class="fas fa-undo mr-1"></i><?= __("undo") ?></button>
							<button type="button" class="btn btn-sm btn-info" id="diagRedoBtn" disabled><i class="fas fa-redo mr-1"></i><?= __("redo") ?></button>
							<button type="button" class="btn btn-sm btn-danger" id="diagClearBtn"><i class="fas fa-trash mr-1"></i><?= __("clear") ?></button>
						</div>
					</div>
				</div>
				<div class="diagram-canvas-container">
					<canvas id="diagramCanvas"></canvas>
				</div>
				<p class="text-muted small mt-2 mb-0"><i class="fas fa-info-circle mr-1"></i><?= __("diagram_hint_before") ?> <strong><?= __("save_diagram") ?></strong> <?= __("diagram_hint_after") ?></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= __("cancel") ?></button>
				<button type="button" class="btn btn-success" id="diagSaveBtn"><i class="fas fa-save mr-1"></i><?= __("save_diagram") ?></button>
			</div>
		</div>
	</div>
</div>

<script>
/* ── Diagram editor (stroke-JSON storage) ──────────────────────────── */
(function () {
	var CSRF_TOKEN    = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
	var CASE_SHEET_ID = <?= (int)$csId ?>;

	var activeDiagram = null;
	var canvas = null, ctx = null;
	var isDrawing = false;
	var currentTool = 'pen', currentColor = '#000000', currentThickness = 4;
	var strokes = [];
	var currentStroke = null;

	var templatePaths = {
		breast: 'assets/images/diagrams/BreastExaminationDiagram.png',
		pelvic: 'assets/images/diagrams/PelvicExaminationDiagram.png',
		via:    'assets/images/diagrams/VIAVILIDiagram.png',
		vili:   'assets/images/diagrams/VIAVILIDiagram.png'
	};
	var modalTitles = {
		breast: '<?= addslashes(__('breast_exam_diagram')) ?>',
		pelvic: '<?= addslashes(__('pelvic_exam_diagram')) ?>',
		via:    '<?= addslashes(__('via_diagram')) ?>',
		vili:   '<?= addslashes(__('vili_diagram')) ?>'
	};
	var templateCache = {};

	window.openDiagram = function (type, fieldId, previewId) {
		activeDiagram = { type: type, fieldId: fieldId, previewId: previewId };
		document.getElementById('diagramEditorTitle').textContent = modalTitles[type] + ' <?= addslashes(__('diagram_editor')) ?>';
		$('#diagramEditorModal').modal('show');
	};

	$('#diagramEditorModal').on('shown.bs.modal', function () {
		canvas = document.getElementById('diagramCanvas');
		ctx    = canvas.getContext('2d');
		canvas.width  = 800;
		canvas.height = 600;
		var raw = document.getElementById(activeDiagram.fieldId)?.value || '';
		try { strokes = raw ? JSON.parse(raw) : []; } catch (e) { strokes = []; }
		loadTemplateAndRedraw();
	});

	$('#diagramEditorModal').on('hidden.bs.modal', function () {
		activeDiagram = null;
		strokes = []; currentStroke = null;
	});

	function loadTemplateAndRedraw() {
		var src = templatePaths[activeDiagram.type];
		if (templateCache[src]) { drawAll(templateCache[src]); return; }
		var img = new Image();
		img.onload = function () { templateCache[src] = img; drawAll(img); };
		img.onerror = function () {
			ctx.fillStyle = '#f8d7da'; ctx.fillRect(0, 0, canvas.width, canvas.height);
			ctx.fillStyle = '#721c24'; ctx.font = '18px sans-serif'; ctx.textAlign = 'center';
			ctx.fillText('Template image could not be loaded', canvas.width / 2, canvas.height / 2);
			replayStrokes();
		};
		img.src = src;
	}

	function drawAll(templateImg) {
		ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
		var scale = Math.min(canvas.width / templateImg.width, canvas.height / templateImg.height);
		var w = templateImg.width * scale, h = templateImg.height * scale;
		ctx.drawImage(templateImg, (canvas.width - w) / 2, (canvas.height - h) / 2, w, h);
		replayStrokes();
	}

	function replayStrokes() { strokes.forEach(function (s) { drawStroke(s); }); }

	function drawStroke(stroke) {
		if (!stroke.points || stroke.points.length < 2) return;
		ctx.save();
		if (stroke.tool === 'eraser') {
			ctx.globalCompositeOperation = 'destination-out';
			ctx.strokeStyle = 'rgba(0,0,0,1)'; ctx.lineWidth = stroke.thickness * 3;
		} else {
			ctx.globalCompositeOperation = 'source-over';
			ctx.strokeStyle = stroke.color; ctx.lineWidth = stroke.thickness;
		}
		ctx.lineCap = ctx.lineJoin = 'round';
		ctx.beginPath();
		ctx.moveTo(stroke.points[0][0], stroke.points[0][1]);
		for (var i = 1; i < stroke.points.length; i++) { ctx.lineTo(stroke.points[i][0], stroke.points[i][1]); }
		ctx.stroke(); ctx.restore();
	}

	function buildThumbnail() {
		var src = templatePaths[activeDiagram.type];
		var thumb = document.createElement('canvas');
		thumb.width = 300; thumb.height = 225;
		var tc = thumb.getContext('2d');
		var img = templateCache[src];
		if (img) {
			var scale = Math.min(300 / img.width, 225 / img.height);
			var w = img.width * scale, h = img.height * scale;
			tc.fillStyle = '#ffffff'; tc.fillRect(0, 0, 300, 225);
			tc.drawImage(img, (300 - w) / 2, (225 - h) / 2, w, h);
		}
		var sx = 300 / canvas.width, sy = 225 / canvas.height;
		strokes.forEach(function (stroke) {
			if (!stroke.points || stroke.points.length < 2) return;
			tc.save();
			if (stroke.tool === 'eraser') { tc.globalCompositeOperation = 'destination-out'; tc.strokeStyle = 'rgba(0,0,0,1)'; tc.lineWidth = stroke.thickness * 3 * sx; }
			else { tc.globalCompositeOperation = 'source-over'; tc.strokeStyle = stroke.color; tc.lineWidth = stroke.thickness * sx; }
			tc.lineCap = tc.lineJoin = 'round';
			tc.beginPath();
			tc.moveTo(stroke.points[0][0] * sx, stroke.points[0][1] * sy);
			for (var i = 1; i < stroke.points.length; i++) { tc.lineTo(stroke.points[i][0] * sx, stroke.points[i][1] * sy); }
			tc.stroke(); tc.restore();
		});
		return thumb.toDataURL('image/png');
	}

	document.querySelectorAll('#diagramEditorModal [data-tool]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			document.querySelectorAll('#diagramEditorModal [data-tool]').forEach(function (b) { b.classList.remove('active'); });
			btn.classList.add('active');
			currentTool = btn.dataset.tool;
			if (btn.dataset.color) currentColor = btn.dataset.color;
			if (canvas) canvas.style.cursor = currentTool === 'eraser' ? 'cell' : 'crosshair';
		});
	});

	document.getElementById('diagLineThickness').addEventListener('change', function () { currentThickness = parseInt(this.value); });
	document.getElementById('diagUndoBtn').addEventListener('click', function () { if (strokes.length > 0) { strokes.pop(); loadTemplateAndRedraw(); } });
	document.getElementById('diagClearBtn').addEventListener('click', function () { if (strokes.length && confirm('<?= addslashes(__('clear')) ?> — Are you sure?')) { strokes = []; loadTemplateAndRedraw(); } });

	function getPos(clientX, clientY) {
		var rect = canvas.getBoundingClientRect();
		return [(clientX - rect.left) * (canvas.width / rect.width), (clientY - rect.top) * (canvas.height / rect.height)];
	}
	function startDraw(x, y) { isDrawing = true; currentStroke = { tool: currentTool, color: currentColor, thickness: currentThickness, points: [[x, y]] }; }
	function continueDraw(x, y) {
		if (!isDrawing || !currentStroke) return;
		currentStroke.points.push([x, y]);
		if (currentStroke.points.length >= 2) {
			var pts = currentStroke.points, prev = pts[pts.length - 2];
			ctx.save();
			if (currentTool === 'eraser') { ctx.globalCompositeOperation = 'destination-out'; ctx.strokeStyle = 'rgba(0,0,0,1)'; ctx.lineWidth = currentThickness * 3; }
			else { ctx.globalCompositeOperation = 'source-over'; ctx.strokeStyle = currentColor; ctx.lineWidth = currentThickness; }
			ctx.lineCap = ctx.lineJoin = 'round';
			ctx.beginPath(); ctx.moveTo(prev[0], prev[1]); ctx.lineTo(x, y); ctx.stroke(); ctx.restore();
		}
	}
	function endDraw() { if (!isDrawing || !currentStroke) return; isDrawing = false; if (currentStroke.points.length >= 2) strokes.push(currentStroke); currentStroke = null; }

	document.addEventListener('DOMContentLoaded', function () {
		var c = document.getElementById('diagramCanvas');
		c.addEventListener('mousedown',  function (e) { var p = getPos(e.clientX, e.clientY); startDraw(p[0], p[1]); });
		c.addEventListener('mousemove',  function (e) { var p = getPos(e.clientX, e.clientY); continueDraw(p[0], p[1]); });
		c.addEventListener('mouseup',    endDraw);
		c.addEventListener('mouseleave', endDraw);
		c.addEventListener('touchstart', function (e) { e.preventDefault(); var t = e.touches[0]; var p = getPos(t.clientX, t.clientY); startDraw(p[0], p[1]); }, { passive: false });
		c.addEventListener('touchmove',  function (e) { e.preventDefault(); var t = e.touches[0]; var p = getPos(t.clientX, t.clientY); continueDraw(p[0], p[1]); }, { passive: false });
		c.addEventListener('touchend',   function (e) { e.preventDefault(); endDraw(); }, { passive: false });
	});

	document.getElementById('diagSaveBtn').addEventListener('click', function () {
		var strokeJSON = JSON.stringify(strokes);
		var field = document.getElementById(activeDiagram.fieldId);
		if (field) field.value = strokeJSON;
		var preview = document.getElementById(activeDiagram.previewId);
		if (preview) {
			var imgEl = preview.querySelector('img');
			if (imgEl) imgEl.src = strokes.length ? buildThumbnail() : '';
			preview.classList.toggle('d-none', !strokes.length);
		}
		document.querySelectorAll('button[onclick*="' + activeDiagram.fieldId + '"]').forEach(function (btn) {
			btn.innerHTML = '<i class="fas fa-draw-polygon mr-1"></i><?= addslashes(__('edit')) ?> ' + modalTitles[activeDiagram.type];
		});
		fetch('update_case_sheet.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ csrf_token: CSRF_TOKEN, case_sheet_id: CASE_SHEET_ID, field: activeDiagram.fieldId, value: strokeJSON })
		}).catch(function (err) { console.error('Diagram save error:', err); });
		$('#diagramEditorModal').modal('hide');
	});
}());
</script>

<script>
/* ── Render existing diagram thumbnails on page load ── */
(function () {
	var templatePaths = {
		breast: 'assets/images/diagrams/BreastExaminationDiagram.png',
		pelvic: 'assets/images/diagrams/PelvicExaminationDiagram.png',
		via:    'assets/images/diagrams/VIAVILIDiagram.png',
		vili:   'assets/images/diagrams/VIAVILIDiagram.png'
	};
	function renderThumbnail(imgEl, templateType, strokes) {
		var thumb = document.createElement('canvas');
		thumb.width = 300; thumb.height = 225;
		var tc = thumb.getContext('2d');
		var tpl = new Image();
		tpl.onload = function () {
			var scale = Math.min(300 / tpl.width, 225 / tpl.height);
			var w = tpl.width * scale, h = tpl.height * scale;
			tc.fillStyle = '#ffffff'; tc.fillRect(0, 0, 300, 225);
			tc.drawImage(tpl, (300 - w) / 2, (225 - h) / 2, w, h);
			var sx = 300 / 800, sy = 225 / 600;
			strokes.forEach(function (stroke) {
				if (!stroke.points || stroke.points.length < 2) return;
				tc.save();
				if (stroke.tool === 'eraser') { tc.globalCompositeOperation = 'destination-out'; tc.strokeStyle = 'rgba(0,0,0,1)'; tc.lineWidth = stroke.thickness * 3 * sx; }
				else { tc.globalCompositeOperation = 'source-over'; tc.strokeStyle = stroke.color; tc.lineWidth = stroke.thickness * sx; }
				tc.lineCap = tc.lineJoin = 'round';
				tc.beginPath();
				tc.moveTo(stroke.points[0][0] * sx, stroke.points[0][1] * sy);
				for (var i = 1; i < stroke.points.length; i++) { tc.lineTo(stroke.points[i][0] * sx, stroke.points[i][1] * sy); }
				tc.stroke(); tc.restore();
			});
			imgEl.src = thumb.toDataURL('image/png');
		};
		tpl.src = templatePaths[templateType];
	}
	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('img[data-diag-field]').forEach(function (imgEl) {
			var field = document.getElementById(imgEl.dataset.diagField);
			if (!field || !field.value) return;
			try {
				var strokes = JSON.parse(field.value);
				if (Array.isArray(strokes) && strokes.length > 0) renderThumbnail(imgEl, imgEl.dataset.diagType, strokes);
			} catch (e) {}
		});
	});
}());
</script>

<!-- ── Order Lab Test Modal ───────────────────────────────────── -->
<div class="modal fade" id="labOrderModal" tabindex="-1" role="dialog" aria-labelledby="labOrderModalTitle" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="labOrderModalTitle"><i class="fas fa-flask mr-2"></i><?= __('order_lab_test') ?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<p class="text-muted small mb-3">Enter each lab test and optional notes. Use <strong>Add Another Test</strong> for multiple orders.</p>
				<div id="labTestRows">
					<div class="lab-test-row mb-3">
						<div class="d-flex align-items-center mb-1">
							<span class="font-weight-bold small mr-2" style="min-width:3rem;">Test</span>
							<input type="text" class="form-control form-control-sm lab-test-name" placeholder="e.g. Complete Blood Count (CBC)" autocomplete="off" />
							<button type="button" class="btn btn-outline-danger btn-sm btn-remove-row ml-2" style="display:none;" aria-label="Remove test"><i class="fas fa-times"></i></button>
						</div>
						<div class="d-flex align-items-center">
							<span class="text-muted small mr-2" style="min-width:3rem;">Notes</span>
							<input type="text" class="form-control form-control-sm lab-test-notes" placeholder="Optional notes for this test" />
						</div>
					</div>
				</div>
				<button type="button" class="btn btn-outline-primary btn-sm" id="btnAddLabRow">
					<i class="fas fa-plus mr-1"></i>Add Another Test
				</button>
				<div class="alert alert-danger d-none mt-3 mb-0" id="labOrderError"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= __('cancel') ?></button>
				<button type="button" class="btn btn-primary" id="btnSubmitLabOrder">
					<i class="fas fa-paper-plane mr-1"></i><?= __('submit_order') ?>
				</button>
			</div>
		</div>
	</div>
</div>

</body>
</html>
