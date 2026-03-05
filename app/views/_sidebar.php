<?php
if (!function_exists('can')) {
	require_once __DIR__ . '/../config/permissions.php';
}

// ── Active-page detection ───────────────────────────────────────────────────
$currentPage        = $_SERVER['PHP_SELF'];
$isAdminPage        = (strpos($currentPage, 'admin.php')        !== false);
$isAdminPanel       = ($isAdminPage && ($_GET['page'] ?? '') === 'panel');
$isAdminDashboard   = ($isAdminPage && !$isAdminPanel);
$isDashboardPage    = (strpos($currentPage, 'dashboard.php')    !== false && !$isAdminPage);
$isProfilePage      = (strpos($currentPage, 'profile.php')      !== false);
$isSettingsPage     = (strpos($currentPage, 'settings.php')     !== false);
$isAssetsPage       = (strpos($currentPage, 'assets.php')       !== false);
$isCalendarPage     = (strpos($currentPage, 'calendar.php')     !== false);
$isIntakePage       = (strpos($currentPage, 'intake.php')       !== false);
$isReviewPage       = (strpos($currentPage, 'review.php')       !== false);
$isFeedbackPage     = (strpos($currentPage, 'feedback.php')     !== false);
$isMessagesPage     = (strpos($currentPage, 'messages.php')     !== false);
$isTasksPage        = (strpos($currentPage, 'tasks.php')        !== false);
$isAppointmentsPage = (strpos($currentPage, 'appointments.php') !== false);
$isPatientsPage     = (strpos($currentPage, 'patients.php')     !== false);
$isLabResultsPage   = (strpos($currentPage, 'lab_results.php')  !== false);

// ── Permission flags (computed once, used throughout) ───────────────────────
// Each flag is derived entirely from the can() function so it automatically
// reflects any changes made through the Permissions admin UI.
$_userRole = $_SESSION['user_role'] ?? '';

$_navCanCaseSheetsWrite = can($_userRole, 'case_sheets', 'W'); // Intake, My Reviews
$_navCanCaseSheetsRead  = can($_userRole, 'case_sheets');      // Appointments
$_navCanPatientData     = can($_userRole, 'patient_data');     // Patients, Lab Results
$_navCanEvents          = can($_userRole, 'events');           // Calendar
$_navCanFeedback        = can($_userRole, 'feedback');         // Feedback
$_navCanMessages        = can($_userRole, 'messages');         // Messages
$_navCanTasks           = can($_userRole, 'tasks');            // Tasks
$_navCanAssets          = can($_userRole, 'assets');           // Assets
$_navCanUsers           = can($_userRole, 'users');            // Admin Panel

// ── Sidebar appointment badge (today's count) ───────────────────────────────
// Gate matches the appointments page access requirement (case_sheets read).
$_apptTodayCount = 0;
if ($_navCanCaseSheetsRead && !$isAppointmentsPage) {
	try {
		$_apptPdo    = getDBConnection();
		$_apptSql    = "SELECT COUNT(*) FROM appointments
		                 WHERE scheduled_date = CURDATE()
		                   AND status NOT IN ('CANCELLED','NO_SHOW')";
		$_apptParams = [];
		if ($_userRole === 'DOCTOR') {
			$_apptSql   .= ' AND doctor_user_id = ?';
			$_apptParams[] = $_SESSION['user_id'];
		}
		$_apptStmt = $_apptPdo->prepare($_apptSql);
		$_apptStmt->execute($_apptParams);
		$_apptTodayCount = (int)$_apptStmt->fetchColumn();
	} catch (Throwable $e) { /* silently skip if table doesn't exist yet */ }
}
?>
<aside class="main-sidebar sidebar-dark-primary elevation-3">
	<a href="#" class="brand-link text-center">
		<span class="brand-text font-weight-light">D3S3 CareSystem</span>
	</a>

	<div class="sidebar">
		<div class="user-panel mt-3 pb-3 mb-3 d-flex">
			<div class="image">
				<i class="fas fa-user-md text-white-50"></i>
			</div>
			<div class="info">
				<span class="d-block text-white"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
				<?php
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
				?>
				<small class="text-white-50"><?= htmlspecialchars($_roleLabels[$_userRole] ?? 'User') ?></small>
			</div>
		</div>

		<nav class="mt-3">
			<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

				<!-- Dashboard – always visible -->
				<li class="nav-item">
					<a href="<?= $_navCanUsers ? 'admin.php' : 'dashboard.php' ?>"
					   class="nav-link <?= ($isDashboardPage || $isAdminDashboard) ? 'active' : '' ?>">
						<i class="nav-icon fas fa-heart-pulse"></i>
						<p>Dashboard</p>
					</a>
				</li>

				<!-- Intake – requires case_sheets write -->
				<?php if ($_navCanCaseSheetsWrite): ?>
				<li class="nav-item">
					<a href="intake.php" class="nav-link <?= $isIntakePage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-clipboard-list"></i>
						<p>Intake</p>
					</a>
				</li>
				<?php endif; ?>

				<!-- My Reviews – Doctor only, requires case_sheets write -->
				<?php if ($_userRole === 'DOCTOR' && $_navCanCaseSheetsWrite): ?>
				<li class="nav-item">
					<a href="dashboard.php" class="nav-link <?= $isReviewPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-notes-medical"></i>
						<p>My Reviews</p>
					</a>
				</li>
				<?php endif; ?>

				<!-- Patients – requires patient_data read -->
				<?php if ($_navCanPatientData): ?>
				<li class="nav-item">
					<a href="patients.php" class="nav-link <?= $isPatientsPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-user-injured"></i>
						<p>Patients</p>
					</a>
				</li>
				<?php endif; ?>

				<!-- Appointments – requires case_sheets read -->
				<?php if ($_navCanCaseSheetsRead): ?>
				<li class="nav-item">
					<a href="appointments.php" class="nav-link <?= $isAppointmentsPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-calendar-check"></i>
						<p>Appointments</p>
						<?php if ($_apptTodayCount > 0): ?>
						<span class="right badge badge-primary"><?= $_apptTodayCount ?></span>
						<?php endif; ?>
					</a>
				</li>
				<?php endif; ?>

				<!-- Lab Results – requires patient_data read -->
				<?php if ($_navCanPatientData): ?>
				<li class="nav-item">
					<a href="lab_results.php" class="nav-link <?= $isLabResultsPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-vial"></i>
						<p>Labwork</p>
					</a>
				</li>
				<?php endif; ?>

				<!-- Calendar – requires events read -->
				<?php if ($_navCanEvents): ?>
				<li class="nav-item">
					<a href="calendar.php" class="nav-link <?= $isCalendarPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-calendar-alt"></i>
						<p>Calendar</p>
					</a>
				</li>
				<?php endif; ?>

				<!-- Feedback – requires feedback read -->
				<?php if ($_navCanFeedback): ?>
				<li class="nav-item">
					<a href="feedback.php" class="nav-link <?= $isFeedbackPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-comment-dots"></i>
						<p>Feedback</p>
					</a>
				</li>
				<?php endif; ?>

				<!-- Messages – requires messages read -->
				<?php if ($_navCanMessages): ?>
				<li class="nav-item">
					<a href="messages.php" class="nav-link <?= $isMessagesPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-envelope"></i>
						<p>Messages</p>
					</a>
				</li>
				<?php endif; ?>

				<!-- Tasks – requires tasks read -->
				<?php if ($_navCanTasks): ?>
				<li class="nav-item">
					<a href="tasks.php" class="nav-link <?= $isTasksPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-tasks"></i>
						<p>Tasks</p>
					</a>
				</li>
				<?php endif; ?>

				<!-- Profile – always visible -->
				<li class="nav-item">
					<a href="profile.php" class="nav-link <?= $isProfilePage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-user-circle"></i>
						<p>Profile</p>
					</a>
				</li>

				<!-- Settings – always visible -->
				<li class="nav-item">
					<a href="settings.php" class="nav-link <?= $isSettingsPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-cog"></i>
						<p>Settings</p>
					</a>
				</li>

				<!-- Admin Panel – requires users read -->
				<?php if ($_navCanUsers): ?>
				<li class="nav-item">
					<a href="admin.php?page=panel" class="nav-link <?= $isAdminPanel ? 'active' : '' ?>">
						<i class="nav-icon fas fa-user-shield"></i>
						<p>Admin Panel</p>
					</a>
				</li>
				<?php endif; ?>

				<!-- Assets – requires assets read -->
				<?php if ($_navCanAssets): ?>
				<li class="nav-item">
					<a href="assets.php" class="nav-link <?= $isAssetsPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-boxes"></i>
						<p>Assets</p>
					</a>
				</li>
				<?php endif; ?>

				<!-- Log Out – always visible -->
				<li class="nav-item">
					<a href="logout.php" class="nav-link">
						<i class="nav-icon fas fa-sign-out-alt"></i>
						<p>Log Out</p>
					</a>
				</li>

			</ul>
		</nav>
	</div>
</aside>
