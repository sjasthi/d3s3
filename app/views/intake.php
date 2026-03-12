<?php
require_once __DIR__ . '/../config/lang.php';
load_language($_SESSION['language'] ?? 'en');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Patient Intake | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
		#intakeTabs.nav-pills .nav-link {
			color: #495057;
			background-color: transparent;
			border-radius: 0.25rem;
			font-size: 0.9rem;
			border: 1px solid transparent;
		}
		#intakeTabs.nav-pills .nav-link:hover { background-color: #f8f9fa; border-color: #dee2e6; }
		#intakeTabs.nav-pills .nav-link.active {
			color: #007bff;
			background-color: #e7f3ff;
			border-color: #007bff;
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

		/* Diagram editor */
		.diagram-preview-img { max-width: 300px; max-height: 220px; cursor: pointer; }
		.diagram-canvas-container {
			overflow: auto; max-height: 65vh; background: #f8f9fa;
			padding: 16px; border: 2px solid #dee2e6; border-radius: 6px; text-align: center;
		}
		#diagramCanvas { border: 1px solid #adb5bd; background: white; cursor: crosshair; touch-action: none; }
		.modal-xl { max-width: 92%; }
		/* Extra top padding when the navbar is taller due to intake tabs */
		body.has-intake-tabs .content-wrapper { padding-top: 30px; }
	</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed<?= ($_SESSION['font_size'] ?? 'normal') === 'large' ? ' font-size-large' : '' ?><?= !empty($caseSheet) ? ' has-intake-tabs' : '' ?>"
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
		</ul>

		<?php if (!empty($caseSheet)): ?>
		<!-- Tabs shown when editing a case sheet -->
		<ul class="nav nav-pills ml-4 d-none d-lg-flex" id="intakeTabs" role="tablist">
			<li class="nav-item"><a class="nav-link active px-3" data-toggle="tab" href="#tab-verification"><?= __('tab_verification') ?></a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-personal"><?= __('tab_personal') ?></a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-history"><?= __('tab_history') ?></a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-general"><?= __('tab_general') ?></a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-examinations"><?= __('tab_examinations') ?></a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-labs"><?= __('tab_labs') ?></a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-summary"><?= __('tab_summary') ?></a></li>
		</ul>
		<?php endif; ?>

		<ul class="navbar-nav ml-auto">
			<li class="nav-item d-flex align-items-center mr-3">
				<div class="custom-control custom-switch theme-switch">
					<input type="checkbox" class="custom-control-input" id="themeToggleIntake" data-theme-toggle />
					<label class="custom-control-label" for="themeToggleIntake"><?= __('dark_mode') ?></label>
				</div>
			</li>
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="dashboard.php" role="button">
					<i class="fas fa-arrow-left mr-1"></i><?= __('dashboard') ?>
				</a>
			</li>
		</ul>
	</nav>

	<?php require __DIR__ . '/_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-8">
						<?php if (!empty($caseSheet)): ?>
						<h1 class="m-0 text-dark">
							<?= htmlspecialchars($patient['first_name'] . ' ' . ($patient['last_name'] ?? '')) ?>
							<small class="text-muted"><?= htmlspecialchars($patient['patient_code']) ?></small>
							<span class="text-muted">| <?= __('tab_intake') ?></span>
						</h1>
						<p class="text-muted mb-0"><?= __('subtitle_ncd') ?></p>
						<?php else: ?>
						<h1 class="m-0 text-dark"><i class="fas fa-clipboard-list mr-2"></i><?= __('page_title') ?></h1>
						<p class="text-muted mb-0"><?= __('subtitle_step1') ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Auto-save indicator -->
		<div id="autoSaveIndicator" class="auto-save-indicator"><i class="fas fa-check-circle"></i> <?= __('saved') ?></div>

		<section class="content">
			<div class="container-fluid">

				<?php if ($flashSuccess): ?>
				<div class="alert alert-success alert-dismissible fade show" role="alert">
					<i class="fas fa-check-circle mr-2"></i><?= $flashSuccess ?>
					<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
				</div>
				<?php endif; ?>

				<?php if ($formError): ?>
				<div class="alert alert-danger alert-dismissible fade show" role="alert">
					<i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($formError) ?>
					<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
				</div>
				<?php endif; ?>

				<?php if (empty($caseSheet)): ?>
				<!-- ============================================================ -->
				<!-- STEP 1: Patient Selection / Case Sheet Creation              -->
				<!-- ============================================================ -->
				<form method="post" action="intake.php" id="intakeCreateForm">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
					<input type="hidden" name="patient_id" id="patientIdField" value="" />

					<!-- Patient Selection -->
					<div class="card card-outline card-primary">
						<div class="card-header"><h3 class="card-title"><i class="fas fa-user-injured mr-2"></i><?= __('patient') ?></h3></div>
						<div class="card-body">
							<div class="form-group">
								<label for="patientSearch"><?= __('search_existing_patient') ?></label>
								<div class="input-group">
									<input type="text" class="form-control" id="patientSearch" placeholder="<?= __('search_placeholder') ?>" autocomplete="off" />
									<div class="input-group-append"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
								</div>
								<div id="searchResults" class="list-group mt-1" style="position:absolute;z-index:1000;width:calc(100% - 30px);display:none;"></div>
							</div>
							<div id="selectedPatient" class="alert alert-info d-none">
								<div class="d-flex justify-content-between align-items-center">
									<div>
										<strong id="selectedPatientName"></strong>
										<span class="badge badge-secondary ml-2" id="selectedPatientCode"></span><br />
										<small class="text-muted"><span id="selectedPatientSex"></span><span id="selectedPatientAge"></span><span id="selectedPatientPhone"></span></small>
									</div>
									<button type="button" class="btn btn-sm btn-outline-danger" id="clearPatient"><i class="fas fa-times mr-1"></i>Clear</button>
								</div>
							</div>
							<div class="mt-3">
								<button type="button" class="btn btn-sm btn-outline-primary" id="toggleNewPatient"><i class="fas fa-user-plus mr-1"></i><?= __('register_new_patient') ?></button>
							</div>
							<div id="newPatientSection" class="mt-3 d-none">
								<div class="card card-body bg-light">
									<h6 class="mb-3"><?= __('new_patient_registration') ?></h6>
									<div class="row">
										<div class="col-md-4"><div class="form-group"><label><?= __('first_name') ?> <span class="text-danger">*</span></label><input type="text" class="form-control" id="newFirstName" /></div></div>
										<div class="col-md-4"><div class="form-group"><label><?= __('last_name') ?></label><input type="text" class="form-control" id="newLastName" /></div></div>
										<div class="col-md-4"><div class="form-group"><label><?= __('sex') ?></label><select class="form-control" id="newSex"><option value="UNKNOWN"><?= __('sex_unknown') ?></option><option value="MALE"><?= __('sex_male') ?></option><option value="FEMALE"><?= __('sex_female') ?></option><option value="OTHER"><?= __('sex_other') ?></option></select></div></div>
									</div>
									<div class="row">
										<div class="col-md-4"><div class="form-group"><label><?= __('date_of_birth') ?></label><input type="date" class="form-control" id="newDob" /></div></div>
										<div class="col-md-4"><div class="form-group"><label><?= __('age_years') ?></label><input type="number" class="form-control" id="newAge" min="0" max="150" /></div></div>
										<div class="col-md-4"><div class="form-group"><label><?= __('phone') ?></label><input type="text" class="form-control" id="newPhone" placeholder="+91..." /></div></div>
									</div>
									<div id="registerError" class="alert alert-danger d-none"></div>
									<button type="button" class="btn btn-primary" id="registerPatientBtn"><i class="fas fa-user-plus mr-1"></i><?= __('register_and_select') ?></button>
								</div>
							</div>
						</div>
					</div>

					<!-- Visit Details -->
					<div class="card card-outline card-info">
						<div class="card-header"><h3 class="card-title"><i class="fas fa-notes-medical mr-2"></i><?= __('visit_details') ?></h3></div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label for="visitType"><?= __('visit_type') ?> <span class="text-danger">*</span></label>
										<select class="form-control" id="visitType" name="visit_type" required>
											<option value=""><?= __('select_placeholder') ?></option>
											<option value="CAMP"><?= __('visit_camp') ?></option>
											<option value="CLINIC"><?= __('visit_clinic') ?></option>
											<option value="FOLLOW_UP"><?= __('visit_follow_up') ?></option>
											<option value="EMERGENCY"><?= __('visit_emergency') ?></option>
											<option value="OTHER"><?= __('other') ?></option>
										</select>
									</div>
								</div>
								<div class="col-md-8">
									<div class="form-group">
										<label for="chiefComplaint"><?= __('chief_complaint') ?> <span class="text-danger">*</span></label>
										<input type="text" class="form-control" id="chiefComplaint" name="chief_complaint" maxlength="255" required placeholder="<?= __('chief_complaint_placeholder') ?>" />
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="mb-4">
						<button type="submit" class="btn btn-lg btn-primary" id="submitCreate"><i class="fas fa-arrow-right mr-2"></i><?= __('create_case_sheet') ?></button>
						<a href="dashboard.php" class="btn btn-lg btn-outline-secondary ml-2"><?= __('cancel') ?></a>
					</div>
				</form>

				<?php else: ?>
				<!-- ============================================================ -->
				<!-- STEP 2: Full Intake Form (all tabs)                          -->
				<!-- ============================================================ -->
				<?php
					$cs = $caseSheet;
					$p = $patient;
					$csId = (int)$cs['case_sheet_id'];
					$vitals = !empty($cs['vitals_json']) ? json_decode($cs['vitals_json'], true) : [];
					$examData = !empty($cs['exam_notes']) ? json_decode($cs['exam_notes'], true) : [];
					$historyData = !empty($cs['assessment']) ? json_decode($cs['assessment'], true) : [];
					$labData = !empty($cs['diagnosis']) ? json_decode($cs['diagnosis'], true) : [];
					$summaryData = !empty($cs['plan_notes']) ? json_decode($cs['plan_notes'], true) : [];
					// If the JSON columns contain plain text (not JSON), treat as empty arrays
					if (!is_array($examData)) $examData = [];
					if (!is_array($historyData)) $historyData = [];
					if (!is_array($labData)) $labData = [];
					if (!is_array($summaryData)) $summaryData = [];
				?>
				<div class="card shadow-sm">
					<div class="card-body">
						<div class="tab-content" id="intakeTabContent">

							<!-- ── Verification Tab ────────────────── -->
							<div class="tab-pane fade show active" id="tab-verification" role="tabpanel">
								<div class="d-flex align-items-center justify-content-between mb-4">
									<h4 class="mb-0"><?= __('patient_verification') ?></h4>
									<div>
										<button type="button" id="btnEditPatient" class="btn btn-sm btn-outline-primary">
											<i class="fas fa-edit mr-1"></i> <?= __('edit_patient_info') ?>
										</button>
										<button type="button" id="btnCancelEdit" class="btn btn-sm btn-outline-secondary ml-2" style="display:none;">
											<i class="fas fa-times mr-1"></i> <?= __('cancel') ?>
										</button>
									</div>
								</div>
								<div id="verif-alert" class="alert" style="display:none;" role="alert"></div>
								<div id="verif-read">
									<div class="card card-outline card-primary mb-3">
										<div class="card-header py-2"><h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><?= __('identity') ?></h6></div>
										<div class="card-body py-3">
											<div class="row align-items-start">
												<div class="col-12 mb-3">
													<div class="text-muted small text-uppercase font-weight-bold mb-1" style="letter-spacing:.05em;"><?= __('full_name') ?></div>
													<div class="h5 mb-0 font-weight-bold">
														<span id="vr-first_name"><?= htmlspecialchars($p['first_name'] ?? '') ?></span>
														<span id="vr-last_name" class="ml-1"><?= htmlspecialchars($p['last_name'] ?? '') ?></span>
													</div>
												</div>
												<div class="col-6 col-md-3 mb-3">
													<div class="text-muted small text-uppercase font-weight-bold mb-1" style="letter-spacing:.05em;"><?= __('patient_code') ?></div>
													<code class="text-dark" style="font-size:1rem;"><?= htmlspecialchars($p['patient_code'] ?? '') ?></code>
												</div>
												<div class="col-6 col-md-3 mb-3">
													<div class="text-muted small text-uppercase font-weight-bold mb-1" style="letter-spacing:.05em;"><?= __('sex') ?></div>
													<?php
													$_sx = $p['sex'] ?? '';
													$_sxBadge = ['MALE'=>'badge-primary','FEMALE'=>'badge-danger','OTHER'=>'badge-secondary','UNKNOWN'=>'badge-light'];
													$_sxLabel = ['MALE'=>__('sex_male'),'FEMALE'=>__('sex_female'),'OTHER'=>__('sex_other'),'UNKNOWN'=>__('sex_unknown')];
													?>
													<span id="vr-sex" class="badge <?= $_sxBadge[$_sx] ?? 'badge-secondary' ?>" style="font-size:.85rem;">
														<?= isset($_sxLabel[$_sx]) ? $_sxLabel[$_sx] : '<span class="text-muted">&mdash;</span>' ?>
													</span>
												</div>
												<div class="col-6 col-md-3 mb-3">
													<div class="text-muted small text-uppercase font-weight-bold mb-1" style="letter-spacing:.05em;"><?= __('date_of_birth') ?></div>
													<span id="vr-date_of_birth"><?= $p['date_of_birth'] ? date('d M Y', strtotime($p['date_of_birth'])) : '<span class="text-muted">&mdash;</span>' ?></span>
												</div>
												<div class="col-6 col-md-3 mb-3">
													<div class="text-muted small text-uppercase font-weight-bold mb-1" style="letter-spacing:.05em;"><?= __('age_label') ?></div>
													<span id="vr-age_years"><?= ($p['age_years'] !== null && $p['age_years'] !== '') ? (int)$p['age_years'] . ' yrs' : '<span class="text-muted">&mdash;</span>' ?></span>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-3">
											<div class="card card-outline card-info h-100">
												<div class="card-header py-2"><h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><?= __('contact') ?></h6></div>
												<div class="card-body py-3">
													<dl class="row mb-0">
														<dt class="col-4 text-muted font-weight-normal small"><?= __('phone') ?></dt>
														<dd class="col-8 mb-2" id="vr-phone_e164"><?= $p['phone_e164'] ? htmlspecialchars($p['phone_e164']) : '<span class="text-muted">&mdash;</span>' ?></dd>
														<dt class="col-4 text-muted font-weight-normal small"><?= __('email') ?></dt>
														<dd class="col-8 mb-0" id="vr-email"><?= $p['email'] ? htmlspecialchars($p['email']) : '<span class="text-muted">&mdash;</span>' ?></dd>
													</dl>
												</div>
											</div>
										</div>
										<div class="col-md-6 mb-3">
											<div class="card card-outline card-warning h-100">
												<div class="card-header py-2"><h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><?= __('clinical') ?></h6></div>
												<div class="card-body py-3">
													<dl class="row mb-0">
														<dt class="col-5 text-muted font-weight-normal small"><?= __('blood_group') ?></dt>
														<dd class="col-7 mb-0">
															<?php if (!empty($p['blood_group'])): ?>
															<span id="vr-blood_group" class="badge badge-danger" style="font-size:.85rem;"><?= htmlspecialchars($p['blood_group']) ?></span>
															<?php else: ?>
															<span id="vr-blood_group" class="text-muted">&mdash;</span>
															<?php endif; ?>
														</dd>
													</dl>
												</div>
											</div>
										</div>
									</div>
									<div class="card card-outline card-secondary mb-3">
										<div class="card-header py-2"><h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><?= __('address') ?></h6></div>
										<div class="card-body py-3">
											<dl class="row mb-0">
												<dt class="col-md-2 col-3 text-muted font-weight-normal small"><?= __('street') ?></dt>
												<dd class="col-md-10 col-9 mb-2" id="vr-address_line1"><?= !empty($p['address_line1']) ? htmlspecialchars($p['address_line1']) : '<span class="text-muted">&mdash;</span>' ?></dd>
												<dt class="col-md-2 col-3 text-muted font-weight-normal small"><?= __('city') ?></dt>
												<dd class="col-md-4 col-9 mb-2" id="vr-city"><?= !empty($p['city']) ? htmlspecialchars($p['city']) : '<span class="text-muted">&mdash;</span>' ?></dd>
												<dt class="col-md-2 col-3 text-muted font-weight-normal small"><?= __('state') ?></dt>
												<dd class="col-md-4 col-9 mb-2" id="vr-state_province"><?= !empty($p['state_province']) ? htmlspecialchars($p['state_province']) : '<span class="text-muted">&mdash;</span>' ?></dd>
												<dt class="col-md-2 col-3 text-muted font-weight-normal small"><?= __('pin_code') ?></dt>
												<dd class="col-md-4 col-9 mb-0" id="vr-postal_code"><?= !empty($p['postal_code']) ? htmlspecialchars($p['postal_code']) : '<span class="text-muted">&mdash;</span>' ?></dd>
											</dl>
										</div>
									</div>
									<div class="card card-outline card-danger mb-3">
										<div class="card-header py-2"><h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><i class="fas fa-phone-alt mr-1 text-danger"></i><?= __('emergency_contact') ?></h6></div>
										<div class="card-body py-3">
											<dl class="row mb-0">
												<dt class="col-md-2 col-3 text-muted font-weight-normal small"><?= __('name') ?></dt>
												<dd class="col-md-4 col-9 mb-2" id="vr-emergency_contact_name"><?= !empty($p['emergency_contact_name']) ? htmlspecialchars($p['emergency_contact_name']) : '<span class="text-muted">&mdash;</span>' ?></dd>
												<dt class="col-md-2 col-3 text-muted font-weight-normal small"><?= __('phone') ?></dt>
												<dd class="col-md-4 col-9 mb-0" id="vr-emergency_contact_phone"><?= !empty($p['emergency_contact_phone']) ? htmlspecialchars($p['emergency_contact_phone']) : '<span class="text-muted">&mdash;</span>' ?></dd>
											</dl>
										</div>
									</div>
								</div>
								<div id="verif-edit" style="display:none;">
									<input type="hidden" id="ve-patient_id" value="<?= (int)($p['patient_id'] ?? 0) ?>">
									<div class="card card-outline card-primary mb-3">
										<div class="card-header py-2"><h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><?= __('identity') ?></h6></div>
										<div class="card-body py-3">
											<div class="row">
												<div class="col-md-6 mb-3"><label class="small font-weight-bold"><?= __('first_name') ?> <span class="text-danger">*</span></label><input type="text" class="form-control" id="ve-first_name" value="<?= htmlspecialchars($p['first_name'] ?? '') ?>" /></div>
												<div class="col-md-6 mb-3"><label class="small font-weight-bold"><?= __('last_name') ?></label><input type="text" class="form-control" id="ve-last_name" value="<?= htmlspecialchars($p['last_name'] ?? '') ?>" /></div>
												<div class="col-md-3 mb-3">
													<label class="small font-weight-bold"><?= __('sex') ?></label>
													<select class="form-control" id="ve-sex">
														<option value=""><?= __('sex_select') ?></option>
														<option value="MALE"    <?= ($p['sex'] ?? '') === 'MALE'    ? 'selected' : '' ?>><?= __('sex_male') ?></option>
														<option value="FEMALE"  <?= ($p['sex'] ?? '') === 'FEMALE'  ? 'selected' : '' ?>><?= __('sex_female') ?></option>
														<option value="OTHER"   <?= ($p['sex'] ?? '') === 'OTHER'   ? 'selected' : '' ?>><?= __('sex_other') ?></option>
														<option value="UNKNOWN" <?= ($p['sex'] ?? '') === 'UNKNOWN' ? 'selected' : '' ?>><?= __('sex_unknown') ?></option>
													</select>
												</div>
												<div class="col-md-3 mb-3"><label class="small font-weight-bold"><?= __('date_of_birth') ?></label><input type="date" class="form-control" id="ve-date_of_birth" value="<?= htmlspecialchars($p['date_of_birth'] ?? '') ?>" /></div>
												<div class="col-md-3 mb-3"><label class="small font-weight-bold"><?= __('age_years') ?></label><input type="number" class="form-control" id="ve-age_years" min="0" max="150" value="<?= htmlspecialchars($p['age_years'] ?? '') ?>" /></div>
												<div class="col-md-3 mb-0">
													<label class="small font-weight-bold"><?= __('blood_group') ?></label>
													<select class="form-control" id="ve-blood_group">
														<option value=""><?= __('select_blood_group') ?></option>
														<?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
														<option value="<?= $bg ?>" <?= ($p['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
														<?php endforeach; ?>
													</select>
												</div>
											</div>
										</div>
									</div>
									<div class="card card-outline card-info mb-3">
										<div class="card-header py-2"><h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><?= __('contact') ?></h6></div>
										<div class="card-body py-3">
											<div class="row">
												<div class="col-md-4 mb-3 mb-md-0"><label class="small font-weight-bold"><?= __('phone') ?></label><input type="text" class="form-control" id="ve-phone_e164" value="<?= htmlspecialchars($p['phone_e164'] ?? '') ?>" placeholder="+91 98765 43210" /></div>
												<div class="col-md-5 mb-0"><label class="small font-weight-bold"><?= __('email') ?></label><input type="email" class="form-control" id="ve-email" value="<?= htmlspecialchars($p['email'] ?? '') ?>" /></div>
											</div>
										</div>
									</div>
									<div class="card card-outline card-secondary mb-3">
										<div class="card-header py-2"><h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><?= __('address') ?></h6></div>
										<div class="card-body py-3">
											<div class="row">
												<div class="col-md-6 mb-3"><label class="small font-weight-bold"><?= __('street_address') ?></label><input type="text" class="form-control" id="ve-address_line1" value="<?= htmlspecialchars($p['address_line1'] ?? '') ?>" /></div>
												<div class="col-md-3 mb-3"><label class="small font-weight-bold"><?= __('city') ?></label><input type="text" class="form-control" id="ve-city" value="<?= htmlspecialchars($p['city'] ?? '') ?>" /></div>
												<div class="col-md-3 mb-3"><label class="small font-weight-bold"><?= __('state') ?></label><input type="text" class="form-control" id="ve-state_province" value="<?= htmlspecialchars($p['state_province'] ?? '') ?>" /></div>
												<div class="col-md-3 mb-0"><label class="small font-weight-bold"><?= __('pin_code') ?></label><input type="text" class="form-control" id="ve-postal_code" value="<?= htmlspecialchars($p['postal_code'] ?? '') ?>" /></div>
											</div>
										</div>
									</div>
									<div class="card card-outline card-danger mb-3">
										<div class="card-header py-2"><h6 class="mb-0 text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.08em;"><i class="fas fa-phone-alt mr-1 text-danger"></i><?= __('emergency_contact') ?></h6></div>
										<div class="card-body py-3">
											<div class="row">
												<div class="col-md-6 mb-3"><label class="small font-weight-bold"><?= __('contact_name') ?></label><input type="text" class="form-control" id="ve-emergency_contact_name" value="<?= htmlspecialchars($p['emergency_contact_name'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label class="small font-weight-bold"><?= __('contact_phone') ?></label><input type="text" class="form-control" id="ve-emergency_contact_phone" value="<?= htmlspecialchars($p['emergency_contact_phone'] ?? '') ?>" placeholder="+91 98765 43210" /></div>
											</div>
										</div>
									</div>
									<div class="mb-4">
										<button type="button" id="btnSavePatient" class="btn btn-success px-4">
											<i class="fas fa-save mr-1"></i> <?= __('save_changes') ?>
										</button>
									</div>
								</div>
								<div class="tab-navigation mt-2">
									<button type="button" class="btn btn-secondary" disabled><i class="fas fa-chevron-left"></i> <?= __('previous') ?></button>
									<button type="button" class="btn btn-success btn-next-tab px-4" data-target="#tab-personal">
										<i class="fas fa-check-circle mr-1"></i> <?= __('patient_verified_continue') ?> <i class="fas fa-chevron-right ml-1"></i>
									</button>
								</div>
							</div>

							<!-- ── Personal Tab ───────────────────────────── -->
							<div class="tab-pane fade" id="tab-personal" role="tabpanel">
								<form class="intake-auto-save">
									<div class="card card-outline card-primary mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-comment-medical mr-2"></i><?= __('presenting_complaint') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-6 mb-3">
													<label><?= __('symptoms_complaints') ?></label>
													<textarea class="form-control" name="symptoms_complaints" rows="3" data-field="symptoms_complaints"><?= htmlspecialchars($vitals['symptoms_complaints'] ?? '') ?></textarea>
												</div>
												<div class="col-md-6 mb-3">
													<label><?= __('duration_of_symptoms') ?></label>
													<input type="text" class="form-control" name="duration_of_symptoms" data-field="duration_of_symptoms" value="<?= htmlspecialchars($vitals['duration_of_symptoms'] ?? '') ?>" placeholder="<?= __('duration_placeholder') ?>" />
												</div>
											</div>
										</div>
									</div>
									<div class="card card-outline card-info mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-user-tag mr-2"></i><?= __('background_information') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-3 mb-3">
													<label><?= __('medicine_sources') ?></label>
													<select class="form-control" name="medicine_sources" data-field="medicine_sources">
														<option value="NONE"       <?= ($vitals['medicine_sources'] ?? 'NONE') === 'NONE'       ? 'selected' : '' ?>><?= __('none') ?></option>
														<option value="PRIVATE"    <?= ($vitals['medicine_sources'] ?? '') === 'PRIVATE'    ? 'selected' : '' ?>><?= __('private') ?></option>
														<option value="GOVERNMENT" <?= ($vitals['medicine_sources'] ?? '') === 'GOVERNMENT' ? 'selected' : '' ?>><?= __('government') ?></option>
													</select>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('occupation') ?></label>
													<input type="text" class="form-control" name="occupation" data-field="occupation" maxlength="100" placeholder="<?= __('occupation_placeholder') ?>" value="<?= htmlspecialchars($vitals['occupation'] ?? '') ?>" />
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('education') ?></label>
													<input type="text" class="form-control" name="education" data-field="education" maxlength="100" placeholder="<?= __('education_placeholder') ?>" value="<?= htmlspecialchars($vitals['education'] ?? '') ?>" />
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('diet') ?></label>
													<input type="text" class="form-control" name="diet" data-field="diet" maxlength="100" placeholder="<?= __('diet_placeholder') ?>" value="<?= htmlspecialchars($vitals['diet'] ?? '') ?>" />
												</div>
											</div>
										</div>
									</div>
									<div class="card card-outline card-secondary mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-baby mr-2"></i><?= __('reproductive_history') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-3 mb-3">
													<label><?= __('number_of_children') ?></label>
													<input type="number" class="form-control" name="number_of_children" id="number_of_children" data-field="number_of_children" min="0" max="20" value="<?= htmlspecialchars($vitals['number_of_children'] ?? '0') ?>" />
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('uterus') ?></label>
													<select class="form-control" name="has_uterus" id="has_uterus" data-field="has_uterus">
														<option value="1" <?= ($vitals['has_uterus'] ?? '1') === '1' || ($vitals['has_uterus'] ?? 1) === 1 ? 'selected' : '' ?>><?= __('yes') ?></option>
														<option value="0" <?= ($vitals['has_uterus'] ?? '1') === '0' || ($vitals['has_uterus'] ?? 1) === 0 ? 'selected' : '' ?>><?= __('no') ?></option>
													</select>
												</div>
											</div>
											<div id="deliveryDetailsSection" style="display:none;">
												<hr class="my-2">
												<h6 class="mb-3 text-secondary"><i class="fas fa-hospital mr-1"></i><?= __('delivery_details') ?></h6>
												<div class="row">
													<div class="col-md-4 mb-3">
														<label><?= __('type_of_delivery') ?></label>
														<select class="form-control" name="type_of_delivery" data-field="type_of_delivery">
															<option value=""><?= __('select_option') ?></option>
															<option value="LSCS" <?= ($vitals['type_of_delivery'] ?? '') === 'LSCS' ? 'selected' : '' ?>><?= __('delivery_lscs') ?></option>
															<option value="ND" <?= ($vitals['type_of_delivery'] ?? '') === 'ND' ? 'selected' : '' ?>><?= __('delivery_nd') ?></option>
														</select>
													</div>
													<div class="col-md-4 mb-3">
														<label><?= __('delivery_location') ?></label>
														<input type="text" class="form-control" name="delivery_location" data-field="delivery_location" value="<?= htmlspecialchars($vitals['delivery_location'] ?? '') ?>" />
													</div>
													<div class="col-md-4 mb-3">
														<label><?= __('delivery_source') ?></label>
														<select class="form-control" name="delivery_source" data-field="delivery_source">
															<option value=""><?= __('select_option') ?></option>
															<option value="PRIVATE" <?= ($vitals['delivery_source'] ?? '') === 'PRIVATE' ? 'selected' : '' ?>><?= __('private') ?></option>
															<option value="GH" <?= ($vitals['delivery_source'] ?? '') === 'GH' ? 'selected' : '' ?>><?= __('delivery_gh') ?></option>
														</select>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="card card-outline card-info mb-3" id="menstrualDetailsSection" style="display:none;">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-calendar-alt mr-2"></i><?= __('menstrual_details') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-3 mb-3"><label><?= __('age_of_onset') ?></label><input type="number" class="form-control" name="menstrual_age_of_onset" data-field="menstrual_age_of_onset" min="0" max="30" value="<?= htmlspecialchars($vitals['menstrual_age_of_onset'] ?? '') ?>" /><small class="text-muted"><?= __('years') ?></small></div>
												<div class="col-md-3 mb-3"><label><?= __('cycle_frequency') ?></label><input type="number" class="form-control" name="menstrual_cycle_frequency" data-field="menstrual_cycle_frequency" min="0" max="90" value="<?= htmlspecialchars($vitals['menstrual_cycle_frequency'] ?? '') ?>" /><small class="text-muted"><?= __('days') ?></small></div>
												<div class="col-md-3 mb-3"><label><?= __('duration_of_flow') ?></label><input type="number" class="form-control" name="menstrual_duration_of_flow" data-field="menstrual_duration_of_flow" min="0" max="30" value="<?= htmlspecialchars($vitals['menstrual_duration_of_flow'] ?? '') ?>" /><small class="text-muted"><?= __('days') ?></small></div>
												<div class="col-md-3 mb-3"><label><?= __('lmp') ?></label><input type="date" class="form-control" name="menstrual_lmp" data-field="menstrual_lmp" value="<?= htmlspecialchars($vitals['menstrual_lmp'] ?? '') ?>" /></div>
											</div>
											<div class="row">
												<div class="col-md-3 mb-0">
													<label><?= __('mh') ?></label>
													<select class="form-control" name="menstrual_mh" data-field="menstrual_mh">
														<option value=""><?= __('select_option') ?></option>
														<option value="REGULAR" <?= ($vitals['menstrual_mh'] ?? '') === 'REGULAR' ? 'selected' : '' ?>><?= __('regular') ?></option>
														<option value="IRREGULAR" <?= ($vitals['menstrual_mh'] ?? '') === 'IRREGULAR' ? 'selected' : '' ?>><?= __('irregular') ?></option>
													</select>
												</div>
											</div>
										</div>
									</div>
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-verification"><i class="fas fa-chevron-left"></i> Previous</button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-history"><?= __('next') ?> <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ── History Tab ────────────────────────────── -->
							<div class="tab-pane fade" id="tab-history" role="tabpanel">
								<form class="intake-auto-save">
									<div class="card card-outline card-warning mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-file-medical-alt mr-2"></i><?= __('medical_conditions') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-3 mb-3">
													<label><?= __('condition_dm') ?></label>
													<select class="form-control" name="condition_dm" data-field="condition_dm">
														<option value="NO" <?= ($historyData['condition_dm'] ?? '') === 'NO' ? 'selected' : '' ?>><?= __('cond_no') ?></option>
														<option value="CURRENT" <?= ($historyData['condition_dm'] ?? '') === 'CURRENT' ? 'selected' : '' ?>><?= __('cond_current') ?></option>
														<option value="PAST" <?= ($historyData['condition_dm'] ?? '') === 'PAST' ? 'selected' : '' ?>><?= __('cond_past') ?></option>
													</select>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('condition_htn') ?></label>
													<select class="form-control" name="condition_htn" data-field="condition_htn">
														<option value="NO" <?= ($historyData['condition_htn'] ?? '') === 'NO' ? 'selected' : '' ?>><?= __('cond_no') ?></option>
														<option value="CURRENT" <?= ($historyData['condition_htn'] ?? '') === 'CURRENT' ? 'selected' : '' ?>><?= __('cond_current') ?></option>
														<option value="PAST" <?= ($historyData['condition_htn'] ?? '') === 'PAST' ? 'selected' : '' ?>><?= __('cond_past') ?></option>
													</select>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('condition_tsh') ?></label>
													<select class="form-control" name="condition_tsh" data-field="condition_tsh">
														<option value="NO" <?= ($historyData['condition_tsh'] ?? '') === 'NO' ? 'selected' : '' ?>><?= __('cond_no') ?></option>
														<option value="CURRENT" <?= ($historyData['condition_tsh'] ?? '') === 'CURRENT' ? 'selected' : '' ?>><?= __('cond_current') ?></option>
														<option value="PAST" <?= ($historyData['condition_tsh'] ?? '') === 'PAST' ? 'selected' : '' ?>><?= __('cond_past') ?></option>
													</select>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('condition_heart_disease') ?></label>
													<select class="form-control" name="condition_heart_disease" data-field="condition_heart_disease">
														<option value="NO" <?= ($historyData['condition_heart_disease'] ?? '') === 'NO' ? 'selected' : '' ?>><?= __('cond_no') ?></option>
														<option value="CURRENT" <?= ($historyData['condition_heart_disease'] ?? '') === 'CURRENT' ? 'selected' : '' ?>><?= __('cond_current') ?></option>
														<option value="PAST" <?= ($historyData['condition_heart_disease'] ?? '') === 'PAST' ? 'selected' : '' ?>><?= __('cond_past') ?></option>
													</select>
												</div>
											</div>
											<div class="row">
												<div class="col-md-6 mb-3"><label><?= __('other_conditions') ?></label><textarea class="form-control" name="condition_others" data-field="condition_others" rows="2"><?= htmlspecialchars($historyData['condition_others'] ?? '') ?></textarea></div>
												<div class="col-md-6 mb-0"><label><?= __('surgical_history') ?></label><textarea class="form-control" name="surgical_history" data-field="surgical_history" rows="2"><?= htmlspecialchars($historyData['surgical_history'] ?? '') ?></textarea></div>
											</div>
										</div>
									</div>
									<div class="card card-outline card-danger mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-allergies mr-2"></i><?= __('allergies') ?></h5>
										</div>
										<div class="card-body">
											<div class="mb-3">
												<div class="custom-control custom-checkbox">
													<input type="checkbox" class="custom-control-input" id="noKnownAllergies"
														name="no_known_allergies" data-field="no_known_allergies" value="1"
														<?= !empty($historyData['no_known_allergies']) ? 'checked' : '' ?> />
													<label class="custom-control-label font-weight-bold text-danger" for="noKnownAllergies"><?= __('no_known_allergies') ?></label>
													<small class="text-muted ml-2"><?= __('no_known_allergies_hint') ?></small>
												</div>
											</div>
											<div id="allergySection"<?= !empty($historyData['no_known_allergies']) ? ' style="display:none;"' : '' ?>>
												<div class="d-flex font-weight-bold small text-muted mb-1 px-1">
													<span class="flex-fill mr-2"><?= __('allergy_substance') ?></span>
													<span class="flex-fill mr-2"><?= __('allergy_reaction') ?></span>
													<span style="width:42px;"></span>
												</div>
												<div id="allergyRows"></div>
												<button type="button" id="btnAddAllergy" class="btn btn-sm btn-outline-secondary mt-2">
													<i class="fas fa-plus mr-1"></i> <?= __('add_another_allergy') ?>
												</button>
											</div>
										</div>
									</div>

									<div class="card card-outline card-success mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-users mr-2"></i><?= __('family_history') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-4 mb-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="fh_cancer" name="family_history_cancer" data-field="family_history_cancer" value="1" <?= !empty($historyData['family_history_cancer']) ? 'checked' : '' ?> /><label class="custom-control-label" for="fh_cancer"><?= __('fh_cancer') ?></label></div></div>
												<div class="col-md-4 mb-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="fh_tb" name="family_history_tuberculosis" data-field="family_history_tuberculosis" value="1" <?= !empty($historyData['family_history_tuberculosis']) ? 'checked' : '' ?> /><label class="custom-control-label" for="fh_tb"><?= __('fh_tuberculosis') ?></label></div></div>
												<div class="col-md-4 mb-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="fh_diabetes" name="family_history_diabetes" data-field="family_history_diabetes" value="1" <?= !empty($historyData['family_history_diabetes']) ? 'checked' : '' ?> /><label class="custom-control-label" for="fh_diabetes"><?= __('fh_diabetes') ?></label></div></div>
											</div>
											<div class="row">
												<div class="col-md-4 mb-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="fh_bp" name="family_history_bp" data-field="family_history_bp" value="1" <?= !empty($historyData['family_history_bp']) ? 'checked' : '' ?> /><label class="custom-control-label" for="fh_bp"><?= __('fh_bp') ?></label></div></div>
												<div class="col-md-4 mb-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="fh_thyroid" name="family_history_thyroid" data-field="family_history_thyroid" value="1" <?= !empty($historyData['family_history_thyroid']) ? 'checked' : '' ?> /><label class="custom-control-label" for="fh_thyroid"><?= __('fh_thyroid') ?></label></div></div>
											</div>
											<div class="row">
												<div class="col-md-12 mb-0"><label><?= __('other_family_history') ?></label><input type="text" class="form-control" name="family_history_other" data-field="family_history_other" value="<?= htmlspecialchars($historyData['family_history_other'] ?? '') ?>" /></div>
											</div>
										</div>
									</div>
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-personal"><i class="fas fa-chevron-left"></i> Previous</button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-general">Next <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ── General Tab ────────────────────────────── -->
							<div class="tab-pane fade" id="tab-general" role="tabpanel">
								<form class="intake-auto-save">
									<div class="card card-outline card-danger mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-heartbeat mr-2"></i><?= __('vital_signs') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-3 mb-3">
													<label><?= __('pulse') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="general_pulse" data-field="general_pulse" min="0" max="300" value="<?= htmlspecialchars($vitals['general_pulse'] ?? $vitals['pulse'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">/mt</span></div>
													</div>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('bp') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="general_bp_systolic" data-field="general_bp_systolic" min="0" max="300" placeholder="Sys" value="<?= htmlspecialchars($vitals['general_bp_systolic'] ?? $vitals['bp_systolic'] ?? '') ?>" />
														<div class="input-group-append input-group-prepend"><span class="input-group-text">/</span></div>
														<input type="number" class="form-control" name="general_bp_diastolic" data-field="general_bp_diastolic" min="0" max="200" placeholder="Dia" value="<?= htmlspecialchars($vitals['general_bp_diastolic'] ?? $vitals['bp_diastolic'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">mmHg</span></div>
													</div>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('spo2') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="spo2" data-field="spo2" min="50" max="100" value="<?= htmlspecialchars($vitals['spo2'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">%</span></div>
													</div>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('temperature') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="temperature" data-field="temperature" min="90" max="110" step="0.1" value="<?= htmlspecialchars($vitals['temperature'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">&deg;F</span></div>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="card card-outline card-primary mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-user-md mr-2"></i><?= __('physical_examination') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-6 mb-3"><label><?= __('heart') ?></label><input type="text" class="form-control" name="general_heart" data-field="general_heart" value="<?= htmlspecialchars($vitals['general_heart'] ?? '') ?>" /></div>
												<div class="col-md-6 mb-3"><label><?= __('lungs') ?></label><input type="text" class="form-control" name="general_lungs" data-field="general_lungs" value="<?= htmlspecialchars($vitals['general_lungs'] ?? '') ?>" /></div>
											</div>
											<div class="row">
												<div class="col-md-6 mb-3"><label><?= __('liver') ?></label><input type="text" class="form-control" name="general_liver" data-field="general_liver" value="<?= htmlspecialchars($vitals['general_liver'] ?? '') ?>" /></div>
												<div class="col-md-6 mb-3"><label><?= __('spleen') ?></label><input type="text" class="form-control" name="general_spleen" data-field="general_spleen" value="<?= htmlspecialchars($vitals['general_spleen'] ?? '') ?>" /></div>
											</div>
											<div class="row">
												<div class="col-md-6 mb-0"><label><?= __('lymph_glands') ?></label><input type="text" class="form-control" name="general_lymph_glands" data-field="general_lymph_glands" value="<?= htmlspecialchars($vitals['general_lymph_glands'] ?? '') ?>" /></div>
											</div>
										</div>
									</div>
									<div class="card card-outline card-success mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-weight mr-2"></i><?= __('anthropometric_measurements') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-3 mb-3">
													<label><?= __('height') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="general_height" data-field="general_height" min="0" max="300" value="<?= htmlspecialchars($vitals['general_height'] ?? $vitals['height_cm'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">cm</span></div>
													</div>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('weight') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="general_weight" data-field="general_weight" min="0" max="500" step="0.1" value="<?= htmlspecialchars($vitals['general_weight'] ?? $vitals['weight_kg'] ?? '') ?>" />
														<div class="input-group-append"><span class="input-group-text">kgs</span></div>
													</div>
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('bmi') ?></label>
													<input type="number" class="form-control" name="general_bmi" data-field="general_bmi" min="0" max="100" step="0.1" value="<?= htmlspecialchars($vitals['general_bmi'] ?? '') ?>" />
												</div>
												<div class="col-md-3 mb-3">
													<label><?= __('obesity_overweight') ?></label>
													<select class="form-control" name="general_obesity_overweight" data-field="general_obesity_overweight">
														<option value="0" <?= ($vitals['general_obesity_overweight'] ?? '0') == '0' ? 'selected' : '' ?>><?= __('no') ?></option>
														<option value="1" <?= ($vitals['general_obesity_overweight'] ?? '0') == '1' ? 'selected' : '' ?>><?= __('yes') ?></option>
													</select>
												</div>
											</div>
										</div>
									</div>
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-history"><i class="fas fa-chevron-left"></i> Previous</button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-examinations">Next <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ── Examinations Tab ────────────────────────── -->
							<div class="tab-pane fade" id="tab-examinations" role="tabpanel">
								<form class="intake-auto-save">
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
												<div class="col-md-4 mb-3"><label><?= __('hypo') ?></label><input type="text" class="form-control" name="exam_hypo" data-field="exam_hypo" value="<?= htmlspecialchars($examData['exam_hypo'] ?? '') ?>" /></div>
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
									<div class="card card-outline card-warning mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-search mr-2"></i><?= __('breasts') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-4 mb-3"><label><?= __('left') ?></label><input type="text" class="form-control" name="exam_breast_left" data-field="exam_breast_left" value="<?= htmlspecialchars($examData['exam_breast_left'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label><?= __('right') ?></label><input type="text" class="form-control" name="exam_breast_right" data-field="exam_breast_right" value="<?= htmlspecialchars($examData['exam_breast_right'] ?? '') ?>" /></div>
												<div class="col-md-4 mb-3"><label><?= __('axillary_nodes') ?></label><input type="text" class="form-control" name="exam_breast_axillary_nodes" data-field="exam_breast_axillary_nodes" value="<?= htmlspecialchars($examData['exam_breast_axillary_nodes'] ?? '') ?>" /></div>
											</div>
											<div class="row">
												<div class="col-12">
													<label class="d-block"><?= __('breast_exam_diagram') ?></label>
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
									<div class="card card-outline card-info mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-female mr-2"></i><?= __('pelvic_exam_introitus') ?></h5>
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
													<label class="d-block"><?= __('pelvic_exam_diagram') ?></label>
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
									<div class="card card-outline card-secondary mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-notes-medical mr-2"></i><?= __('rectal_examination') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-6 mb-3"><label><?= __('skin') ?></label><input type="text" class="form-control" name="exam_rectal_skin" data-field="exam_rectal_skin" value="<?= htmlspecialchars($examData['exam_rectal_skin'] ?? '') ?>" /></div>
												<div class="col-md-6 mb-0"><label><?= __('remarks') ?></label><input type="text" class="form-control" name="exam_rectal_remarks" data-field="exam_rectal_remarks" value="<?= htmlspecialchars($examData['exam_rectal_remarks'] ?? '') ?>" /></div>
											</div>
										</div>
									</div>
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
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-general"><i class="fas fa-chevron-left"></i> Previous</button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-labs">Next <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ── Labs Tab ───────────────────────────────── -->
							<div class="tab-pane fade" id="tab-labs" role="tabpanel">
								<form class="intake-auto-save">
									<div class="card card-outline card-success mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-vial mr-2"></i><?= __('investigations') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-3 mb-3">
													<label><?= __('lab_hb') ?></label>
													<div class="input-group">
														<input type="number" class="form-control" name="lab_hb_percentage" data-field="lab_hb_percentage" min="0" max="100" value="<?= htmlspecialchars($labData['lab_hb_percentage'] ?? '') ?>" placeholder="%" />
														<div class="input-group-append"><span class="input-group-text">%</span></div>
														<input type="number" class="form-control" name="lab_hb_gms" data-field="lab_hb_gms" min="0" max="30" step="0.1" value="<?= htmlspecialchars($labData['lab_hb_gms'] ?? '') ?>" placeholder="gms" />
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
									<div class="card card-outline card-info mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-microscope mr-2"></i><?= __('cytology_report') ?></h5>
										</div>
										<div class="card-body">
											<div class="row">
												<div class="col-md-6 mb-3">
													<label><?= __('papsmear') ?></label>
													<select class="form-control" name="cytology_papsmear" data-field="cytology_papsmear">
														<option value="NONE" <?= ($labData['cytology_papsmear'] ?? '') === 'NONE' ? 'selected' : '' ?>><?= __('none') ?></option>
														<option value="DONE" <?= ($labData['cytology_papsmear'] ?? '') === 'DONE' ? 'selected' : '' ?>><?= __('done') ?></option>
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
														<option value="NONE" <?= ($labData['cytology_colposcopy'] ?? '') === 'NONE' ? 'selected' : '' ?>><?= __('none') ?></option>
														<option value="DONE" <?= ($labData['cytology_colposcopy'] ?? '') === 'DONE' ? 'selected' : '' ?>><?= __('done') ?></option>
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
														<option value="NONE" <?= ($labData['cytology_biopsy'] ?? '') === 'NONE' ? 'selected' : '' ?>><?= __('none') ?></option>
														<option value="DONE" <?= ($labData['cytology_biopsy'] ?? '') === 'DONE' ? 'selected' : '' ?>><?= __('done') ?></option>
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
								</form>

								<!-- ── Lab Orders Card ───────────────────────────────────── -->
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
										<?php if (empty($labOrders)): ?>
										<div class="text-center text-muted py-4" id="noLabOrders">
											<i class="fas fa-flask fa-2x mb-2 d-block" style="opacity:.4"></i><?= __('no_lab_orders') ?>
										</div>
										<?php else: ?>
										<?php endif; ?>
										<table class="table table-sm mb-0<?= empty($labOrders) ? ' d-none' : '' ?>" id="labOrdersTable">
											<thead class="thead-light">
												<tr>
													<th><?= __('col_test') ?></th>
													<th><?= __('col_notes') ?></th>
													<th><?= __('col_ordered_by') ?></th>
													<th><?= __('col_status') ?></th>
													<th><?= __('col_ordered') ?></th>
												</tr>
											</thead>
											<tbody id="labOrdersBody">
												<?php foreach ($labOrders ?? [] as $lo): ?>
												<tr>
													<td><?= htmlspecialchars($lo['test_name']) ?></td>
													<td class="text-muted"><?= $lo['order_notes'] ? htmlspecialchars($lo['order_notes']) : '&mdash;' ?></td>
													<td><?= htmlspecialchars(trim($lo['ordered_by_first'] . ' ' . $lo['ordered_by_last'])) ?></td>
													<td>
														<?php if ($lo['status'] === 'COMPLETED'): ?>
														<span class="badge badge-success"><?= __('status_completed') ?></span>
														<?php else: ?>
														<span class="badge badge-warning"><?= __('status_pending') ?></span>
														<?php endif; ?>
													</td>
													<td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($lo['ordered_at']))) ?></td>
												</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>

								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-examinations"><i class="fas fa-chevron-left"></i> Previous</button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-summary">Next <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ── Summary Tab ────────────────────────────── -->
							<div class="tab-pane fade" id="tab-summary" role="tabpanel">
								<form class="intake-auto-save">
									<div class="card card-outline card-warning mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-clipboard-check mr-2"></i><?= __('assessment_disposition') ?></h5>
										</div>
										<div class="card-body">
											<div class="form-group">
												<label><?= __('risk_level') ?></label>
												<textarea class="form-control" name="summary_risk_level" data-field="summary_risk_level" rows="3"><?= htmlspecialchars($summaryData['summary_risk_level'] ?? '') ?></textarea>
											</div>
											<div class="form-group">
												<label><?= __('referral') ?></label>
												<textarea class="form-control" name="summary_referral" data-field="summary_referral" rows="3"><?= htmlspecialchars($summaryData['summary_referral'] ?? '') ?></textarea>
											</div>
											<div class="form-group mb-0">
												<label><?= __('patient_acceptance') ?></label>
												<textarea class="form-control" name="summary_patient_acceptance" data-field="summary_patient_acceptance" rows="3"><?= htmlspecialchars($summaryData['summary_patient_acceptance'] ?? '') ?></textarea>
											</div>
										</div>
									</div>
									<div class="card card-outline card-primary mb-3">
										<div class="card-header">
											<h5 class="card-title mb-0"><i class="fas fa-stethoscope mr-2"></i><?= __('doctors_summary') ?></h5>
										</div>
										<div class="card-body">
											<div class="form-group mb-0">
												<label><?= __('summary_recommendations') ?></label>
												<textarea class="form-control" name="summary_doctor_summary" data-field="summary_doctor_summary" rows="5"><?= htmlspecialchars($summaryData['summary_doctor_summary'] ?? '') ?></textarea>
											</div>
										</div>
									</div>
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-labs"><i class="fas fa-chevron-left"></i> Previous</button>
									<form method="post" action="intake.php?action=complete" style="display:inline">
										<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
										<input type="hidden" name="case_sheet_id" value="<?= $csId ?>" />
										<button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check-circle mr-1"></i><?= __('complete_intake') ?></button>
									</form>
								</div>
							</div>


						</div>
					</div>
				</div>
				<?php endif; ?>

			</div>
		</section>
	</div>

	<footer class="main-footer text-sm"><strong>CareSystem</strong> &middot; <?= __('page_title') ?></footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script>
(function () {
	var csrfToken = <?= json_encode($_SESSION['csrf_token']) ?>;
	var allergySubstancePlaceholder = <?= json_encode(__('allergy_substance')) ?>;
	var allergyReactionPlaceholder  = <?= json_encode(__('allergy_reaction')) ?>;
	var caseSheetId = <?= json_encode(!empty($caseSheet) ? (int)$caseSheet['case_sheet_id'] : null) ?>;

	// ── Tab navigation ──────────────────────────────────
	$(document).on('click', '.btn-next-tab', function () {
		var target = $(this).data('target');
		$('#intakeTabs a[href="' + target + '"]').tab('show');
		window.scrollTo(0, 0);
	});

	// ── Unsaved-navigation guard ───────────────────────
	if (caseSheetId) {
		var formDirty = false;
		$('.intake-auto-save').on('change input', 'input, select, textarea', function () {
			formDirty = true;
		});
		$(window).on('beforeunload', function (e) {
			if (formDirty) {
				e.preventDefault();
				return '';
			}
		});
		// Allow the "Complete Intake" form submit through
		$('form[action="intake.php?action=complete"]').on('submit', function () {
			formDirty = false;
		});
	}

	// ── Auto-save for Step 2 form fields ────────────────
	if (caseSheetId) {
		var saveTimeout = {};
		var pendingSaves = 0;
		var $indicator = $('#autoSaveIndicator');

		function autoSave(field, value) {
			pendingSaves++;
			$indicator.removeClass('error').addClass('saving').text('Saving...').fadeIn();
			$.ajax({
				url: 'update_case_sheet.php',
				method: 'POST',
				contentType: 'application/json',
				data: JSON.stringify({ case_sheet_id: caseSheetId, field: field, value: value, csrf_token: csrfToken }),
				dataType: 'json',
				success: function (r) {
					if (r.success) {
						$indicator.removeClass('saving').html('<i class="fas fa-check-circle"></i> Saved').fadeIn();
						setTimeout(function () { $indicator.fadeOut(); }, 1500);
						pendingSaves--;
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

		$('.intake-auto-save').on('change', 'input, select, textarea', function () {
			var field = $(this).data('field');
			if (!field) return;
			var value;
			if ($(this).is(':checkbox')) {
				value = this.checked ? '1' : '0';
			} else {
				value = $(this).val();
			}
			clearTimeout(saveTimeout[field]);
			saveTimeout[field] = setTimeout(function () { autoSave(field, value); }, 500);
		});

		$('.intake-auto-save').on('input', 'textarea', function () {
			var field = $(this).data('field');
			if (!field) return;
			var value = $(this).val();
			clearTimeout(saveTimeout[field]);
			saveTimeout[field] = setTimeout(function () { autoSave(field, value); }, 1000);
		});

		// Conditional sections
		function toggleDelivery() {
			var n = parseInt($('#number_of_children').val()) || 0;
			$('#deliveryDetailsSection')[n > 0 ? 'slideDown' : 'slideUp']();
		}
		function toggleMenstrual() {
			$('#menstrualDetailsSection')[$('#has_uterus').val() === '1' ? 'slideDown' : 'slideUp']();
		}
		toggleDelivery(); toggleMenstrual();
		$('#number_of_children').on('change', toggleDelivery);
		$('#has_uterus').on('change', toggleMenstrual);

		// ── Allergy row management (History tab) ─────────
		function escHtml(s) {
			return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
		}

		function saveAllergiesJSON() {
			var rows = [];
			document.querySelectorAll('#allergyRows .allergy-row').forEach(function(row) {
				var al = row.querySelector('.allergy-name').value.trim();
				var rx = row.querySelector('.allergy-reaction').value.trim();
				if (al || rx) rows.push({ allergy: al, reaction: rx });
			});
			autoSave('allergies_json', JSON.stringify(rows));
		}

		function wireAllergyRow(row) {
			row.querySelectorAll('input').forEach(function(inp) {
				inp.addEventListener('change', saveAllergiesJSON);
				inp.addEventListener('input', function() {
					clearTimeout(inp._st);
					inp._st = setTimeout(saveAllergiesJSON, 700);
				});
			});
		}

		function addAllergyRow(allergy, reaction) {
			var row = document.createElement('div');
			row.className = 'allergy-row d-flex align-items-center mb-2';
			row.innerHTML =
				'<input type="text" class="form-control allergy-name mr-2" placeholder="' + allergySubstancePlaceholder + '" value="' + escHtml(allergy || '') + '">' +
				'<input type="text" class="form-control allergy-reaction mr-2" placeholder="' + allergyReactionPlaceholder + '" value="' + escHtml(reaction || '') + '">' +
				'<button type="button" class="btn btn-sm btn-outline-danger remove-allergy-btn" title="Remove"><i class="fas fa-times"></i></button>';
			document.getElementById('allergyRows').appendChild(row);
			row.querySelector('.remove-allergy-btn').addEventListener('click', function() {
				row.remove();
				saveAllergiesJSON();
			});
			wireAllergyRow(row);
		}

		// Initialise allergy rows from saved JSON
		(function() {
			var raw = <?= json_encode(!empty($historyData['allergies_json']) ? $historyData['allergies_json'] : '') ?>;
			var rows = [];
			if (raw) { try { rows = JSON.parse(raw); } catch(e) {} }
			if (rows.length > 0) {
				rows.forEach(function(r) { addAllergyRow(r.allergy || '', r.reaction || ''); });
			} else {
				addAllergyRow('', ''); // start with one empty row
			}
		})();

		document.getElementById('btnAddAllergy').addEventListener('click', function() {
			addAllergyRow('', '');
		});

		// No known allergies toggle
		document.getElementById('noKnownAllergies').addEventListener('change', function() {
			var checked = this.checked;
			document.getElementById('allergySection').style.display = checked ? 'none' : '';
			autoSave('no_known_allergies', checked ? '1' : '');
		});

		// ── Lab order modal ─────────────────────────────
		(function () {
			var $modal      = $('#labOrderModal');
			var $filter     = $('#labTestFilter');
			var $error      = $('#labOrderError');
			var $submitBtn  = $('#btnSubmitLabOrder');
			var $notes      = $('#labOrderNotes');
			var $noOrders   = $('#noLabOrders');
			var $table      = $('#labOrdersTable');
			var $tbody      = $('#labOrdersBody');

			// Filter test checkboxes by text
			$filter.on('input', function () {
				var q = this.value.toLowerCase();
				$modal.find('.lab-test-item').each(function () {
					var match = $(this).find('label').text().toLowerCase().indexOf(q) !== -1;
					$(this).toggle(q === '' || match);
				});
				$modal.find('.lab-category-group').each(function () {
					$(this).toggle($(this).find('.lab-test-item:visible').length > 0);
				});
			});

			// Reset modal state on close
			$modal.on('hidden.bs.modal', function () {
				$modal.find('.lab-test-cb').prop('checked', false);
				$notes.val('');
				$filter.val('').trigger('input');
				$error.addClass('d-none').text('');
				$submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i>Submit Order');
			});

			// Submit order
			$submitBtn.on('click', function () {
				var tests = $modal.find('.lab-test-cb:checked').map(function () { return this.value; }).get();
				if (!tests.length) {
					$error.text('Please select at least one test.').removeClass('d-none');
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
						tests: tests,
						notes: $notes.val().trim()
					}),
					dataType: 'json',
					success: function (r) {
						if (!r.success) {
							$error.text(r.message || 'Failed to submit.').removeClass('d-none');
							$submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i>Submit Order');
							return;
						}
						$modal.modal('hide');
						$noOrders.hide();
						$table.removeClass('d-none');
						var noteTd = r.notes ? escHtml(r.notes) : '<em class="text-muted">—</em>';
						var by     = escHtml(r.ordered_by || 'You');
						var now    = new Date().toLocaleString('en-US', {month:'short',day:'numeric',year:'numeric',hour:'numeric',minute:'2-digit',hour12:true});
						r.orders.forEach(function (o) {
							$tbody.prepend(
								'<tr>' +
								'<td>' + escHtml(o.test_name) + '</td>' +
								'<td>' + noteTd + '</td>' +
								'<td>' + by + '</td>' +
								'<td><span class="badge badge-warning">Pending</span></td>' +
								'<td>' + now + '</td>' +
								'</tr>'
							);
						});
					},
					error: function () {
						$error.text('Server error. Please try again.').removeClass('d-none');
						$submitBtn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i>Submit Order');
					}
				});
			});
		})();

	}

	// ── Step 1: Patient search & registration ───────────
	if (!caseSheetId) {
		var searchTimeout = null;
		var $search = $('#patientSearch'), $results = $('#searchResults'), $selected = $('#selectedPatient'), $patientId = $('#patientIdField');

		$search.on('input', function () {
			clearTimeout(searchTimeout);
			var q = $.trim(this.value);
			if (q.length < 2) { $results.hide().empty(); return; }
			searchTimeout = setTimeout(function () {
				$.getJSON('intake.php?action=patient-search&q=' + encodeURIComponent(q), function (data) {
					$results.empty();
					if (!data.success || !data.patients.length) { $results.append('<div class="list-group-item text-muted">No patients found</div>').show(); return; }
					data.patients.forEach(function (p) {
						var label = p.first_name + ' ' + (p.last_name || '') + ' (' + p.patient_code + ')' + (p.sex && p.sex !== 'UNKNOWN' ? ' &middot; ' + p.sex : '') + (p.age_years ? ' &middot; ' + p.age_years + 'y' : '') + (p.phone_e164 ? ' &middot; ' + p.phone_e164 : '');
						$results.append('<a href="#" class="list-group-item list-group-item-action" data-patient=\'' + JSON.stringify(p).replace(/'/g, '&#39;') + '\'>' + label + '</a>');
					});
					$results.show();
				});
			}, 300);
		});
		$results.on('click', 'a', function (e) { e.preventDefault(); selectPatient($(this).data('patient')); });

		function selectPatient(p) {
			$patientId.val(p.patient_id);
			$('#selectedPatientName').text(p.first_name + ' ' + (p.last_name || ''));
			$('#selectedPatientCode').text(p.patient_code);
			$('#selectedPatientSex').text(p.sex && p.sex !== 'UNKNOWN' ? p.sex : '');
			$('#selectedPatientAge').text(p.age_years ? ' | ' + p.age_years + ' years' : '');
			$('#selectedPatientPhone').text(p.phone_e164 ? ' | ' + p.phone_e164 : '');
			$selected.removeClass('d-none'); $results.hide().empty();
			$search.val('').prop('disabled', true); $('#toggleNewPatient').prop('disabled', true); $('#newPatientSection').addClass('d-none');
		}
		$('#clearPatient').on('click', function () { $patientId.val(''); $selected.addClass('d-none'); $search.prop('disabled', false).val('').focus(); $('#toggleNewPatient').prop('disabled', false); });
		$(document).on('click', function (e) { if (!$(e.target).closest('#patientSearch, #searchResults').length) $results.hide(); });
		$('#toggleNewPatient').on('click', function () { $('#newPatientSection').toggleClass('d-none'); });

		$('#registerPatientBtn').on('click', function () {
			var $btn = $(this), $err = $('#registerError'); $err.addClass('d-none');
			var firstName = $.trim($('#newFirstName').val());
			if (!firstName) { $err.text('First name is required.').removeClass('d-none'); return; }
			$btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Registering...');
			$.post('intake.php?action=register-patient', { csrf_token: csrfToken, first_name: firstName, last_name: $.trim($('#newLastName').val()), sex: $('#newSex').val(), date_of_birth: $('#newDob').val(), age_years: $('#newAge').val(), phone_e164: $.trim($('#newPhone').val()) }, function (data) {
				$btn.prop('disabled', false).html('<i class="fas fa-user-plus mr-1"></i>Register & Select');
				if (data.success) { selectPatient({ patient_id: data.patient_id, patient_code: data.patient_code, first_name: data.first_name, last_name: data.last_name || '', sex: $('#newSex').val(), age_years: $('#newAge').val() || null, phone_e164: $.trim($('#newPhone').val()) || null }); $('#newFirstName, #newLastName, #newDob, #newAge, #newPhone').val(''); $('#newSex').val('UNKNOWN'); }
				else { $err.text(data.message || 'Registration failed.').removeClass('d-none'); }
			}, 'json').fail(function () { $btn.prop('disabled', false).html('<i class="fas fa-user-plus mr-1"></i>Register & Select'); $err.text('Server error.').removeClass('d-none'); });
		});

		$('#intakeCreateForm').on('submit', function (e) { if (!$patientId.val()) { e.preventDefault(); alert('Please select or register a patient first.'); } });
	}
})();
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
				<div class="form-group mb-3">
					<label class="font-weight-bold"><?= __('filter_tests') ?></label>
					<input type="text" class="form-control" id="labTestFilter" placeholder="<?= __('filter_placeholder') ?>" autocomplete="off" />
				</div>
				<div id="labTestList" style="max-height:380px;overflow-y:auto;border:1px solid #dee2e6;border-radius:4px;padding:12px;">
<?php
$_labCategories = [
	'Hematology' => [
		'Complete Blood Count (CBC)',
		'Peripheral Blood Smear',
		'Hemoglobin (Hb)',
		'Erythrocyte Sedimentation Rate (ESR)',
		'Reticulocyte Count',
	],
	'Chemistry' => [
		'Basic Metabolic Panel (BMP)',
		'Comprehensive Metabolic Panel (CMP)',
		'Liver Function Tests (LFT)',
		'Kidney Function Tests (KFT)',
		'Lipid Panel',
		'Serum Electrolytes (Na/K/Cl/HCO₃)',
		'Serum Calcium',
		'Serum Magnesium',
		'Serum Phosphorus',
		'Serum Uric Acid',
		'Blood Urea Nitrogen (BUN)',
		'Serum Creatinine',
		'Serum Amylase',
		'Serum Lipase',
	],
	'Endocrinology' => [
		'Thyroid Panel (TSH, Free T3, Free T4)',
		'Hemoglobin A1C (HbA1c)',
		'Fasting Blood Glucose (FBG)',
		'FSH / LH / Estradiol',
		'Testosterone',
		'Prolactin',
		'Cortisol (Morning)',
		'ACTH Stimulation Test',
	],
	'Vitamins &amp; Micronutrients' => [
		'Serum Iron / TIBC / Ferritin',
		'Vitamin B12',
		'Vitamin D (25-OH)',
		'Serum Folate',
	],
	'Coagulation' => [
		'Prothrombin Time / INR (PT/INR)',
		'Partial Thromboplastin Time (PTT)',
		'D-Dimer',
	],
	'Cardiac Markers' => [
		'Troponin I',
		'BNP / NT-proBNP',
	],
	'Inflammation &amp; Immunology' => [
		'C-Reactive Protein (CRP)',
		'Antistreptolysin O Titer (ASO)',
		'Rheumatoid Factor (RF)',
		'ANA (Antinuclear Antibody)',
		'Anti-dsDNA',
	],
	'Infectious Disease' => [
		'Blood Culture &amp; Sensitivity',
		'Urine Culture &amp; Sensitivity',
		'Sputum Culture',
		'HIV Rapid Test',
		'Hepatitis B Surface Antigen (HBsAg)',
		'Hepatitis C Antibody (Anti-HCV)',
		'Malaria Rapid Diagnostic Test (RDT)',
		'Dengue NS1 / IgM / IgG',
		'COVID-19 Rapid Antigen Test',
		'Sputum AFB Smear (TB)',
	],
	'Urinalysis &amp; Stool' => [
		'Urinalysis (UA)',
		'Stool Analysis',
	],
	'Gynecology &amp; Cytology' => [
		'Papanicolaou (Pap) Smear',
		'Colposcopy',
		'Cervical Biopsy',
		'Beta-hCG (Pregnancy Test)',
	],
	'Other' => [
		'Arterial Blood Gas (ABG)',
	],
];
$_labIdx = 0;
foreach ($_labCategories as $_labCat => $_labTests): ?>
					<div class="lab-category-group mb-3">
						<div class="font-weight-bold text-uppercase small text-muted mb-1" style="letter-spacing:.05em;"><?= $_labCat ?></div>
						<?php foreach ($_labTests as $_labTest): $_labIdx++; ?>
						<div class="lab-test-item custom-control custom-checkbox">
							<input type="checkbox" class="custom-control-input lab-test-cb" id="lt<?= $_labIdx ?>" value="<?= htmlspecialchars($_labTest) ?>">
							<label class="custom-control-label" for="lt<?= $_labIdx ?>"><?= htmlspecialchars($_labTest) ?></label>
						</div>
						<?php endforeach; ?>
					</div>
<?php endforeach; ?>
				</div>
				<div class="form-group mt-3 mb-1">
					<label class="font-weight-bold" for="labOrderNotes"><?= __('order_notes_label') ?> <small class="font-weight-normal text-muted"><?= __('order_notes_hint') ?></small></label>
					<textarea class="form-control" id="labOrderNotes" rows="2" placeholder="<?= __('order_notes_placeholder') ?>"></textarea>
				</div>
				<div class="alert alert-danger d-none mt-2 mb-0" id="labOrderError"></div>
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

<!-- ── Diagram Editor Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="diagramEditorModal" tabindex="-1" role="dialog" aria-labelledby="diagramEditorTitle" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="diagramEditorTitle"><?= __('diagram_editor') ?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<!-- Toolbar -->
				<div class="p-3 bg-light border rounded mb-3">
					<div class="row align-items-end">
						<div class="col-sm-5 mb-2 mb-sm-0">
							<label class="mb-1 small font-weight-bold"><?= __('drawing_tool') ?></label>
							<div class="btn-group btn-group-sm" role="group">
								<button type="button" class="btn btn-outline-dark active" data-tool="pen" data-color="#000000">
									<i class="fas fa-pen mr-1"></i><?= __('color_black') ?>
								</button>
								<button type="button" class="btn btn-outline-danger" data-tool="pen" data-color="#e63946">
									<i class="fas fa-pen mr-1"></i><?= __('color_red') ?>
								</button>
								<button type="button" class="btn btn-outline-secondary" data-tool="eraser">
									<i class="fas fa-eraser mr-1"></i><?= __('eraser') ?>
								</button>
							</div>
						</div>
						<div class="col-sm-3 mb-2 mb-sm-0">
							<label for="diagLineThickness" class="mb-1 small font-weight-bold"><?= __('thickness') ?></label>
							<select class="form-control form-control-sm" id="diagLineThickness">
								<option value="2"><?= __('thickness_fine') ?></option>
								<option value="4" selected><?= __('thickness_normal') ?></option>
								<option value="6"><?= __('thickness_medium') ?></option>
								<option value="8"><?= __('thickness_thick') ?></option>
								<option value="12"><?= __('thickness_very_thick') ?></option>
							</select>
						</div>
						<div class="col-sm-4 text-sm-right">
							<button type="button" class="btn btn-sm btn-warning" id="diagUndoBtn"><i class="fas fa-undo mr-1"></i><?= __('undo') ?></button>
							<button type="button" class="btn btn-sm btn-info"    id="diagRedoBtn" disabled title="Redo not available with stroke model"><i class="fas fa-redo mr-1"></i><?= __('redo') ?></button>
							<button type="button" class="btn btn-sm btn-danger"  id="diagClearBtn"><i class="fas fa-trash mr-1"></i><?= __('clear') ?></button>
						</div>
					</div>
				</div>
				<!-- Canvas -->
				<div class="diagram-canvas-container">
					<canvas id="diagramCanvas"></canvas>
				</div>
				<p class="text-muted small mt-2 mb-0"><i class="fas fa-info-circle mr-1"></i><?= __('diagram_hint_before') ?> <strong><?= __('save_diagram') ?></strong> <?= __('diagram_hint_after') ?></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= __('cancel') ?></button>
				<button type="button" class="btn btn-success" id="diagSaveBtn"><i class="fas fa-save mr-1"></i><?= __('save_diagram') ?></button>
			</div>
		</div>
	</div>
</div>

<script>
/* ── Diagram editor (stroke-JSON storage) ──────────────────────────── */
/*
 * Only the nurse's annotations are stored — never the template image.
 * Each diagram field holds a compact JSON array of strokes:
 *   [ { tool, color, thickness, points: [[x,y], ...] }, ... ]
 * On load the template PNG is drawn first, then strokes are replayed on top.
 * Typical size: 1–15 KB vs 150–400 KB for a full-composite PNG.
 */
(function () {
	var CSRF_TOKEN    = document.querySelector('input[name="csrf_token"]')?.value || '';
	var CASE_SHEET_ID = <?= isset($csId) ? (int)$csId : 'null' ?>;

	/* State */
	var activeDiagram = null; // { type, fieldId, previewId }
	var canvas = null, ctx = null;
	var isDrawing = false;
	var currentTool = 'pen', currentColor = '#000000', currentThickness = 4;
	/* Stroke storage: array of completed strokes */
	var strokes = [];
	/* Current stroke being drawn */
	var currentStroke = null;

	var templatePaths = {
		breast: 'assets/images/diagrams/BreastExaminationDiagram.png',
		pelvic: 'assets/images/diagrams/PelvicExaminationDiagram.png',
		via:    'assets/images/diagrams/VIAVILIDiagram.png',
		vili:   'assets/images/diagrams/VIAVILIDiagram.png'
	};
	var modalTitles = {
		breast: 'Breast Examination Diagram',
		pelvic: 'Pelvic Examination Diagram',
		via:    'VIA Diagram',
		vili:   'VILI Diagram'
	};

	/* Cached template images so we only fetch each file once per session */
	var templateCache = {};

	/* Called by the Draw/Edit buttons in the form */
	window.openDiagram = function (type, fieldId, previewId) {
		activeDiagram = { type: type, fieldId: fieldId, previewId: previewId };
		document.getElementById('diagramEditorTitle').textContent = modalTitles[type] + ' Editor';
		$('#diagramEditorModal').modal('show');
	};

	/* Initialise canvas when modal fully opens */
	$('#diagramEditorModal').on('shown.bs.modal', function () {
		canvas = document.getElementById('diagramCanvas');
		ctx    = canvas.getContext('2d');
		canvas.width  = 800;
		canvas.height = 600;

		/* Load existing strokes from the hidden field */
		var raw = document.getElementById(activeDiagram.fieldId)?.value || '';
		try { strokes = raw ? JSON.parse(raw) : []; } catch (e) { strokes = []; }

		loadTemplateAndRedraw();
	});

	/* Reset when modal closes */
	$('#diagramEditorModal').on('hidden.bs.modal', function () {
		activeDiagram = null;
		strokes = []; currentStroke = null;
	});

	/* ── Rendering ─────────────────────────────────────────────── */

	function loadTemplateAndRedraw() {
		var src = templatePaths[activeDiagram.type];
		if (templateCache[src]) {
			drawAll(templateCache[src]);
		} else {
			var img = new Image();
			img.onload = function () {
				templateCache[src] = img;
				drawAll(img);
			};
			img.onerror = function () {
				ctx.fillStyle = '#f8d7da';
				ctx.fillRect(0, 0, canvas.width, canvas.height);
				ctx.fillStyle = '#721c24';
				ctx.font = '18px sans-serif'; ctx.textAlign = 'center';
				ctx.fillText('Template image could not be loaded', canvas.width / 2, canvas.height / 2);
				replayStrokes();
			};
			img.src = src;
		}
	}

	function drawAll(templateImg) {
		/* 1. White background */
		ctx.fillStyle = '#ffffff';
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		/* 2. Template centred, aspect-ratio preserved */
		var scale = Math.min(canvas.width / templateImg.width, canvas.height / templateImg.height);
		var w = templateImg.width * scale, h = templateImg.height * scale;
		var x = (canvas.width - w) / 2, y = (canvas.height - h) / 2;
		ctx.drawImage(templateImg, x, y, w, h);
		/* 3. All saved strokes */
		replayStrokes();
	}

	function replayStrokes() {
		strokes.forEach(function (stroke) { drawStroke(stroke); });
	}

	function drawStroke(stroke) {
		if (!stroke.points || stroke.points.length < 2) return;
		ctx.save();
		if (stroke.tool === 'eraser') {
			ctx.globalCompositeOperation = 'destination-out';
			ctx.strokeStyle = 'rgba(0,0,0,1)';
			ctx.lineWidth   = stroke.thickness * 3;
		} else {
			ctx.globalCompositeOperation = 'source-over';
			ctx.strokeStyle = stroke.color;
			ctx.lineWidth   = stroke.thickness;
		}
		ctx.lineCap = ctx.lineJoin = 'round';
		ctx.beginPath();
		ctx.moveTo(stroke.points[0][0], stroke.points[0][1]);
		for (var i = 1; i < stroke.points.length; i++) {
			ctx.lineTo(stroke.points[i][0], stroke.points[i][1]);
		}
		ctx.stroke();
		ctx.restore();
	}

	/* ── Thumbnail generation ──────────────────────────────────── */

	function buildThumbnail() {
		/* Render a 300x225 composite of template + strokes for the preview */
		var src = templatePaths[activeDiagram.type];
		var thumb = document.createElement('canvas');
		thumb.width  = 300; thumb.height = 225;
		var tc = thumb.getContext('2d');
		var img = templateCache[src];
		if (img) {
			var scale = Math.min(300 / img.width, 225 / img.height);
			var w = img.width * scale, h = img.height * scale;
			var x = (300 - w) / 2, y = (225 - h) / 2;
			tc.fillStyle = '#ffffff'; tc.fillRect(0, 0, 300, 225);
			tc.drawImage(img, x, y, w, h);
		}
		/* Scale and replay strokes onto thumbnail */
		var sx = 300 / canvas.width, sy = 225 / canvas.height;
		strokes.forEach(function (stroke) {
			if (!stroke.points || stroke.points.length < 2) return;
			tc.save();
			if (stroke.tool === 'eraser') {
				tc.globalCompositeOperation = 'destination-out';
				tc.strokeStyle = 'rgba(0,0,0,1)';
				tc.lineWidth   = stroke.thickness * 3 * sx;
			} else {
				tc.globalCompositeOperation = 'source-over';
				tc.strokeStyle = stroke.color;
				tc.lineWidth   = stroke.thickness * sx;
			}
			tc.lineCap = tc.lineJoin = 'round';
			tc.beginPath();
			tc.moveTo(stroke.points[0][0] * sx, stroke.points[0][1] * sy);
			for (var i = 1; i < stroke.points.length; i++) {
				tc.lineTo(stroke.points[i][0] * sx, stroke.points[i][1] * sy);
			}
			tc.stroke();
			tc.restore();
		});
		return thumb.toDataURL('image/png');
	}

	/* ── Toolbar ───────────────────────────────────────────────── */

	document.querySelectorAll('#diagramEditorModal [data-tool]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			document.querySelectorAll('#diagramEditorModal [data-tool]').forEach(function (b) { b.classList.remove('active'); });
			btn.classList.add('active');
			currentTool  = btn.dataset.tool;
			if (btn.dataset.color) currentColor = btn.dataset.color;
			if (canvas) canvas.style.cursor = currentTool === 'eraser' ? 'cell' : 'crosshair';
		});
	});

	document.getElementById('diagLineThickness').addEventListener('change', function () {
		currentThickness = parseInt(this.value);
	});

	document.getElementById('diagUndoBtn').addEventListener('click', function () {
		if (strokes.length > 0) {
			strokes.pop();
			loadTemplateAndRedraw();
		}
	});

	document.getElementById('diagRedoBtn').setAttribute('disabled', 'disabled'); // redo not applicable with stroke model

	document.getElementById('diagClearBtn').addEventListener('click', function () {
		if (strokes.length === 0) return;
		if (confirm('Clear all marks from the diagram?')) {
			strokes = [];
			loadTemplateAndRedraw();
		}
	});

	/* ── Drawing input ─────────────────────────────────────────── */

	function getPos(clientX, clientY) {
		var rect = canvas.getBoundingClientRect();
		return [
			(clientX - rect.left) * (canvas.width  / rect.width),
			(clientY - rect.top)  * (canvas.height / rect.height)
		];
	}

	function startDraw(x, y) {
		isDrawing = true;
		currentStroke = { tool: currentTool, color: currentColor, thickness: currentThickness, points: [[x, y]] };
	}

	function continueDraw(x, y) {
		if (!isDrawing || !currentStroke) return;
		currentStroke.points.push([x, y]);
		/* Live render: just draw the latest segment */
		if (currentStroke.points.length >= 2) {
			var pts = currentStroke.points;
			var prev = pts[pts.length - 2];
			ctx.save();
			if (currentTool === 'eraser') {
				ctx.globalCompositeOperation = 'destination-out';
				ctx.strokeStyle = 'rgba(0,0,0,1)';
				ctx.lineWidth   = currentThickness * 3;
			} else {
				ctx.globalCompositeOperation = 'source-over';
				ctx.strokeStyle = currentColor;
				ctx.lineWidth   = currentThickness;
			}
			ctx.lineCap = ctx.lineJoin = 'round';
			ctx.beginPath();
			ctx.moveTo(prev[0], prev[1]);
			ctx.lineTo(x, y);
			ctx.stroke();
			ctx.restore();
		}
	}

	function endDraw() {
		if (!isDrawing || !currentStroke) return;
		isDrawing = false;
		if (currentStroke.points.length >= 2) {
			strokes.push(currentStroke);
		}
		currentStroke = null;
	}

	document.addEventListener('DOMContentLoaded', function () {
		var c = document.getElementById('diagramCanvas');

		c.addEventListener('mousedown',  function (e) { var p = getPos(e.clientX, e.clientY); startDraw(p[0], p[1]); });
		c.addEventListener('mousemove',  function (e) { var p = getPos(e.clientX, e.clientY); continueDraw(p[0], p[1]); });
		c.addEventListener('mouseup',    endDraw);
		c.addEventListener('mouseleave', endDraw);

		/* Touch / stylus */
		c.addEventListener('touchstart', function (e) { e.preventDefault(); var t = e.touches[0]; var p = getPos(t.clientX, t.clientY); startDraw(p[0], p[1]); },    { passive: false });
		c.addEventListener('touchmove',  function (e) { e.preventDefault(); var t = e.touches[0]; var p = getPos(t.clientX, t.clientY); continueDraw(p[0], p[1]); }, { passive: false });
		c.addEventListener('touchend',   function (e) { e.preventDefault(); endDraw(); }, { passive: false });
	});

	/* ── Save ──────────────────────────────────────────────────── */

	document.getElementById('diagSaveBtn').addEventListener('click', function () {
		var strokeJSON = JSON.stringify(strokes);

		/* Write JSON to hidden field */
		var field = document.getElementById(activeDiagram.fieldId);
		if (field) field.value = strokeJSON;

		/* Update preview thumbnail (composite for display only) */
		var preview = document.getElementById(activeDiagram.previewId);
		if (preview) {
			var imgEl = preview.querySelector('img');
			if (imgEl) imgEl.src = strokes.length ? buildThumbnail() : '';
			if (strokes.length) {
				preview.classList.remove('d-none');
			} else {
				preview.classList.add('d-none');
			}
		}

		/* Update button label */
		document.querySelectorAll('button[onclick*="' + activeDiagram.fieldId + '"]').forEach(function (btn) {
			btn.innerHTML = '<i class="fas fa-draw-polygon mr-1"></i>Edit ' + modalTitles[activeDiagram.type];
		});

		/* Auto-save stroke JSON to DB */
		if (CASE_SHEET_ID) {
			var csrfInput = document.querySelector('input[name="csrf_token"]');
			var token = csrfInput ? csrfInput.value : '';
			fetch('update_case_sheet.php', {
				method:  'POST',
				headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
				body: JSON.stringify({
					csrf_token:    token,
					case_sheet_id: CASE_SHEET_ID,
					field:         activeDiagram.fieldId,
					value:         strokeJSON
				})
			}).catch(function (err) { console.error('Diagram save error:', err); });
		}

		$('#diagramEditorModal').modal('hide');
	});

	// ── DOB → Age auto-calculation ─────────────────
	var dobInput = document.getElementById('ve-date_of_birth');
	if (dobInput) {
		dobInput.addEventListener('change', function() {
			var dob = this.value;
			if (!dob) return;
			var today = new Date();
			var birth = new Date(dob);
			var age = today.getFullYear() - birth.getFullYear();
			var m = today.getMonth() - birth.getMonth();
			if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
			var ageInput = document.getElementById('ve-age_years');
			if (age >= 0 && age <= 150 && ageInput) ageInput.value = age;
		});
	}

}());
</script>

<script>
/* ── Render existing diagram thumbnails on page load ────────────────── */
/* For any diagram field that has saved stroke JSON, build a preview     */
/* thumbnail so the nurse sees their previous marks immediately.         */
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
				if (stroke.tool === 'eraser') {
					tc.globalCompositeOperation = 'destination-out';
					tc.strokeStyle = 'rgba(0,0,0,1)';
					tc.lineWidth   = stroke.thickness * 3 * sx;
				} else {
					tc.globalCompositeOperation = 'source-over';
					tc.strokeStyle = stroke.color;
					tc.lineWidth   = stroke.thickness * sx;
				}
				tc.lineCap = tc.lineJoin = 'round';
				tc.beginPath();
				tc.moveTo(stroke.points[0][0] * sx, stroke.points[0][1] * sy);
				for (var i = 1; i < stroke.points.length; i++) {
					tc.lineTo(stroke.points[i][0] * sx, stroke.points[i][1] * sy);
				}
				tc.stroke();
				tc.restore();
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
				if (Array.isArray(strokes) && strokes.length > 0) {
					renderThumbnail(imgEl, imgEl.dataset.diagType, strokes);
				}
			} catch (e) { /* not JSON — old PNG data, ignore */ }
		});
	});
}());
</script>

<script>
/* Patient Verification: Edit / Save / Cancel */
(function () {
	var CSRF = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';

	function showAlert(msg, type) {
		var el = document.getElementById('verif-alert');
		el.className = 'alert alert-' + type;
		el.textContent = msg;
		el.style.display = '';
		setTimeout(function () { el.style.display = 'none'; }, 5000);
	}

	function switchToRead() {
		document.getElementById('verif-read').style.display = '';
		document.getElementById('verif-edit').style.display = 'none';
		document.getElementById('btnEditPatient').style.display = '';
		document.getElementById('btnCancelEdit').style.display = 'none';
	}

	function switchToEdit() {
		document.getElementById('verif-read').style.display = 'none';
		document.getElementById('verif-edit').style.display = '';
		document.getElementById('btnEditPatient').style.display = 'none';
		document.getElementById('btnCancelEdit').style.display = '';
	}

	document.getElementById('btnEditPatient').addEventListener('click', switchToEdit);
	document.getElementById('btnCancelEdit').addEventListener('click', switchToRead);

	document.getElementById('btnSavePatient').addEventListener('click', function () {
		var btn = this;
		var patientId = parseInt(document.getElementById('ve-patient_id').value, 10);
		if (!patientId) { showAlert('Patient ID missing.', 'danger'); return; }

		var firstName = document.getElementById('ve-first_name').value.trim();
		if (!firstName) { showAlert('First Name is required.', 'warning'); return; }

		var payload = {
			csrf_token:              CSRF,
			patient_id:              patientId,
			first_name:              firstName,
			last_name:               document.getElementById('ve-last_name').value.trim(),
			sex:                     document.getElementById('ve-sex').value,
			date_of_birth:           document.getElementById('ve-date_of_birth').value,
			age_years:               document.getElementById('ve-age_years').value,
			blood_group:             document.getElementById('ve-blood_group').value,
			phone_e164:              document.getElementById('ve-phone_e164').value.trim(),
			email:                   document.getElementById('ve-email').value.trim(),
			address_line1:           document.getElementById('ve-address_line1').value.trim(),
			city:                    document.getElementById('ve-city').value.trim(),
			state_province:          document.getElementById('ve-state_province').value.trim(),
			postal_code:             document.getElementById('ve-postal_code').value.trim(),
			emergency_contact_name:  document.getElementById('ve-emergency_contact_name').value.trim(),
			emergency_contact_phone: document.getElementById('ve-emergency_contact_phone').value.trim()
		};

		btn.disabled = true;
		btn.textContent = 'Saving...';

		fetch('intake.php?action=update-patient', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(payload)
		})
		.then(function (r) { return r.json(); })
		.then(function (data) {
			btn.disabled = false;
			btn.innerHTML = '<i class="fas fa-save mr-1"></i> Save Changes';

			if (!data.success) {
				showAlert(data.message || 'Save failed.', 'danger');
				return;
			}

			var p = data.patient;
			document.getElementById('vr-first_name').textContent              = p.first_name || '';
			document.getElementById('vr-last_name').textContent               = p.last_name || '';
			document.getElementById('vr-sex').textContent                     = p.sex || '';
			document.getElementById('vr-date_of_birth').textContent           = p.date_of_birth || '';
			document.getElementById('vr-age_years').textContent               = p.age_years || '';
			document.getElementById('vr-blood_group').textContent             = p.blood_group || '';
			document.getElementById('vr-phone_e164').textContent              = p.phone_e164 || '';
			document.getElementById('vr-email').textContent                   = p.email || '';
			document.getElementById('vr-address_line1').textContent           = p.address_line1 || '';
			document.getElementById('vr-city').textContent                    = p.city || '';
			document.getElementById('vr-state_province').textContent          = p.state_province || '';
			document.getElementById('vr-postal_code').textContent             = p.postal_code || '';
			document.getElementById('vr-emergency_contact_name').textContent  = p.emergency_contact_name || '';
			document.getElementById('vr-emergency_contact_phone').textContent = p.emergency_contact_phone || '';


			switchToRead();
			showAlert('Patient information updated successfully.', 'success');
		})
		.catch(function () {
			btn.disabled = false;
			btn.innerHTML = '<i class="fas fa-save mr-1"></i> Save Changes';
			showAlert('Network error. Please try again.', 'danger');
		});
	});
}());
</script>
</body>
</html>
