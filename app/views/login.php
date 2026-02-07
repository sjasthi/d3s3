<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Sign In | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
</head>
<body class="login-page">

<div class="card login-card">
	<div class="login-brand">
		<i class="fas fa-heart-pulse"></i>
		<h2>D3S3 CareSystem</h2>
		<p>Sign in to your workspace</p>
	</div>

	<?php if ($loginSuccess !== null): ?>
	<div class="px-4 pt-2">
		<div class="alert alert-success" role="alert">
			<?= htmlspecialchars($loginSuccess) ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ($loginError !== null): ?>
	<div class="px-4 pt-2">
		<div class="alert alert-danger" role="alert">
			<?= htmlspecialchars($loginError) ?>
		</div>
	</div>
	<?php endif; ?>

	<div class="card-body">
		<form method="POST" action="login.php" novalidate>
			<input type="hidden" name="csrf_token"
				   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />

			<div class="form-group">
				<label for="email" class="small font-weight-bold text-muted">Email</label>
				<div class="input-group">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fas fa-envelope"></i></span>
					</div>
					<input type="email" name="email" id="email" class="form-control"
						   placeholder="you@example.com"
						   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
						   autocomplete="email" required />
				</div>
			</div>

			<div class="form-group">
				<label for="password" class="small font-weight-bold text-muted">Password</label>
				<div class="input-group">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fas fa-lock"></i></span>
					</div>
					<input type="password" name="password" id="password" class="form-control"
						   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
						   autocomplete="current-password" required />
				</div>
			</div>

			<button type="submit" class="btn btn-login mt-2">Sign In</button>

			<p class="text-center text-muted small mt-3 mb-0">
				Don't have an account? <a href="emp_register.php">Create one</a>
			</p>
		</form>
	</div>
</div>

</body>
</html>
