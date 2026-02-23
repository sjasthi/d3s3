<?php
$currentPage = $_SERVER['PHP_SELF'];
$isAdminPage = (strpos($currentPage, 'admin.php') !== false);
$isAdminPanel = ($isAdminPage && ($_GET['page'] ?? '') === 'panel');
$isAdminDashboard = ($isAdminPage && !$isAdminPanel);
$isDashboardPage = (strpos($currentPage, 'dashboard.php') !== false && !$isAdminPage);
$isProfilePage = (strpos($currentPage, 'profile.php') !== false);
$isSettingsPage = (strpos($currentPage, 'settings.php') !== false);
$isAssetsPage = (strpos($currentPage, 'assets.php') !== false);
$isCalendarPage = (strpos($currentPage, 'calendar.php') !== false);
$isIntakePage = (strpos($currentPage, 'intake.php') !== false);
$isReviewPage = (strpos($currentPage, 'review.php') !== false);

$_userRole = $_SESSION['user_role'] ?? '';
$_isAdminRole = in_array($_userRole, ['SUPER_ADMIN', 'ADMIN'], true);
$_isClinicalRole = in_array($_userRole, ['DOCTOR', 'TRIAGE_NURSE', 'NURSE'], true);
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
				$_roleLabels = ['SUPER_ADMIN' => 'Super Admin', 'ADMIN' => 'Admin', 'DOCTOR' => 'Doctor', 'TRIAGE_NURSE' => 'Triage Nurse', 'NURSE' => 'Nurse', 'PARAMEDIC' => 'Paramedic', 'GRIEVANCE_OFFICER' => 'Grievance Officer', 'EDUCATION_TEAM' => 'Education Team', 'DATA_ENTRY_OPERATOR' => 'Data Entry Operator'];
				?>
				<small class="text-white-50"><?= htmlspecialchars($_roleLabels[$_userRole] ?? 'User') ?></small>
			</div>
		</div>

		<nav class="mt-3">
			<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
				<li class="nav-item">
					<a href="<?= $_isAdminRole ? 'admin.php' : 'dashboard.php' ?>" class="nav-link <?= ($isDashboardPage || $isAdminDashboard) ? 'active' : '' ?>">
						<i class="nav-icon fas fa-heart-pulse"></i>
						<p>Dashboard</p>
					</a>
				</li>

				<?php if ($_isClinicalRole): ?>
				<li class="nav-item">
					<a href="intake.php" class="nav-link <?= $isIntakePage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-clipboard-list"></i>
						<p>Intake</p>
					</a>
				</li>
				<?php if ($_userRole === 'DOCTOR'): ?>
				<li class="nav-item">
					<a href="dashboard.php" class="nav-link <?= $isReviewPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-notes-medical"></i>
						<p>My Reviews</p>
					</a>
				</li>
				<?php endif; ?>
				<li class="nav-item">
					<a href="#" class="nav-link">
						<i class="nav-icon fas fa-user-injured"></i>
						<p>Patients</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="#" class="nav-link">
						<i class="nav-icon fas fa-calendar-check"></i>
						<p>Appointments</p>
						<span class="right badge badge-primary">3</span>
					</a>
				</li>
				<li class="nav-item">
					<a href="#" class="nav-link">
						<i class="nav-icon fas fa-vial"></i>
						<p>Lab Results</p>
					</a>
				</li>
				<?php endif; ?>

				<li class="nav-item">
					<a href="calendar.php" class="nav-link <?= $isCalendarPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-calendar-alt"></i>
						<p>Calendar</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="#" class="nav-link">
						<i class="nav-icon fas fa-envelope"></i>
						<p>Messages</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="#" class="nav-link">
						<i class="nav-icon fas fa-tasks"></i>
						<p>Tasks</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="profile.php" class="nav-link <?= $isProfilePage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-user-circle"></i>
						<p>Profile</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="settings.php" class="nav-link <?= $isSettingsPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-cog"></i>
						<p>Settings</p>
					</a>
				</li>

				<?php if ($_isAdminRole): ?>
				<li class="nav-item">
					<a href="admin.php?page=panel" class="nav-link <?= $isAdminPanel ? 'active' : '' ?>">
						<i class="nav-icon fas fa-user-shield"></i>
						<p>Admin Panel</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="assets.php" class="nav-link <?= $isAssetsPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-boxes"></i>
						<p>Assets</p>
					</a>
				</li>
				<?php endif; ?>

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
