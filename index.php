<?php
/**
 * index.php – D3S3 CareSystem landing page (prototype).
 *
 * Already-authenticated staff go straight to the dashboard.
 * Already-authenticated patients go straight to the portal.
 * Everyone else sees the landing page with both sign-in options.
 */

require_once __DIR__ . '/app/config/session.php';

if (isset($_SESSION['user_id'])) {
	header('Location: dashboard.php');
	exit;
}
if (isset($_SESSION['patient_account_id'])) {
	header('Location: patient_portal.php');
	exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>D3S3 CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
		body {
			min-height: 100vh;
			background: linear-gradient(135deg, #1a4f8a 0%, #1a7cb5 60%, #22a8d4 100%);
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			padding: 2rem 1rem;
		}

		.landing-brand {
			text-align: center;
			color: #fff;
			margin-bottom: 2.5rem;
		}
		.landing-brand i {
			font-size: 3rem;
			margin-bottom: .75rem;
			display: block;
			opacity: .9;
		}
		.landing-brand h1 {
			font-size: 2rem;
			font-weight: 700;
			margin-bottom: .25rem;
			letter-spacing: -.5px;
		}
		.landing-brand p {
			opacity: .8;
			font-size: 1rem;
			margin: 0;
		}

		.portal-cards {
			display: flex;
			gap: 1.5rem;
			flex-wrap: wrap;
			justify-content: center;
			max-width: 700px;
			width: 100%;
		}

		.portal-card {
			flex: 1 1 280px;
			max-width: 320px;
			background: #fff;
			border-radius: 12px;
			padding: 2rem 1.75rem 1.75rem;
			text-align: center;
			box-shadow: 0 8px 32px rgba(0,0,0,.18);
			text-decoration: none;
			color: inherit;
			transition: transform .15s, box-shadow .15s;
			display: flex;
			flex-direction: column;
			align-items: center;
		}
		.portal-card:hover {
			transform: translateY(-4px);
			box-shadow: 0 14px 40px rgba(0,0,0,.22);
			text-decoration: none;
			color: inherit;
		}

		.portal-card .card-icon {
			width: 64px;
			height: 64px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 1.75rem;
			margin-bottom: 1.25rem;
		}
		.portal-card .card-icon.staff   { background: #e8f0fe; color: #1a4f8a; }
		.portal-card .card-icon.patient { background: #d4ede3; color: #2e7d5e; }

		.portal-card h2 {
			font-size: 1.2rem;
			font-weight: 700;
			margin-bottom: .4rem;
		}
		.portal-card p {
			font-size: .875rem;
			color: #6c757d;
			margin-bottom: 1.5rem;
			line-height: 1.5;
		}

		.portal-card .btn-enter {
			width: 100%;
			border-radius: 6px;
			font-weight: 600;
			padding: .55rem 1rem;
			font-size: .9rem;
		}
		.portal-card.staff-card  .btn-enter { background: #1a4f8a; color: #fff; border: none; }
		.portal-card.staff-card  .btn-enter:hover { background: #163f6e; }
		.portal-card.patient-card .btn-enter { background: #2e7d5e; color: #fff; border: none; }
		.portal-card.patient-card .btn-enter:hover { background: #245f49; }

		.landing-footer {
			margin-top: 2.5rem;
			color: rgba(255,255,255,.55);
			font-size: .8rem;
			text-align: center;
		}
	</style>
</head>
<body>

<div class="landing-brand">
	<i class="fas fa-heart-pulse"></i>
	<h1>D3S3 CareSystem</h1>
	<p>Community Health Management Platform</p>
</div>

<div class="portal-cards">

	<a href="login.php" class="portal-card staff-card">
		<div class="card-icon staff">
			<i class="fas fa-user-md"></i>
		</div>
		<h2>Staff Portal</h2>
		<p>For clinicians, nurses, administrators, and all care team members.</p>
		<span class="btn-enter">
			<i class="fas fa-sign-in-alt mr-1"></i> Staff Sign In
		</span>
	</a>

	<a href="patient_login.php" class="portal-card patient-card">
		<div class="card-icon patient">
			<i class="fas fa-user-injured"></i>
		</div>
		<h2>Patient Portal</h2>
		<p>View your appointments, health record, lab results, and message your care team.</p>
		<span class="btn-enter">
			<i class="fas fa-sign-in-alt mr-1"></i> Patient Sign In
		</span>
	</a>

</div>

<div class="landing-footer">
	&copy; <?= date('Y') ?> D3S3 CareSystem &mdash; Prototype
</div>

</body>
</html>
