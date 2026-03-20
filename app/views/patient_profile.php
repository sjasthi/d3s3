<?php
// ── Label maps ──────────────────────────────────────────────────────────────
$_sexLabels = [
	'MALE'    => 'Male',
	'FEMALE'  => 'Female',
	'OTHER'   => 'Other',
	'UNKNOWN' => 'Unknown',
];

$_csStatusColors = [
	'INTAKE_IN_PROGRESS' => 'warning',
	'INTAKE_COMPLETE'    => 'info',
	'SCHEDULED'          => 'primary',
	'DOCTOR_REVIEW'      => 'primary',
	'CLOSED'             => 'success',
];
$_csStatusLabels = [
	'INTAKE_IN_PROGRESS' => 'In Progress',
	'INTAKE_COMPLETE'    => 'Intake Complete',
	'SCHEDULED'          => 'Scheduled',
	'DOCTOR_REVIEW'      => 'Doctor Review',
	'CLOSED'             => 'Closed',
];

$_visitTypeLabels = [
	'CAMP'      => 'Camp',
	'CLINIC'    => 'Clinic',
	'FOLLOW_UP' => 'Follow-up',
	'OTHER'     => 'Other',
];

$_fbTypeColors = [
	'POSITIVE'   => 'badge-success',
	'COMPLAINT'  => 'badge-warning',
	'SUGGESTION' => 'badge-info',
	'GRIEVANCE'  => 'badge-danger',
];

$_fbStatusLabels = [
	'NEW'      => 'New',
	'REVIEWED' => 'Reviewed',
	'ACTIONED' => 'Actioned',
	'CLOSED'   => 'Closed',
];

$_roleLabels = [
	'SUPER_ADMIN'         => 'Super Admin',
	'ADMIN'               => 'Admin',
	'DOCTOR'              => 'Doctor',
	'TRIAGE_NURSE'        => 'Triage Nurse',
	'NURSE'               => 'Nurse',
	'PARAMEDIC'           => 'Paramedic',
	'GRIEVANCE_OFFICER'   => 'Grievance Officer',
	'EDUCATION_TEAM'      => 'Education Team',
	'DATA_ENTRY_OPERATOR' => 'Data Entry Operator',
];

$_accessTypeLabels = [
	'VIEW_PROFILE'    => 'Viewed Profile',
	'VIEW_CASE_SHEET' => 'Viewed Case Sheet',
];

// ── Computed display values ──────────────────────────────────────────────────
$_fullName  = htmlspecialchars(($patient['first_name'] ?? '') . (isset($patient['last_name']) ? ' ' . $patient['last_name'] : ''));
$_dob       = $patient['date_of_birth']  ? date('d M Y', strtotime($patient['date_of_birth']))  : null;
$_firstSeen = $patient['first_seen_date'] ? date('d M Y', strtotime($patient['first_seen_date'])) : null;

// ── Back-navigation URL ───────────────────────────────────────────────────────
// Carry forward whatever search params brought the user here so clicking
// "Back" restores the exact results list rather than an empty search page.
$_backParams = [];
if (!empty($_GET['name'])) $_backParams[] = 'name=' . urlencode($_GET['name']);
if (!empty($_GET['dob']))  $_backParams[] = 'dob='  . urlencode($_GET['dob']);
$_backUrl   = 'patients.php' . (!empty($_backParams) ? '?' . implode('&', $_backParams) : '');
$_backLabel = !empty($_backParams) ? 'Back to Results' : 'Back to Patients';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?= $_fullName ?> | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
		/* ── General info layout ────────────────────────────────────────────── */
		.patient-header-card { border-left: 4px solid #007bff; }
		.info-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; margin-bottom: .1rem; }
		.info-value { font-size: .95rem; margin-bottom: .75rem; }
		.access-log-notice { font-size: .8rem; }

		/* ── Case sheet entry cards ─────────────────────────────────────────── */
		.cs-entry {
			border-left-width: 4px !important;
			border-left-style: solid !important;
		}
		.cs-entry.border-left-warning  { border-left-color: #ffc107 !important; }
		.cs-entry.border-left-info     { border-left-color: #17a2b8 !important; }
		.cs-entry.border-left-primary  { border-left-color: #007bff !important; }
		.cs-entry.border-left-success  { border-left-color: #28a745 !important; }
		.cs-entry.border-left-secondary{ border-left-color: #6c757d !important; }
		.cs-entry.cs-locked            { opacity: .8; }

		/* ── Tappable card header ───────────────────────────────────────────── */
		/* min-height 56px gives a generous touch target on tablet.            */
		.cs-entry-header {
			min-height: 56px;
			padding: .75rem 1rem;
			display: flex;
			align-items: center;
			cursor: pointer;
			user-select: none;
			-webkit-user-select: none;
			transition: background .12s;
			background: transparent;
			border-bottom: none;
		}
		.cs-entry-header:hover  { background: rgba(0, 0, 0, .035); }
		.cs-entry-header:active { background: rgba(0, 0, 0, .07);  }

		/* Remove Bootstrap's default card-header styling for our custom headers */
		.cs-entry > .cs-entry-header { border-radius: inherit; }

		/* ── Chevron rotates when panel is open ─────────────────────────────── */
		.cs-chevron i {
			transition: transform .22s ease;
			font-size: 1rem;
			color: #adb5bd;
		}
		.cs-entry-header[aria-expanded="true"] .cs-chevron i {
			transform: rotate(180deg);
			color: #495057;
		}

		/* ── Clinical detail body ───────────────────────────────────────────── */
		.cs-detail-body {
			background: #f8f9fa;
			border-top: 1px solid rgba(0,0,0,.08);
			padding: 1rem 1.25rem;
		}
		.cs-field-label {
			font-size: .7rem;
			text-transform: uppercase;
			letter-spacing: .06em;
			color: #6c757d;
			margin-bottom: .2rem;
		}
		.cs-field-value {
			font-size: .9rem;
			white-space: pre-wrap;
			word-break: break-word;
		}

		/* ── Vitals grid ────────────────────────────────────────────────────── */
		.vitals-grid {
			display: flex;
			flex-wrap: wrap;
			gap: .5rem;
		}
		.vitals-item {
			background: #fff;
			border: 1px solid #dee2e6;
			border-radius: .3rem;
			padding: .3rem .6rem;
			min-width: 90px;
			text-align: center;
		}
		.vitals-item .v-label { font-size: .65rem; text-transform: uppercase; color: #6c757d; }
		.vitals-item .v-value { font-size: .95rem; font-weight: 600; }

		/* ── "No entries" hint text ─────────────────────────────────────────── */
		.hint-tap { font-size: .8rem; color: #6c757d; }

		/* ── Full-record modal ──────────────────────────────────────────────── */
		.cs-modal-section-title {
			font-size: .7rem;
			text-transform: uppercase;
			letter-spacing: .07em;
			color: #6c757d;
			border-bottom: 1px solid #dee2e6;
			padding-bottom: .3rem;
			margin-bottom: .75rem;
			margin-top: 1.25rem;
		}
		.cs-modal-section-title:first-child { margin-top: 0; }
		.cs-modal-field-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: #868e96; margin-bottom: .15rem; }
		.cs-modal-field-value { font-size: .9rem; white-space: pre-wrap; word-break: break-word; margin-bottom: .75rem; }
		.cs-open-btn { padding: .2rem .45rem; line-height: 1; }

		@media print {
			.main-header, .main-sidebar, .main-footer,
			#patientTabs, .content-header { display: none !important; }
			.modal { position: static !important; overflow: visible !important; }
			.modal-dialog { max-width: 100% !important; margin: 0 !important; transform: none !important; }
			.modal-content { border: none !important; box-shadow: none !important; }
			.modal-body { overflow: visible !important; max-height: none !important; }
		}
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
			<li class="nav-item mr-2">
				<a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($_backUrl) ?>" role="button">
					<i class="fas fa-arrow-left mr-1"></i><?= htmlspecialchars($_backLabel) ?>
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
				<div class="row mb-2 align-items-center">
					<div class="col">
						<a href="<?= htmlspecialchars($_backUrl) ?>"
						   class="btn btn-sm btn-outline-secondary mb-2">
							<i class="fas fa-arrow-left mr-1"></i><?= htmlspecialchars($_backLabel) ?>
						</a>
						<h1 class="m-0 text-dark">
							<i class="fas fa-user-injured mr-2"></i><?= $_fullName ?>
						</h1>
						<p class="text-muted mb-0">
							<span class="text-monospace"><?= htmlspecialchars($patient['patient_code']) ?></span>
							<?php if (!$patient['is_active']): ?>
							&nbsp;<span class="badge badge-secondary">Inactive</span>
							<?php endif; ?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

				<!-- Patient summary banner -->
				<div class="card patient-header-card mb-3">
					<div class="card-body py-3">
						<div class="row text-center text-sm-left">
							<div class="col-6 col-sm-3 col-md-2 mb-2 mb-md-0">
								<div class="info-label">Sex</div>
								<div class="info-value mb-0"><?= htmlspecialchars($_sexLabels[$patient['sex']] ?? $patient['sex']) ?></div>
							</div>
							<div class="col-6 col-sm-3 col-md-2 mb-2 mb-md-0">
								<div class="info-label">Age</div>
								<div class="info-value mb-0">
									<?php if ($_dob): ?>
									<?= htmlspecialchars($patient['age_years'] ?? '') ?> yrs
									<small class="text-muted d-block"><?= $_dob ?></small>
									<?php elseif ($patient['age_years']): ?>
									<?= (int)$patient['age_years'] ?> yrs
									<?php else: ?>
									&mdash;
									<?php endif; ?>
								</div>
							</div>
							<div class="col-6 col-sm-3 col-md-2 mb-2 mb-md-0">
								<div class="info-label">Blood Group</div>
								<div class="info-value mb-0">
									<?= $patient['blood_group'] ? '<strong>' . htmlspecialchars($patient['blood_group']) . '</strong>' : '&mdash;' ?>
								</div>
							</div>
							<div class="col-6 col-sm-3 col-md-2 mb-2 mb-md-0">
								<div class="info-label">Phone</div>
								<div class="info-value mb-0"><?= htmlspecialchars($patient['phone_e164'] ?? '&mdash;') ?></div>
							</div>
							<div class="col-6 col-sm-3 col-md-2 mb-2 mb-md-0">
								<div class="info-label">Visits</div>
								<div class="info-value mb-0"><?= count($caseSheets) ?></div>
							</div>
							<div class="col-6 col-sm-3 col-md-2 mb-2 mb-md-0">
								<div class="info-label">First Seen</div>
								<div class="info-value mb-0"><?= $_firstSeen ?? '&mdash;' ?></div>
							</div>
						</div>
					</div>
				</div>

				<!-- Allergies alert -->
				<?php if (!empty($patient['allergies'])): ?>
				<div class="alert alert-danger mb-3" role="alert">
					<i class="fas fa-exclamation-triangle mr-2"></i>
					<strong>Allergies:</strong> <?= htmlspecialchars($patient['allergies']) ?>
				</div>
				<?php endif; ?>

				<!-- Tabbed content -->
				<div class="card card-primary card-tabs">
					<div class="card-header p-0 pt-1">
						<ul class="nav nav-tabs" id="patientTabs" role="tablist">
							<li class="nav-item">
								<a class="nav-link active" id="tab-info-tab" data-toggle="pill"
								   href="#tab-info" role="tab" aria-controls="tab-info" aria-selected="true">
									<i class="fas fa-id-card mr-1"></i>Personal Info
								</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" id="tab-history-tab" data-toggle="pill"
								   href="#tab-history" role="tab" aria-controls="tab-history" aria-selected="false">
									<i class="fas fa-notes-medical mr-1"></i>Medical History
									<?php if (count($caseSheets) > 0): ?>
									<span class="badge badge-primary ml-1"><?= count($caseSheets) ?></span>
									<?php endif; ?>
								</a>
							</li>
							<?php if ($canSeeFeedback): ?>
							<li class="nav-item">
								<a class="nav-link" id="tab-grievances-tab" data-toggle="pill"
								   href="#tab-grievances" role="tab" aria-controls="tab-grievances" aria-selected="false">
									<i class="fas fa-comment-alt mr-1"></i>Grievances
									<?php if (count($grievances) > 0): ?>
									<span class="badge badge-warning ml-1"><?= count($grievances) ?></span>
									<?php endif; ?>
								</a>
							</li>
							<?php endif; ?>
							<?php if ($canSeeAccessLog): ?>
							<li class="nav-item">
								<a class="nav-link" id="tab-access-tab" data-toggle="pill"
								   href="#tab-access" role="tab" aria-controls="tab-access" aria-selected="false">
									<i class="fas fa-shield-alt mr-1"></i>Access Log
									<?php if (count($accessLog) > 0): ?>
									<span class="badge badge-secondary ml-1"><?= count($accessLog) ?></span>
									<?php endif; ?>
								</a>
							</li>
							<?php endif; ?>
						</ul>
					</div>
					<div class="card-body">
						<div class="tab-content" id="patientTabsContent">

							<!-- ── Tab 1: Personal Information ───────────────────── -->
							<div class="tab-pane fade show active" id="tab-info" role="tabpanel" aria-labelledby="tab-info-tab">
								<div class="row">

									<!-- Demographics column -->
									<div class="col-md-6">
										<h6 class="text-uppercase text-muted font-weight-bold mb-3 border-bottom pb-1">
											<i class="fas fa-user mr-1"></i>Demographics
										</h6>

										<div class="info-label">Full Name</div>
										<div class="info-value"><?= $_fullName ?></div>

										<div class="info-label">Patient Code</div>
										<div class="info-value text-monospace"><?= htmlspecialchars($patient['patient_code']) ?></div>

										<div class="info-label">Sex</div>
										<div class="info-value"><?= htmlspecialchars($_sexLabels[$patient['sex']] ?? $patient['sex']) ?></div>

										<?php if ($_dob): ?>
										<div class="info-label">Date of Birth</div>
										<div class="info-value"><?= $_dob ?></div>
										<?php endif; ?>

										<?php if ($patient['age_years'] !== null): ?>
										<div class="info-label">Age</div>
										<div class="info-value"><?= (int)$patient['age_years'] ?> years</div>
										<?php endif; ?>

										<div class="info-label">Blood Group</div>
										<div class="info-value">
											<?= $patient['blood_group'] ? htmlspecialchars($patient['blood_group']) : '&mdash;' ?>
										</div>

										<div class="info-label">Allergies</div>
										<div class="info-value">
											<?php if (!empty($patient['allergies'])): ?>
											<span class="text-danger font-weight-bold"><?= htmlspecialchars($patient['allergies']) ?></span>
											<?php else: ?>
											<span class="text-muted">None on record</span>
											<?php endif; ?>
										</div>

										<div class="info-label">First Seen</div>
										<div class="info-value"><?= $_firstSeen ?? '&mdash;' ?></div>
									</div>

									<!-- Contact column -->
									<div class="col-md-6">
										<h6 class="text-uppercase text-muted font-weight-bold mb-3 border-bottom pb-1">
											<i class="fas fa-phone mr-1"></i>Contact &amp; Address
										</h6>

										<div class="info-label">Phone</div>
										<div class="info-value"><?= htmlspecialchars($patient['phone_e164'] ?? '—') ?></div>

										<?php if (!empty($patient['whatsapp_e164'])): ?>
										<div class="info-label">WhatsApp</div>
										<div class="info-value"><?= htmlspecialchars($patient['whatsapp_e164']) ?></div>
										<?php endif; ?>

										<?php
										$_addrParts = array_filter([
											$patient['address_line1']  ?? null,
											$patient['address_line2']  ?? null,
											$patient['city']           ?? null,
											$patient['state_province'] ?? null,
											$patient['postal_code']    ?? null,
											($patient['country_code'] ?? 'IN') !== 'IN' ? ($patient['country_code'] ?? null) : null,
										]);
										?>
										<div class="info-label">Address</div>
										<div class="info-value">
											<?php if (!empty($_addrParts)): ?>
											<?= implode(', ', array_map('htmlspecialchars', $_addrParts)) ?>
											<?php else: ?>
											<span class="text-muted">Not on record</span>
											<?php endif; ?>
										</div>

										<h6 class="text-uppercase text-muted font-weight-bold mb-3 mt-4 border-bottom pb-1">
											<i class="fas fa-user-friends mr-1"></i>Emergency Contact
										</h6>

										<div class="info-label">Name</div>
										<div class="info-value">
											<?= !empty($patient['emergency_contact_name'])
												? htmlspecialchars($patient['emergency_contact_name'])
												: '<span class="text-muted">Not on record</span>' ?>
										</div>

										<div class="info-label">Phone</div>
										<div class="info-value">
											<?= !empty($patient['emergency_contact_phone'])
												? htmlspecialchars($patient['emergency_contact_phone'])
												: '<span class="text-muted">Not on record</span>' ?>
										</div>
									</div>

								</div>
							</div>

							<!-- ── Tab 2: Medical History (Case Sheets) ───────────── -->
							<div class="tab-pane fade" id="tab-history" role="tabpanel" aria-labelledby="tab-history-tab">

								<?php if (empty($caseSheets)): ?>
								<div class="text-center py-5 text-muted">
									<i class="fas fa-notes-medical fa-2x mb-2 d-block"></i>
									No case sheets on record for this patient.
								</div>
								<?php else: ?>

								<p class="hint-tap mb-3">
									<i class="fas fa-hand-pointer mr-1"></i>
									Tap or click any entry to expand its clinical notes.
									<?= count($caseSheets) ?> visit<?= count($caseSheets) !== 1 ? 's' : '' ?> on record, most recent first.
								</p>

								<div id="caseSheetList">
								<?php foreach ($caseSheets as $_cs):
									$_csId      = (int)$_cs['case_sheet_id'];
									$_csColor   = $_csStatusColors[$_cs['status']] ?? 'secondary';

									// Determine if this entry has any clinical content to expand
									$_hasBody   = !empty($_cs['chief_complaint'])
									           || !empty($_cs['history_present_illness'])
									           || !empty($_cs['assessment'])
									           || !empty($_cs['diagnosis'])
									           || !empty($_cs['plan_notes'])
									           || !empty($_cs['doctor_assessment'])
									           || !empty($_cs['doctor_diagnosis'])
									           || !empty($_cs['doctor_plan_notes'])
									           || !empty($_cs['prescriptions'])
									           || !empty($_cs['follow_up_notes'])
									           || !empty($_cs['vitals_json']);

									// Parse vitals JSON for display
									$_vitals = [];
									if (!empty($_cs['vitals_json'])) {
										$_decoded = @json_decode($_cs['vitals_json'], true);
										if (is_array($_decoded)) {
											$_vitals = $_decoded;
										}
									}
								?>
								<div class="card cs-entry border-left-<?= $_csColor ?> mb-2<?= !empty($_cs['is_locked']) ? ' cs-locked' : '' ?>">

									<!-- ── Tappable header ───────────────────────── -->
									<div class="cs-entry-header<?= $_hasBody ? '' : ' pe-none' ?>"
									     <?php if ($_hasBody): ?>
									     data-toggle="collapse"
									     data-target="#cs-body-<?= $_csId ?>"
									     aria-expanded="false"
									     aria-controls="cs-body-<?= $_csId ?>"
									     role="button"
									     tabindex="0"
									     <?php endif; ?>>

										<!-- Date column (fixed width) -->
										<div class="mr-3 text-center flex-shrink-0" style="min-width:52px">
											<div class="font-weight-bold" style="font-size:1rem; line-height:1.1">
												<?= date('d', strtotime($_cs['visit_datetime'])) ?>
											</div>
											<div class="text-muted small"><?= date('M Y', strtotime($_cs['visit_datetime'])) ?></div>
											<div class="text-muted" style="font-size:.7rem"><?= date('H:i', strtotime($_cs['visit_datetime'])) ?></div>
										</div>

										<!-- Main info (flex-grow) -->
										<div class="flex-grow-1" style="min-width:0">
											<!-- Status + type badges -->
											<div class="mb-1">
												<span class="badge badge-<?= $_csColor ?>">
													<?= htmlspecialchars($_csStatusLabels[$_cs['status']] ?? $_cs['status']) ?>
												</span>
												<span class="badge badge-light border text-muted ml-1">
													<?= htmlspecialchars($_visitTypeLabels[$_cs['visit_type']] ?? $_cs['visit_type']) ?>
												</span>
												<?php if (!empty($_cs['is_locked'])): ?>
												<i class="fas fa-lock text-muted ml-1 small" title="Record locked"></i>
												<?php endif; ?>
											</div>

											<!-- Chief complaint -->
											<div class="text-truncate font-weight-500" style="max-width:100%">
												<?php if (!empty($_cs['chief_complaint'])): ?>
												<?= htmlspecialchars(mb_strimwidth($_cs['chief_complaint'], 0, 120, '…')) ?>
												<?php else: ?>
												<span class="text-muted font-italic small">No chief complaint recorded</span>
												<?php endif; ?>
											</div>

											<!-- Staff names -->
											<div class="text-muted small mt-1">
												<?php if (!empty($_cs['doctor_first'])): ?>
												<i class="fas fa-user-md mr-1"></i><?= htmlspecialchars($_cs['doctor_first'] . ' ' . ($_cs['doctor_last'] ?? '')) ?>
												<?php else: ?>
												<span class="text-muted"><i class="fas fa-user-md mr-1"></i>Doctor unassigned</span>
												<?php endif; ?>
												<?php if (!empty($_cs['creator_first'])): ?>
												&ensp;&bull;&ensp;<i class="fas fa-user-nurse mr-1"></i><?= htmlspecialchars($_cs['creator_first'] . ' ' . ($_cs['creator_last'] ?? '')) ?>
												<?php endif; ?>
											</div>
										</div>

										<!-- Actions: open-modal button + chevron ──────────────────── -->
										<div class="ml-2 flex-shrink-0 d-flex align-items-center">
											<button type="button" class="btn btn-sm btn-outline-secondary cs-open-btn mr-1"
											        data-toggle="modal"
											        data-target="#cs-modal-<?= $_csId ?>"
											        title="Open full record">
												<i class="fas fa-expand-alt"></i>
											</button>
											<?php if ($_hasBody): ?>
											<div class="cs-chevron">
												<i class="fas fa-chevron-down"></i>
											</div>
											<?php else: ?>
											<div style="width:16px"></div>
											<?php endif; ?>
										</div>

									</div><!-- /cs-entry-header -->

									<!-- ── Collapsible clinical detail ───────────── -->
									<?php if ($_hasBody): ?>
									<div id="cs-body-<?= $_csId ?>" class="collapse">
										<div class="cs-detail-body">
											<div class="row">

												<?php if (!empty($_cs['chief_complaint'])): ?>
												<div class="col-12 mb-3">
													<div class="cs-field-label">Chief Complaint</div>
													<div class="cs-field-value"><?= nl2br(htmlspecialchars($_cs['chief_complaint'])) ?></div>
												</div>
												<?php endif; ?>

												<?php if (!empty($_cs['history_present_illness'])): ?>
												<div class="col-md-6 mb-3">
													<div class="cs-field-label">History of Present Illness</div>
													<div class="cs-field-value"><?= nl2br(htmlspecialchars($_cs['history_present_illness'])) ?></div>
												</div>
												<?php endif; ?>

												<?php if (!empty($_cs['exam_notes'])): ?>
												<div class="col-md-6 mb-3">
													<div class="cs-field-label">Examination Notes</div>
													<div class="cs-field-value"><?= nl2br(htmlspecialchars($_cs['exam_notes'])) ?></div>
												</div>
												<?php endif; ?>

												<?php
												$_assessment = $_cs['doctor_assessment'] ?? $_cs['assessment'] ?? null;
												if (!empty($_assessment)):
												?>
												<div class="col-md-6 mb-3">
													<div class="cs-field-label">Assessment</div>
													<div class="cs-field-value"><?= nl2br(htmlspecialchars($_assessment)) ?></div>
												</div>
												<?php endif; ?>

												<?php
												$_diagnosis = $_cs['doctor_diagnosis'] ?? $_cs['diagnosis'] ?? null;
												if (!empty($_diagnosis)):
												?>
												<div class="col-md-6 mb-3">
													<div class="cs-field-label">Diagnosis</div>
													<div class="cs-field-value"><?= nl2br(htmlspecialchars($_diagnosis)) ?></div>
												</div>
												<?php endif; ?>

												<?php
												$_plan = $_cs['doctor_plan_notes'] ?? $_cs['plan_notes'] ?? null;
												if (!empty($_plan)):
												?>
												<div class="col-md-6 mb-3">
													<div class="cs-field-label">Plan / Treatment</div>
													<div class="cs-field-value"><?= nl2br(htmlspecialchars($_plan)) ?></div>
												</div>
												<?php endif; ?>

												<?php if (!empty($_cs['prescriptions'])): ?>
												<div class="col-md-6 mb-3">
													<div class="cs-field-label">Prescriptions</div>
													<div class="cs-field-value"><?= nl2br(htmlspecialchars($_cs['prescriptions'])) ?></div>
												</div>
												<?php endif; ?>

												<?php if (!empty($_cs['follow_up_notes'])): ?>
												<div class="col-md-6 mb-3">
													<div class="cs-field-label">Follow-up Notes</div>
													<div class="cs-field-value"><?= nl2br(htmlspecialchars($_cs['follow_up_notes'])) ?></div>
												</div>
												<?php endif; ?>

												<?php if (!empty($_vitals)): ?>
												<div class="col-12 mb-3">
													<div class="cs-field-label mb-2">Vitals</div>
													<div class="vitals-grid">
														<?php foreach ($_vitals as $_vKey => $_vVal): ?>
														<?php if (!is_array($_vVal) && (string)$_vVal !== ''): ?>
														<div class="vitals-item">
															<div class="v-label"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $_vKey))) ?></div>
															<div class="v-value"><?= htmlspecialchars($_vVal) ?></div>
														</div>
														<?php endif; ?>
														<?php endforeach; ?>
													</div>
												</div>
												<?php endif; ?>

												<div class="col-12 mt-2 pt-2 border-top d-flex justify-content-end">
													<button type="button" class="btn btn-primary btn-sm cs-open-btn"
													        data-toggle="modal"
													        data-target="#cs-modal-<?= $_csId ?>">
														<i class="fas fa-expand-alt mr-1"></i>Open Full Record
													</button>
												</div>
											</div><!-- /row -->
										</div><!-- /cs-detail-body -->
									</div><!-- /collapse -->
									<?php endif; ?>

								</div><!-- /cs-entry card -->
								<?php endforeach; ?>
								</div><!-- /caseSheetList -->

								<!-- ── Full-record modals (one per case sheet) ──────────────────────── -->
								<?php
								$_mBorderColorMap = [
									'warning'  => '#ffc107',
									'info'     => '#17a2b8',
									'primary'  => '#007bff',
									'success'  => '#28a745',
									'secondary'=> '#6c757d',
								];
								?>
								<?php foreach ($caseSheets as $_cs):
									$_csId         = (int)$_cs['case_sheet_id'];
									$_csColor      = $_csStatusColors[$_cs['status']] ?? 'secondary';
									$_mBorder      = $_mBorderColorMap[$_csColor] ?? '#6c757d';
									$_mVitals      = [];
									if (!empty($_cs['vitals_json'])) {
										$_vdec = @json_decode($_cs['vitals_json'], true);
										if (is_array($_vdec)) $_mVitals = $_vdec;
									}
									$_mAssessment  = $_cs['doctor_assessment'] ?? $_cs['assessment'] ?? null;
									$_mDiagnosis   = $_cs['doctor_diagnosis']  ?? $_cs['diagnosis']  ?? null;
									$_mPlan        = $_cs['doctor_plan_notes'] ?? $_cs['plan_notes'] ?? null;
									$_mHasIntake   = !empty($_cs['chief_complaint']) || !empty($_cs['history_present_illness']) || !empty($_cs['exam_notes']);
									$_mHasClinical = !empty($_mAssessment) || !empty($_mDiagnosis) || !empty($_mPlan)
									              || !empty($_cs['prescriptions']) || !empty($_cs['follow_up_notes']);
								?>
								<div class="modal fade" id="cs-modal-<?= $_csId ?>" tabindex="-1"
									role="dialog" aria-labelledby="cs-mlbl-<?= $_csId ?>" aria-hidden="true">
									<div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
										<div class="modal-content">

											<!-- Modal header -->
											<div class="modal-header" style="border-left:4px solid <?= $_mBorder ?>">
												<div>
													<h5 class="modal-title mb-1" id="cs-mlbl-<?= $_csId ?>">
														<i class="fas fa-notes-medical text-primary mr-1"></i>
														Case Sheet &mdash; <?= htmlspecialchars(date('d M Y, H:i', strtotime($_cs['visit_datetime']))) ?>
													</h5>
													<div class="small text-muted">
														<?= $_fullName ?>
														&bull; <span class="text-monospace"><?= htmlspecialchars($patient['patient_code']) ?></span>
														&bull; <span class="badge badge-<?= $_csColor ?>"><?= htmlspecialchars($_csStatusLabels[$_cs['status']] ?? $_cs['status']) ?></span>
														<span class="badge badge-light border ml-1"><?= htmlspecialchars($_visitTypeLabels[$_cs['visit_type']] ?? $_cs['visit_type']) ?></span>
													</div>
												</div>
												<div class="ml-auto d-flex align-items-center flex-shrink-0">
													<button type="button" class="btn btn-sm btn-outline-secondary mr-2"
													        onclick="window.print()" title="Print this record">
														<i class="fas fa-print mr-1"></i>Print
													</button>
													<button type="button" class="close ml-1" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true">&times;</span>
													</button>
												</div>
											</div>

											<!-- Modal body -->
											<div class="modal-body">

												<!-- Allergy warning -->
												<?php if (!empty($patient['allergies'])): ?>
												<div class="alert alert-danger py-2">
													<i class="fas fa-exclamation-triangle mr-1"></i>
													<strong>Allergies:</strong> <?= htmlspecialchars($patient['allergies']) ?>
												</div>
												<?php endif; ?>

												<!-- Vitals -->
												<?php if (!empty($_mVitals)): ?>
												<div class="cs-modal-section-title" style="margin-top:0">
													<i class="fas fa-heartbeat mr-1"></i>Vitals
												</div>
												<div class="vitals-grid mb-3">
													<?php foreach ($_mVitals as $_vk => $_vv): ?>
													<?php if (!is_array($_vv) && (string)$_vv !== ''): ?>
													<div class="vitals-item">
														<div class="v-label"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $_vk))) ?></div>
														<div class="v-value"><?= htmlspecialchars($_vv) ?></div>
													</div>
													<?php endif; ?>
													<?php endforeach; ?>
												</div>
												<?php endif; ?>

												<!-- Triage & Intake -->
												<?php if ($_mHasIntake): ?>
												<div class="cs-modal-section-title">
													<i class="fas fa-clipboard-list mr-1"></i>Triage &amp; Intake
												</div>
												<div class="row">
													<?php if (!empty($_cs['chief_complaint'])): ?>
													<div class="col-12 col-md-6">
														<div class="cs-modal-field-label">Chief Complaint</div>
														<div class="cs-modal-field-value"><?= nl2br(htmlspecialchars($_cs['chief_complaint'])) ?></div>
													</div>
													<?php endif; ?>
													<?php if (!empty($_cs['history_present_illness'])): ?>
													<div class="col-12 col-md-6">
														<div class="cs-modal-field-label">History of Present Illness</div>
														<div class="cs-modal-field-value"><?= nl2br(htmlspecialchars($_cs['history_present_illness'])) ?></div>
													</div>
													<?php endif; ?>
													<?php if (!empty($_cs['exam_notes'])): ?>
													<div class="col-12 col-md-6">
														<div class="cs-modal-field-label">Examination Notes</div>
														<div class="cs-modal-field-value"><?= nl2br(htmlspecialchars($_cs['exam_notes'])) ?></div>
													</div>
													<?php endif; ?>
												</div>
												<?php endif; ?>

												<!-- Clinical Assessment -->
												<?php if ($_mHasClinical): ?>
												<div class="cs-modal-section-title">
													<i class="fas fa-user-md mr-1"></i>Clinical Assessment
												</div>
												<div class="row">
													<?php if (!empty($_mAssessment)): ?>
													<div class="col-12 col-md-6">
														<div class="cs-modal-field-label">Assessment</div>
														<div class="cs-modal-field-value"><?= nl2br(htmlspecialchars($_mAssessment)) ?></div>
													</div>
													<?php endif; ?>
													<?php if (!empty($_mDiagnosis)): ?>
													<div class="col-12 col-md-6">
														<div class="cs-modal-field-label">Diagnosis</div>
														<div class="cs-modal-field-value"><?= nl2br(htmlspecialchars($_mDiagnosis)) ?></div>
													</div>
													<?php endif; ?>
													<?php if (!empty($_mPlan)): ?>
													<div class="col-12 col-md-6">
														<div class="cs-modal-field-label">Plan / Treatment</div>
														<div class="cs-modal-field-value"><?= nl2br(htmlspecialchars($_mPlan)) ?></div>
													</div>
													<?php endif; ?>
													<?php if (!empty($_cs['prescriptions'])): ?>
													<div class="col-12 col-md-6">
														<div class="cs-modal-field-label">Prescriptions</div>
														<div class="cs-modal-field-value"><?= nl2br(htmlspecialchars($_cs['prescriptions'])) ?></div>
													</div>
													<?php endif; ?>
													<?php if (!empty($_cs['follow_up_notes'])): ?>
													<div class="col-12 col-md-6">
														<div class="cs-modal-field-label">Follow-up Notes</div>
														<div class="cs-modal-field-value"><?= nl2br(htmlspecialchars($_cs['follow_up_notes'])) ?></div>
													</div>
													<?php endif; ?>
												</div>
												<?php endif; ?>

												<!-- Record metadata -->
												<div class="cs-modal-section-title">
													<i class="fas fa-info-circle mr-1"></i>Record Information
												</div>
												<div class="row">
													<div class="col-6 col-md-3">
														<div class="cs-modal-field-label">Case Sheet #</div>
														<div class="cs-modal-field-value text-monospace"><?= $_csId ?></div>
													</div>
													<div class="col-6 col-md-3">
														<div class="cs-modal-field-label">Visit Date &amp; Time</div>
														<div class="cs-modal-field-value"><?= htmlspecialchars(date('d M Y, H:i', strtotime($_cs['visit_datetime']))) ?></div>
													</div>
													<div class="col-6 col-md-3">
														<div class="cs-modal-field-label">Status</div>
														<div class="cs-modal-field-value"><span class="badge badge-<?= $_csColor ?>"><?= htmlspecialchars($_csStatusLabels[$_cs['status']] ?? $_cs['status']) ?></span></div>
													</div>
													<div class="col-6 col-md-3">
														<div class="cs-modal-field-label">Visit Type</div>
														<div class="cs-modal-field-value"><?= htmlspecialchars($_visitTypeLabels[$_cs['visit_type']] ?? $_cs['visit_type']) ?></div>
													</div>
													<?php if (!empty($_cs['doctor_first'])): ?>
													<div class="col-6 col-md-3">
														<div class="cs-modal-field-label">Assigned Doctor</div>
														<div class="cs-modal-field-value"><?= htmlspecialchars($_cs['doctor_first'] . ' ' . ($_cs['doctor_last'] ?? '')) ?></div>
													</div>
													<?php endif; ?>
													<?php if (!empty($_cs['creator_first'])): ?>
													<div class="col-6 col-md-3">
														<div class="cs-modal-field-label">Created By</div>
														<div class="cs-modal-field-value"><?= htmlspecialchars($_cs['creator_first'] . ' ' . ($_cs['creator_last'] ?? '')) ?></div>
													</div>
													<?php endif; ?>
													<?php if (!empty($_cs['is_locked'])): ?>
													<div class="col-6 col-md-3">
														<div class="cs-modal-field-label">Record Lock</div>
														<div class="cs-modal-field-value"><i class="fas fa-lock text-warning mr-1"></i>Locked</div>
													</div>
													<?php endif; ?>
												</div><!-- /row -->

											</div><!-- /modal-body -->

											<div class="modal-footer">
												<button type="button" class="btn btn-outline-secondary" onclick="window.print()">
													<i class="fas fa-print mr-1"></i>Print
												</button>
												<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
											</div>

										</div><!-- /modal-content -->
									</div><!-- /modal-dialog -->
								</div><!-- /#cs-modal-<?= $_csId ?> -->
								<?php endforeach; ?>

								<?php endif; ?>
							</div><!-- /tab-history -->

							<!-- ── Tab 3: Grievances ─────────────────────────────── -->
							<?php if ($canSeeFeedback): ?>
							<div class="tab-pane fade" id="tab-grievances" role="tabpanel" aria-labelledby="tab-grievances-tab">

								<?php if (empty($grievances)): ?>
								<div class="text-center py-5 text-muted">
									<i class="fas fa-comment-alt fa-2x mb-2 d-block"></i>
									No grievances or feedback on record for this patient.
								</div>
								<?php else: ?>
								<div class="table-responsive">
									<table class="table table-hover mb-0">
										<thead class="thead-light">
											<tr>
												<th>#</th>
												<th>Type</th>
												<th>Rating</th>
												<th>Status</th>
												<th>Summary / Excerpt</th>
												<th>Date</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($grievances as $_fb): ?>
											<tr>
												<td class="text-muted small"><?= (int)$_fb['feedback_id'] ?></td>
												<td>
													<span class="badge <?= $_fbTypeColors[$_fb['feedback_type']] ?? 'badge-secondary' ?>">
														<?= htmlspecialchars(ucfirst(strtolower($_fb['feedback_type']))) ?>
													</span>
												</td>
												<td>
													<?php if (isset($_fb['rating']) && $_fb['rating'] !== null): ?>
													<?= (int)$_fb['rating'] ?>/5
													<?php else: ?>
													&mdash;
													<?php endif; ?>
												</td>
												<td><?= htmlspecialchars($_fbStatusLabels[$_fb['status']] ?? $_fb['status']) ?></td>
												<td>
													<?php if (!empty($_fb['feedback_text'])): ?>
													<?= htmlspecialchars(mb_strimwidth($_fb['feedback_text'], 0, 100, '…')) ?>
													<?php else: ?>
													<span class="text-muted">No text</span>
													<?php endif; ?>
												</td>
												<td class="text-nowrap text-muted small">
													<?= htmlspecialchars(date('d M Y', strtotime($_fb['created_at']))) ?>
												</td>
											</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
								<?php endif; ?>

							</div>
							<?php endif; ?>

							<!-- ── Tab 4: Access Log ─────────────────────────────── -->
							<?php if ($canSeeAccessLog): ?>
							<div class="tab-pane fade" id="tab-access" role="tabpanel" aria-labelledby="tab-access-tab">

								<div class="alert alert-info access-log-notice mb-3" role="alert">
									<i class="fas fa-shield-alt mr-1"></i>
									This log records every time a staff member has viewed this patient's record.
									Access to patient records should only occur when there is a clinical reason.
									Unexplained access should be investigated per your organisation's privacy policy.
									<strong>Note:</strong> the current page view will appear the next time this tab is loaded.
								</div>

								<?php if (empty($accessLog)): ?>
								<div class="text-center py-5 text-muted">
									<i class="fas fa-shield-alt fa-2x mb-2 d-block"></i>
									No access log entries found.
									<small class="d-block">(Run migration 018 if this table does not yet exist.)</small>
								</div>
								<?php else: ?>
								<div class="table-responsive">
									<table class="table table-sm table-hover mb-0">
										<thead class="thead-light">
											<tr>
												<th>Date / Time</th>
												<th>Staff Member</th>
												<th>Role</th>
												<th>Action</th>
												<th>IP Address</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($accessLog as $_al): ?>
											<tr>
												<td class="text-nowrap text-muted small">
													<?= htmlspecialchars(date('d M Y H:i:s', strtotime($_al['accessed_at']))) ?>
												</td>
												<td><?= htmlspecialchars($_al['first_name'] . ' ' . $_al['last_name']) ?></td>
												<td>
													<span class="badge badge-secondary">
														<?= htmlspecialchars($_roleLabels[$_al['viewer_role']] ?? $_al['viewer_role']) ?>
													</span>
												</td>
												<td><?= htmlspecialchars($_accessTypeLabels[$_al['access_type']] ?? $_al['access_type']) ?></td>
												<td class="text-monospace small"><?= htmlspecialchars($_al['ip_address'] ?? '&mdash;') ?></td>
											</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
								<p class="text-muted small mt-2 mb-0">Showing most recent <?= count($accessLog) ?> entries (max 200).</p>
								<?php endif; ?>

							</div>
							<?php endif; ?>

						</div><!-- /tab-content -->
					</div>
				</div><!-- /card -->

			</div>
		</section>
	</div>

	<footer class="main-footer">
		<strong>D3S3 CareSystem</strong>
	</footer>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script>
$(function () {

	// ── Keyboard: Enter/Space on a card header triggers collapse ─────────────
	// Bootstrap collapse already handles mouse clicks via data-toggle.
	// This adds keyboard and touch accessibility for the header rows.
	// Stop the collapse toggle from firing when the Open button is clicked
	$(document).on('click', '.cs-open-btn', function (e) {
		e.stopPropagation();
	});

	$(document).on('keydown', '.cs-entry-header[data-toggle]', function (e) {
		if (e.key === 'Enter' || e.key === ' ') {
			e.preventDefault();
			$(this).trigger('click');
		}
	});

	// ── Preserve active tab across navigation via URL hash ───────────────────
	var hash = window.location.hash;
	if (hash) {
		$('#patientTabs a[href="' + hash + '"]').tab('show');
	}
	$('#patientTabs a').on('shown.bs.tab', function (e) {
		history.replaceState(null, null, e.target.getAttribute('href'));
	});

});
</script>
</body>
</html>
