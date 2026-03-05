<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Appointments | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
<style>
#apptDrawerOverlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.38);z-index:1054;}
#apptDrawerOverlay.open{display:block;}
#apptDrawer{position:fixed;top:0;right:0;width:380px;max-width:95vw;height:100%;background:#fff;z-index:1055;transform:translateX(110%);transition:transform .28s cubic-bezier(.4,0,.2,1);overflow-y:auto;box-shadow:-6px 0 28px rgba(0,0,0,.18);display:flex;flex-direction:column;}
#apptDrawer.open{transform:translateX(0);}
.dark-mode #apptDrawer{background:#343a40;color:#dee2e6;border-left:1px solid #495057;}
.appt-drawer-hd{padding:1rem 1.25rem;border-bottom:1px solid rgba(0,0,0,.1);display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;flex-shrink:0;}
.dark-mode .appt-drawer-hd{border-color:rgba(255,255,255,.1);}
.appt-drawer-bd{padding:1.25rem;overflow-y:auto;flex:1;}
.appt-drawer-sec{margin-bottom:1.25rem;padding-bottom:1.25rem;border-bottom:1px solid rgba(0,0,0,.08);}
.appt-drawer-sec:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0;}
.dark-mode .appt-drawer-sec{border-color:rgba(255,255,255,.08);}
.appt-drawer-sec-title{font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;margin-bottom:.6rem;}
table tbody tr:has(.appt-open-drawer){cursor:pointer;}
table tbody tr:has(.appt-open-drawer):hover td{background-color:rgba(0,0,0,.025);}
.dark-mode table tbody tr:has(.appt-open-drawer):hover td{background-color:rgba(255,255,255,.04);}
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
			<li class="nav-item d-flex align-items-center mr-3">
				<div class="custom-control custom-switch theme-switch">
					<input type="checkbox" class="custom-control-input" id="themeToggleAppt" data-theme-toggle />
					<label class="custom-control-label" for="themeToggleAppt">Dark mode</label>
				</div>
			</li>
			<li class="nav-item">
				<a class="btn btn-sm btn-outline-secondary" href="<?= $isAdminRole ? 'admin.php' : 'dashboard.php' ?>" role="button">
					<i class="fas fa-arrow-left mr-1"></i>Dashboard
				</a>
			</li>
		</ul>
	</nav>

	<?php require __DIR__ . '/_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2 align-items-center">
					<div class="col-sm-6">
						<h1 class="m-0 text-dark">Appointments</h1>
						<p class="text-muted mb-0">Upcoming patient appointments.</p>
					</div>
					<div class="col-sm-6 d-flex justify-content-end align-items-center flex-wrap">
						<?php if ($isNurseRole || $isAdminRole): ?>
						<button type="button" class="btn btn-primary btn-sm mr-2 mb-1"
						        data-toggle="modal" data-target="#scheduleModal">
							<i class="fas fa-calendar-plus mr-1"></i>New Appointment
						</button>
						<?php endif; ?>
						<div class="input-group input-group-sm d-inline-flex mb-1" style="max-width:260px;">
							<input type="text" id="patientSearchInput" class="form-control"
							       placeholder="Search patient…" autocomplete="off" />
							<div class="input-group-append">
								<span class="input-group-text"><i class="fas fa-search"></i></span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">

				<!-- Patient search results (hidden until query) -->
				<div id="patientSearchResults" class="card d-none mb-3">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h3 class="card-title"><i class="fas fa-search mr-2"></i>Search Results</h3>
						<button type="button" class="btn btn-sm btn-outline-secondary" id="clearSearch">
							<i class="fas fa-times mr-1"></i>Clear
						</button>
					</div>
					<div class="card-body p-0" id="searchResultsBody"></div>
				</div>

				<!-- Tabs -->
				<ul class="nav nav-tabs mb-3">
					<li class="nav-item">
						<a class="nav-link <?= ($tab === 'today' || $tab === '') ? 'active' : '' ?>"
						   href="appointments.php?tab=today">
							<i class="fas fa-calendar-day mr-1"></i>Today
							<?php if (count($todayAppts) > 0): ?>
							<span class="badge badge-primary ml-1"><?= count($todayAppts) ?></span>
							<?php endif; ?>
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?= $tab === 'upcoming' ? 'active' : '' ?>"
						   href="appointments.php?tab=upcoming">
							<i class="fas fa-calendar-week mr-1"></i>Upcoming (7 days)
							<?php if (count($upcomingAppts) > 0): ?>
							<span class="badge badge-secondary ml-1"><?= count($upcomingAppts) ?></span>
							<?php endif; ?>
						</a>
					</li>
					<?php if ($isNurseRole || $isAdminRole): ?>
					<li class="nav-item">
						<a class="nav-link <?= $tab === 'pending' ? 'active' : '' ?>"
						   href="appointments.php?tab=pending">
							<i class="fas fa-user-clock mr-1"></i>Pending Assignment
							<?php if (count($pendingCases) > 0): ?>
							<span class="badge badge-warning ml-1"><?= count($pendingCases) ?></span>
							<?php endif; ?>
						</a>
					</li>
					<?php endif; ?>
				</ul>

				<?php
				$apptStatusColors = [
					'SCHEDULED'   => 'badge-info',
					'CONFIRMED'   => 'badge-primary',
					'IN_PROGRESS' => 'badge-warning',
					'COMPLETED'   => 'badge-success',
					'CANCELLED'   => 'badge-danger',
					'NO_SHOW'     => 'badge-secondary',
				];
				$apptStatusLabels = [
					'SCHEDULED'   => 'Scheduled',
					'CONFIRMED'   => 'Confirmed',
					'IN_PROGRESS' => 'In Progress',
					'COMPLETED'   => 'Completed',
					'CANCELLED'   => 'Cancelled',
					'NO_SHOW'     => 'No Show',
				];
				$visitModeIcons = [
					'IN_PERSON' => 'fa-hospital',
					'REMOTE'    => 'fa-video',
					'CAMP'      => 'fa-campground',
				];
				$visitModeLabels = [
					'IN_PERSON' => 'In Person',
					'REMOTE'    => 'Remote',
					'CAMP'      => 'Camp',
				];
				?>

				<?php if ($tab === 'today' || $tab === ''): ?>
				<!-- ── Today ─────────────────────────────────── -->
				<div class="card">
					<div class="card-header">
						<h3 class="card-title"><i class="fas fa-calendar-day mr-2"></i>Today's Appointments</h3>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-hover mb-0">
								<thead class="thead-light">
									<tr>
										<th>Patient</th>
										<th>Time</th>
										<th>Doctor</th>
										<th>Mode</th>
										<th>Chief Complaint</th>
										<th>Status</th>
										<?php if ($isNurseRole || $isAdminRole || $isDoctorRole): ?><th>Actions</th><?php endif; ?>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($todayAppts as $a): ?>
									<tr id="appt-row-<?= (int)$a['appointment_id'] ?>">
										<td>
											<strong><?= htmlspecialchars($a['first_name'] . ' ' . ($a['last_name'] ?? '')) ?></strong>
											<br /><small class="text-muted">
												<?= htmlspecialchars($a['patient_code']) ?>
												<?= $a['age_years'] ? ' · ' . (int)$a['age_years'] . ' yrs' : '' ?>
												<?= ($a['sex'] && $a['sex'] !== 'UNKNOWN') ? ' · ' . htmlspecialchars($a['sex']) : '' ?>
											</small>
										</td>
										<td class="text-nowrap">
											<?= $a['scheduled_time']
												? htmlspecialchars(date('g:i A', strtotime($a['scheduled_time'])))
												: '<span class="text-muted">—</span>' ?>
										</td>
										<td>Dr. <?= htmlspecialchars($a['doc_first'] . ' ' . $a['doc_last']) ?></td>
										<td>
											<i class="fas <?= htmlspecialchars($visitModeIcons[$a['visit_mode']] ?? 'fa-hospital') ?> mr-1 text-muted"></i>
											<?= htmlspecialchars($visitModeLabels[$a['visit_mode']] ?? $a['visit_mode']) ?>
											<?= $a['event_title'] ? '<br /><small class="text-muted">' . htmlspecialchars($a['event_title']) . '</small>' : '' ?>
										</td>
										<td class="small"><?= htmlspecialchars($a['chief_complaint'] ?? '—') ?></td>
										<td>
											<span class="badge <?= $apptStatusColors[$a['appt_status']] ?? 'badge-secondary' ?>">
												<?= $apptStatusLabels[$a['appt_status']] ?? htmlspecialchars($a['appt_status']) ?>
											</span>
										</td>
									<?php if ($isNurseRole || $isAdminRole || $isDoctorRole): ?>
									<td><?php echo apptActionDropdown((int)$a['appointment_id'], $a['appt_status'], (int)($a['doctor_user_id'] ?? 0), $isDoctorRole, $isNurseRole || $isAdminRole, $a['first_name'] . ' ' . ($a['last_name'] ?? ''), $a['patient_code'] ?? '', 'Dr. ' . $a['doc_first'] . ' ' . $a['doc_last'], $a['scheduled_time'] ?? '', (int)($a['case_sheet_id'] ?? 0)); ?></td>
									<?php endif; ?>
									</tr>
									<?php endforeach; ?>
									<?php if (empty($todayAppts)): ?>
									<tr>
										<td colspan="6" class="text-center text-muted py-4">No appointments scheduled for today.</td>
									</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<?php elseif ($tab === 'upcoming'): ?>
				<!-- ── Upcoming ──────────────────────────────── -->
				<div class="card">
					<div class="card-header">
						<h3 class="card-title"><i class="fas fa-calendar-week mr-2"></i>Upcoming Appointments – Next 7 Days</h3>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-hover mb-0">
								<thead class="thead-light">
									<tr>
										<th>Date</th>
										<th>Patient</th>
										<th>Time</th>
										<th>Doctor</th>
										<th>Mode</th>
										<th>Chief Complaint</th>
										<th>Status</th>
										<?php if ($isNurseRole || $isAdminRole || $isDoctorRole): ?><th>Actions</th><?php endif; ?>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($upcomingAppts as $a): ?>
									<tr id="appt-row-<?= (int)$a['appointment_id'] ?>">
										<td class="text-nowrap font-weight-bold">
											<?= htmlspecialchars(date('D, M j', strtotime($a['scheduled_date']))) ?>
										</td>
										<td>
											<strong><?= htmlspecialchars($a['first_name'] . ' ' . ($a['last_name'] ?? '')) ?></strong>
											<br /><small class="text-muted">
												<?= htmlspecialchars($a['patient_code']) ?>
												<?= $a['age_years'] ? ' · ' . (int)$a['age_years'] . ' yrs' : '' ?>
											</small>
										</td>
										<td class="text-nowrap">
											<?= $a['scheduled_time']
												? htmlspecialchars(date('g:i A', strtotime($a['scheduled_time'])))
												: '<span class="text-muted">—</span>' ?>
										</td>
										<td>Dr. <?= htmlspecialchars($a['doc_first'] . ' ' . $a['doc_last']) ?></td>
										<td>
											<i class="fas <?= htmlspecialchars($visitModeIcons[$a['visit_mode']] ?? 'fa-hospital') ?> mr-1 text-muted"></i>
											<?= htmlspecialchars($visitModeLabels[$a['visit_mode']] ?? $a['visit_mode']) ?>
										</td>
										<td class="small"><?= htmlspecialchars($a['chief_complaint'] ?? '—') ?></td>
										<td>
											<span class="badge <?= $apptStatusColors[$a['appt_status']] ?? 'badge-secondary' ?>">
												<?= $apptStatusLabels[$a['appt_status']] ?? htmlspecialchars($a['appt_status']) ?>
											</span>
										</td>
									<?php if ($isNurseRole || $isAdminRole || $isDoctorRole): ?>
									<td><?php echo apptActionDropdown((int)$a['appointment_id'], $a['appt_status'], (int)($a['doctor_user_id'] ?? 0), $isDoctorRole, $isNurseRole || $isAdminRole, $a['first_name'] . ' ' . ($a['last_name'] ?? ''), $a['patient_code'] ?? '', 'Dr. ' . $a['doc_first'] . ' ' . $a['doc_last'], $a['scheduled_time'] ?? '', (int)($a['case_sheet_id'] ?? 0)); ?></td>
									<?php endif; ?>
									</tr>
									<?php endforeach; ?>
									<?php if (empty($upcomingAppts)): ?>
									<tr>
										<td colspan="7" class="text-center text-muted py-4">No appointments in the next 7 days.</td>
									</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<?php elseif ($tab === 'pending' && ($isNurseRole || $isAdminRole)): ?>
				<!-- ── Pending assignment ─────────────────────── -->
				<div id="assignFeedback" class="d-none mb-3"></div>
				<div class="card">
					<div class="card-header">
						<h3 class="card-title"><i class="fas fa-user-clock mr-2"></i>Cases Pending Doctor Assignment</h3>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-hover mb-0">
								<thead class="thead-light">
									<tr>
										<th>Patient</th>
										<th>Visit Type</th>
										<th>Chief Complaint</th>
										<th>Currently Assigned</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody id="pendingTableBody">
									<?php foreach ($pendingCases as $cs): ?>
									<tr id="pending-row-<?= (int)$cs['case_sheet_id'] ?>">
										<td>
											<strong><?= htmlspecialchars($cs['first_name'] . ' ' . ($cs['last_name'] ?? '')) ?></strong>
											<br /><small class="text-muted">
												<?= htmlspecialchars($cs['patient_code']) ?>
												<?= $cs['age_years'] ? ' · ' . (int)$cs['age_years'] . ' yrs' : '' ?>
											</small>
										</td>
										<td><span class="badge badge-secondary"><?= htmlspecialchars($cs['visit_type']) ?></span></td>
										<td class="small"><?= htmlspecialchars($cs['chief_complaint'] ?? '—') ?></td>
										<td class="small assigned-doctor-cell">
											<?= $cs['assigned_doctor_user_id']
												? 'Dr. ' . htmlspecialchars($cs['doc_first'] . ' ' . $cs['doc_last'])
												: '<span class="text-muted">Unassigned</span>' ?>
										</td>
										<td>
											<button type="button" class="btn btn-sm btn-outline-primary assign-btn"
											        data-case-sheet-id="<?= (int)$cs['case_sheet_id'] ?>"
											        data-patient-name="<?= htmlspecialchars($cs['first_name'] . ' ' . ($cs['last_name'] ?? '')) ?>">
												<i class="fas fa-user-md mr-1"></i>Assign
											</button>
											<button type="button" class="btn btn-sm btn-success schedule-from-pending-btn ml-1"
											        data-case-sheet-id="<?= (int)$cs['case_sheet_id'] ?>"
											        data-patient-id="<?= (int)$cs['patient_id'] ?>"
											        data-patient-name="<?= htmlspecialchars($cs['first_name'] . ' ' . ($cs['last_name'] ?? '')) ?>"
											        data-doctor-user-id="<?= (int)($cs['assigned_doctor_user_id'] ?? 0) ?>"
											        data-doctor-name="<?= $cs['assigned_doctor_user_id'] ? htmlspecialchars('Dr. ' . $cs['doc_first'] . ' ' . $cs['doc_last']) : '' ?>">
												<i class="fas fa-calendar-plus mr-1"></i>Schedule
											</button>
										</td>
									</tr>
									<?php endforeach; ?>
									<?php if (empty($pendingCases)): ?>
									<tr>
										<td colspan="5" class="text-center text-muted py-4">No cases pending doctor assignment.</td>
									</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<?php endif; ?>

			</div>
		</section>
	</div>

	<footer class="main-footer">
		<strong>D3S3 CareSystem</strong>
	</footer>
</div>

<?php
/**
 * apptActionDropdown – render the trigger button for the slide-out action drawer.
 */
function apptActionDropdown(int $apptId, string $currentStatus, int $doctorUserId, bool $isDoctor, bool $isNurseOrAdmin, string $patientName = '', string $patientCode = '', string $doctorName = '', string $scheduledTime = '', int $caseSheetId = 0): string
{
    $terminal = in_array($currentStatus, ['COMPLETED', 'CANCELLED', 'NO_SHOW'], true);
    if ($terminal) return '';

    $myUserId = (int)($_SESSION['user_id'] ?? 0);
    if ($isDoctor && $doctorUserId !== $myUserId) {
        return '';
    }

    $timeDisplay = ($scheduledTime && $scheduledTime !== '00:00:00')
        ? htmlspecialchars(date('g:i A', strtotime($scheduledTime)))
        : '';

    return '<button type="button" class="btn btn-sm btn-outline-secondary appt-open-drawer" '
         . 'data-appt-id="' . $apptId . '" '
         . 'data-case-sheet-id="' . $caseSheetId . '" '
         . 'data-status="' . htmlspecialchars($currentStatus) . '" '
         . 'data-doctor-user-id="' . $doctorUserId . '" '
         . 'data-is-nurse-admin="' . ($isNurseOrAdmin ? '1' : '0') . '" '
         . 'data-patient-name="' . htmlspecialchars($patientName) . '" '
         . 'data-patient-code="' . htmlspecialchars($patientCode) . '" '
         . 'data-doctor-name="' . htmlspecialchars($doctorName) . '" '
         . 'data-scheduled-time="' . $timeDisplay . '" '
         . 'title="Appointment Actions" aria-label="Actions for ' . htmlspecialchars($patientName) . '">'
         . '<i class="fas fa-ellipsis-h"></i></button>';
}
?>

<!-- ── Appointment action drawer ─────────────────────────────────────── -->
<div id="apptDrawerOverlay"></div>
<div id="apptDrawer" role="complementary" aria-label="Appointment actions">
	<div class="appt-drawer-hd">
		<div>
			<h5 class="mb-0 font-weight-bold" id="apptDrawerPatient"></h5>
			<small class="text-muted" id="apptDrawerMeta"></small>
		</div>
		<button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" id="apptDrawerClose" aria-label="Close panel">
			<i class="fas fa-times"></i>
		</button>
	</div>
	<div class="appt-drawer-bd">
		<div id="apptDrawerAlert" class="d-none mb-3"></div>

		<!-- Check In -->
		<div class="appt-drawer-sec d-none" id="apptDrawerCheckInSec">
			<button type="button" id="apptDrawerCheckInBtn" class="btn btn-success btn-block">
				<i class="fas fa-user-check mr-2"></i>Check In Patient
			</button>
			<small class="text-muted d-block mt-1 text-center">Marks as In Progress &amp; opens intake form</small>
		</div>

		<!-- Status actions -->
		<div class="appt-drawer-sec" id="apptDrawerStatusSec">
			<div class="appt-drawer-sec-title"><i class="fas fa-exchange-alt mr-1"></i>Update Status</div>
			<div id="apptDrawerStatusBtns" class="d-flex flex-wrap" style="gap:.4rem;"></div>
		</div>

		<!-- Start Intake -->
		<div class="appt-drawer-sec d-none" id="apptDrawerIntakeSec">
			<div class="appt-drawer-sec-title"><i class="fas fa-clipboard-list mr-1"></i>Intake</div>
			<a id="apptDrawerIntakeBtn" href="#" class="btn btn-outline-primary btn-sm btn-block">
				<i class="fas fa-notes-medical mr-1"></i>Start / Resume Intake
			</a>
		</div>

		<!-- Reschedule -->
		<div class="appt-drawer-sec d-none" id="apptDrawerReschedSec">
			<div class="appt-drawer-sec-title"><i class="fas fa-calendar-alt mr-1"></i>Reschedule</div>
			<div class="form-group mb-2">
				<label class="small mb-1">New Date <span class="text-danger">*</span></label>
				<input type="date" id="aamReschedDate" class="form-control form-control-sm" />
			</div>
			<div class="form-group mb-2">
				<label class="small mb-1">New Time <span class="text-muted">(optional)</span></label>
				<input type="time" id="aamReschedTime" class="form-control form-control-sm" />
			</div>
			<div class="form-group mb-2">
				<label class="small mb-1">Note <span class="text-muted">(optional)</span></label>
				<textarea id="aamReschedNote" class="form-control form-control-sm" rows="2" maxlength="500"></textarea>
			</div>
			<button type="button" id="aamReschedBtn" class="btn btn-primary btn-sm btn-block">
				<i class="fas fa-calendar-check mr-1"></i>Confirm Reschedule
			</button>
		</div>

		<!-- Cancel -->
		<div class="appt-drawer-sec d-none" id="apptDrawerCancelSec">
			<div class="appt-drawer-sec-title text-danger"><i class="fas fa-ban mr-1"></i>Cancel Appointment</div>
			<div class="form-group mb-2">
				<label class="small mb-1">Reason / Note <span class="text-muted">(optional)</span></label>
				<textarea id="aamCancelNote" class="form-control form-control-sm" rows="2" maxlength="500"></textarea>
			</div>
			<button type="button" id="aamCancelBtn" class="btn btn-danger btn-sm btn-block">
				<i class="fas fa-ban mr-1"></i>Cancel Appointment
			</button>
		</div>
	</div>
</div>

<?php if ($isNurseRole || $isAdminRole): ?>
<!-- ── Schedule / New Appointment modal ───────────────────────────────── -->
<div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog"
     aria-labelledby="scheduleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="scheduleModalLabel">
					<i class="fas fa-calendar-plus mr-2"></i>New Appointment
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div id="scheduleAlert" class="d-none mb-3"></div>

				<!-- Patient search (name + optional DOB filter) -->
				<div class="form-group">
					<label>Patient <span class="text-danger">*</span></label>
					<div class="row no-gutters">
						<div class="col-md-8 pr-md-1">
							<div class="input-group">
								<input type="text" id="schedPatientSearch" class="form-control"
								       placeholder="Type patient name…" autocomplete="off" />
								<div class="input-group-append">
									<span class="input-group-text"><i class="fas fa-search"></i></span>
								</div>
							</div>
						</div>
						<div class="col-md-4 pl-md-1 mt-2 mt-md-0">
							<input type="date" id="schedPatientDob" class="form-control"
							       title="Filter by date of birth (optional)" />
						</div>
					</div>
					<input type="hidden" id="schedPatientId" />
					<div id="schedPatientDropdown" class="list-group mt-1 d-none"
					     style="position:absolute; z-index:9999; max-height:220px; overflow-y:auto; width:calc(100% - 30px);">
					</div>
					<small id="schedPatientSelected" class="text-success d-none">
						<i class="fas fa-check mr-1"></i><span></span>
					</small>
				</div>

				<!-- Inline new-patient registration panel (shown when no match found) -->
				<div id="newPatientPanel" class="card card-outline card-success d-none mb-3">
					<div class="card-header">
						<h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>Register New Patient</h3>
						<div class="card-tools">
							<button type="button" id="cancelNewPatientBtn" class="btn btn-tool" title="Cancel">
								<i class="fas fa-times"></i>
							</button>
						</div>
					</div>
					<div class="card-body">
						<div id="newPatientAlert" class="d-none mb-2"></div>
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label for="newPtFirstName">First Name <span class="text-danger">*</span></label>
									<input type="text" id="newPtFirstName" class="form-control" placeholder="First name" />
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label for="newPtLastName">Last Name</label>
									<input type="text" id="newPtLastName" class="form-control" placeholder="Last name (optional)" />
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label for="newPtDob">Date of Birth</label>
									<input type="date" id="newPtDob" class="form-control" />
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label for="newPtPhone">Phone</label>
									<input type="text" id="newPtPhone" class="form-control" placeholder="Phone number" />
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label for="newPtEmail">Email</label>
									<input type="email" id="newPtEmail" class="form-control" placeholder="Email (optional)" />
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label for="newPtAddress">Address</label>
									<input type="text" id="newPtAddress" class="form-control" placeholder="Street address" />
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group mb-0">
									<label for="newPtCity">City</label>
									<input type="text" id="newPtCity" class="form-control" placeholder="City" />
								</div>
							</div>
						</div>
					</div>
					<div class="card-footer text-right">
						<button type="button" id="saveNewPatientBtn" class="btn btn-success">
							<i class="fas fa-user-plus mr-1"></i>Create Patient &amp; Continue
						</button>
					</div>
				</div>

				<!-- Case sheet select -->
				<div class="form-group d-none" id="schedCaseSheetGroup">
					<label for="schedCaseSheetId">Case Sheet <span class="text-danger">*</span></label>
					<select id="schedCaseSheetId" class="form-control">
						<option value="">— Select a case sheet —</option>
					</select>
				</div>

				<div class="row">
					<!-- Doctor -->
					<div class="col-md-6">
						<div class="form-group">
							<label for="schedDoctorId">Doctor <span class="text-danger">*</span></label>
							<select id="schedDoctorId" class="form-control">
								<option value="">— Select a doctor —</option>
								<?php foreach ($doctors as $d): ?>
								<option value="<?= (int)$d['user_id'] ?>">
									Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?>
								</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<!-- Date -->
					<div class="col-md-6">
						<div class="form-group">
							<label for="schedDate">Date <span class="text-danger">*</span></label>
							<input type="date" id="schedDate" class="form-control"
							       min="<?= date('Y-m-d') ?>" />
						</div>
					</div>
					<!-- Time -->
					<div class="col-md-6">
						<div class="form-group">
							<label for="schedTime">Time <small class="text-muted">(optional)</small></label>
							<input type="time" id="schedTime" class="form-control" />
						</div>
					</div>
					<!-- Visit mode -->
					<div class="col-md-6">
						<div class="form-group">
							<label for="schedMode">Visit Mode</label>
							<select id="schedMode" class="form-control">
								<option value="IN_PERSON">In Person</option>
								<option value="REMOTE">Remote</option>
								<option value="CAMP">Camp</option>
							</select>
						</div>
					</div>
					<!-- Notes -->
					<div class="col-12">
						<div class="form-group mb-0">
							<label for="schedNotes">Notes <small class="text-muted">(optional)</small></label>
							<textarea id="schedNotes" class="form-control" rows="2" maxlength="1000"></textarea>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="confirmScheduleBtn">
					<i class="fas fa-calendar-check mr-1"></i>Schedule Appointment
				</button>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if ($isNurseRole || $isAdminRole): ?>
<!-- ── Assign to Doctor modal ────────────────────────────────── -->
<div class="modal fade" id="assignModal" tabindex="-1" role="dialog"
     aria-labelledby="assignModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="assignModalLabel">
					<i class="fas fa-user-md mr-2"></i>Assign to Doctor
				</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<p class="mb-3">Assign <strong id="assignPatientName"></strong> to:</p>
				<input type="hidden" id="assignCaseSheetId" />
				<div class="form-group mb-0">
					<label for="assignDoctorSelect">Doctor</label>
					<select id="assignDoctorSelect" class="form-control">
						<option value="">— Select a doctor —</option>
						<?php foreach ($doctors as $d): ?>
						<option value="<?= (int)$d['user_id'] ?>">
							Dr. <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?>
						</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="confirmAssignBtn">
					<i class="fas fa-save mr-1"></i>Assign
				</button>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme.js"></script>
<script>
const CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token']) ?>;

// ── Patient search (appointments list) ──────────────────────────────────────
let searchTimer;

$('#patientSearchInput').on('input', function () {
	clearTimeout(searchTimer);
	const q = $(this).val().trim();
	if (q.length < 2) {
		$('#patientSearchResults').addClass('d-none');
		return;
	}
	searchTimer = setTimeout(function () {
		$.getJSON('appointments.php?action=patient-search', { q: q }, function (data) {
			renderSearchResults(data.results || []);
		});
	}, 300);
});

$('#clearSearch').on('click', function () {
	$('#patientSearchInput').val('');
	$('#patientSearchResults').addClass('d-none');
});

function renderSearchResults(results) {
	const $body = $('#searchResultsBody');
	$body.empty();

	if (results.length === 0) {
		$body.html('<p class="text-muted p-3 mb-0">No patients with upcoming scheduled appointments found.</p>');
		$('#patientSearchResults').removeClass('d-none');
		return;
	}

	results.forEach(function (r) {
		let html = '<div class="p-3 border-bottom">';
		html += '<strong>' + escHtml(r.first_name + ' ' + r.last_name) + '</strong> ';
		html += '<small class="text-muted">' + escHtml(r.patient_code);
		if (r.age_years) html += ' · ' + r.age_years + ' yrs';
		if (r.sex)       html += ' · ' + escHtml(r.sex);
		html += '</small>';
		html += '<table class="table table-sm mt-2 mb-0"><thead class="thead-light">';
		html += '<tr><th>Date</th><th>Time</th><th>Doctor</th><th>Mode</th><th>Complaint</th></tr>';
		html += '</thead><tbody>';
		r.appointments.forEach(function (a) {
			html += '<tr>';
			html += '<td class="text-nowrap">' + escHtml(a.scheduled_date_fmt) + '</td>';
			html += '<td>' + (a.scheduled_time_fmt ? escHtml(a.scheduled_time_fmt) : '<span class="text-muted">—</span>') + '</td>';
			html += '<td>' + escHtml(a.doctor_name) + '</td>';
			html += '<td>' + escHtml(a.visit_mode) + '</td>';
			html += '<td class="small">' + (a.chief_complaint ? escHtml(a.chief_complaint) : '<span class="text-muted">—</span>') + '</td>';
			html += '</tr>';
		});
		html += '</tbody></table></div>';
		$body.append(html);
	});

	$('#patientSearchResults').removeClass('d-none');
}

// ── Assign to doctor ────────────────────────────────────────────────────────
<?php if ($isNurseRole || $isAdminRole): ?>
$(document).on('click', '.assign-btn', function () {
	$('#assignCaseSheetId').val($(this).data('case-sheet-id'));
	$('#assignPatientName').text($(this).data('patient-name'));
	$('#assignDoctorSelect').val('');
	$('#assignModal').modal('show');
});

$('#confirmAssignBtn').on('click', function () {
	const caseSheetId  = parseInt($('#assignCaseSheetId').val(), 10);
	const doctorUserId = parseInt($('#assignDoctorSelect').val(), 10);

	if (!caseSheetId || !doctorUserId) {
		alert('Please select a doctor.');
		return;
	}

	const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Saving…');

	$.ajax({
		url:         'appointments.php?action=assign-doctor',
		method:      'POST',
		contentType: 'application/json',
		data:        JSON.stringify({ case_sheet_id: caseSheetId, doctor_user_id: doctorUserId, csrf_token: CSRF_TOKEN }),
		dataType:    'json',
		success: function (data) {
			$('#assignModal').modal('hide');
			const $feedback = $('#assignFeedback');
			if (data.success) {
				$feedback.attr('class', 'alert alert-success').text(data.message).removeClass('d-none');
				$('#pending-row-' + caseSheetId + ' .assigned-doctor-cell').html(escHtml(data.doctor_name));
			} else {
				$feedback.attr('class', 'alert alert-danger').text(data.message || 'An error occurred.').removeClass('d-none');
			}
			setTimeout(function () { $feedback.addClass('d-none'); }, 5000);
		},
		error: function () {
			$('#assignModal').modal('hide');
			$('#assignFeedback').attr('class', 'alert alert-danger')
				.text('A network error occurred. Please try again.').removeClass('d-none');
		},
		complete: function () {
			$btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Assign');
		}
	});
});
<?php endif; ?>

// ── Appointment action drawer ────────────────────────────────────────────────
var apptDrawerApptId = null;

var drawerNurseActions = {
	'CONFIRMED':   {label:'Confirm',          cls:'btn-primary'},
	'IN_PROGRESS': {label:'Mark In Progress', cls:'btn-warning'},
	'COMPLETED':   {label:'Complete',         cls:'btn-success'},
	'NO_SHOW':     {label:'No Show',          cls:'btn-secondary'},
	'CANCELLED':   {label:'Cancel',           cls:'btn-danger'}
};
var drawerDoctorActions = {
	'IN_PROGRESS': {label:'Start',    cls:'btn-warning'},
	'COMPLETED':   {label:'Complete', cls:'btn-success'},
	'NO_SHOW':     {label:'No Show',  cls:'btn-secondary'}
};

function openApptDrawer(d) {
	apptDrawerApptId = d.apptId;

	// Header
	$('#apptDrawerPatient').text(d.patientName + (d.patientCode ? ' \u00b7 ' + d.patientCode : ''));
	var meta = [];
	if (d.scheduledTime) meta.push(d.scheduledTime);
	if (d.doctorName)    meta.push(d.doctorName);
	meta.push(d.status.replace(/_/g, ' '));
	$('#apptDrawerMeta').text(meta.join(' \u00b7 '));

	// Clear alert
	$('#apptDrawerAlert').addClass('d-none').empty();

	// Status buttons
	var actions = d.isNurseAdmin ? drawerNurseActions : drawerDoctorActions;
	var $btns = $('#apptDrawerStatusBtns').empty();
	$.each(actions, function(status, info) {
		if (status === d.status) return;
		$btns.append(
			$('<button type="button" class="btn btn-sm appt-drawer-status-btn"></button>')
				.addClass(info.cls).text(info.label).data('status', status)
		);
	});
	$('#apptDrawerStatusSec').toggleClass('d-none', $btns.children().length === 0);

	// Check In (nurse/admin, only for SCHEDULED or CONFIRMED)
	var canCheckIn = d.isNurseAdmin && d.caseSheetId && (d.status === 'SCHEDULED' || d.status === 'CONFIRMED');
	$('#apptDrawerCheckInSec').toggleClass('d-none', !canCheckIn);
	$('#apptDrawerCheckInBtn').data('case-sheet-id', d.caseSheetId);

	// Intake link (nurse/admin)
	if (d.isNurseAdmin && d.caseSheetId) {
		$('#apptDrawerIntakeBtn').attr('href', 'intake.php?case_sheet_id=' + d.caseSheetId);
		$('#apptDrawerIntakeSec').removeClass('d-none');
	} else {
		$('#apptDrawerIntakeSec').addClass('d-none');
	}

	// Reschedule & Cancel (nurse/admin)
	if (d.isNurseAdmin) {
		$('#aamReschedDate,#aamReschedTime,#aamReschedNote,#aamCancelNote').val('');
		$('#apptDrawerReschedSec,#apptDrawerCancelSec').removeClass('d-none');
	} else {
		$('#apptDrawerReschedSec,#apptDrawerCancelSec').addClass('d-none');
	}

	// Slide in
	$('#apptDrawerOverlay').addClass('open');
	$('#apptDrawer').addClass('open');
}

function closeApptDrawer() {
	$('#apptDrawer').removeClass('open');
	$('#apptDrawerOverlay').removeClass('open');
	apptDrawerApptId = null;
}

function drawerDataFromBtn($b) {
	return {
		apptId:        parseInt($b.data('appt-id'), 10),
		caseSheetId:   parseInt($b.data('case-sheet-id'), 10) || 0,
		status:        $b.data('status'),
		patientName:   $b.data('patient-name') || '',
		patientCode:   $b.data('patient-code') || '',
		doctorName:    $b.data('doctor-name')  || '',
		scheduledTime: $b.data('scheduled-time') || '',
		isNurseAdmin:  $b.data('is-nurse-admin') == '1'
	};
}

$(document).on('click', '.appt-open-drawer', function (e) {
	e.stopPropagation();
	openApptDrawer(drawerDataFromBtn($(this)));
});

$(document).on('click', 'tbody tr', function (e) {
	var $drawerBtn = $(this).find('.appt-open-drawer');
	if (!$drawerBtn.length) return;
	// Let other interactive elements (links, buttons, inputs) handle their own clicks
	if ($(e.target).closest('a, button, input, select, textarea').length) return;
	openApptDrawer(drawerDataFromBtn($drawerBtn));
});

$('#apptDrawerClose, #apptDrawerOverlay').on('click', function () {
	closeApptDrawer();
});

// Status update from drawer
$(document).on('click', '.appt-drawer-status-btn', function () {
	var $btn   = $(this);
	var status = $btn.data('status');
	$btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
	$.ajax({
		url:         'appointments.php?action=update-status',
		method:      'POST',
		contentType: 'application/json',
		data:        JSON.stringify({appointment_id: apptDrawerApptId, status: status, csrf_token: CSRF_TOKEN}),
		dataType:    'json',
		success: function (data) {
			if (data.success) { closeApptDrawer(); window.location.reload(); }
			else {
				$('#apptDrawerAlert').attr('class', 'alert alert-danger').text(data.message || 'Failed to update status.').removeClass('d-none');
				$btn.prop('disabled', false).text(status.replace(/_/g, ' '));
			}
		},
		error: function () {
			$('#apptDrawerAlert').attr('class', 'alert alert-danger').text('A network error occurred.').removeClass('d-none');
			$btn.prop('disabled', false).text(status.replace(/_/g, ' '));
		}
	});
});

// Check In from drawer
$('#apptDrawerCheckInBtn').on('click', function () {
	var caseSheetId = $(this).data('case-sheet-id');
	var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Checking in\u2026');
	$.ajax({
		url:         'appointments.php?action=update-status',
		method:      'POST',
		contentType: 'application/json',
		data:        JSON.stringify({appointment_id: apptDrawerApptId, status: 'IN_PROGRESS', csrf_token: CSRF_TOKEN}),
		dataType:    'json',
		success: function (data) {
			if (data.success) {
				window.location.href = 'intake.php?case_sheet_id=' + caseSheetId;
			} else {
				$('#apptDrawerAlert').attr('class', 'alert alert-danger').text(data.message || 'Check-in failed.').removeClass('d-none');
				$btn.prop('disabled', false).html('<i class="fas fa-user-check mr-2"></i>Check In Patient');
			}
		},
		error: function () {
			$('#apptDrawerAlert').attr('class', 'alert alert-danger').text('A network error occurred.').removeClass('d-none');
			$btn.prop('disabled', false).html('<i class="fas fa-user-check mr-2"></i>Check In Patient');
		}
	});
});

// Reschedule from drawer
$('#aamReschedBtn').on('click', function () {
	var newDate = $('#aamReschedDate').val().trim();
	if (!newDate) {
		$('#apptDrawerAlert').attr('class', 'alert alert-warning').text('Please enter a new date.').removeClass('d-none');
		return;
	}
	var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Saving\u2026');
	$.ajax({
		url:         'appointments.php?action=reschedule',
		method:      'POST',
		contentType: 'application/json',
		data:        JSON.stringify({
			appointment_id: apptDrawerApptId,
			new_date:       newDate,
			new_time:       $('#aamReschedTime').val().trim() || null,
			note:           $('#aamReschedNote').val().trim() || null,
			csrf_token:     CSRF_TOKEN
		}),
		dataType: 'json',
		success: function (data) {
			if (data.success) { closeApptDrawer(); window.location.reload(); }
			else { $('#apptDrawerAlert').attr('class', 'alert alert-danger').text(data.message || 'An error occurred.').removeClass('d-none'); }
		},
		error: function () {
			$('#apptDrawerAlert').attr('class', 'alert alert-danger').text('A network error occurred.').removeClass('d-none');
		},
		complete: function () {
			$btn.prop('disabled', false).html('<i class="fas fa-calendar-check mr-1"></i>Confirm Reschedule');
		}
	});
});

// Cancel from drawer
$('#aamCancelBtn').on('click', function () {
	if (!confirm('Are you sure you want to cancel this appointment?')) return;
	var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Cancelling\u2026');
	$.ajax({
		url:         'appointments.php?action=cancel',
		method:      'POST',
		contentType: 'application/json',
		data:        JSON.stringify({
			appointment_id: apptDrawerApptId,
			note:           $('#aamCancelNote').val().trim() || null,
			csrf_token:     CSRF_TOKEN
		}),
		dataType: 'json',
		success: function (data) {
			if (data.success) { closeApptDrawer(); window.location.reload(); }
			else { $('#apptDrawerAlert').attr('class', 'alert alert-danger').text(data.message || 'An error occurred.').removeClass('d-none'); }
		},
		error: function () {
			$('#apptDrawerAlert').attr('class', 'alert alert-danger').text('A network error occurred.').removeClass('d-none');
		},
		complete: function () {
			$btn.prop('disabled', false).html('<i class="fas fa-ban mr-1"></i>Cancel Appointment');
		}
	});
});

<?php if ($isNurseRole || $isAdminRole): ?>
// ── Schedule from pending tab ("Schedule" button on a pending row) ───────────
$(document).on('click', '.schedule-from-pending-btn', function () {
	const $btn       = $(this);
	const patientId  = parseInt($btn.data('patient-id'), 10);
	const caseSheetId = parseInt($btn.data('case-sheet-id'), 10);
	const doctorId   = parseInt($btn.data('doctor-user-id'), 10) || 0;
	const doctorName = $btn.data('doctor-name') || '';
	const patientName = $btn.data('patient-name') || '';

	// Pre-fill and open the schedule modal
	resetScheduleModal();
	$('#schedPatientId').val(patientId);
	$('#schedPatientSearch').val(patientName).prop('readonly', true);
	$('#schedPatientSelected span').text(patientName);
	$('#schedPatientSelected').removeClass('d-none');

	// Load case sheets for this patient, then pre-select the right one
	loadPatientCases(patientId, caseSheetId, doctorId);
	$('#scheduleModal').modal('show');
});

// ── New Appointment modal: patient typeahead ─────────────────────────────────
let schedSearchTimer;

function renderSchedDropdown(results, searchTerm) {
	const $dd = $('#schedPatientDropdown').empty();
	if (!results || results.length === 0) {
		$dd.append('<div class="list-group-item text-muted small py-2">No patients found.</div>');
	} else {
		results.slice(0, 8).forEach(function (p) {
			const name = escHtml(p.first_name + ' ' + (p.last_name || ''));
			const metaParts = [];
			if (p.patient_code)  metaParts.push('<strong>ID:</strong> '  + escHtml(p.patient_code));
			if (p.date_of_birth) metaParts.push('<strong>DOB:</strong> ' + escHtml(p.date_of_birth.substring(0, 10)));
			if (p.age_years)     metaParts.push(p.age_years + ' yrs');
			$dd.append(
				$('<button type="button" class="list-group-item list-group-item-action py-2"></button>')
					.html('<strong>' + name + '</strong> <small class="text-muted">' + metaParts.join(' \u00b7 ') + '</small>')
					.on('click', function () {
						selectSchedulePatient(p.patient_id, p.first_name + ' ' + (p.last_name || ''));
					})
			);
		});
	}
	// Always offer the option to register a new patient — a search hit might
	// be a different person who happens to share the same name.
	const label = searchTerm ? 'Register \u201c' + escHtml(searchTerm) + '\u201d as a new patient' : 'Register a new patient';
	$dd.append(
		$('<button type="button" id="openNewPatientFormBtn" class="list-group-item list-group-item-action list-group-item-success py-2 border-top"></button>')
			.html('<i class="fas fa-user-plus mr-1"></i>' + label)
	);
	$dd.removeClass('d-none');
}

$('#schedPatientSearch, #schedPatientDob').on('input change', function () {
	clearTimeout(schedSearchTimer);
	const q   = $('#schedPatientSearch').val().trim();
	const dob = $('#schedPatientDob').val().trim();
	if (q.length < 2 && !dob) {
		$('#schedPatientDropdown').addClass('d-none').empty();
		$('#schedPatientId').val('');
		$('#schedPatientSelected').addClass('d-none');
		$('#schedCaseSheetGroup').addClass('d-none');
		return;
	}
	schedSearchTimer = setTimeout(function () {
		const params = {};
		if (q)   params.name = q;
		if (dob) params.dob  = dob;
		$.getJSON('patients.php?action=search', params, function (results) {
			renderSchedDropdown(results, q);
		});
	}, 300);
});

$(document).on('click', function (e) {
	if (!$(e.target).closest('#schedPatientSearch, #schedPatientDob, #schedPatientDropdown').length) {
		$('#schedPatientDropdown').addClass('d-none');
	}
});

$(document).on('click', '#openNewPatientFormBtn', function () {
	const searchVal = $('#schedPatientSearch').val().trim();
	const nameParts = searchVal.split(' ');
	$('#newPtFirstName').val(nameParts[0] || '');
	$('#newPtLastName').val(nameParts.slice(1).join(' ') || '');
	$('#newPtDob').val($('#schedPatientDob').val() || '');
	$('#newPtPhone, #newPtEmail, #newPtAddress, #newPtCity').val('');
	$('#newPatientAlert').addClass('d-none').empty();
	$('#schedPatientDropdown').addClass('d-none');
	$('#newPatientPanel').removeClass('d-none');
});

$('#cancelNewPatientBtn').on('click', function () {
	$('#newPatientPanel').addClass('d-none');
	resetNewPatientForm();
});

$('#saveNewPatientBtn').on('click', function () {
	const firstName = $('#newPtFirstName').val().trim();
	if (!firstName) {
		$('#newPatientAlert').attr('class', 'alert alert-warning').text('First name is required.').removeClass('d-none');
		return;
	}
	const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Creating\u2026');
	$.ajax({
		url:         'appointments.php?action=create-patient',
		method:      'POST',
		contentType: 'application/json',
		data: JSON.stringify({
			first_name:    firstName,
			last_name:     $('#newPtLastName').val().trim()  || null,
			date_of_birth: $('#newPtDob').val()              || null,
			phone_e164:    $('#newPtPhone').val().trim()     || null,
			email:         $('#newPtEmail').val().trim()     || null,
			address_line1: $('#newPtAddress').val().trim()   || null,
			city:          $('#newPtCity').val().trim()      || null,
			csrf_token:    CSRF_TOKEN,
		}),
		dataType: 'json',
		success: function (data) {
			if (data.success) {
				const fullName = data.first_name + (data.last_name ? ' ' + data.last_name : '');
				$('#newPatientPanel').addClass('d-none');
				resetNewPatientForm();
				$('#schedPatientId').val(data.patient_id);
				$('#schedPatientSearch').val(fullName);
				$('#schedPatientSelected span').html(
					escHtml(fullName) + ' <span class="badge badge-info ml-1">New \u00b7 ' + escHtml(data.patient_code) + '</span>'
				);
				$('#schedPatientSelected').removeClass('d-none');
				$('#schedPatientDob').val('');
				loadPatientCases(data.patient_id, data.case_sheet_id, 0);
			} else {
				$('#newPatientAlert').attr('class', 'alert alert-danger').text(data.message || 'Error creating patient.').removeClass('d-none');
			}
		},
		error: function () {
			$('#newPatientAlert').attr('class', 'alert alert-danger').text('A network error occurred. Please try again.').removeClass('d-none');
		},
		complete: function () {
			$btn.prop('disabled', false).html('<i class="fas fa-user-plus mr-1"></i>Create Patient &amp; Continue');
		}
	});
});

function resetNewPatientForm() {
	$('#newPtFirstName, #newPtLastName, #newPtDob, #newPtPhone, #newPtEmail, #newPtAddress, #newPtCity').val('');
	$('#newPatientAlert').addClass('d-none').empty();
}

function selectSchedulePatient(patientId, patientName) {
	$('#schedPatientId').val(patientId);
	$('#schedPatientSearch').val(patientName);
	$('#schedPatientSelected span').text(patientName);
	$('#schedPatientSelected').removeClass('d-none');
	$('#schedPatientDropdown').addClass('d-none');
	$('#newPatientPanel').addClass('d-none');
	loadPatientCases(patientId, 0, 0);
}

function loadPatientCases(patientId, preselectCaseId, preselectDoctorId) {
	$.getJSON('appointments.php?action=patient-cases', { patient_id: patientId }, function (data) {
		const $sel = $('#schedCaseSheetId').empty().append('<option value="">— Select a case sheet —</option>');
		(data.cases || []).forEach(function (c) {
			const label = '#' + c.case_sheet_id + ' — '
				+ (c.chief_complaint ? c.chief_complaint.substring(0, 50) : c.visit_type)
				+ ' [' + c.status + ']';
			const $opt = $('<option></option>').val(c.case_sheet_id).text(label)
				.data('doctor-id', c.assigned_doctor_user_id || 0);
			$sel.append($opt);
		});
		$('#schedCaseSheetGroup').removeClass('d-none');
		if (preselectCaseId) {
			$sel.val(preselectCaseId);
		}
		// Pre-fill doctor if assigned
		if (preselectDoctorId) {
			$('#schedDoctorId').val(preselectDoctorId);
		} else {
			const $selected = $sel.find(':selected');
			const did = $selected.data('doctor-id');
			if (did) $('#schedDoctorId').val(did);
		}
	});
}

$('#schedCaseSheetId').on('change', function () {
	const did = $(this).find(':selected').data('doctor-id');
	if (did) $('#schedDoctorId').val(did);
});

function resetScheduleModal() {
	$('#schedAlert').addClass('d-none').empty();
	$('#schedPatientSearch').val('').prop('readonly', false);
	$('#schedPatientDob').val('');
	$('#schedPatientId').val('');
	$('#schedPatientSelected').addClass('d-none');
	$('#schedPatientDropdown').addClass('d-none').empty();
	$('#newPatientPanel').addClass('d-none');
	resetNewPatientForm();
	$('#schedCaseSheetGroup').addClass('d-none');
	$('#schedCaseSheetId').empty().append('<option value="">— Select a case sheet —</option>');
	$('#schedDoctorId').val('');
	$('#schedDate').val('');
	$('#schedTime').val('');
	$('#schedMode').val('IN_PERSON');
	$('#schedNotes').val('');
}

$('#scheduleModal').on('hidden.bs.modal', function () {
	resetScheduleModal();
});

$('#confirmScheduleBtn').on('click', function () {
	const caseSheetId = parseInt($('#schedCaseSheetId').val(), 10);
	const doctorId    = parseInt($('#schedDoctorId').val(), 10);
	const date        = $('#schedDate').val().trim();
	const $alert      = $('#scheduleAlert');

	if (!caseSheetId || !doctorId || !date) {
		$alert.attr('class', 'alert alert-warning').text('Please fill in all required fields.').removeClass('d-none');
		return;
	}

	const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Scheduling…');

	$.ajax({
		url:         'appointments.php?action=create',
		method:      'POST',
		contentType: 'application/json',
		data: JSON.stringify({
			case_sheet_id:  caseSheetId,
			doctor_user_id: doctorId,
			scheduled_date: date,
			scheduled_time: $('#schedTime').val().trim() || null,
			visit_mode:     $('#schedMode').val(),
			notes:          $('#schedNotes').val().trim() || null,
			csrf_token:     CSRF_TOKEN,
		}),
		dataType: 'json',
		success: function (data) {
			if (data.success) {
				$('#scheduleModal').modal('hide');
				// Show success and reload to see new appointment
				alert(data.message);
				window.location.href = 'appointments.php?tab=upcoming';
			} else {
				$alert.attr('class', 'alert alert-danger').text(data.message || 'An error occurred.').removeClass('d-none');
				$btn.prop('disabled', false).html('<i class="fas fa-calendar-check mr-1"></i>Schedule Appointment');
			}
		},
		error: function () {
			$alert.attr('class', 'alert alert-danger').text('A network error occurred. Please try again.').removeClass('d-none');
			$btn.prop('disabled', false).html('<i class="fas fa-calendar-check mr-1"></i>Schedule Appointment');
		}
	});
});
<?php endif; ?>

function escHtml(str) {
	if (str == null) return '';
	return String(str)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;');
}
</script>
</body>
</html>
