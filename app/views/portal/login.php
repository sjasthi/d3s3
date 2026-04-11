<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Patient Sign In | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
		/* ── Patient portal colour overrides ── */
		:root {
			--brand-primary:   #2e7d5e;
			--brand-secondary: #1b4d39;
		}
		body.login-page {
			background: linear-gradient(135deg, #1b4d39 0%, #2e7d5e 60%, #48a97e 100%);
		}
		.login-brand i          { color: #2e7d5e; }
		.login-card .form-control:focus {
			border-color: #2e7d5e;
			box-shadow: 0 0 0 3px rgba(46,125,94,.18);
		}
		.btn-login {
			background: linear-gradient(135deg, #2e7d5e, #1b4d39);
		}
	</style>
</head>
<body class="login-page">

<div class="card login-card">
	<div class="login-brand">
		<i class="fas fa-user-injured"></i>
		<h2>Patient Portal</h2>
		<p>D3S3 CareSystem – sign in to your account</p>
	</div>

	<?php if (!empty($loginSuccess)): ?>
	<div class="px-4 pt-2">
		<div class="alert alert-success"><?= htmlspecialchars($loginSuccess) ?></div>
	</div>
	<?php endif; ?>

	<?php if (!empty($loginError)): ?>
	<div class="px-4 pt-2">
		<div class="alert alert-danger"><?= htmlspecialchars($loginError) ?></div>
	</div>
	<?php endif; ?>

	<div class="card-body">
		<form method="POST" action="patient_login.php" novalidate>
			<input type="hidden" name="csrf_token"
				   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

			<div class="form-group">
				<label for="identifier" class="small font-weight-bold text-muted">Email or Username</label>
				<div class="input-group">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fas fa-user"></i></span>
					</div>
					<input type="text" name="identifier" id="identifier" class="form-control"
						   placeholder="your@email.com or username"
						   value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
						   autocomplete="username" required />
				</div>
			</div>

			<div class="form-group">
				<label for="password" class="small font-weight-bold text-muted">Password</label>
				<div class="input-group">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fas fa-lock"></i></span>
					</div>
					<input type="password" name="password" id="password" class="form-control"
						   placeholder="••••••••"
						   autocomplete="current-password" required />
				</div>
			</div>

			<button type="submit" class="btn btn-login mt-2">Sign In</button>
		</form>
	</div>

	<div class="card-footer text-center text-muted small py-3">
		Staff? <a href="login.php">Sign in here</a>
	</div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
