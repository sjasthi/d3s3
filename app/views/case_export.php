<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Case Sheet Export | CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<link rel="stylesheet" href="assets/vendor/datatables/jquery.dataTables.min.css" />
	<style>
		#caseExportTable { width: 100% !important; }
		.dataTables_wrapper .dataTables_filter input { margin-left: .5rem; }
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
	</nav>

	<?php require __DIR__ . '/_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2 align-items-center">
					<div class="col-sm-8">
						<h1 class="m-0 text-dark">Export case sheets</h1>
						<p class="text-muted mb-0">
							Search and filter the table, then download an Excel-compatible file
							aligned with the camp screening spreadsheet columns.
							The download includes only the rows matching the current filter.
						</p>
					</div>
					<div class="col-sm-4 text-sm-right mt-2 mt-sm-0">
						<a id="downloadExcelBtn" class="btn btn-success" href="case_export.php?format=xlsx">
							<i class="fas fa-file-excel mr-1"></i>Download Excel
						</a>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">
				<div class="card shadow-sm">
					<div class="card-body table-responsive">
						<table id="caseExportTable" class="table table-sm table-bordered table-striped">
							<thead>
								<tr>
									<th>S No</th>
									<th>Patient code</th>
									<th>Name</th>
									<th>Age</th>
									<th>Phone</th>
									<th>Visit</th>
									<th>Type</th>
									<th>Status</th>
									<th>Complaints</th>
									<th>BP</th>
									<th>BMI</th>
									<th>Follow-up</th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ($pageRows as $row): ?>
								<tr data-case-id="<?= (int)$row['_case_sheet_id'] ?>">
									<td><?= htmlspecialchars((string)$row['S No']) ?></td>
									<td><?= htmlspecialchars((string)$row['Patient code']) ?></td>
									<td><?= htmlspecialchars((string)$row['Full Name']) ?></td>
									<td><?= htmlspecialchars((string)$row['Age']) ?></td>
									<td><?= htmlspecialchars((string)$row['Ph No']) ?></td>
									<td><?= htmlspecialchars((string)$row['Visit date']) ?></td>
									<td><?= htmlspecialchars((string)$row['Visit type']) ?></td>
									<td><?= htmlspecialchars((string)$row['Status']) ?></td>
									<td><?= htmlspecialchars((string)$row['Complaints']) ?></td>
									<td><?= htmlspecialchars((string)$row['BP']) ?></td>
									<td><?= htmlspecialchars((string)$row['BMI']) ?></td>
									<td><?= htmlspecialchars((string)$row['Follow-up']) ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</section>
	</div>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script src="assets/js/theme-toggle.js"></script>
<script src="assets/vendor/datatables/jquery.dataTables.min.js"></script>
<script>
$(function () {
	var table = $('#caseExportTable').DataTable({
		pageLength: 25,
		order: [[5, 'desc']],
		scrollX: true
	});

	function syncDownloadHref() {
		var params = new URLSearchParams();
		params.set('format', 'xlsx');
		var q = table.search();
		if (q) {
			params.set('q', q);
		}
		var ids = [];
		table.rows({ search: 'applied' }).every(function () {
			var id = $(this.node()).attr('data-case-id');
			if (id) {
				ids.push(id);
			}
		});
		if (ids.length) {
			params.set('ids', ids.join(','));
		}
		$('#downloadExcelBtn').attr('href', 'case_export.php?' + params.toString());
	}

	table.on('search.dt draw.dt', syncDownloadHref);
	syncDownloadHref();
});
</script>
</body>
</html>
