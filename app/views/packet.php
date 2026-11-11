<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<title>Patient packet | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<style>
		@media print { .no-print { display: none !important; } }
	</style>
</head>
<body class="p-4">
	<div class="no-print mb-3">
		<a class="btn btn-outline-secondary" href="patients.php?action=view&amp;id=<?= (int)$patient['patient_id'] ?>">Back</a>
		<button class="btn btn-secondary" onclick="window.print()">Print / Save PDF</button>
		<?php if ($waUrl !== ''): ?>
		<a class="btn btn-success" href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener">Send on WhatsApp</a>
		<?php else: ?>
		<span class="text-danger ml-2">This patient has no phone number, so WhatsApp cannot be opened.</span>
		<?php endif; ?>
	</div>
	<h1>Patient packet</h1>
	<p><strong><?= htmlspecialchars(trim($patient['first_name'] . ' ' . ($patient['last_name'] ?? ''))) ?></strong>
		<code><?= htmlspecialchars($patient['patient_code']) ?></code></p>
	<pre style="white-space:pre-wrap;font-family:Georgia,serif"><?= htmlspecialchars($message) ?></pre>
	<p class="text-muted">This packet is a treatment summary for the patient or their family. It is sent through WhatsApp only when a staff member chooses Send.</p>
</body>
</html>
