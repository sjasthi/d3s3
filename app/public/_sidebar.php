<?php
$currentPage = $_SERVER['PHP_SELF'];
$isAdminPage = (strpos($currentPage, 'admin/dashboard.php') !== false);
$isDashboardPage = (strpos($currentPage, 'dashboard.php') !== false && !$isAdminPage);
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
				<span class="d-block text-white">Clinical Lead</span>
				<small class="text-white-50">Connected</small>
			</div>
		</div>

		<nav class="mt-3">
			<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
				<li class="nav-item">
					<a href="/d3s3/dashboard.php" class="nav-link <?= $isDashboardPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-heart-pulse"></i>
						<p>Dashboard</p>
					</a>
				</li>
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
				<li class="nav-item">
					<a href="#" class="nav-link">
						<i class="nav-icon fas fa-file-invoice-dollar"></i>
						<p>Billing</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="#" class="nav-link">
						<i class="nav-icon fas fa-tasks"></i>
						<p>Tasks</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="#" class="nav-link">
						<i class="nav-icon fas fa-cog"></i>
						<p>Settings</p>
					</a>
				</li>
				<li class="nav-item">
					<a href="/d3s3/app/views/admin/dashboard.php" class="nav-link <?= $isAdminPage ? 'active' : '' ?>">
						<i class="nav-icon fas fa-user-shield"></i>
						<p>Admin</p>
					</a>
				</li>
			</ul>
		</nav>
	</div>
</aside>
