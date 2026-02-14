<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>New Patient Intake | D3S3 CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">
	<nav class="main-header navbar navbar-expand navbar-white navbar-light">
		<ul class="navbar-nav">
			<li class="nav-item">
				<a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Toggle sidebar">
					<i class="fas fa-bars"></i>
				</a>
			</li>
			<li class="nav-item d-none d-sm-inline-block">
				<span class="navbar-brand mb-0 h6 text-primary">CareSystem - New Intake</span>
			</li>
		</ul>
		<ul class="navbar-nav ml-auto">
			<li class="nav-item">
				<a class="btn btn-sm btn-secondary" href="dashboard.php">
					<i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
				</a>
			</li>
		</ul>
	</nav>

	<?php include __DIR__ . '/app/views/_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-12">
						<h1 class="m-0 text-dark">New Patient Intake</h1>
						<p class="text-muted mb-0">Create a new case sheet for patient visit</p>
					</div>
				</div>
			</div>
		</div>

		<section class="content">
			<div class="container-fluid">
				<div class="row justify-content-center">
					<div class="col-md-6">
						<div class="card shadow">
							<div class="card-header bg-primary">
								<h3 class="card-title text-white"><i class="fas fa-clipboard-list mr-2"></i>Intake Information</h3>
							</div>
							<div class="card-body">
								<form id="intakeForm">
									<div class="form-group">
										<label for="patient_id">Patient ID <span class="text-danger">*</span></label>
										<input type="number" class="form-control form-control-lg" id="patient_id" name="patient_id" 
										       placeholder="Enter patient ID" required min="1">
										<small class="form-text text-muted">Enter the patient's ID number from the system</small>
									</div>

									<div class="form-group">
										<label for="visit_type">Visit Type <span class="text-danger">*</span></label>
										<input type="text" class="form-control form-control-lg" id="visit_type" name="visit_type" 
										       placeholder="e.g., Screening, Follow-up, Consultation" required>
										<small class="form-text text-muted">Describe the purpose of this visit</small>
									</div>

									<div class="alert alert-info">
										<i class="fas fa-info-circle mr-2"></i>
										<strong>Note:</strong> A new case sheet will be created. If this patient has previous visits, 
										their personal information and medical history will be automatically copied to the new case sheet.
									</div>

									<div id="errorAlert" class="alert alert-danger" style="display: none;">
										<i class="fas fa-exclamation-triangle mr-2"></i>
										<span id="errorMessage"></span>
									</div>

									<div id="successAlert" class="alert alert-success" style="display: none;">
										<i class="fas fa-check-circle mr-2"></i>
										<span id="successMessage"></span>
									</div>

									<div class="text-right">
										<button type="button" class="btn btn-secondary" onclick="window.location.href='dashboard.php'">
											<i class="fas fa-times mr-1"></i>Cancel
										</button>
										<button type="submit" class="btn btn-primary btn-lg" id="createCaseBtn">
											<i class="fas fa-plus-circle mr-1"></i>Create New Case
										</button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>

	<footer class="main-footer text-sm">
		<strong>CareSystem</strong> &middot; Patient intake and case management
	</footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
	$('#intakeForm').on('submit', function(e) {
		e.preventDefault();
		
		// Disable button to prevent double submission
		$('#createCaseBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Creating...');
		
		// Hide previous alerts
		$('#errorAlert').hide();
		$('#successAlert').hide();
		
		var patientId = $('#patient_id').val();
		var visitType = $('#visit_type').val();
		
		$.ajax({
			url: 'create_case_sheet.php',
			type: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				patient_id: patientId,
				visit_type: visitType,
				user_id: 1 // TODO: Get from session
			}),
			dataType: 'json',
			success: function(response) {
				console.log('Server response:', response);
				
				if (response.success) {
					$('#successMessage').text('Case sheet created successfully! Redirecting...');
					$('#successAlert').fadeIn();
					
					console.log('Redirecting to case sheet:', response.case_sheet_id);
					
					// Redirect to the new case sheet after 1 second
					setTimeout(function() {
						var redirectUrl = '/d3s3/case-sheet.php?patient_id=' + patientId + '&case_sheet_id=' + response.case_sheet_id;
						console.log('Redirect URL:', redirectUrl);
						window.location.href = redirectUrl;
					}, 1000);
				} else {
					console.error('Server returned error:', response.message);
					$('#errorMessage').text(response.message || 'Failed to create case sheet');
					$('#errorAlert').fadeIn();
					$('#createCaseBtn').prop('disabled', false).html('<i class="fas fa-plus-circle mr-1"></i>Create New Case');
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX error:', {xhr: xhr, status: status, error: error});
				console.error('Response text:', xhr.responseText);
				
				var errorMsg = 'Error: ' + error;
				if (xhr.responseText) {
					try {
						var errorResponse = JSON.parse(xhr.responseText);
						errorMsg = errorResponse.message || errorMsg;
					} catch(e) {
						errorMsg = 'Server error. Check console for details.';
					}
				}
				
				$('#errorMessage').text(errorMsg);
				$('#errorAlert').fadeIn();
				$('#createCaseBtn').prop('disabled', false).html('<i class="fas fa-plus-circle mr-1"></i>Create New Case');
			}
		});
	});
});
</script>
</body>
</html>
