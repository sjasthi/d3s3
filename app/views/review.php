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
		#reviewTabs.nav-pills .nav-link {
			color: #495057; background-color: transparent;
			border-radius: 0.25rem; font-size: 0.9rem; border: 1px solid transparent;
		}
		#reviewTabs.nav-pills .nav-link:hover { background-color: #f8f9fa; border-color: #dee2e6; }
		#reviewTabs.nav-pills .nav-link.active {
			color: #28a745; background-color: #e6f4ea; border-color: #28a745; font-weight: 600;
		}
		.tab-navigation { margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6; display: flex; justify-content: space-between; }
		.auto-save-indicator {
			position: fixed; top: 70px; right: 20px; padding: 10px 20px;
			background-color: #28a745; color: white; border-radius: 4px;
			display: none; z-index: 1000;
		}
		.auto-save-indicator.saving { background-color: #ffc107; color: #000; }
		.auto-save-indicator.error { background-color: #dc3545; }
		/* Extra top padding for taller navbar with tabs */
		.content-wrapper { padding-top: 30px; }
		.intake-summary-label { font-size: 0.75rem; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 2px; }
		.intake-summary-value { font-size: 0.95rem; color: #212529; margin-bottom: 0; }
		.intake-summary-empty { color: #adb5bd; font-style: italic; }
		.summary-section { border-left: 3px solid #dee2e6; padding-left: 12px; margin-bottom: 1.5rem; }
		.summary-section h6 { color: #495057; font-weight: 700; margin-bottom: 0.75rem; }
		.audit-row-changed td { background-color: rgba(255, 193, 7, 0.08); }
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
		</ul>

		<!-- Tab bar in navbar -->
		<ul class="nav nav-pills ml-4 d-none d-lg-flex" id="reviewTabs" role="tablist">
			<li class="nav-item"><a class="nav-link active px-3" data-toggle="tab" href="#tab-summary">Intake Summary</a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-examination">Examination</a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-assessment">Assessment &amp; Diagnosis</a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-plan">Treatment Plan</a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-followup">Follow-up &amp; Referrals</a></li>
			<li class="nav-item"><a class="nav-link px-3" data-toggle="tab" href="#tab-audit">Audit History</a></li>
		</ul>

		<ul class="navbar-nav ml-auto">
			<li class="nav-item d-flex align-items-center mr-3">
				<div class="custom-control custom-switch theme-switch">
					<input type="checkbox" class="custom-control-input" id="themeToggleReview" data-theme-toggle />
					<label class="custom-control-label" for="themeToggleReview">Dark mode</label>
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

			// Helper: display a value or a placeholder
			function rv($val, $placeholder = '—') {
				$v = trim((string)($val ?? ''));
				if ($v === '') return '<span class="intake-summary-empty">' . $placeholder . '</span>';
				return htmlspecialchars($v);
			}
		?>

		<div class="content-header">
			<div class="container-fluid">
				<div class="row align-items-center">
					<div class="col">
						<h1 class="m-0 text-dark">
							<?= htmlspecialchars($p['first_name'] . ' ' . ($p['last_name'] ?? '')) ?>
							<small class="text-muted ml-1"><?= htmlspecialchars($p['patient_code']) ?></small>
							<span class="badge badge-warning ml-2" style="font-size:0.6rem;vertical-align:middle;">DOCTOR REVIEW</span>
						</h1>
						<p class="text-muted mb-0 small">
							Intake by <?= htmlspecialchars(($intakeUser['first_name'] ?? '') . ' ' . ($intakeUser['last_name'] ?? '')) ?>
							&middot; <?= date('M j, Y g:i A', strtotime($cs['visit_datetime'])) ?>
							&middot; <strong><?= htmlspecialchars($cs['visit_type']) ?></strong>
						</p>
					</div>
				</div>
			</div>
		</div>

		<!-- Auto-save indicator -->
		<div id="autoSaveIndicator" class="auto-save-indicator"><i class="fas fa-check-circle"></i> Saved</div>

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
						<div class="tab-content" id="reviewTabContent">

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 1: INTAKE SUMMARY (read-only)                 -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade show active" id="tab-summary" role="tabpanel">
								<h4 class="mb-4">Intake Summary <small class="text-muted" style="font-size:0.7em;">Read-only — completed by nurse</small></h4>

								<!-- Patient -->
								<div class="summary-section">
									<h6><i class="fas fa-user mr-1"></i>Patient</h6>
									<div class="row">
										<div class="col-md-3 mb-2"><div class="intake-summary-label">Name</div><div class="intake-summary-value"><?= rv($p['first_name'] . ' ' . ($p['last_name'] ?? '')) ?></div></div>
										<div class="col-md-2 mb-2"><div class="intake-summary-label">Code</div><div class="intake-summary-value"><?= rv($p['patient_code']) ?></div></div>
										<div class="col-md-2 mb-2"><div class="intake-summary-label">Sex</div><div class="intake-summary-value"><?= rv($p['sex']) ?></div></div>
										<div class="col-md-2 mb-2"><div class="intake-summary-label">Age</div><div class="intake-summary-value"><?= $p['age_years'] ? (int)$p['age_years'] . ' years' : rv(null) ?></div></div>
										<div class="col-md-3 mb-2"><div class="intake-summary-label">Date of Birth</div><div class="intake-summary-value"><?= rv($p['date_of_birth']) ?></div></div>
									</div>
									<div class="row">
										<div class="col-md-3 mb-2"><div class="intake-summary-label">Phone</div><div class="intake-summary-value"><?= rv($p['phone_e164']) ?></div></div>
										<div class="col-md-3 mb-2"><div class="intake-summary-label">Blood Group</div><div class="intake-summary-value"><?= rv($p['blood_group']) ?></div></div>
										<div class="col-md-6 mb-2"><div class="intake-summary-label">Allergies</div><div class="intake-summary-value"><?= rv($p['allergies']) ?></div></div>
									</div>
									<div class="row">
										<div class="col-md-4 mb-2"><div class="intake-summary-label">Address</div><div class="intake-summary-value"><?= rv(($p['address_line1'] ?? '') . (($p['city'] ?? '') ? ', ' . $p['city'] : '')) ?></div></div>
										<div class="col-md-4 mb-2"><div class="intake-summary-label">Emergency Contact</div><div class="intake-summary-value"><?= rv($p['emergency_contact_name']) ?> <?= $p['emergency_contact_phone'] ? '&middot; ' . htmlspecialchars($p['emergency_contact_phone']) : '' ?></div></div>
									</div>
								</div>

								<!-- Visit -->
								<div class="summary-section">
									<h6><i class="fas fa-notes-medical mr-1"></i>Visit</h6>
									<div class="row">
										<div class="col-md-3 mb-2"><div class="intake-summary-label">Visit Type</div><div class="intake-summary-value"><?= rv($cs['visit_type']) ?></div></div>
										<div class="col-md-9 mb-2"><div class="intake-summary-label">Chief Complaint</div><div class="intake-summary-value"><?= rv($cs['chief_complaint']) ?></div></div>
									</div>
									<?php if (!empty($vitals['symptoms_complaints'])): ?>
									<div class="row">
										<div class="col-md-6 mb-2"><div class="intake-summary-label">Symptoms / Complaints</div><div class="intake-summary-value"><?= rv($vitals['symptoms_complaints']) ?></div></div>
										<div class="col-md-6 mb-2"><div class="intake-summary-label">Duration</div><div class="intake-summary-value"><?= rv($vitals['duration_of_symptoms']) ?></div></div>
									</div>
									<?php endif; ?>
								</div>

								<!-- Vitals -->
								<div class="summary-section">
									<h6><i class="fas fa-heartbeat mr-1"></i>Vitals &amp; Anthropometrics</h6>
									<div class="row">
										<div class="col-6 col-md-2 mb-2"><div class="intake-summary-label">Pulse</div><div class="intake-summary-value"><?= rv($vitals['general_pulse'] ?? $vitals['pulse'] ?? null) ?> <small class="text-muted">/mt</small></div></div>
										<div class="col-6 col-md-2 mb-2"><div class="intake-summary-label">B.P.</div><div class="intake-summary-value"><?= rv(($vitals['general_bp_systolic'] ?? $vitals['bp_systolic'] ?? null)) ?>/<?= rv(($vitals['general_bp_diastolic'] ?? $vitals['bp_diastolic'] ?? null)) ?> <small class="text-muted">mmHg</small></div></div>
										<div class="col-6 col-md-2 mb-2"><div class="intake-summary-label">SpO2</div><div class="intake-summary-value"><?= rv($vitals['spo2'] ?? null) ?> <small class="text-muted">%</small></div></div>
										<div class="col-6 col-md-2 mb-2"><div class="intake-summary-label">Temp</div><div class="intake-summary-value"><?= rv($vitals['temperature'] ?? null) ?> <small class="text-muted">&deg;F</small></div></div>
										<div class="col-6 col-md-2 mb-2"><div class="intake-summary-label">Height</div><div class="intake-summary-value"><?= rv($vitals['general_height'] ?? $vitals['height_cm'] ?? null) ?> <small class="text-muted">cm</small></div></div>
										<div class="col-6 col-md-2 mb-2"><div class="intake-summary-label">Weight</div><div class="intake-summary-value"><?= rv($vitals['general_weight'] ?? $vitals['weight_kg'] ?? null) ?> <small class="text-muted">kg</small></div></div>
									</div>
									<div class="row">
										<div class="col-6 col-md-2 mb-2"><div class="intake-summary-label">BMI</div><div class="intake-summary-value"><?= rv($vitals['general_bmi'] ?? null) ?></div></div>
										<div class="col-6 col-md-2 mb-2"><div class="intake-summary-label">Obesity</div><div class="intake-summary-value"><?= ($vitals['general_obesity_overweight'] ?? '0') == '1' ? 'Yes' : 'No' ?></div></div>
									</div>
								</div>

								<!-- Physical Exam (General) -->
								<?php $hasGeneral = array_intersect_key($vitals, array_flip(['general_heart','general_lungs','general_liver','general_spleen','general_lymph_glands']));
								if (!empty(array_filter($hasGeneral))): ?>
								<div class="summary-section">
									<h6><i class="fas fa-stethoscope mr-1"></i>General Physical Exam (Nurse)</h6>
									<div class="row">
										<?php foreach (['general_heart'=>'Heart','general_lungs'=>'Lungs','general_liver'=>'Liver','general_spleen'=>'Spleen','general_lymph_glands'=>'Lymph Glands'] as $key=>$label): ?>
										<?php if (!empty($vitals[$key])): ?>
										<div class="col-md-4 mb-2"><div class="intake-summary-label"><?= $label ?></div><div class="intake-summary-value"><?= rv($vitals[$key]) ?></div></div>
										<?php endif; ?>
										<?php endforeach; ?>
									</div>
								</div>
								<?php endif; ?>

								<!-- Reproductive / Menstrual -->
								<?php if (!empty($vitals['number_of_children']) || !empty($vitals['menstrual_lmp'])): ?>
								<div class="summary-section">
									<h6><i class="fas fa-female mr-1"></i>Reproductive &amp; Menstrual</h6>
									<div class="row">
										<div class="col-md-2 mb-2"><div class="intake-summary-label">Children</div><div class="intake-summary-value"><?= rv($vitals['number_of_children'] ?? null) ?></div></div>
										<div class="col-md-2 mb-2"><div class="intake-summary-label">Uterus</div><div class="intake-summary-value"><?= ($vitals['has_uterus'] ?? '1') == '1' ? 'Yes' : 'No' ?></div></div>
										<?php if (!empty($vitals['type_of_delivery'])): ?>
										<div class="col-md-2 mb-2"><div class="intake-summary-label">Delivery Type</div><div class="intake-summary-value"><?= rv($vitals['type_of_delivery']) ?></div></div>
										<?php endif; ?>
										<div class="col-md-2 mb-2"><div class="intake-summary-label">LMP</div><div class="intake-summary-value"><?= rv($vitals['menstrual_lmp'] ?? null) ?></div></div>
										<div class="col-md-2 mb-2"><div class="intake-summary-label">Cycle</div><div class="intake-summary-value"><?= rv($vitals['menstrual_mh'] ?? null) ?></div></div>
									</div>
								</div>
								<?php endif; ?>

								<!-- Medical History -->
								<?php if (!empty($histData)): ?>
								<div class="summary-section">
									<h6><i class="fas fa-history mr-1"></i>Medical History</h6>
									<div class="row">
										<?php foreach (['condition_dm'=>'DM','condition_htn'=>'HTN','condition_tsh'=>'TSH','condition_heart_disease'=>'Heart Disease'] as $key=>$label): ?>
										<?php if (!empty($histData[$key]) && $histData[$key] !== 'NO'): ?>
										<div class="col-md-3 mb-2"><div class="intake-summary-label"><?= $label ?></div><div class="intake-summary-value"><?= rv($histData[$key]) ?></div></div>
										<?php endif; ?>
										<?php endforeach; ?>
									</div>
									<?php if (!empty($histData['condition_others'])): ?>
									<div class="mb-2"><div class="intake-summary-label">Other Conditions</div><div class="intake-summary-value"><?= rv($histData['condition_others']) ?></div></div>
									<?php endif; ?>
									<?php if (!empty($histData['surgical_history'])): ?>
									<div class="mb-2"><div class="intake-summary-label">Surgical History</div><div class="intake-summary-value"><?= rv($histData['surgical_history']) ?></div></div>
									<?php endif; ?>
									<?php
										$fhItems = [];
										foreach (['family_history_cancer'=>'Cancer','family_history_tuberculosis'=>'Tuberculosis','family_history_diabetes'=>'Diabetes','family_history_bp'=>'BP','family_history_thyroid'=>'Thyroid'] as $k=>$l) {
											if (!empty($histData[$k])) $fhItems[] = $l;
										}
										if (!empty($histData['family_history_other'])) $fhItems[] = $histData['family_history_other'];
									?>
									<?php if (!empty($fhItems)): ?>
									<div class="mb-2"><div class="intake-summary-label">Family History</div><div class="intake-summary-value"><?= implode(', ', array_map('htmlspecialchars', $fhItems)) ?></div></div>
									<?php endif; ?>
								</div>
								<?php endif; ?>

								<!-- Examinations -->
								<?php if (!empty($examData)): ?>
								<div class="summary-section">
									<h6><i class="fas fa-search mr-1"></i>Physical Examinations (Nurse)</h6>
									<div class="row">
										<?php
										$examLabels = [
											'exam_mouth'=>'Mouth','exam_lips'=>'Lips','exam_buccal_mucosa'=>'Buccal Mucosa',
											'exam_teeth'=>'Teeth','exam_tongue'=>'Tongue','exam_oropharynx'=>'Oropharynx',
											'exam_nose'=>'Nose','exam_ears'=>'Ears','exam_neck'=>'Neck',
											'exam_bones_joints'=>'Bones/Joints','exam_abdomen_genital'=>'Abdomen/Genital',
											'exam_breast_left'=>'Breast L','exam_breast_right'=>'Breast R','exam_breast_axillary_nodes'=>'Axillary Nodes',
											'exam_pelvic_cervix'=>'Cervix','exam_pelvic_uterus'=>'Uterus','exam_pelvic_ovaries'=>'Ovaries','exam_pelvic_adnexa'=>'Adnexa',
											'exam_rectal_skin'=>'Rectal Skin','exam_rectal_remarks'=>'Rectal Remarks',
											'exam_gynae_ps'=>'P/S','exam_gynae_pv'=>'P/V','exam_gynae_via'=>'VIA','exam_gynae_vili'=>'VILI',
										];
										foreach ($examLabels as $key => $label) {
											if (!empty($examData[$key])) {
												echo '<div class="col-md-3 mb-2"><div class="intake-summary-label">' . $label . '</div><div class="intake-summary-value">' . rv($examData[$key]) . '</div></div>';
											}
										}
										?>
									</div>
								</div>
								<?php endif; ?>

								<!-- Labs -->
								<?php if (!empty($labData)): ?>
								<div class="summary-section">
									<h6><i class="fas fa-vial mr-1"></i>Laboratory Results</h6>
									<div class="row">
										<?php foreach (['lab_hb_gms'=>'Hb (gms)','lab_hb_percentage'=>'Hb (%)','lab_fbs'=>'FBS','lab_tsh'=>'TSH','lab_sr_creatinine'=>'Sr. Creatinine'] as $k=>$l): ?>
										<?php if (!empty($labData[$k])): ?>
										<div class="col-md-2 mb-2"><div class="intake-summary-label"><?= $l ?></div><div class="intake-summary-value"><?= rv($labData[$k]) ?></div></div>
										<?php endif; ?>
										<?php endforeach; ?>
										<?php if (!empty($labData['lab_others'])): ?>
										<div class="col-md-4 mb-2"><div class="intake-summary-label">Other Labs</div><div class="intake-summary-value"><?= rv($labData['lab_others']) ?></div></div>
										<?php endif; ?>
									</div>
									<?php
									$cyto = [];
									foreach (['cytology_papsmear'=>'Papsmear','cytology_colposcopy'=>'Colposcopy','cytology_biopsy'=>'Biopsy'] as $k=>$l) {
										if (!empty($labData[$k]) && $labData[$k] !== 'NONE') {
											$note = !empty($labData[$k.'_notes']) ? ': ' . $labData[$k.'_notes'] : '';
											$cyto[] = $l . ' — ' . $labData[$k] . htmlspecialchars($note);
										}
									}
									?>
									<?php if (!empty($cyto)): ?>
									<div class="mt-2"><div class="intake-summary-label">Cytology</div>
										<?php foreach ($cyto as $c): ?>
										<div class="intake-summary-value"><?= htmlspecialchars($c) ?></div>
										<?php endforeach; ?>
									</div>
									<?php endif; ?>
								</div>
								<?php endif; ?>

								<!-- Nurse Summary -->
								<?php if (!empty($summData)): ?>
								<div class="summary-section">
									<h6><i class="fas fa-clipboard-check mr-1"></i>Nurse Summary</h6>
									<div class="row">
										<?php foreach (['summary_risk_level'=>'Risk Level','summary_referral'=>'Referral','summary_patient_acceptance'=>'Patient Acceptance','summary_doctor_summary'=>'Nurse Notes'] as $k=>$l): ?>
										<?php if (!empty($summData[$k])): ?>
										<div class="col-md-6 mb-2"><div class="intake-summary-label"><?= $l ?></div><div class="intake-summary-value"><?= rv($summData[$k]) ?></div></div>
										<?php endif; ?>
										<?php endforeach; ?>
									</div>
								</div>
								<?php endif; ?>

								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary" disabled><i class="fas fa-chevron-left"></i> Previous</button>
									<button type="button" class="btn btn-success btn-next-tab" data-target="#tab-examination">Begin Examination <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 2: EXAMINATION (doctor)                       -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-examination" role="tabpanel">
								<h4 class="mb-1">Clinical Examination</h4>
								<p class="text-muted mb-4">Record your findings from the physical examination.</p>
								<form class="doctor-auto-save">
									<div class="form-group">
										<label for="doctor_exam_notes">Examination Findings</label>
										<textarea class="form-control" id="doctor_exam_notes" name="doctor_exam_notes"
										          data-field="doctor_exam_notes" rows="12"
										          placeholder="Document your clinical examination findings here — e.g. general appearance, systems review, specific findings..."
										><?= htmlspecialchars($cs['doctor_exam_notes'] ?? '') ?></textarea>
										<small class="text-muted">Auto-saved. Every change is logged with your name and timestamp.</small>
									</div>
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-summary"><i class="fas fa-chevron-left"></i> Intake Summary</button>
									<button type="button" class="btn btn-success btn-next-tab" data-target="#tab-assessment">Assessment &amp; Diagnosis <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 3: ASSESSMENT & DIAGNOSIS (doctor)            -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-assessment" role="tabpanel">
								<h4 class="mb-1">Assessment &amp; Diagnosis</h4>
								<p class="text-muted mb-4">Record your clinical assessment and formal diagnosis.</p>
								<form class="doctor-auto-save">
									<div class="form-group">
										<label for="doctor_assessment">Clinical Assessment</label>
										<textarea class="form-control" id="doctor_assessment" name="doctor_assessment"
										          data-field="doctor_assessment" rows="6"
										          placeholder="Your clinical impression and assessment of the patient's condition..."
										><?= htmlspecialchars($cs['doctor_assessment'] ?? '') ?></textarea>
									</div>
									<div class="form-group">
										<label for="doctor_diagnosis">Diagnosis</label>
										<textarea class="form-control" id="doctor_diagnosis" name="doctor_diagnosis"
										          data-field="doctor_diagnosis" rows="6"
										          placeholder="Formal diagnosis (primary and secondary diagnoses, ICD codes if applicable)..."
										><?= htmlspecialchars($cs['doctor_diagnosis'] ?? '') ?></textarea>
									</div>
									<small class="text-muted">Auto-saved. Every change is logged with your name and timestamp.</small>
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-examination"><i class="fas fa-chevron-left"></i> Examination</button>
									<button type="button" class="btn btn-success btn-next-tab" data-target="#tab-plan">Treatment Plan <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 4: TREATMENT PLAN (doctor)                    -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-plan" role="tabpanel">
								<h4 class="mb-1">Treatment Plan</h4>
								<p class="text-muted mb-4">Document the treatment plan, prescriptions, and advice for this patient.</p>
								<form class="doctor-auto-save">
									<div class="form-group">
										<label for="doctor_plan_notes">Treatment Plan / Clinical Notes</label>
										<textarea class="form-control" id="doctor_plan_notes" name="doctor_plan_notes"
										          data-field="doctor_plan_notes" rows="5"
										          placeholder="Overall treatment plan and any additional clinical notes..."
										><?= htmlspecialchars($cs['doctor_plan_notes'] ?? '') ?></textarea>
									</div>
									<div class="form-group">
										<label for="prescriptions">Prescriptions</label>
										<textarea class="form-control" id="prescriptions" name="prescriptions"
										          data-field="prescriptions" rows="5"
										          placeholder="List medications, dosage, frequency, and duration..."
										><?= htmlspecialchars($cs['prescriptions'] ?? '') ?></textarea>
									</div>
									<div class="form-group">
										<label for="advice">Advice &amp; Instructions</label>
										<textarea class="form-control" id="advice" name="advice"
										          data-field="advice" rows="4"
										          placeholder="Dietary advice, lifestyle changes, warning signs to watch for..."
										><?= htmlspecialchars($cs['advice'] ?? '') ?></textarea>
									</div>
									<small class="text-muted">Auto-saved. Every change is logged with your name and timestamp.</small>
								</form>
								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-assessment"><i class="fas fa-chevron-left"></i> Assessment</button>
									<button type="button" class="btn btn-success btn-next-tab" data-target="#tab-followup">Follow-up &amp; Referrals <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 5: FOLLOW-UP & REFERRALS + CLOSE CHART        -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-followup" role="tabpanel">
								<h4 class="mb-1">Follow-up &amp; Referrals</h4>
								<p class="text-muted mb-4">Specify any follow-up appointments, referrals, and final disposition.</p>
								<form class="doctor-auto-save">
									<h5 class="mb-3">Follow-up</h5>
									<div class="row">
										<div class="col-md-4 mb-3">
											<label for="follow_up_date">Follow-up Date</label>
											<input type="date" class="form-control" id="follow_up_date" name="follow_up_date"
											       data-field="follow_up_date"
											       value="<?= htmlspecialchars($cs['follow_up_date'] ?? '') ?>" />
										</div>
										<div class="col-md-8 mb-3">
											<label for="follow_up_notes">Follow-up Instructions</label>
											<textarea class="form-control" id="follow_up_notes" name="follow_up_notes"
											          data-field="follow_up_notes" rows="3"
											          placeholder="Who to follow up with, what to monitor, what to bring to next appointment..."
											><?= htmlspecialchars($cs['follow_up_notes'] ?? '') ?></textarea>
										</div>
									</div>

									<h5 class="mb-3">Referrals</h5>
									<div class="row">
										<div class="col-md-4 mb-3">
											<label for="referral_to">Referral To</label>
											<input type="text" class="form-control" id="referral_to" name="referral_to"
											       data-field="referral_to"
											       value="<?= htmlspecialchars($cs['referral_to'] ?? '') ?>"
											       placeholder="Doctor name, specialty, or facility..." />
										</div>
										<div class="col-md-8 mb-3">
											<label for="referral_reason">Referral Reason</label>
											<textarea class="form-control" id="referral_reason" name="referral_reason"
											          data-field="referral_reason" rows="3"
											          placeholder="Reason for referral and any relevant clinical information to include..."
											><?= htmlspecialchars($cs['referral_reason'] ?? '') ?></textarea>
										</div>
									</div>

									<h5 class="mb-3">History of Present Illness</h5>
									<div class="mb-3">
										<label for="history_present_illness">HPI Narrative</label>
										<textarea class="form-control" id="history_present_illness" name="history_present_illness"
										          data-field="history_present_illness" rows="4"
										          placeholder="Narrative history of the present illness as obtained during consultation..."
										><?= htmlspecialchars($cs['history_present_illness'] ?? '') ?></textarea>
									</div>

									<div class="row">
										<div class="col-md-6 mb-3">
											<label for="disposition">Disposition</label>
											<input type="text" class="form-control" id="disposition" name="disposition"
											       data-field="disposition"
											       value="<?= htmlspecialchars($cs['disposition'] ?? '') ?>"
											       placeholder="e.g. Discharged home, Admitted, Transfer..." />
										</div>
									</div>
									<small class="text-muted">Auto-saved. Every change is logged with your name and timestamp.</small>
								</form>

								<!-- ─── Close Chart ─────────────────────────────── -->
								<div class="card card-outline card-danger mt-5">
									<div class="card-header">
										<h3 class="card-title"><i class="fas fa-folder-minus mr-2"></i>Close Chart</h3>
									</div>
									<div class="card-body">
										<p class="text-muted">Closing this chart will lock it. This action cannot be undone. Ensure all examination notes, assessment, diagnosis, and treatment plan are complete before proceeding.</p>
										<form method="post" action="review.php?action=close" id="closeChartForm">
											<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
											<input type="hidden" name="case_sheet_id" value="<?= $csId ?>" />
											<div class="row align-items-end">
												<div class="col-md-4 mb-3">
													<label for="closure_type">Closure Type <span class="text-danger">*</span></label>
													<select class="form-control" id="closure_type" name="closure_type" required>
														<option value="">-- Select --</option>
														<option value="DISCHARGED" <?= ($cs['closure_type'] ?? '') === 'DISCHARGED' ? 'selected' : '' ?>>Discharged</option>
														<option value="FOLLOW_UP"  <?= ($cs['closure_type'] ?? '') === 'FOLLOW_UP'  ? 'selected' : '' ?>>Follow-up Scheduled</option>
														<option value="REFERRAL"   <?= ($cs['closure_type'] ?? '') === 'REFERRAL'   ? 'selected' : '' ?>>Referred</option>
														<option value="PENDING"    <?= ($cs['closure_type'] ?? '') === 'PENDING'    ? 'selected' : '' ?>>Pending</option>
													</select>
												</div>
												<div class="col-md-8 mb-3">
													<button type="submit" class="btn btn-danger btn-lg" id="closeChartBtn">
														<i class="fas fa-folder-minus mr-1"></i>Close Chart
													</button>
													<small class="d-block text-muted mt-1">
														<i class="fas fa-lock mr-1"></i>Chart will be locked and marked as closed.
														Recorded: closed by <?= htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) ?> at time of submission.
													</small>
												</div>
											</div>
										</form>
									</div>
								</div>

								<div class="tab-navigation">
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-plan"><i class="fas fa-chevron-left"></i> Treatment Plan</button>
									<button type="button" class="btn btn-outline-secondary btn-next-tab" data-target="#tab-audit">View Audit History <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

							<!-- ══════════════════════════════════════════════════ -->
							<!-- TAB 6: AUDIT HISTORY                              -->
							<!-- ══════════════════════════════════════════════════ -->
							<div class="tab-pane fade" id="tab-audit" role="tabpanel">
								<h4 class="mb-1">Audit History</h4>
								<p class="text-muted mb-4">Complete record of every change made to this chart — who changed it, when, and what the previous value was.</p>
								<?php if (empty($auditLog)): ?>
								<div class="alert alert-light border">No changes recorded yet.</div>
								<?php else: ?>
								<div class="table-responsive">
									<table class="table table-sm table-hover table-bordered mb-0">
										<thead class="thead-light">
											<tr>
												<th style="width:160px">When</th>
												<th style="width:140px">By</th>
												<th style="width:160px">Field</th>
												<th>Previous Value</th>
												<th>New Value</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($auditLog as $entry): ?>
											<tr class="<?= ($entry['old_value'] !== $entry['new_value'] && $entry['old_value'] !== null) ? 'audit-row-changed' : '' ?>">
												<td class="text-nowrap small"><?= date('M j, Y g:i:s A', strtotime($entry['changed_at'])) ?></td>
												<td class="small"><?= htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']) ?></td>
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
									<button type="button" class="btn btn-secondary btn-next-tab" data-target="#tab-followup"><i class="fas fa-chevron-left"></i> Follow-up &amp; Referrals</button>
									<button type="button" class="btn btn-secondary" disabled>End <i class="fas fa-chevron-right"></i></button>
								</div>
							</div>

						</div><!-- /tab-content -->
					</div>
				</div>
			</div>
		</section>
	</div>

	<footer class="main-footer text-sm"><strong>CareSystem</strong> &middot; Doctor Review</footer>
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
		$indicator.removeClass('error').addClass('saving').text('Saving...').fadeIn();
		$.ajax({
			url: 'update_case_sheet.php',
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({ case_sheet_id: caseSheetId, field: field, value: value, csrf_token: csrfToken }),
			dataType: 'json',
			success: function (r) {
				pendingSaves--;
				if (r.success) {
					$indicator.removeClass('saving').html('<i class="fas fa-check-circle"></i> Saved').fadeIn();
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
</body>
</html>
