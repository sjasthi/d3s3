<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Case Sheet Export | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/vendor/datatables/jquery.dataTables.min.css" />
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
	<div class="content-wrapper ml-0">
		<div class="content-header">
			<div class="container-fluid d-flex justify-content-between align-items-center">
				<div>
					<h1 class="m-0">Case sheets</h1>
					<p class="text-muted mb-0">The same rows download as an Excel file.</p>
				</div>
				<a class="btn btn-success" href="case_export.php?format=xlsx"><i class="fas fa-file-excel mr-1"></i>Download Excel</a>
			</div>
		</div>
		<section class="content">
			<div class="container-fluid">
				<table id="caseExportTable" class="table table-sm table-bordered table-striped">
					<thead>
						<tr>
							<th>Patient code</th>
							<th>Name</th>
							<th>Age</th>
							<th>Visit</th>
							<th>Type</th>
							<th>Status</th>
							<th>Chief complaint</th>
							<th>Follow-up</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($pageRows as $row): ?>
						<tr>
							<td><?= htmlspecialchars($row['patient_code']) ?></td>
							<td><?= htmlspecialchars(trim($row['first_name'] . ' ' . ($row['last_name'] ?? ''))) ?></td>
							<td><?= htmlspecialchars((string)($row['age_years'] ?? '')) ?></td>
							<td><?= htmlspecialchars(date('Y-m-d', strtotime($row['visit_datetime']))) ?></td>
							<td><?= htmlspecialchars($row['visit_type']) ?></td>
							<td><?= htmlspecialchars($row['status']) ?></td>
							<td><?= htmlspecialchars($row['chief_complaint'] ?? '') ?></td>
							<td><?= htmlspecialchars($row['follow_up_date'] ?? '') ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
	</div>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/vendor/datatables/jquery.dataTables.min.js"></script>
<script>
$(function () { $('#caseExportTable').DataTable({ pageLength: 25, order: [[3, 'desc']] }); });
</script>
</body>
</html>
