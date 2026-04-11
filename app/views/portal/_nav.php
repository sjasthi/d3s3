<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?= htmlspecialchars($pageTitle ?? 'Patient Portal') ?> | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
		/* ── Patient portal green theme ── */
		body { background: #f0f7f4; }
		.portal-navbar { background: linear-gradient(90deg, #1b4d39 0%, #2e7d5e 100%); }
		.portal-navbar .navbar-brand,
		.portal-navbar .nav-link,
		.portal-navbar .navbar-text { color: #fff !important; }
		.portal-navbar .nav-link:hover,
		.portal-navbar .nav-link.active { background: rgba(255,255,255,.18); border-radius: 4px; }
		.portal-navbar .navbar-toggler { border-color: rgba(255,255,255,.5); }
		.portal-navbar .navbar-toggler-icon { filter: invert(1); }

		/* Remap Bootstrap primary to portal green */
		.btn-primary, .badge-primary, .bg-primary { background-color: #2e7d5e !important; border-color: #2e7d5e !important; }
		.btn-primary:hover  { background-color: #245f49 !important; border-color: #245f49 !important; }
		.btn-outline-primary { color: #2e7d5e !important; border-color: #2e7d5e !important; }
		.btn-outline-primary:hover { background-color: #2e7d5e !important; color: #fff !important; }
		.text-primary { color: #2e7d5e !important; }
		a { color: #2e7d5e; }
		a:hover { color: #1b4d39; }
		.portal-card { border: none; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }

		.portal-main { padding: 1.5rem 0 3rem; }
		.badge-unread { background: #dc3545; color: #fff; font-size: .7rem;
		                padding: 2px 6px; border-radius: 10px; margin-left: 4px; }
		.thread-item { cursor: pointer; }
		.thread-item:hover { background: #eaf4ef; }
		.thread-item.active-thread { background: #d4ede3; border-left: 3px solid #2e7d5e; }
		.msg-bubble { border-radius: 12px; padding: .6rem 1rem; max-width: 80%; }
		.msg-bubble.patient { background: #2e7d5e; color: #fff; margin-left: auto; }
		.msg-bubble.staff   { background: #e9ecef; color: #212529; }
	</style>
</head>
<body>

<nav class="navbar navbar-expand-md portal-navbar shadow-sm">
	<div class="container">
		<a class="navbar-brand font-weight-bold" href="patient_portal.php">
			<i class="fas fa-heart-pulse mr-1"></i> CareSystem Patient Portal
		</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#portalNav">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="portalNav">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item">
					<a class="nav-link <?= ($activePage ?? '') === 'dashboard'    ? 'active' : '' ?>"
					   href="patient_portal.php">
						<i class="fas fa-th-large mr-1"></i> Dashboard
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= ($activePage ?? '') === 'appointments' ? 'active' : '' ?>"
					   href="patient_portal.php?page=appointments">
						<i class="fas fa-calendar-check mr-1"></i> Appointments
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= ($activePage ?? '') === 'health_record' ? 'active' : '' ?>"
					   href="patient_portal.php?page=health_record">
						<i class="fas fa-file-medical mr-1"></i> Health Record
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= ($activePage ?? '') === 'lab_results'  ? 'active' : '' ?>"
					   href="patient_portal.php?page=lab_results">
						<i class="fas fa-vial mr-1"></i> Lab Results
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= ($activePage ?? '') === 'messages'     ? 'active' : '' ?>"
					   href="patient_portal.php?page=messages">
						<i class="fas fa-envelope mr-1"></i> Messages
						<?php if (!empty($portalUnreadCount)): ?>
						<span class="badge-unread"><?= (int)$portalUnreadCount ?></span>
						<?php endif; ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= ($activePage ?? '') === 'resources'    ? 'active' : '' ?>"
					   href="patient_portal.php?page=resources">
						<i class="fas fa-layer-group mr-1"></i> Resources
						<?php if (!empty($resourcesUnreadCount)): ?>
						<span class="badge-unread"><?= (int)$resourcesUnreadCount ?></span>
						<?php endif; ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= ($activePage ?? '') === 'feedback'     ? 'active' : '' ?>"
					   href="patient_portal.php?page=feedback">
						<i class="fas fa-comment-dots mr-1"></i> Feedback
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link <?= ($activePage ?? '') === 'profile'      ? 'active' : '' ?>"
					   href="patient_portal.php?page=profile">
						<i class="fas fa-user-circle mr-1"></i> Profile
					</a>
				</li>
			</ul>
			<span class="navbar-text mr-3 d-none d-md-inline">
				<i class="fas fa-user-injured mr-1"></i>
				<?= htmlspecialchars($_SESSION['patient_name'] ?? 'Patient') ?>
			</span>
			<form method="POST" action="patient_portal.php" class="d-inline">
				<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
				<input type="hidden" name="action" value="logout" />
				<button type="submit" class="btn btn-sm btn-outline-light">
					<i class="fas fa-sign-out-alt mr-1"></i> Sign Out
				</button>
			</form>
		</div>
	</div>
</nav>

<main class="portal-main">
<div class="container">
