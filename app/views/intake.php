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
			<li class="nav-item"><a class="nav-link active px-3" data-toggle="tab" href="#tab-verification">Verification</a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-personal">Personal</a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-history">History</a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-general">General</a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-examinations">Examinations</a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-labs">Labs</a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-summary">Summary</a></li>
		</ul>
		<?php endif; ?>

		<ul class="navbar-nav ml-auto">
			<li class="nav-item d-flex align-items-center mr-3">
				<div class="custom-control custom-switch theme-switch">
					<input type="checkbox" class="custom-control-input" id="themeToggleIntake" data-theme-toggle />
					<label class="custom-control-label" for="themeToggleIntake">Dark mode</label>
				</div>
			</li>
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="dashboard.php" role="button">
					<i class="fas fa-arrow-left mr-1"></i>Dashboard
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
							<span class="text-muted">| Intake</span>
						</h1>
						<p class="text-muted mb-0">NCD Screening and Treatment Documentation</p>
						<?php else: ?>
						<h1 class="m-0 text-dark"><i class="fas fa-clipboard-list mr-2"></i>Patient Intake</h1>
						<p class="text-muted mb-0">Select or register a patient to begin intake.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Auto-save indicator -->
		<div id="autoSaveIndicator" class="auto-save-indicator"><i class="fas fa-check-circle"></i> Saved</div>

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
						<div class="card-header"><h3 class="card-title"><i class="fas fa-user-injured mr-2"></i>Patient</h3></div>
						<div class="card-body">
							<div class="form-group">
								<label for="patientSearch">Search Existing Patient</label>
								<div class="input-group">
									<input type="text" class="form-control" id="patientSearch" placeholder="Search by name, patient code, or phone..." autocomplete="off" />
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
								<button type="button" class="btn btn-sm btn-outline-primary" id="toggleNewPatient"><i class="fas fa-user-plus mr-1"></i>Register New Patient</button>
							</div>
							<div id="newPatientSection" class="mt-3 d-none">
								<div class="card card-body bg-light">
									<h6 class="mb-3">New Patient Registration</h6>
									<div class="row">
										<div class="col-md-4"><div class="form-group"><label>First Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="newFirstName" /></div></div>
										<div class="col-md-4"><div class="form-group"><label>Last Name</label><input type="text" class="form-control" id="newLastName" /></div></div>
										<div class="col-md-4"><div class="form-group"><label>Sex</label><select class="form-control" id="newSex"><option value="UNKNOWN">Unknown</option><option value="MALE">Male</option><option value="FEMALE">Female</option><option value="OTHER">Other</option></select></div></div>
									</div>
									<div class="row">
										<div class="col-md-4"><div class="form-group"><label>Date of Birth</label><input type="date" class="form-control" id="newDob" /></div></div>
										<div class="col-md-4"><div class="form-group"><label>Age (years)</label><input type="number" class="form-control" id="newAge" min="0" max="150" /></div></div>
										<div class="col-md-4"><div class="form-group"><label>Phone</label><input type="text" class="form-control" id="newPhone" placeholder="+91..." /></div></div>
									</div>
									<div id="registerError" class="alert alert-danger d-none"></div>
									<button type="button" class="btn btn-primary" id="registerPatientBtn"><i class="fas fa-user-plus mr-1"></i>Register &amp; Select</button>
								</div>
							</div>
						</div>
					</div>

					<!-- Visit Details -->
					<div class="card card-outline card-info">
						<div class="card-header"><h3 class="card-title"><i class="fas fa-notes-medical mr-2"></i>Visit Details</h3></div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label for="visitType">Visit Type <span class="text-danger">*</span></label>
										<select class="form-control" id="visitType" name="visit_type" required>
											<option value="">-- Select --</option>
											<option value="CAMP">Camp</option>
											<option value="CLINIC">Clinic</option>
											<option value="FOLLOW_UP">Follow-up</option>
											<option value="EMERGENCY">Emergency</option>
											<option value="OTHER">Other</option>
										</select>
									</div>
								</div>
								<div class="col-md-8">
									<div class="form-group">
										<label for="chiefComplaint">Chief Complaint <span class="text-danger">*</span></label>
										<input type="text" class="form-control" id="chiefComplaint" name="chief_complaint" maxlength="255" required placeholder="Primary reason for visit" />
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="mb-4">
						<button type="submit" class="btn btn-lg btn-primary" id="submitCreate"><i class="fas fa-arrow-right mr-2"></i>Create Case Sheet &amp; Continue</button>
						<a href="dashboard.php" class="btn btn-lg btn-outline-secondary ml-2">Cancel</a>
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

							<!-- ── Verification Tab ────────────────────────── -->
							<div class="tab-pane fade show active" id="tab-verification" role="tabpanel">
								<h4 class="mb-3">Patient Verification</h4>
								<div class="row">
									<div class="col-md-6 mb-3"><label>First Name</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['first_name'] ?? '') ?>" readonly /></div>
									<div class="col-md-6 mb-3"><label>Last Name</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['last_name'] ?? '') ?>" readonly /></div>
								</div>
								<div class="row">
									<div class="col-md-4 mb-3"><label>Patient Code</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['patient_code'] ?? '') ?>" readonly /></div>
									<div class="col-md-4 mb-3"><label>Age</label><input type="text" class="form-control" value="<?= $p['age_years'] ? (int)$p['age_years'] . ' years' : '' ?>" readonly /></div>
									<div class="col-md-4 mb-3"><label>Sex</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['sex'] ?? '') ?>" readonly /></div>
								</div>
								<div class="row">
									<div class="col-md-4 mb-3"><label>Date of Birth</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['date_of_birth'] ?? '') ?>" readonly /></div>
									<div class="col-md-4 mb-3"><label>Phone</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['phone_e164'] ?? '') ?>" readonly /></div>
									<div class="col-md-4 mb-3"><label>Blood Group</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['blood_group'] ?? '') ?>" readonly /></div>
								</div>
								<h5 class="mt-3 mb-3">Address</h5>
								<div class="row">
									<div class="col-md-4 mb-3"><label>Address</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['address_line1'] ?? '') ?>" readonly /></div>
									<div class="col-md-4 mb-3"><label>City</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['city'] ?? '') ?>" readonly /></div>
									<div class="col-md-4 mb-3"><label>State</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['state_province'] ?? '') ?>" readonly /></div>
								</div>
								<h5 class="mt-3 mb-3">Emergency Contact</h5>
								<div class="row">
									<div class="col-md-6 mb-3"><label>Name</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['emergency_contact_name'] ?? '') ?>" readonly /></div>
									<div class="col-md-6 mb-3"><label>Phone</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['emergency_contact_phone'] ?? '') ?>" readonly /></div>
								</div>
								<div class="row">
									<div class="col-md-12 mb-3"><label>Allergies</label><input type="text" class="form-control" value="<?= htmlspecialchars($p['allergies'] ?? '') ?>" readonly /></div>
								</div>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary" disabled><i class="fas fa-chevron-left"></i> Previous</button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-personal">Next <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ── Personal Tab ───────────────────────────── -->
							<div class="tab-pane fade" id="tab-personal" role="tabpanel">
								<h4 class="mb-3">Personal Information</h4>
								<form class="intake-auto-save">
									<div class="row">
										<div class="col-md-6 mb-3">
											<label>Symptoms/Complaints</label>
											<textarea class="form-control" name="symptoms_complaints" rows="3" data-field="symptoms_complaints"><?= htmlspecialchars($vitals['symptoms_complaints'] ?? '') ?></textarea>
										</div>
										<div class="col-md-6 mb-3">
											<label>Duration of Symptoms</label>
											<input type="text" class="form-control" name="duration_of_symptoms" data-field="duration_of_symptoms" value="<?= htmlspecialchars($vitals['duration_of_symptoms'] ?? '') ?>" placeholder="e.g., 3 days, 2 weeks" />
										</div>
									</div>

									<h5 class="mt-4 mb-3">Reproductive History</h5>
									<div class="row">
										<div class="col-md-3 mb-3">
											<label>Number of Children</label>
											<input type="number" class="form-control" name="number_of_children" id="number_of_children" data-field="number_of_children" min="0" max="20" value="<?= htmlspecialchars($vitals['number_of_children'] ?? '0') ?>" />
										</div>
										<div class="col-md-3 mb-3">
											<label>Uterus</label>
											<select class="form-control" name="has_uterus" id="has_uterus" data-field="has_uterus">
												<option value="1" <?= ($vitals['has_uterus'] ?? '1') === '1' || ($vitals['has_uterus'] ?? 1) === 1 ? 'selected' : '' ?>>Yes</option>
												<option value="0" <?= ($vitals['has_uterus'] ?? '1') === '0' || ($vitals['has_uterus'] ?? 1) === 0 ? 'selected' : '' ?>>No</option>
											</select>
										</div>
									</div>

									<div id="deliveryDetailsSection" style="display:none;">
										<h6 class="mb-3">Delivery Details</h6>
										<div class="row">
											<div class="col-md-4 mb-3">
												<label>Type of Delivery</label>
												<select class="form-control" name="type_of_delivery" data-field="type_of_delivery">
													<option value="">Select...</option>
													<option value="LSCS" <?= ($vitals['type_of_delivery'] ?? '') === 'LSCS' ? 'selected' : '' ?>>LSCS</option>
													<option value="ND" <?= ($vitals['type_of_delivery'] ?? '') === 'ND' ? 'selected' : '' ?>>ND</option>
												</select>
											</div>
											<div class="col-md-4 mb-3">
												<label>Delivery Location</label>
												<input type="text" class="form-control" name="delivery_location" data-field="delivery_location" value="<?= htmlspecialchars($vitals['delivery_location'] ?? '') ?>" />
											</div>
											<div class="col-md-4 mb-3">
												<label>Delivery Source</label>
												<select class="form-control" name="delivery_source" data-field="delivery_source">
													<option value="">Select...</option>
													<option value="PRIVATE" <?= ($vitals['delivery_source'] ?? '') === 'PRIVATE' ? 'selected' : '' ?>>Private</option>
													<option value="GH" <?= ($vitals['delivery_source'] ?? '') === 'GH' ? 'selected' : '' ?>>GH</option>
												</select>
											</div>
										</div>
									</div>

									<div id="menstrualDetailsSection" style="display:none;">
										<h5 class="mt-4 mb-3">Menstrual Details</h5>
										<div class="row">
											<div class="col-md-3 mb-3"><label>Age of Onset</label><input type="number" class="form-control" name="menstrual_age_of_onset" data-field="menstrual_age_of_onset" min="0" max="30" value="<?= htmlspecialchars($vitals['menstrual_age_of_onset'] ?? '') ?>" /><small class="text-muted">Years</small></div>
											<div class="col-md-3 mb-3"><label>Cycle Frequency</label><input type="number" class="form-control" name="menstrual_cycle_frequency" data-field="menstrual_cycle_frequency" min="0" max="90" value="<?= htmlspecialchars($vitals['menstrual_cycle_frequency'] ?? '') ?>" /><small class="text-muted">Days</small></div>
											<div class="col-md-3 mb-3"><label>Duration of Flow</label><input type="number" class="form-control" name="menstrual_duration_of_flow" data-field="menstrual_duration_of_flow" min="0" max="30" value="<?= htmlspecialchars($vitals['menstrual_duration_of_flow'] ?? '') ?>" /><small class="text-muted">Days</small></div>
											<div class="col-md-3 mb-3"><label>LMP</label><input type="date" class="form-control" name="menstrual_lmp" data-field="menstrual_lmp" value="<?= htmlspecialchars($vitals['menstrual_lmp'] ?? '') ?>" /></div>
										</div>
										<div class="row">
											<div class="col-md-3 mb-3">
												<label>MH</label>
												<select class="form-control" name="menstrual_mh" data-field="menstrual_mh">
													<option value="">Select...</option>
													<option value="REGULAR" <?= ($vitals['menstrual_mh'] ?? '') === 'REGULAR' ? 'selected' : '' ?>>Regular</option>
													<option value="IRREGULAR" <?= ($vitals['menstrual_mh'] ?? '') === 'IRREGULAR' ? 'selected' : '' ?>>Irregular</option>
												</select>
											</div>
										</div>
									</div>
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-verification"><i class="fas fa-chevron-left"></i> Previous</button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-history">Next <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ── History Tab ────────────────────────────── -->
							<div class="tab-pane fade" id="tab-history" role="tabpanel">
								<h4 class="mb-3">Medical History</h4>
								<form class="intake-auto-save">
									<h5 class="mb-3">Conditions</h5>
									<div class="row">
										<div class="col-md-3 mb-3">
											<label>DM (Diabetes)</label>
											<select class="form-control" name="condition_dm" data-field="condition_dm">
												<option value="NO" <?= ($historyData['condition_dm'] ?? '') === 'NO' ? 'selected' : '' ?>>No</option>
												<option value="CURRENT" <?= ($historyData['condition_dm'] ?? '') === 'CURRENT' ? 'selected' : '' ?>>Current</option>
												<option value="PAST" <?= ($historyData['condition_dm'] ?? '') === 'PAST' ? 'selected' : '' ?>>Past</option>
											</select>
										</div>
										<div class="col-md-3 mb-3">
											<label>HTN (Hypertension)</label>
											<select class="form-control" name="condition_htn" data-field="condition_htn">
												<option value="NO" <?= ($historyData['condition_htn'] ?? '') === 'NO' ? 'selected' : '' ?>>No</option>
												<option value="CURRENT" <?= ($historyData['condition_htn'] ?? '') === 'CURRENT' ? 'selected' : '' ?>>Current</option>
												<option value="PAST" <?= ($historyData['condition_htn'] ?? '') === 'PAST' ? 'selected' : '' ?>>Past</option>
											</select>
										</div>
										<div class="col-md-3 mb-3">
											<label>TSH (Thyroid)</label>
											<select class="form-control" name="condition_tsh" data-field="condition_tsh">
												<option value="NO" <?= ($historyData['condition_tsh'] ?? '') === 'NO' ? 'selected' : '' ?>>No</option>
												<option value="CURRENT" <?= ($historyData['condition_tsh'] ?? '') === 'CURRENT' ? 'selected' : '' ?>>Current</option>
												<option value="PAST" <?= ($historyData['condition_tsh'] ?? '') === 'PAST' ? 'selected' : '' ?>>Past</option>
											</select>
										</div>
										<div class="col-md-3 mb-3">
											<label>Heart Disease</label>
											<select class="form-control" name="condition_heart_disease" data-field="condition_heart_disease">
												<option value="NO" <?= ($historyData['condition_heart_disease'] ?? '') === 'NO' ? 'selected' : '' ?>>No</option>
												<option value="CURRENT" <?= ($historyData['condition_heart_disease'] ?? '') === 'CURRENT' ? 'selected' : '' ?>>Current</option>
												<option value="PAST" <?= ($historyData['condition_heart_disease'] ?? '') === 'PAST' ? 'selected' : '' ?>>Past</option>
											</select>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-3"><label>Other Conditions</label><textarea class="form-control" name="condition_others" data-field="condition_others" rows="2"><?= htmlspecialchars($historyData['condition_others'] ?? '') ?></textarea></div>
										<div class="col-md-6 mb-3"><label>Surgical History</label><textarea class="form-control" name="surgical_history" data-field="surgical_history" rows="2"><?= htmlspecialchars($historyData['surgical_history'] ?? '') ?></textarea></div>
									</div>

									<h5 class="mt-4 mb-3">Family History</h5>
									<div class="row">
										<div class="col-md-4 mb-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="fh_cancer" name="family_history_cancer" data-field="family_history_cancer" value="1" <?= !empty($historyData['family_history_cancer']) ? 'checked' : '' ?> /><label class="custom-control-label" for="fh_cancer">Cancer</label></div></div>
										<div class="col-md-4 mb-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="fh_tb" name="family_history_tuberculosis" data-field="family_history_tuberculosis" value="1" <?= !empty($historyData['family_history_tuberculosis']) ? 'checked' : '' ?> /><label class="custom-control-label" for="fh_tb">Tuberculosis</label></div></div>
										<div class="col-md-4 mb-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="fh_diabetes" name="family_history_diabetes" data-field="family_history_diabetes" value="1" <?= !empty($historyData['family_history_diabetes']) ? 'checked' : '' ?> /><label class="custom-control-label" for="fh_diabetes">Diabetes</label></div></div>
									</div>
									<div class="row">
										<div class="col-md-4 mb-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="fh_bp" name="family_history_bp" data-field="family_history_bp" value="1" <?= !empty($historyData['family_history_bp']) ? 'checked' : '' ?> /><label class="custom-control-label" for="fh_bp">BP (Hypertension)</label></div></div>
										<div class="col-md-4 mb-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="fh_thyroid" name="family_history_thyroid" data-field="family_history_thyroid" value="1" <?= !empty($historyData['family_history_thyroid']) ? 'checked' : '' ?> /><label class="custom-control-label" for="fh_thyroid">Thyroid</label></div></div>
									</div>
									<div class="row">
										<div class="col-md-12 mb-3"><label>Other Family History</label><input type="text" class="form-control" name="family_history_other" data-field="family_history_other" value="<?= htmlspecialchars($historyData['family_history_other'] ?? '') ?>" /></div>
									</div>
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-personal"><i class="fas fa-chevron-left"></i> Previous</button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-general">Next <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ── General Tab ────────────────────────────── -->
							<div class="tab-pane fade" id="tab-general" role="tabpanel">
								<h4 class="mb-3">General Examination</h4>
								<form class="intake-auto-save">
									<h5 class="mb-3">Vital Signs</h5>
									<div class="row">
										<div class="col-md-3 mb-3"><label>Pulse</label><div class="input-group"><input type="number" class="form-control" name="general_pulse" data-field="general_pulse" min="0" max="300" value="<?= htmlspecialchars($vitals['general_pulse'] ?? $vitals['pulse'] ?? '') ?>" /><div class="input-group-append"><span class="input-group-text">/mt</span></div></div></div>
										<div class="col-md-3 mb-3">
											<label>B.P.</label>
											<div class="input-group">
												<input type="number" class="form-control" name="general_bp_systolic" data-field="general_bp_systolic" min="0" max="300" placeholder="Sys" value="<?= htmlspecialchars($vitals['general_bp_systolic'] ?? $vitals['bp_systolic'] ?? '') ?>" />
												<div class="input-group-append input-group-prepend"><span class="input-group-text">/</span></div>
												<input type="number" class="form-control" name="general_bp_diastolic" data-field="general_bp_diastolic" min="0" max="200" placeholder="Dia" value="<?= htmlspecialchars($vitals['general_bp_diastolic'] ?? $vitals['bp_diastolic'] ?? '') ?>" />
												<div class="input-group-append"><span class="input-group-text">mmHg</span></div>
											</div>
										</div>
										<div class="col-md-3 mb-3"><label>SpO2</label><div class="input-group"><input type="number" class="form-control" name="spo2" data-field="spo2" min="50" max="100" value="<?= htmlspecialchars($vitals['spo2'] ?? '') ?>" /><div class="input-group-append"><span class="input-group-text">%</span></div></div></div>
										<div class="col-md-3 mb-3"><label>Temperature</label><div class="input-group"><input type="number" class="form-control" name="temperature" data-field="temperature" min="90" max="110" step="0.1" value="<?= htmlspecialchars($vitals['temperature'] ?? '') ?>" /><div class="input-group-append"><span class="input-group-text">&deg;F</span></div></div></div>
									</div>

									<h5 class="mt-4 mb-3">Physical Examination</h5>
									<div class="row">
										<div class="col-md-6 mb-3"><label>Heart</label><input type="text" class="form-control" name="general_heart" data-field="general_heart" value="<?= htmlspecialchars($vitals['general_heart'] ?? '') ?>" /></div>
										<div class="col-md-6 mb-3"><label>Lungs</label><input type="text" class="form-control" name="general_lungs" data-field="general_lungs" value="<?= htmlspecialchars($vitals['general_lungs'] ?? '') ?>" /></div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-3"><label>Liver</label><input type="text" class="form-control" name="general_liver" data-field="general_liver" value="<?= htmlspecialchars($vitals['general_liver'] ?? '') ?>" /></div>
										<div class="col-md-6 mb-3"><label>Spleen</label><input type="text" class="form-control" name="general_spleen" data-field="general_spleen" value="<?= htmlspecialchars($vitals['general_spleen'] ?? '') ?>" /></div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-3"><label>Lymph Glands</label><input type="text" class="form-control" name="general_lymph_glands" data-field="general_lymph_glands" value="<?= htmlspecialchars($vitals['general_lymph_glands'] ?? '') ?>" /></div>
									</div>

									<h5 class="mt-4 mb-3">Anthropometric Measurements</h5>
									<div class="row">
										<div class="col-md-3 mb-3"><label>Height</label><div class="input-group"><input type="number" class="form-control" name="general_height" data-field="general_height" min="0" max="300" value="<?= htmlspecialchars($vitals['general_height'] ?? $vitals['height_cm'] ?? '') ?>" /><div class="input-group-append"><span class="input-group-text">cm</span></div></div></div>
										<div class="col-md-3 mb-3"><label>Weight</label><div class="input-group"><input type="number" class="form-control" name="general_weight" data-field="general_weight" min="0" max="500" step="0.1" value="<?= htmlspecialchars($vitals['general_weight'] ?? $vitals['weight_kg'] ?? '') ?>" /><div class="input-group-append"><span class="input-group-text">kgs</span></div></div></div>
										<div class="col-md-3 mb-3"><label>BMI</label><input type="number" class="form-control" name="general_bmi" data-field="general_bmi" min="0" max="100" step="0.1" value="<?= htmlspecialchars($vitals['general_bmi'] ?? '') ?>" /></div>
										<div class="col-md-3 mb-3"><label>Obesity/Overweight</label><select class="form-control" name="general_obesity_overweight" data-field="general_obesity_overweight"><option value="0" <?= ($vitals['general_obesity_overweight'] ?? '0') == '0' ? 'selected' : '' ?>>No</option><option value="1" <?= ($vitals['general_obesity_overweight'] ?? '0') == '1' ? 'selected' : '' ?>>Yes</option></select></div>
									</div>
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-history"><i class="fas fa-chevron-left"></i> Previous</button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-examinations">Next <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ── Examinations Tab ────────────────────────── -->
							<div class="tab-pane fade" id="tab-examinations" role="tabpanel">
								<h4 class="mb-3">Physical Examinations</h4>
								<form class="intake-auto-save">
									<h5 class="mb-3">Head and Neck</h5>
									<div class="row">
										<div class="col-md-4 mb-3"><label>Mouth</label><input type="text" class="form-control" name="exam_mouth" data-field="exam_mouth" value="<?= htmlspecialchars($examData['exam_mouth'] ?? '') ?>" /></div>
										<div class="col-md-4 mb-3"><label>Lips</label><input type="text" class="form-control" name="exam_lips" data-field="exam_lips" value="<?= htmlspecialchars($examData['exam_lips'] ?? '') ?>" /></div>
										<div class="col-md-4 mb-3"><label>Buccal Mucosa</label><input type="text" class="form-control" name="exam_buccal_mucosa" data-field="exam_buccal_mucosa" value="<?= htmlspecialchars($examData['exam_buccal_mucosa'] ?? '') ?>" /></div>
									</div>
									<div class="row">
										<div class="col-md-4 mb-3"><label>Teeth</label><input type="text" class="form-control" name="exam_teeth" data-field="exam_teeth" value="<?= htmlspecialchars($examData['exam_teeth'] ?? '') ?>" /></div>
										<div class="col-md-4 mb-3"><label>Tongue</label><input type="text" class="form-control" name="exam_tongue" data-field="exam_tongue" value="<?= htmlspecialchars($examData['exam_tongue'] ?? '') ?>" /></div>
										<div class="col-md-4 mb-3"><label>Oropharynx</label><input type="text" class="form-control" name="exam_oropharynx" data-field="exam_oropharynx" value="<?= htmlspecialchars($examData['exam_oropharynx'] ?? '') ?>" /></div>
									</div>
									<div class="row">
										<div class="col-md-4 mb-3"><label>Hypo</label><input type="text" class="form-control" name="exam_hypo" data-field="exam_hypo" value="<?= htmlspecialchars($examData['exam_hypo'] ?? '') ?>" /></div>
										<div class="col-md-4 mb-3"><label>Naso-Pharynx</label><input type="text" class="form-control" name="exam_naso_pharynx" data-field="exam_naso_pharynx" value="<?= htmlspecialchars($examData['exam_naso_pharynx'] ?? '') ?>" /></div>
										<div class="col-md-4 mb-3"><label>Larynx</label><input type="text" class="form-control" name="exam_larynx" data-field="exam_larynx" value="<?= htmlspecialchars($examData['exam_larynx'] ?? '') ?>" /></div>
									</div>
									<div class="row">
										<div class="col-md-4 mb-3"><label>Nose</label><input type="text" class="form-control" name="exam_nose" data-field="exam_nose" value="<?= htmlspecialchars($examData['exam_nose'] ?? '') ?>" /></div>
										<div class="col-md-4 mb-3"><label>Ears</label><input type="text" class="form-control" name="exam_ears" data-field="exam_ears" value="<?= htmlspecialchars($examData['exam_ears'] ?? '') ?>" /></div>
										<div class="col-md-4 mb-3"><label>Neck</label><input type="text" class="form-control" name="exam_neck" data-field="exam_neck" value="<?= htmlspecialchars($examData['exam_neck'] ?? '') ?>" /></div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-3"><label>Bones and Joints</label><input type="text" class="form-control" name="exam_bones_joints" data-field="exam_bones_joints" value="<?= htmlspecialchars($examData['exam_bones_joints'] ?? '') ?>" /></div>
										<div class="col-md-6 mb-3"><label>Abdomen and Genital Organs</label><input type="text" class="form-control" name="exam_abdomen_genital" data-field="exam_abdomen_genital" value="<?= htmlspecialchars($examData['exam_abdomen_genital'] ?? '') ?>" /></div>
									</div>

									<h5 class="mt-4 mb-3">Breasts</h5>
									<div class="row">
										<div class="col-md-4 mb-3"><label>Left</label><input type="text" class="form-control" name="exam_breast_left" data-field="exam_breast_left" value="<?= htmlspecialchars($examData['exam_breast_left'] ?? '') ?>" /></div>
										<div class="col-md-4 mb-3"><label>Right</label><input type="text" class="form-control" name="exam_breast_right" data-field="exam_breast_right" value="<?= htmlspecialchars($examData['exam_breast_right'] ?? '') ?>" /></div>
										<div class="col-md-4 mb-3"><label>Axillary Nodes</label><input type="text" class="form-control" name="exam_breast_axillary_nodes" data-field="exam_breast_axillary_nodes" value="<?= htmlspecialchars($examData['exam_breast_axillary_nodes'] ?? '') ?>" /></div>
									</div>
									<div class="row">
										<div class="col-12 mb-3">
											<label class="d-block">Breast Examination Diagram</label>
											<button type="button" class="btn btn-outline-primary btn-sm" onclick="openDiagram('breast','diag_breast','breastDiagramPreview')">
												<i class="fas fa-draw-polygon mr-1"></i><?= !empty($cs['diag_breast']) ? 'Edit' : 'Draw' ?> Breast Diagram
											</button>
											<div id="breastDiagramPreview" class="mt-2<?= empty($cs['diag_breast']) ? ' d-none' : '' ?>">
												<img src="<?= !empty($cs['diag_breast']) ? 'data:image/png;base64,' . htmlspecialchars($cs['diag_breast']) : '' ?>"
												     alt="Breast Examination Diagram" class="img-thumbnail diagram-preview-img">
											</div>
											<input type="hidden" id="diag_breast" value="<?= htmlspecialchars($cs['diag_breast'] ?? '') ?>">
										</div>
									</div>

									<h5 class="mt-4 mb-3">Pelvic Examination Introitus</h5>
									<div class="row">
										<div class="col-md-3 mb-3"><label>Cervix</label><input type="text" class="form-control" name="exam_pelvic_cervix" data-field="exam_pelvic_cervix" value="<?= htmlspecialchars($examData['exam_pelvic_cervix'] ?? '') ?>" /></div>
										<div class="col-md-3 mb-3"><label>Uterus</label><input type="text" class="form-control" name="exam_pelvic_uterus" data-field="exam_pelvic_uterus" value="<?= htmlspecialchars($examData['exam_pelvic_uterus'] ?? '') ?>" /></div>
										<div class="col-md-3 mb-3"><label>Ovaries</label><input type="text" class="form-control" name="exam_pelvic_ovaries" data-field="exam_pelvic_ovaries" value="<?= htmlspecialchars($examData['exam_pelvic_ovaries'] ?? '') ?>" /></div>
										<div class="col-md-3 mb-3"><label>Adnexa</label><input type="text" class="form-control" name="exam_pelvic_adnexa" data-field="exam_pelvic_adnexa" value="<?= htmlspecialchars($examData['exam_pelvic_adnexa'] ?? '') ?>" /></div>
									</div>
									<div class="row">
										<div class="col-12 mb-3">
											<label class="d-block">Pelvic Examination Diagram</label>
											<button type="button" class="btn btn-outline-primary btn-sm" onclick="openDiagram('pelvic','diag_pelvic','pelvicDiagramPreview')">
												<i class="fas fa-draw-polygon mr-1"></i><?= !empty($cs['diag_pelvic']) ? 'Edit' : 'Draw' ?> Pelvic Diagram
											</button>
											<div id="pelvicDiagramPreview" class="mt-2<?= empty($cs['diag_pelvic']) ? ' d-none' : '' ?>">
												<img src="<?= !empty($cs['diag_pelvic']) ? 'data:image/png;base64,' . htmlspecialchars($cs['diag_pelvic']) : '' ?>"
												     alt="Pelvic Examination Diagram" class="img-thumbnail diagram-preview-img">
											</div>
											<input type="hidden" id="diag_pelvic" value="<?= htmlspecialchars($cs['diag_pelvic'] ?? '') ?>">
										</div>
									</div>

									<h5 class="mt-4 mb-3">Rectal Examination</h5>
									<div class="row">
										<div class="col-md-6 mb-3"><label>Skin</label><input type="text" class="form-control" name="exam_rectal_skin" data-field="exam_rectal_skin" value="<?= htmlspecialchars($examData['exam_rectal_skin'] ?? '') ?>" /></div>
										<div class="col-md-6 mb-3"><label>Remarks</label><input type="text" class="form-control" name="exam_rectal_remarks" data-field="exam_rectal_remarks" value="<?= htmlspecialchars($examData['exam_rectal_remarks'] ?? '') ?>" /></div>
									</div>

									<h5 class="mt-4 mb-3">Gynaecological Examination</h5>
									<div class="row">
										<div class="col-md-6 mb-3"><label>P/S</label><input type="text" class="form-control" name="exam_gynae_ps" data-field="exam_gynae_ps" value="<?= htmlspecialchars($examData['exam_gynae_ps'] ?? '') ?>" /></div>
										<div class="col-md-6 mb-3"><label>P/V</label><input type="text" class="form-control" name="exam_gynae_pv" data-field="exam_gynae_pv" value="<?= htmlspecialchars($examData['exam_gynae_pv'] ?? '') ?>" /></div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-3">
											<label>VIA</label>
											<input type="text" class="form-control mb-2" name="exam_gynae_via" data-field="exam_gynae_via" value="<?= htmlspecialchars($examData['exam_gynae_via'] ?? '') ?>" />
											<button type="button" class="btn btn-outline-primary btn-sm" onclick="openDiagram('via','diag_via','viaDiagramPreview')">
												<i class="fas fa-draw-polygon mr-1"></i><?= !empty($cs['diag_via']) ? 'Edit' : 'Draw' ?> VIA Diagram
											</button>
											<div id="viaDiagramPreview" class="mt-2<?= empty($cs['diag_via']) ? ' d-none' : '' ?>">
												<img src="<?= !empty($cs['diag_via']) ? 'data:image/png;base64,' . htmlspecialchars($cs['diag_via']) : '' ?>"
												     alt="VIA Diagram" class="img-thumbnail diagram-preview-img">
											</div>
											<input type="hidden" id="diag_via" value="<?= htmlspecialchars($cs['diag_via'] ?? '') ?>">
										</div>
										<div class="col-md-6 mb-3">
											<label>VILI</label>
											<input type="text" class="form-control mb-2" name="exam_gynae_vili" data-field="exam_gynae_vili" value="<?= htmlspecialchars($examData['exam_gynae_vili'] ?? '') ?>" />
											<button type="button" class="btn btn-outline-primary btn-sm" onclick="openDiagram('vili','diag_vili','viliDiagramPreview')">
												<i class="fas fa-draw-polygon mr-1"></i><?= !empty($cs['diag_vili']) ? 'Edit' : 'Draw' ?> VILI Diagram
											</button>
											<div id="viliDiagramPreview" class="mt-2<?= empty($cs['diag_vili']) ? ' d-none' : '' ?>">
												<img src="<?= !empty($cs['diag_vili']) ? 'data:image/png;base64,' . htmlspecialchars($cs['diag_vili']) : '' ?>"
												     alt="VILI Diagram" class="img-thumbnail diagram-preview-img">
											</div>
											<input type="hidden" id="diag_vili" value="<?= htmlspecialchars($cs['diag_vili'] ?? '') ?>">
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
								<h4 class="mb-3">Laboratory Tests</h4>
								<form class="intake-auto-save">
									<h5 class="mb-3">Investigations</h5>
									<div class="row">
										<div class="col-md-3 mb-3"><label>Hb</label><div class="input-group"><input type="number" class="form-control" name="lab_hb_percentage" data-field="lab_hb_percentage" min="0" max="100" value="<?= htmlspecialchars($labData['lab_hb_percentage'] ?? '') ?>" placeholder="%" /><div class="input-group-append"><span class="input-group-text">%</span></div><input type="number" class="form-control" name="lab_hb_gms" data-field="lab_hb_gms" min="0" max="30" step="0.1" value="<?= htmlspecialchars($labData['lab_hb_gms'] ?? '') ?>" placeholder="gms" /><div class="input-group-append"><span class="input-group-text">gms</span></div></div></div>
										<div class="col-md-3 mb-3"><label>FBS</label><div class="input-group"><input type="number" class="form-control" name="lab_fbs" data-field="lab_fbs" min="0" max="1000" step="0.1" value="<?= htmlspecialchars($labData['lab_fbs'] ?? '') ?>" /><div class="input-group-append"><span class="input-group-text">mg/dl</span></div></div></div>
										<div class="col-md-3 mb-3"><label>TSH</label><input type="number" class="form-control" name="lab_tsh" data-field="lab_tsh" min="0" max="100" step="0.01" value="<?= htmlspecialchars($labData['lab_tsh'] ?? '') ?>" /></div>
										<div class="col-md-3 mb-3"><label>Sr. Creatinine</label><input type="number" class="form-control" name="lab_sr_creatinine" data-field="lab_sr_creatinine" min="0" max="20" step="0.01" value="<?= htmlspecialchars($labData['lab_sr_creatinine'] ?? '') ?>" /></div>
									</div>
									<div class="row">
										<div class="col-md-12 mb-3"><label>Others</label><textarea class="form-control" name="lab_others" data-field="lab_others" rows="2"><?= htmlspecialchars($labData['lab_others'] ?? '') ?></textarea></div>
									</div>

									<h5 class="mt-4 mb-3">Cytology Report</h5>
									<div class="row">
										<div class="col-md-6 mb-3"><label>Papsmear</label><select class="form-control" name="cytology_papsmear" data-field="cytology_papsmear"><option value="NONE" <?= ($labData['cytology_papsmear'] ?? '') === 'NONE' ? 'selected' : '' ?>>None</option><option value="DONE" <?= ($labData['cytology_papsmear'] ?? '') === 'DONE' ? 'selected' : '' ?>>Done</option><option value="ADVISED" <?= ($labData['cytology_papsmear'] ?? '') === 'ADVISED' ? 'selected' : '' ?>>Advised</option></select></div>
										<div class="col-md-6 mb-3"><label>Papsmear Notes</label><textarea class="form-control" name="cytology_papsmear_notes" data-field="cytology_papsmear_notes" rows="2"><?= htmlspecialchars($labData['cytology_papsmear_notes'] ?? '') ?></textarea></div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-3"><label>Colposcopy</label><select class="form-control" name="cytology_colposcopy" data-field="cytology_colposcopy"><option value="NONE" <?= ($labData['cytology_colposcopy'] ?? '') === 'NONE' ? 'selected' : '' ?>>None</option><option value="DONE" <?= ($labData['cytology_colposcopy'] ?? '') === 'DONE' ? 'selected' : '' ?>>Done</option><option value="ADVISED" <?= ($labData['cytology_colposcopy'] ?? '') === 'ADVISED' ? 'selected' : '' ?>>Advised</option></select></div>
										<div class="col-md-6 mb-3"><label>Colposcopy Notes</label><textarea class="form-control" name="cytology_colposcopy_notes" data-field="cytology_colposcopy_notes" rows="2"><?= htmlspecialchars($labData['cytology_colposcopy_notes'] ?? '') ?></textarea></div>
									</div>
									<div class="row">
										<div class="col-md-6 mb-3"><label>Biopsy</label><select class="form-control" name="cytology_biopsy" data-field="cytology_biopsy"><option value="NONE" <?= ($labData['cytology_biopsy'] ?? '') === 'NONE' ? 'selected' : '' ?>>None</option><option value="DONE" <?= ($labData['cytology_biopsy'] ?? '') === 'DONE' ? 'selected' : '' ?>>Done</option><option value="ADVISED" <?= ($labData['cytology_biopsy'] ?? '') === 'ADVISED' ? 'selected' : '' ?>>Advised</option></select></div>
										<div class="col-md-6 mb-3"><label>Biopsy Notes</label><textarea class="form-control" name="cytology_biopsy_notes" data-field="cytology_biopsy_notes" rows="2"><?= htmlspecialchars($labData['cytology_biopsy_notes'] ?? '') ?></textarea></div>
									</div>
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-examinations"><i class="fas fa-chevron-left"></i> Previous</button>
									<button type="button" class="btn btn-primary btn-next-tab" data-target="#tab-summary">Next <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ── Summary Tab ────────────────────────────── -->
							<div class="tab-pane fade" id="tab-summary" role="tabpanel">
								<h4 class="mb-3">Case Summary</h4>
								<form class="intake-auto-save">
									<h5 class="mb-3">Assessment and Disposition</h5>
									<div class="row"><div class="col-md-12 mb-3"><label>Risk Level</label><textarea class="form-control" name="summary_risk_level" data-field="summary_risk_level" rows="4"><?= htmlspecialchars($summaryData['summary_risk_level'] ?? '') ?></textarea></div></div>
									<div class="row"><div class="col-md-12 mb-3"><label>Referral</label><textarea class="form-control" name="summary_referral" data-field="summary_referral" rows="4"><?= htmlspecialchars($summaryData['summary_referral'] ?? '') ?></textarea></div></div>
									<div class="row"><div class="col-md-12 mb-3"><label>Patient Acceptance</label><textarea class="form-control" name="summary_patient_acceptance" data-field="summary_patient_acceptance" rows="4"><?= htmlspecialchars($summaryData['summary_patient_acceptance'] ?? '') ?></textarea></div></div>
									<h5 class="mt-4 mb-3">Doctor&rsquo;s Summary</h5>
									<div class="row"><div class="col-md-12 mb-3"><label>Summary and Recommendations</label><textarea class="form-control" name="summary_doctor_summary" data-field="summary_doctor_summary" rows="4"><?= htmlspecialchars($summaryData['summary_doctor_summary'] ?? '') ?></textarea></div></div>
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-labs"><i class="fas fa-chevron-left"></i> Previous</button>
									<form method="post" action="intake.php?action=complete" style="display:inline">
										<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
										<input type="hidden" name="case_sheet_id" value="<?= $csId ?>" />
										<button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check-circle mr-1"></i>Complete Intake</button>
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

	<footer class="main-footer text-sm"><strong>CareSystem</strong> &middot; Patient Intake</footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script>
(function () {
	var csrfToken = <?= json_encode($_SESSION['csrf_token']) ?>;
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

<!-- ── Diagram Editor Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="diagramEditorModal" tabindex="-1" role="dialog" aria-labelledby="diagramEditorTitle" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="diagramEditorTitle">Diagram Editor</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<!-- Toolbar -->
				<div class="p-3 bg-light border rounded mb-3">
					<div class="row align-items-end">
						<div class="col-sm-5 mb-2 mb-sm-0">
							<label class="mb-1 small font-weight-bold">Drawing Tool</label>
							<div class="btn-group btn-group-sm" role="group">
								<button type="button" class="btn btn-outline-dark active" data-tool="pen" data-color="#000000">
									<i class="fas fa-pen mr-1"></i>Black
								</button>
								<button type="button" class="btn btn-outline-danger" data-tool="pen" data-color="#e63946">
									<i class="fas fa-pen mr-1"></i>Red
								</button>
								<button type="button" class="btn btn-outline-secondary" data-tool="eraser">
									<i class="fas fa-eraser mr-1"></i>Eraser
								</button>
							</div>
						</div>
						<div class="col-sm-3 mb-2 mb-sm-0">
							<label for="diagLineThickness" class="mb-1 small font-weight-bold">Thickness</label>
							<select class="form-control form-control-sm" id="diagLineThickness">
								<option value="2">Fine (2 px)</option>
								<option value="4" selected>Normal (4 px)</option>
								<option value="6">Medium (6 px)</option>
								<option value="8">Thick (8 px)</option>
								<option value="12">Very thick (12 px)</option>
							</select>
						</div>
						<div class="col-sm-4 text-sm-right">
							<button type="button" class="btn btn-sm btn-warning" id="diagUndoBtn"><i class="fas fa-undo mr-1"></i>Undo</button>
							<button type="button" class="btn btn-sm btn-info"    id="diagRedoBtn"><i class="fas fa-redo mr-1"></i>Redo</button>
							<button type="button" class="btn btn-sm btn-danger"  id="diagClearBtn"><i class="fas fa-trash mr-1"></i>Clear</button>
						</div>
					</div>
				</div>
				<!-- Canvas -->
				<div class="diagram-canvas-container">
					<canvas id="diagramCanvas"></canvas>
				</div>
				<p class="text-muted small mt-2 mb-0"><i class="fas fa-info-circle mr-1"></i>Draw on the diagram above. Click <strong>Save Diagram</strong> when finished.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-success" id="diagSaveBtn"><i class="fas fa-save mr-1"></i>Save Diagram</button>
			</div>
		</div>
	</div>
</div>

<script>
/* ── Diagram editor ─────────────────────────────────────────────────── */
(function () {
	var CSRF_TOKEN    = document.querySelector('input[name="csrf_token"]')?.value || '';
	var CASE_SHEET_ID = <?= isset($csId) ? (int)$csId : 'null' ?>;

	/* State */
	var activeDiagram = null; // { type, fieldId, previewId }
	var canvas = null, ctx = null;
	var isDrawing = false;
	var currentTool = 'pen', currentColor = '#000000', currentThickness = 4;
	var history = [], historyStep = -1;

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
		history = []; historyStep = -1;
		loadTemplate();
	});

	/* Reset state when modal closes */
	$('#diagramEditorModal').on('hidden.bs.modal', function () {
		activeDiagram = null;
		if (canvas) { canvas.width = canvas.width; } // clear
		history = []; historyStep = -1;
	});

	function loadTemplate() {
		ctx.fillStyle = '#ffffff';
		ctx.fillRect(0, 0, canvas.width, canvas.height);

		var img = new Image();
		img.onload = function () {
			var scale = Math.min(canvas.width / img.width, canvas.height / img.height);
			var w = img.width * scale, h = img.height * scale;
			var x = (canvas.width  - w) / 2;
			var y = (canvas.height - h) / 2;
			ctx.drawImage(img, x, y, w, h);

			/* Overlay any previously saved drawing */
			var existing = document.getElementById(activeDiagram.fieldId)?.value;
			if (existing) {
				var overlay = new Image();
				overlay.onload = function () {
					ctx.drawImage(overlay, 0, 0);
					saveHistory();
				};
				overlay.src = 'data:image/png;base64,' + existing;
			} else {
				saveHistory();
			}
		};
		img.onerror = function () {
			ctx.fillStyle = '#f8d7da';
			ctx.fillRect(0, 0, canvas.width, canvas.height);
			ctx.fillStyle = '#721c24';
			ctx.font = '18px sans-serif'; ctx.textAlign = 'center';
			ctx.fillText('Template image could not be loaded', canvas.width / 2, canvas.height / 2);
			saveHistory();
		};
		img.src = templatePaths[activeDiagram.type];
	}

	function saveHistory() {
		historyStep++;
		if (historyStep < history.length) history.length = historyStep;
		history.push(canvas.toDataURL());
		if (history.length > 30) { history.shift(); historyStep--; }
	}

	function restoreHistory(step) {
		var img = new Image();
		img.onload = function () {
			ctx.clearRect(0, 0, canvas.width, canvas.height);
			ctx.drawImage(img, 0, 0);
		};
		img.src = history[step];
	}

	/* ── Toolbar interactions ─────────────────────────────────── */
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
		if (historyStep > 0) { historyStep--; restoreHistory(historyStep); }
	});
	document.getElementById('diagRedoBtn').addEventListener('click', function () {
		if (historyStep < history.length - 1) { historyStep++; restoreHistory(historyStep); }
	});
	document.getElementById('diagClearBtn').addEventListener('click', function () {
		if (confirm('Clear all marks from the diagram?')) {
			history = []; historyStep = -1;
			loadTemplate();
		}
	});

	/* ── Drawing (mouse) ───────────────────────────────────────── */
	function getPos(e) {
		var rect = canvas.getBoundingClientRect();
		var scaleX = canvas.width  / rect.width;
		var scaleY = canvas.height / rect.height;
		return {
			x: (e.clientX - rect.left) * scaleX,
			y: (e.clientY - rect.top)  * scaleY
		};
	}

	function startDraw(x, y) {
		isDrawing = true;
		ctx.beginPath();
		ctx.moveTo(x, y);
	}

	function draw(x, y) {
		if (!isDrawing) return;
		if (currentTool === 'pen') {
			ctx.strokeStyle = currentColor;
			ctx.lineWidth   = currentThickness;
			ctx.lineCap = ctx.lineJoin = 'round';
			ctx.lineTo(x, y);
			ctx.stroke();
		} else if (currentTool === 'eraser') {
			var r = currentThickness * 3;
			ctx.clearRect(x - r / 2, y - r / 2, r, r);
		}
	}

	function endDraw() {
		if (isDrawing) { isDrawing = false; saveHistory(); }
	}

	/* Mouse events (queued to ensure canvas is ready) */
	document.addEventListener('DOMContentLoaded', function () {
		var c = document.getElementById('diagramCanvas');

		c.addEventListener('mousedown',  function (e) { var p = getPos(e); startDraw(p.x, p.y); });
		c.addEventListener('mousemove',  function (e) { var p = getPos(e); draw(p.x, p.y); });
		c.addEventListener('mouseup',    endDraw);
		c.addEventListener('mouseleave', endDraw);

		/* ── Touch support (tablet / stylus) ────────────────────── */
		function touchPos(touch) {
			var rect = c.getBoundingClientRect();
			var scaleX = c.width  / rect.width;
			var scaleY = c.height / rect.height;
			return {
				x: (touch.clientX - rect.left) * scaleX,
				y: (touch.clientY - rect.top)  * scaleY
			};
		}
		c.addEventListener('touchstart', function (e) { e.preventDefault(); var p = touchPos(e.touches[0]); startDraw(p.x, p.y); }, { passive: false });
		c.addEventListener('touchmove',  function (e) { e.preventDefault(); var p = touchPos(e.touches[0]); draw(p.x, p.y);      }, { passive: false });
		c.addEventListener('touchend',   function (e) { e.preventDefault(); endDraw(); }, { passive: false });
	});

	/* ── Save ──────────────────────────────────────────────────── */
	document.getElementById('diagSaveBtn').addEventListener('click', function () {
		var base64 = canvas.toDataURL('image/png').split(',')[1];

		/* Update hidden field + thumbnail */
		var field   = document.getElementById(activeDiagram.fieldId);
		var preview = document.getElementById(activeDiagram.previewId);
		if (field)   field.value = base64;
		if (preview) {
			var img = preview.querySelector('img');
			if (img) img.src = 'data:image/png;base64,' + base64;
			preview.classList.remove('d-none');
		}

		/* Update the button label */
		document.querySelectorAll('button[onclick*="' + activeDiagram.fieldId + '"]').forEach(function (btn) {
			btn.innerHTML = '<i class="fas fa-draw-polygon mr-1"></i>Edit ' + modalTitles[activeDiagram.type];
		});

		/* Auto-save to DB if case sheet exists */
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
					value:         base64
				})
			}).catch(function (err) { console.error('Diagram save error:', err); });
		}

		$('#diagramEditorModal').modal('hide');
	});
}());
</script>
</body>
</html>
