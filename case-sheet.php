<?php
// Start session for data storage
session_start();

// Get case_sheet_id and patient_id from URL
$case_sheet_id = $_GET['case_sheet_id'] ?? null;
$patient_id = $_GET['patient_id'] ?? null;

// TODO: Add validation and database fetch for case sheet
// For now, we'll work with the patient_id

if (!$patient_id) {
    // Redirect to patient selection or show error
    header('Location: dashboard.php');
    exit;
}

// Page title
$pageTitle = "Case Sheet";
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo $pageTitle; ?> | D3S3 CareSystem</title>
	<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
	<link rel="stylesheet" href="assets/icons/css/all.min.css" />
	<link rel="stylesheet" href="assets/css/adminlte.min.css" />
	<link rel="stylesheet" href="assets/css/theme.css" />
	<style>
		/* Navbar tab styling (Fix 3) */
		#caseSheetTabs.nav-pills .nav-link {
			color: #495057;  /* Dark gray text for white navbar */
			background-color: transparent;
			border-radius: 0.25rem;
			font-size: 0.9rem;
			transition: all 0.2s;
			border: 1px solid transparent;
		}
		
		#caseSheetTabs.nav-pills .nav-link:hover {
			background-color: #f8f9fa;
			border-color: #dee2e6;
		}
		
		#caseSheetTabs.nav-pills .nav-link.active {
			color: #007bff;  /* Primary blue for active tab */
			background-color: #e7f3ff;
			border-color: #007bff;
			font-weight: 600;
			box-shadow: 0 2px 4px rgba(0,123,255,0.15);
		}
		
		/* Tab content styling */
		.tab-content {
			padding: 20px;
			background-color: #fff;
			min-height: 400px;
		}
		
		.tab-navigation {
			margin-top: 20px;
			padding-top: 20px;
			border-top: 1px solid #dee2e6;
			display: flex;
			justify-content: space-between;
		}
		
		.auto-save-indicator {
			position: fixed;
			top: 70px;
			right: 20px;
			padding: 10px 20px;
			background-color: #28a745;
			color: white;
			border-radius: 4px;
			display: none;
			z-index: 1000;
			box-shadow: 0 2px 4px rgba(0,0,0,0.2);
		}
		
		.auto-save-indicator.saving {
			background-color: #ffc107;
			color: #000;
		}
		
		.auto-save-indicator.error {
			background-color: #dc3545;
		}
	</style>
	<style>
		/* Diagram Editor Styles */
		.diagram-toolbar {
			background: #f8f9fa !important;
		}
		
		.diagram-toolbar .btn-group {
			width: 100%;
		}
		
		.diagram-toolbar .btn {
			border-radius: 0.2rem !important;
		}
		
		.diagram-toolbar .btn.active {
			box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
		}
		
		#diagramCanvas {
			max-width: 100%;
			height: auto;
		}
		
		.diagram-canvas-container {
			overflow: auto;
			max-height: 70vh;
		}
		
		/* Modal sizing for diagram editor */
		.modal-xl {
			max-width: 90%;
		}
	</style>
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
				<span class="navbar-brand mb-0 h6 text-primary">CareSystem</span>
			</li>
		</ul>
		
		<!-- Case Sheet Tabs in Navbar (Fix 3) -->
		<ul class="nav nav-pills ml-4 d-none d-lg-flex" id="caseSheetTabs" role="tablist">
			<li class="nav-item">
				<a class="nav-link active px-3" id="verification-tab" data-toggle="tab" href="#verification" role="tab" aria-controls="verification" aria-selected="true">
					Verification
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link px-3" id="personal-tab" data-toggle="tab" href="#personal" role="tab" aria-controls="personal" aria-selected="false">
					Personal
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link px-3" id="history-tab" data-toggle="tab" href="#history" role="tab" aria-controls="history" aria-selected="false">
					History
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link px-3" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="false">
					General
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link px-3" id="examinations-tab" data-toggle="tab" href="#examinations" role="tab" aria-controls="examinations" aria-selected="false">
					Examinations
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link px-3" id="labs-tab" data-toggle="tab" href="#labs" role="tab" aria-controls="labs" aria-selected="false">
					Labs
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link px-3" id="summary-tab" data-toggle="tab" href="#summary" role="tab" aria-controls="summary" aria-selected="false">
					Summary
				</a>
			</li>
		</ul>
		
		<ul class="navbar-nav ml-auto">
			<li class="nav-item">
				<button type="button" class="btn btn-sm btn-secondary mr-2" id="saveExitBtn">
					<i class="fas fa-save mr-1"></i>Save and Exit
				</button>
			</li>
		</ul>
	</nav>

	<?php include __DIR__ . '/app/views/_sidebar.php'; ?>

	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2 align-items-center">
					<div class="col-sm-8">
						<h1 class="m-0 text-dark">
							<span id="patientHeaderName">Loading...</span> 
							<small class="text-muted" id="patientHeaderDOB"></small>
							<span class="text-muted">| Case Sheet</span>
						</h1>
						<p class="text-muted mb-0">NCD Screening and Treatment Documentation</p>
					</div>
					<div class="col-sm-4 text-sm-right">
						<span id="caseStatusBadge" class="badge badge-secondary" style="font-size: 1rem; padding: 0.5rem 1rem; display: none;">
							<i class="fas fa-circle mr-1"></i><span id="statusText">LOADING</span>
						</span>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Auto-save indicator -->
		<div id="autoSaveIndicator" class="auto-save-indicator">
			<i class="fas fa-check-circle"></i> Saved
		</div>
		
		<section class="content">
			<div class="container-fluid">
				<div class="card shadow-sm">
					<div class="card-body">
                        
                        <!-- Tab Content -->
                        <div class="tab-content" id="caseSheetTabContent">
                            
                            <!-- Verification Tab -->
                            <div class="tab-pane fade show active" id="verification" role="tabpanel" aria-labelledby="verification-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h4 class="mb-1">Patient Verification</h4>
                                        <p class="text-muted mb-0">Verify and update patient demographic information</p>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary" id="editPatientBtn">
                                        <i class="fas fa-edit mr-1"></i>Edit Information
                                    </button>
                                </div>
                                
                                <div class="alert alert-info" id="editModeAlert" style="display: none;">
                                    <i class="fas fa-info-circle mr-2"></i>Edit mode enabled. Changes will be saved automatically.
                                </div>
                                
                                <form id="patientVerificationForm">
                                    <input type="hidden" id="patient_id" name="patient_id" value="<?php echo htmlspecialchars($patient_id); ?>">
                                    
                                    <!-- Name Row -->
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="first_name" class="form-label">First Name</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" readonly required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="last_name" class="form-label">Last Name</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" readonly>
                                        </div>
                                    </div>
                                    
                                    <!-- Aadhaar, Age, Sex Row -->
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="aadhaar_number" class="form-label">Aadhaar Number</label>
                                            <input type="text" class="form-control" id="aadhaar_number" name="aadhaar_number" 
                                                   maxlength="12" pattern="[0-9]{12}" placeholder="000000000000" readonly>
                                            <small class="form-text text-muted">12-digit Aadhaar number</small>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="age_years" class="form-label">Age (Years)</label>
                                            <input type="number" class="form-control" id="age_years" name="age_years" 
                                                   min="0" max="150" readonly>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="sex" class="form-label">Sex</label>
                                            <select class="form-control" id="sex" name="sex" disabled>
                                                <option value="">Select...</option>
                                                <option value="MALE">Male</option>
                                                <option value="FEMALE">Female</option>
                                                <option value="OTHER">Other</option>
                                                <option value="UNKNOWN">Unknown</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Date of Birth Row -->
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" readonly>
                                        </div>
                                    </div>
                                    
                                    <!-- Address Section -->
                                    <h5 class="mt-4 mb-3">Address Information</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="address_line1" class="form-label">Address Line 1</label>
                                            <input type="text" class="form-control" id="address_line1" name="address_line1" 
                                                   maxlength="120" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="address_line2" class="form-label">Address Line 2</label>
                                            <input type="text" class="form-control" id="address_line2" name="address_line2" 
                                                   maxlength="120" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="city" class="form-label">City</label>
                                            <input type="text" class="form-control" id="city" name="city" 
                                                   maxlength="80" readonly>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="state_province" class="form-label">State/Province</label>
                                            <input type="text" class="form-control" id="state_province" name="state_province" 
                                                   maxlength="80" readonly>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="postal_code" class="form-label">Postal Code</label>
                                            <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                                   maxlength="20" readonly>
                                        </div>
                                    </div>
                                    
                                    <!-- Contact Information -->
                                    <h5 class="mt-4 mb-3">Contact Information</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="phone_e164" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone_e164" name="phone_e164" 
                                                   placeholder="+919876543210" readonly>
                                            <small class="form-text text-muted">Include country code (e.g., +91)</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Emergency Contact -->
                                    <h5 class="mt-4 mb-3">Emergency Contact</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                            <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                                   maxlength="120" readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                                            <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone" 
                                                   maxlength="20" readonly>
                                        </div>
                                    </div>
                                    
                                    <!-- Medical Information -->
                                    <h5 class="mt-4 mb-3">Medical Information</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="blood_group" class="form-label">Blood Group</label>
                                            <select class="form-control" id="blood_group" name="blood_group" disabled>
                                                <option value="">Unknown</option>
                                                <option value="A+">A+</option>
                                                <option value="A-">A-</option>
                                                <option value="B+">B+</option>
                                                <option value="B-">B-</option>
                                                <option value="AB+">AB+</option>
                                                <option value="AB-">AB-</option>
                                                <option value="O+">O+</option>
                                                <option value="O-">O-</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="allergies" class="form-label">Known Allergies</label>
                                            <input type="text" class="form-control" id="allergies" name="allergies" 
                                                   maxlength="255" placeholder="e.g., Penicillin, Peanuts" readonly>
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="tab-navigation">
                                    <button type="button" class="btn btn-secondary" disabled>
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <button type="button" class="btn btn-primary" data-next-tab="personal">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Personal Tab -->
                            <div class="tab-pane fade" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                                <h4 class="mb-3">Personal Information</h4>
                                
                                <form id="personalForm" class="case-sheet-form">
                                    <!-- Visit Information -->
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="visit_type" class="form-label">Purpose of Visit</label>
                                            <input type="text" class="form-control" id="visit_type" name="visit_type" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="symptoms_complaints" class="form-label">Symptoms/Complaints</label>
                                            <textarea class="form-control" id="symptoms_complaints" name="symptoms_complaints" 
                                                      rows="3" data-table="case_sheets"></textarea>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="duration_of_symptoms" class="form-label">Duration of Symptoms</label>
                                            <input type="text" class="form-control" id="duration_of_symptoms" name="duration_of_symptoms" 
                                                   maxlength="255" placeholder="e.g., 3 days, 2 weeks" data-table="case_sheets">
                                        </div>
                                    </div>
                                    
                                    <!-- Patient Background Information -->
                                    <h5 class="mt-4 mb-3">Background Information</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="medicine_sources" class="form-label">Medicine Sources</label>
                                            <select class="form-control" id="medicine_sources" name="medicine_sources" data-table="patients">
                                                <option value="NONE">None</option>
                                                <option value="PRIVATE">Private</option>
                                                <option value="GOVERNMENT">Government</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="occupation" class="form-label">Occupation</label>
                                            <input type="text" class="form-control" id="occupation" name="occupation" 
                                                   maxlength="100" data-table="patients">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="education" class="form-label">Education</label>
                                            <input type="text" class="form-control" id="education" name="education" 
                                                   maxlength="100" data-table="patients">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="diet" class="form-label">Diet</label>
                                            <input type="text" class="form-control" id="diet" name="diet" 
                                                   maxlength="100" data-table="patients">
                                        </div>
                                    </div>
                                    
                                    <!-- Reproductive History -->
                                    <h5 class="mt-4 mb-3">Reproductive History</h5>
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="number_of_children" class="form-label">Number of Children</label>
                                            <input type="number" class="form-control" id="number_of_children" name="number_of_children" 
                                                   min="0" max="20" value="0" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="has_uterus" class="form-label">Uterus</label>
                                            <select class="form-control" id="has_uterus" name="has_uterus" data-table="case_sheets">
                                                <option value="1">Yes</option>
                                                <option value="0">No</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Delivery Details (conditional - only shown if number_of_children > 0) -->
                                    <div id="deliveryDetailsSection" style="display: none;">
                                        <h6 class="mb-3">Delivery Details</h6>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="type_of_delivery" class="form-label">Type of Delivery</label>
                                                <select class="form-control" id="type_of_delivery" name="type_of_delivery" data-table="case_sheets">
                                                    <option value="">Select...</option>
                                                    <option value="LSCS">LSCS</option>
                                                    <option value="ND">ND</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="delivery_location" class="form-label">Delivery Location</label>
                                                <input type="text" class="form-control" id="delivery_location" name="delivery_location" 
                                                       maxlength="255" data-table="case_sheets">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="delivery_source" class="form-label">Delivery Source</label>
                                                <select class="form-control" id="delivery_source" name="delivery_source" data-table="case_sheets">
                                                    <option value="">Select...</option>
                                                    <option value="PRIVATE">Private</option>
                                                    <option value="GH">GH</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Menstrual Details (conditional - only shown if has_uterus = 1) -->
                                    <div id="menstrualDetailsSection" style="display: none;">
                                        <!-- Last Menstrual Details from previous visit (only shown if previous case sheet exists) -->
                                        <div id="lastMenstrualDetailsSection" style="display: none;">
                                            <h5 class="mt-4 mb-3">Last Menstrual Details <small class="text-muted">(from previous visit)</small></h5>
                                            <div class="card bg-light border-0 shadow-sm mb-3">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-3 mb-2">
                                                            <label class="form-label text-muted small">Cycle Frequency</label>
                                                            <div id="prev_menstrual_cycle_frequency" class="font-weight-bold">--</div>
                                                        </div>
                                                        <div class="col-md-3 mb-2">
                                                            <label class="form-label text-muted small">Duration of Flow</label>
                                                            <div id="prev_menstrual_duration_of_flow" class="font-weight-bold">--</div>
                                                        </div>
                                                        <div class="col-md-3 mb-2">
                                                            <label class="form-label text-muted small">LMP</label>
                                                            <div id="prev_menstrual_lmp" class="font-weight-bold">--</div>
                                                        </div>
                                                        <div class="col-md-3 mb-2">
                                                            <label class="form-label text-muted small">MH</label>
                                                            <div id="prev_menstrual_mh" class="font-weight-bold">--</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <h5 class="mt-4 mb-3">Current Menstrual Details</h5>
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="menstrual_age_of_onset" class="form-label">Age of Onset</label>
                                                <input type="number" class="form-control" id="menstrual_age_of_onset" 
                                                       name="menstrual_age_of_onset" min="0" max="30" data-table="case_sheets">
                                                <small class="form-text text-muted">Years</small>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="menstrual_cycle_frequency" class="form-label">Cycle Frequency</label>
                                                <input type="number" class="form-control" id="menstrual_cycle_frequency" 
                                                       name="menstrual_cycle_frequency" min="0" max="90" data-table="case_sheets">
                                                <small class="form-text text-muted">Days</small>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="menstrual_duration_of_flow" class="form-label">Duration of Flow</label>
                                                <input type="number" class="form-control" id="menstrual_duration_of_flow" 
                                                       name="menstrual_duration_of_flow" min="0" max="30" data-table="case_sheets">
                                                <small class="form-text text-muted">Days</small>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="menstrual_lmp" class="form-label">LMP</label>
                                                <input type="date" class="form-control" id="menstrual_lmp" 
                                                       name="menstrual_lmp" data-table="case_sheets">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="menstrual_mh" class="form-label">MH</label>
                                                <select class="form-control" id="menstrual_mh" name="menstrual_mh" data-table="case_sheets">
                                                    <option value="">Select...</option>
                                                    <option value="REGULAR">Regular</option>
                                                    <option value="IRREGULAR">Irregular</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="tab-navigation">
                                    <button type="button" class="btn btn-secondary" data-prev-tab="verification">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <button type="button" class="btn btn-primary" data-next-tab="history">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- History Tab -->
                            <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
                                <h4 class="mb-3">Medical History</h4>
                                
                                <form id="historyForm" class="case-sheet-form">
                                    <!-- Conditions -->
                                    <h5 class="mb-3">Conditions</h5>
                                    <p class="text-muted small mb-3"><i class="fas fa-info-circle mr-1"></i>Note to doctor: Add new diagnoses here</p>
                                    
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="condition_dm" class="form-label">DM (Diabetes)</label>
                                            <select class="form-control" id="condition_dm" name="condition_dm" data-table="case_sheets">
                                                <option value="NO">No</option>
                                                <option value="CURRENT">Current</option>
                                                <option value="PAST">Past</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="condition_htn" class="form-label">HTN (Hypertension)</label>
                                            <select class="form-control" id="condition_htn" name="condition_htn" data-table="case_sheets">
                                                <option value="NO">No</option>
                                                <option value="CURRENT">Current</option>
                                                <option value="PAST">Past</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="condition_tsh" class="form-label">TSH (Thyroid)</label>
                                            <select class="form-control" id="condition_tsh" name="condition_tsh" data-table="case_sheets">
                                                <option value="NO">No</option>
                                                <option value="CURRENT">Current</option>
                                                <option value="PAST">Past</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="condition_heart_disease" class="form-label">Heart Disease</label>
                                            <select class="form-control" id="condition_heart_disease" name="condition_heart_disease" data-table="case_sheets">
                                                <option value="NO">No</option>
                                                <option value="CURRENT">Current</option>
                                                <option value="PAST">Past</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="condition_others" class="form-label">Other Conditions</label>
                                            <textarea class="form-control" id="condition_others" name="condition_others" 
                                                      rows="2" maxlength="500" data-table="case_sheets"></textarea>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="surgical_history" class="form-label">Surgical History</label>
                                            <textarea class="form-control" id="surgical_history" name="surgical_history" 
                                                      rows="2" maxlength="500" data-table="case_sheets"></textarea>
                                        </div>
                                    </div>
                                    
                                    <!-- Last General Exam (only shown if previous case sheet exists) -->
                                    <div id="lastGeneralExamSection" style="display: none;">
                                        <h5 class="mt-4 mb-3">Last General Exam <small class="text-muted">(from previous visit)</small></h5>
                                        <div class="card bg-light border-0 shadow-sm">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-2 mb-3">
                                                        <label class="form-label text-muted small">Pulse</label>
                                                        <div id="prev_pulse" class="font-weight-bold">--</div>
                                                    </div>
                                                    <div class="col-md-2 mb-3">
                                                        <label class="form-label text-muted small">B.P.</label>
                                                        <div id="prev_bp" class="font-weight-bold">--</div>
                                                    </div>
                                                    <div class="col-md-2 mb-3">
                                                        <label class="form-label text-muted small">Height</label>
                                                        <div id="prev_height" class="font-weight-bold">--</div>
                                                    </div>
                                                    <div class="col-md-2 mb-3">
                                                        <label class="form-label text-muted small">Weight</label>
                                                        <div id="prev_weight" class="font-weight-bold">--</div>
                                                    </div>
                                                    <div class="col-md-2 mb-3">
                                                        <label class="form-label text-muted small">BMI</label>
                                                        <div id="prev_bmi" class="font-weight-bold">--</div>
                                                    </div>
                                                    <div class="col-md-2 mb-3">
                                                        <label class="form-label text-muted small">Obesity/Overweight</label>
                                                        <div id="prev_obesity" class="font-weight-bold">--</div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <label class="form-label text-muted small">Summary of Last Visit</label>
                                                        <div id="prev_summary" class="font-weight-normal" style="white-space: pre-wrap;">--</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Family History -->
                                    <h5 class="mt-4 mb-3">Family History</h5>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="family_history_cancer" 
                                                       name="family_history_cancer" value="1" data-table="case_sheets">
                                                <label class="custom-control-label" for="family_history_cancer">Cancer</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="family_history_tuberculosis" 
                                                       name="family_history_tuberculosis" value="1" data-table="case_sheets">
                                                <label class="custom-control-label" for="family_history_tuberculosis">Tuberculosis</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="family_history_diabetes" 
                                                       name="family_history_diabetes" value="1" data-table="case_sheets">
                                                <label class="custom-control-label" for="family_history_diabetes">Diabetes</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="family_history_bp" 
                                                       name="family_history_bp" value="1" data-table="case_sheets">
                                                <label class="custom-control-label" for="family_history_bp">BP (Hypertension)</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="family_history_thyroid" 
                                                       name="family_history_thyroid" value="1" data-table="case_sheets">
                                                <label class="custom-control-label" for="family_history_thyroid">Thyroid</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="family_history_other" class="form-label">Other Family History</label>
                                            <input type="text" class="form-control" id="family_history_other" 
                                                   name="family_history_other" maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="tab-navigation">
                                    <button type="button" class="btn btn-secondary" data-prev-tab="personal">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <button type="button" class="btn btn-primary" data-next-tab="general">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- General Tab -->
                            <div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="general-tab">
                                <h4 class="mb-3">General Examination</h4>
                                
                                <form id="generalForm" class="case-sheet-form">
                                    <!-- Vital Signs -->
                                    <h5 class="mb-3">Vital Signs</h5>
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="general_pulse" class="form-label">Pulse</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="general_pulse" name="general_pulse" 
                                                       min="0" max="300" data-table="case_sheets">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">/mt</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="general_bp_systolic" class="form-label">B.P.</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="general_bp_systolic" name="general_bp_systolic" 
                                                       min="0" max="300" placeholder="Systolic" data-table="case_sheets">
                                                <div class="input-group-append input-group-prepend">
                                                    <span class="input-group-text">/</span>
                                                </div>
                                                <input type="number" class="form-control" id="general_bp_diastolic" name="general_bp_diastolic" 
                                                       min="0" max="200" placeholder="Diastolic" data-table="case_sheets">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">mm of Hg</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Physical Examination -->
                                    <h5 class="mt-4 mb-3">Physical Examination</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="general_heart" class="form-label">Heart</label>
                                            <input type="text" class="form-control" id="general_heart" name="general_heart" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="general_lungs" class="form-label">Lungs</label>
                                            <input type="text" class="form-control" id="general_lungs" name="general_lungs" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="general_liver" class="form-label">Liver</label>
                                            <input type="text" class="form-control" id="general_liver" name="general_liver" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="general_spleen" class="form-label">Spleen</label>
                                            <input type="text" class="form-control" id="general_spleen" name="general_spleen" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="general_lymph_glands" class="form-label">Lymph Glands</label>
                                            <input type="text" class="form-control" id="general_lymph_glands" name="general_lymph_glands" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    
                                    <!-- Anthropometric Measurements -->
                                    <h5 class="mt-4 mb-3">Anthropometric Measurements</h5>
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="general_height" class="form-label">Height</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="general_height" name="general_height" 
                                                       min="0" max="300" data-table="case_sheets">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">cm</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="general_weight" class="form-label">Weight</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="general_weight" name="general_weight" 
                                                       min="0" max="500" step="0.1" data-table="case_sheets">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">kgs</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="general_bmi" class="form-label">BMI</label>
                                            <input type="number" class="form-control" id="general_bmi" name="general_bmi" 
                                                   min="0" max="100" step="0.1" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="general_obesity_overweight" class="form-label">Obesity/Overweight</label>
                                            <select class="form-control" id="general_obesity_overweight" name="general_obesity_overweight" data-table="case_sheets">
                                                <option value="0">No</option>
                                                <option value="1">Yes</option>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="tab-navigation">
                                    <button type="button" class="btn btn-secondary" data-prev-tab="history">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <button type="button" class="btn btn-primary" data-next-tab="examinations">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Examinations Tab -->
                            <div class="tab-pane fade" id="examinations" role="tabpanel" aria-labelledby="examinations-tab">
                                <h4 class="mb-3">Physical Examinations</h4>
                                
                                <form id="examinationsForm" class="case-sheet-form">
                                    <!-- Head and Neck Examination -->
                                    <h5 class="mb-3">Head and Neck</h5>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_mouth" class="form-label">Mouth</label>
                                            <input type="text" class="form-control" id="exam_mouth" name="exam_mouth" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_lips" class="form-label">Lips</label>
                                            <input type="text" class="form-control" id="exam_lips" name="exam_lips" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_buccal_mucosa" class="form-label">Buccal Mucosa</label>
                                            <input type="text" class="form-control" id="exam_buccal_mucosa" name="exam_buccal_mucosa" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_teeth" class="form-label">Teeth</label>
                                            <input type="text" class="form-control" id="exam_teeth" name="exam_teeth" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_tongue" class="form-label">Tongue</label>
                                            <input type="text" class="form-control" id="exam_tongue" name="exam_tongue" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_oropharynx" class="form-label">Oropharynx</label>
                                            <input type="text" class="form-control" id="exam_oropharynx" name="exam_oropharynx" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_hypo" class="form-label">Hypo</label>
                                            <input type="text" class="form-control" id="exam_hypo" name="exam_hypo" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_naso_pharynx" class="form-label">Naso-Pharynx</label>
                                            <input type="text" class="form-control" id="exam_naso_pharynx" name="exam_naso_pharynx" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_larynx" class="form-label">Larynx</label>
                                            <input type="text" class="form-control" id="exam_larynx" name="exam_larynx" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_nose" class="form-label">Nose</label>
                                            <input type="text" class="form-control" id="exam_nose" name="exam_nose" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_ears" class="form-label">Ears</label>
                                            <input type="text" class="form-control" id="exam_ears" name="exam_ears" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_neck" class="form-label">Neck</label>
                                            <input type="text" class="form-control" id="exam_neck" name="exam_neck" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    
                                    <!-- Other Examinations -->
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="exam_bones_joints" class="form-label">Bones and Joints</label>
                                            <input type="text" class="form-control" id="exam_bones_joints" name="exam_bones_joints" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="exam_abdomen_genital" class="form-label">Abdomen and Genital Organs</label>
                                            <input type="text" class="form-control" id="exam_abdomen_genital" name="exam_abdomen_genital" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    
                                    <!-- Breasts -->
                                    <h5 class="mt-4 mb-3">Breasts</h5>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_breast_left" class="form-label">Left</label>
                                            <input type="text" class="form-control" id="exam_breast_left" name="exam_breast_left" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_breast_right" class="form-label">Right</label>
                                            <input type="text" class="form-control" id="exam_breast_right" name="exam_breast_right" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="exam_breast_axillary_nodes" class="form-label">Axillary Nodes</label>
                                            <input type="text" class="form-control" id="exam_breast_axillary_nodes" name="exam_breast_axillary_nodes" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="openBreastDiagramBtn">
                                                <i class="fas fa-draw-polygon mr-1"></i>Open Breast Diagram
                                            </button>
                                            <input type="hidden" id="exam_breast_diagram" name="exam_breast_diagram" data-table="case_sheets">
                                            <div id="breastDiagramPreview" class="mt-2">
                                                <img src="assets/images/diagrams/BreastExaminationDiagram.png" alt="Breast Diagram" class="img-thumbnail" style="max-width: 300px;">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Pelvic Examination Introitus -->
                                    <h5 class="mt-4 mb-3">Pelvic Examination Introitus</h5>
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="exam_pelvic_cervix" class="form-label">Cervix</label>
                                            <input type="text" class="form-control" id="exam_pelvic_cervix" name="exam_pelvic_cervix" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="exam_pelvic_uterus" class="form-label">Uterus</label>
                                            <input type="text" class="form-control" id="exam_pelvic_uterus" name="exam_pelvic_uterus" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="exam_pelvic_ovaries" class="form-label">Ovaries</label>
                                            <input type="text" class="form-control" id="exam_pelvic_ovaries" name="exam_pelvic_ovaries" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="exam_pelvic_adnexa" class="form-label">Adnexa</label>
                                            <input type="text" class="form-control" id="exam_pelvic_adnexa" name="exam_pelvic_adnexa" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="openPelvicDiagramBtn">
                                                <i class="fas fa-draw-polygon mr-1"></i>Open Pelvic Diagram
                                            </button>
                                            <input type="hidden" id="exam_pelvic_diagram" name="exam_pelvic_diagram" data-table="case_sheets">
                                            <div id="pelvicDiagramPreview" class="mt-2">
                                                <img src="assets/images/diagrams/PelvicExaminationDiagram.png" alt="Pelvic Diagram" class="img-thumbnail" style="max-width: 300px;">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Rectal Examination -->
                                    <h5 class="mt-4 mb-3">Rectal Examination</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="exam_rectal_skin" class="form-label">Skin</label>
                                            <input type="text" class="form-control" id="exam_rectal_skin" name="exam_rectal_skin" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="exam_rectal_remarks" class="form-label">Remarks</label>
                                            <input type="text" class="form-control" id="exam_rectal_remarks" name="exam_rectal_remarks" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    
                                    <!-- Gynaecological Examination -->
                                    <h5 class="mt-4 mb-3">Gynaecological Examination</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="exam_gynae_ps" class="form-label">P/S</label>
                                            <input type="text" class="form-control" id="exam_gynae_ps" name="exam_gynae_ps" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="exam_gynae_pv" class="form-label">P/V</label>
                                            <input type="text" class="form-control" id="exam_gynae_pv" name="exam_gynae_pv" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="exam_gynae_via" class="form-label">VIA</label>
                                            <input type="text" class="form-control" id="exam_gynae_via" name="exam_gynae_via" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-4" id="openViaDiagramBtn">
                                                <i class="fas fa-draw-polygon mr-1"></i>Open VIA Diagram
                                            </button>
                                            <input type="hidden" id="exam_gynae_via_diagram" name="exam_gynae_via_diagram" data-table="case_sheets">
                                            <div id="viaDiagramPreview" class="mt-2">
                                                <img src="assets/images/diagrams/VIAVILIDiagram.png" alt="VIA Diagram" class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="exam_gynae_vili" class="form-label">VILI</label>
                                            <input type="text" class="form-control" id="exam_gynae_vili" name="exam_gynae_vili" 
                                                   maxlength="255" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-4" id="openViliDiagramBtn">
                                                <i class="fas fa-draw-polygon mr-1"></i>Open VILI Diagram
                                            </button>
                                            <input type="hidden" id="exam_gynae_vili_diagram" name="exam_gynae_vili_diagram" data-table="case_sheets">
                                            <div id="viliDiagramPreview" class="mt-2">
                                                <img src="assets/images/diagrams/VIAVILIDiagram.png" alt="VILI Diagram" class="img-thumbnail" style="max-width: 200px;">
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="tab-navigation">
                                    <button type="button" class="btn btn-secondary" data-prev-tab="general">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <button type="button" class="btn btn-primary" data-next-tab="labs">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Labs Tab -->
                            <div class="tab-pane fade" id="labs" role="tabpanel" aria-labelledby="labs-tab">
                                <h4 class="mb-3">Laboratory Tests</h4>
                                
                                <form id="labsForm" class="case-sheet-form">
                                    <!-- Investigations -->
                                    <h5 class="mb-3">Investigations</h5>
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label for="lab_hb_percentage" class="form-label">Hb (Haemoglobin)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="lab_hb_percentage" name="lab_hb_percentage" 
                                                       min="0" max="100" placeholder="%" data-table="case_sheets">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                <input type="number" class="form-control" id="lab_hb_gms" name="lab_hb_gms" 
                                                       min="0" max="30" step="0.1" placeholder="gms" data-table="case_sheets">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">gms</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="lab_fbs" class="form-label">FBS</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="lab_fbs" name="lab_fbs" 
                                                       min="0" max="1000" step="0.1" data-table="case_sheets">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">mg/dl</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="lab_tsh" class="form-label">TSH</label>
                                            <input type="number" class="form-control" id="lab_tsh" name="lab_tsh" 
                                                   min="0" max="100" step="0.01" data-table="case_sheets">
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label for="lab_sr_creatinine" class="form-label">Sr. Creatinine</label>
                                            <input type="number" class="form-control" id="lab_sr_creatinine" name="lab_sr_creatinine" 
                                                   min="0" max="20" step="0.01" data-table="case_sheets">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="lab_others" class="form-label">Others</label>
                                            <textarea class="form-control" id="lab_others" name="lab_others" 
                                                      rows="2" maxlength="500" data-table="case_sheets"></textarea>
                                        </div>
                                    </div>
                                    
                                    <!-- Cytology Report -->
                                    <h5 class="mt-4 mb-3">Cytology Report</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cytology_papsmear" class="form-label">Papsmear</label>
                                            <select class="form-control" id="cytology_papsmear" name="cytology_papsmear" data-table="case_sheets">
                                                <option value="NONE">None</option>
                                                <option value="DONE">Done</option>
                                                <option value="ADVISED">Advised</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cytology_papsmear_notes" class="form-label">Papsmear Notes</label>
                                            <textarea class="form-control" id="cytology_papsmear_notes" name="cytology_papsmear_notes" 
                                                      rows="2" maxlength="500" data-table="case_sheets"></textarea>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cytology_colposcopy" class="form-label">Colposcopy</label>
                                            <select class="form-control" id="cytology_colposcopy" name="cytology_colposcopy" data-table="case_sheets">
                                                <option value="NONE">None</option>
                                                <option value="DONE">Done</option>
                                                <option value="ADVISED">Advised</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cytology_colposcopy_notes" class="form-label">Colposcopy Notes</label>
                                            <textarea class="form-control" id="cytology_colposcopy_notes" name="cytology_colposcopy_notes" 
                                                      rows="2" maxlength="500" data-table="case_sheets"></textarea>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cytology_biopsy" class="form-label">Biopsy</label>
                                            <select class="form-control" id="cytology_biopsy" name="cytology_biopsy" data-table="case_sheets">
                                                <option value="NONE">None</option>
                                                <option value="DONE">Done</option>
                                                <option value="ADVISED">Advised</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cytology_biopsy_notes" class="form-label">Biopsy Notes</label>
                                            <textarea class="form-control" id="cytology_biopsy_notes" name="cytology_biopsy_notes" 
                                                      rows="2" maxlength="500" data-table="case_sheets"></textarea>
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="tab-navigation">
                                    <button type="button" class="btn btn-secondary" data-prev-tab="examinations">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <button type="button" class="btn btn-primary" data-next-tab="summary">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Summary Tab -->
                            <div class="tab-pane fade" id="summary" role="tabpanel" aria-labelledby="summary-tab">
                                <h4 class="mb-3">Case Summary</h4>
                                
                                <form id="summaryForm" class="case-sheet-form">
                                    <!-- Risk Assessment and Referral -->
                                    <h5 class="mb-3">Assessment and Disposition</h5>
                                    <p class="text-muted small mb-3">
                                        <i class="fas fa-info-circle mr-1"></i><strong>Reminder:</strong> Add any new diagnoses to the Conditions section in the History tab.
                                    </p>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="summary_risk_level" class="form-label">Risk Level</label>
                                            <textarea class="form-control" id="summary_risk_level" name="summary_risk_level" 
                                                      rows="6" data-table="case_sheets"></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="summary_referral" class="form-label">Referral</label>
                                            <textarea class="form-control" id="summary_referral" name="summary_referral" 
                                                      rows="6" data-table="case_sheets"></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="summary_patient_acceptance" class="form-label">Patient Acceptance</label>
                                            <textarea class="form-control" id="summary_patient_acceptance" name="summary_patient_acceptance" 
                                                      rows="6" data-table="case_sheets"></textarea>
                                        </div>
                                    </div>
                                    
                                    <!-- Doctor's Summary -->
                                    <h5 class="mt-4 mb-3">Doctor's Summary</h5>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="summary_doctor_summary" class="form-label">Summary and Recommendations</label>
                                            <textarea class="form-control" id="summary_doctor_summary" name="summary_doctor_summary" 
                                                      rows="6" data-table="case_sheets"></textarea>
                                            <small class="form-text text-muted">
                                                <i class="fas fa-info-circle mr-1"></i>This summary will appear in the "Last General Exam" section for future visits.
                                            </small>
                                        </div>
                                    </div>
                                </form>
                                
                                <div class="tab-navigation">
                                    <button type="button" class="btn btn-secondary" data-prev-tab="labs">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </button>
                                    <button type="button" class="btn btn-success" id="finalSubmit">
                                        <i class="fas fa-check"></i> Complete Case Sheet
                                    </button>
                                </div>
                            </div>
                            
                        </div>
					</div>
				</div>
			</div>
		</section>
	</div>

	<footer class="main-footer text-sm">
		<strong>CareSystem</strong> &middot; Designed for clarity in bright conditions.
	</footer>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
<script>
$(document).ready(function() {
	var isEditMode = false;
	var caseSheetId = '<?php echo $case_sheet_id ?? ''; ?>';
	var patientId = $('#patient_id').val();
	var caseSheetStatus = 'OPEN'; // Default to OPEN
	
	// Load patient data on page load
	loadPatientData();
	
	// Load case sheet data on page load
	if (caseSheetId) {
		loadCaseSheetData();
	}
	
	// Conditional section display for Personal tab
	toggleDeliveryDetails();
	toggleMenstrualDetails();
	
	$('#number_of_children').on('change', toggleDeliveryDetails);
	$('#has_uterus').on('change', toggleMenstrualDetails);
	
	function toggleDeliveryDetails() {
		var numChildren = parseInt($('#number_of_children').val()) || 0;
		if (numChildren > 0) {
			$('#deliveryDetailsSection').slideDown();
		} else {
			$('#deliveryDetailsSection').slideUp();
			// Clear delivery fields when hidden
			$('#type_of_delivery, #delivery_location, #delivery_source').val('');
		}
	}
	
	function toggleMenstrualDetails() {
		var hasUterus = $('#has_uterus').val() === '1';
		if (hasUterus) {
			$('#menstrualDetailsSection').slideDown();
		} else {
			$('#menstrualDetailsSection').slideUp();
			// Clear menstrual fields when hidden
			$('#menstrualDetailsSection input, #menstrualDetailsSection select').val('');
		}
	}
	
	// Function to lock case sheet if CLOSED
	function lockCaseSheet() {
		// Disable edit button in verification tab
		$('#editPatientBtn').prop('disabled', true).html('<i class="fas fa-lock mr-1"></i>Locked');
		$('#editPatientBtn').removeClass('btn-outline-primary btn-warning').addClass('btn-secondary');
		
		// Make sure patient verification fields are read-only
		$('#patientVerificationForm input[type="text"], #patientVerificationForm input[type="tel"], #patientVerificationForm input[type="number"], #patientVerificationForm input[type="date"]').prop('readonly', true);
		$('#patientVerificationForm select').prop('disabled', true);
		
		// Make case sheet form inputs readonly (text/number/date inputs)
		$('.case-sheet-form input[type="text"], .case-sheet-form input[type="number"], .case-sheet-form input[type="date"], .case-sheet-form textarea').prop('readonly', true);
		
		// For selects, prevent changes but don't disable (so conditional logic still works)
		$('.case-sheet-form select').on('mousedown keydown', function(e) {
			e.preventDefault();
			return false;
		}).css({
			'pointer-events': 'none',
			'background-color': '#e9ecef',
			'opacity': '0.65'
		});
		
		// Disable Save and Exit button
		$('#saveExitBtn').prop('disabled', true).html('<i class="fas fa-lock mr-1"></i>Case Closed');
		
		// Show warning alert at top of form
		var warningHtml = '<div class="alert alert-warning alert-dismissible fade show" role="alert" style="margin: 1rem;">' +
			'<strong><i class="fas fa-lock mr-2"></i>This case is closed.</strong> ' +
			'All fields are read-only. Contact an admin to reopen this case if changes are needed.' +
			'<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
			'<span aria-hidden="true">&times;</span></button></div>';
		$('.tab-content').prepend(warningHtml);
		
		console.log('Case sheet locked - status is CLOSED');
	}
	
	// Function to update status badge
	function updateStatusBadge(status) {
		caseSheetStatus = status;
		var $badge = $('#caseStatusBadge');
		var $statusText = $('#statusText');
		
		if (status === 'OPEN') {
			$badge.removeClass('badge-secondary badge-danger').addClass('badge-success');
			$statusText.text('OPEN');
			$badge.fadeIn();
		} else if (status === 'CLOSED') {
			$badge.removeClass('badge-secondary badge-success').addClass('badge-danger');
			$statusText.text('CLOSED');
			$badge.fadeIn();
			lockCaseSheet();
		}
	}
	
	// Load patient data function
	function loadPatientData() {
		if (!patientId) {
			console.error('No patient ID provided');
			return;
		}
		
		$.ajax({
			url: 'get_patient.php',
			type: 'GET',
			data: { patient_id: patientId },
			dataType: 'json',
			success: function(response) {
				if (response.success && response.patient) {
					populatePatientForm(response.patient);
				} else {
					showAutoSaveIndicator('error', 'Failed to load patient data');
					console.error(response.message);
				}
			},
			error: function(xhr, status, error) {
				showAutoSaveIndicator('error', 'Error loading patient data');
				console.error('AJAX error:', error);
			}
		});
	}
	
	// Load case sheet data function
	function loadCaseSheetData() {
		$.ajax({
			url: 'get_case_sheet.php',
			type: 'GET',
			data: { case_sheet_id: caseSheetId },
			dataType: 'json',
			success: function(response) {
				if (response.success && response.case_sheet) {
					populateCaseSheetForm(response.case_sheet);
					toggleDeliveryDetails();
					toggleMenstrualDetails();
					
					// Update status badge
					if (response.case_sheet.status) {
						updateStatusBadge(response.case_sheet.status);
					}
				} else {
					console.error('Failed to load case sheet:', response.message);
				}
			},
			error: function(xhr, status, error) {
				console.error('Error loading case sheet:', error);
			}
		});
	}
	
	// Populate patient form fields
	function populatePatientForm(patient) {
		// Verification tab fields
		$('#first_name').val(patient.first_name || '');
		$('#last_name').val(patient.last_name || '');
		$('#aadhaar_number').val(patient.aadhaar_number || '');
		$('#age_years').val(patient.age_years || '');
		$('#sex').val(patient.sex || '');
		$('#date_of_birth').val(patient.date_of_birth || '');
		$('#address_line1').val(patient.address_line1 || '');
		$('#address_line2').val(patient.address_line2 || '');
		$('#city').val(patient.city || '');
		$('#state_province').val(patient.state_province || '');
		$('#postal_code').val(patient.postal_code || '');
		$('#phone_e164').val(patient.phone_e164 || '');
		$('#emergency_contact_name').val(patient.emergency_contact_name || '');
		$('#emergency_contact_phone').val(patient.emergency_contact_phone || '');
		$('#blood_group').val(patient.blood_group || '');
		$('#allergies').val(patient.allergies || '');
		
		// Personal tab fields from patients table
		$('#medicine_sources').val(patient.medicine_sources || 'NONE');
		$('#occupation').val(patient.occupation || '');
		$('#education').val(patient.education || '');
		$('#diet').val(patient.diet || '');
		
		// Fix 2: Update header with patient name and DOB
		var fullName = (patient.first_name || '') + ' ' + (patient.last_name || '');
		$('#patientHeaderName').text(fullName.trim());
		if (patient.date_of_birth) {
			var dob = new Date(patient.date_of_birth);
			var formattedDOB = '(DOB: ' + dob.toLocaleDateString('en-IN', { 
				year: 'numeric', 
				month: 'short', 
				day: 'numeric' 
			}) + ')';
			$('#patientHeaderDOB').text(formattedDOB);
		}
	}
	
	// Populate case sheet form fields
	function populateCaseSheetForm(caseSheet) {
		// Personal tab
		$('#visit_type').val(caseSheet.visit_type || '');
		$('#symptoms_complaints').val(caseSheet.symptoms_complaints || '');
		$('#duration_of_symptoms').val(caseSheet.duration_of_symptoms || '');
		$('#number_of_children').val(caseSheet.number_of_children || 0);
		$('#type_of_delivery').val(caseSheet.type_of_delivery || '');
		$('#delivery_location').val(caseSheet.delivery_location || '');
		$('#delivery_source').val(caseSheet.delivery_source || '');
		$('#has_uterus').val(caseSheet.has_uterus || 1);
		
		// Menstrual details
		$('#menstrual_age_of_onset').val(caseSheet.menstrual_age_of_onset || '');
		$('#menstrual_cycle_frequency').val(caseSheet.menstrual_cycle_frequency || '');
		$('#menstrual_duration_of_flow').val(caseSheet.menstrual_duration_of_flow || '');
		$('#menstrual_lmp').val(caseSheet.menstrual_lmp || '');
		$('#menstrual_mh').val(caseSheet.menstrual_mh || '');
		
		// History tab - Conditions
		$('#condition_dm').val(caseSheet.condition_dm || 'NO');
		$('#condition_htn').val(caseSheet.condition_htn || 'NO');
		$('#condition_tsh').val(caseSheet.condition_tsh || 'NO');
		$('#condition_heart_disease').val(caseSheet.condition_heart_disease || 'NO');
		$('#condition_others').val(caseSheet.condition_others || '');
		$('#surgical_history').val(caseSheet.surgical_history || '');
		
		// History tab - Family History (checkboxes)
		$('#family_history_cancer').prop('checked', caseSheet.family_history_cancer == 1);
		$('#family_history_tuberculosis').prop('checked', caseSheet.family_history_tuberculosis == 1);
		$('#family_history_diabetes').prop('checked', caseSheet.family_history_diabetes == 1);
		$('#family_history_bp').prop('checked', caseSheet.family_history_bp == 1);
		$('#family_history_thyroid').prop('checked', caseSheet.family_history_thyroid == 1);
		$('#family_history_other').val(caseSheet.family_history_other || '');
		
		// General tab
		$('#general_pulse').val(caseSheet.general_pulse || '');
		$('#general_bp_systolic').val(caseSheet.general_bp_systolic || '');
		$('#general_bp_diastolic').val(caseSheet.general_bp_diastolic || '');
		$('#general_heart').val(caseSheet.general_heart || '');
		$('#general_lungs').val(caseSheet.general_lungs || '');
		$('#general_liver').val(caseSheet.general_liver || '');
		$('#general_spleen').val(caseSheet.general_spleen || '');
		$('#general_lymph_glands').val(caseSheet.general_lymph_glands || '');
		$('#general_height').val(caseSheet.general_height || '');
		$('#general_weight').val(caseSheet.general_weight || '');
		$('#general_bmi').val(caseSheet.general_bmi || '');
		$('#general_obesity_overweight').val(caseSheet.general_obesity_overweight || 0);
		
		// Examinations tab
		$('#exam_mouth').val(caseSheet.exam_mouth || '');
		$('#exam_lips').val(caseSheet.exam_lips || '');
		$('#exam_buccal_mucosa').val(caseSheet.exam_buccal_mucosa || '');
		$('#exam_teeth').val(caseSheet.exam_teeth || '');
		$('#exam_tongue').val(caseSheet.exam_tongue || '');
		$('#exam_oropharynx').val(caseSheet.exam_oropharynx || '');
		$('#exam_hypo').val(caseSheet.exam_hypo || '');
		$('#exam_naso_pharynx').val(caseSheet.exam_naso_pharynx || '');
		$('#exam_larynx').val(caseSheet.exam_larynx || '');
		$('#exam_nose').val(caseSheet.exam_nose || '');
		$('#exam_ears').val(caseSheet.exam_ears || '');
		$('#exam_neck').val(caseSheet.exam_neck || '');
		$('#exam_bones_joints').val(caseSheet.exam_bones_joints || '');
		$('#exam_abdomen_genital').val(caseSheet.exam_abdomen_genital || '');
		$('#exam_breast_left').val(caseSheet.exam_breast_left || '');
		$('#exam_breast_right').val(caseSheet.exam_breast_right || '');
		$('#exam_breast_axillary_nodes').val(caseSheet.exam_breast_axillary_nodes || '');
		$('#exam_pelvic_cervix').val(caseSheet.exam_pelvic_cervix || '');
		$('#exam_pelvic_uterus').val(caseSheet.exam_pelvic_uterus || '');
		$('#exam_pelvic_ovaries').val(caseSheet.exam_pelvic_ovaries || '');
		$('#exam_pelvic_adnexa').val(caseSheet.exam_pelvic_adnexa || '');
		$('#exam_rectal_skin').val(caseSheet.exam_rectal_skin || '');
		$('#exam_rectal_remarks').val(caseSheet.exam_rectal_remarks || '');
		$('#exam_gynae_ps').val(caseSheet.exam_gynae_ps || '');
		$('#exam_gynae_pv').val(caseSheet.exam_gynae_pv || '');
		$('#exam_gynae_via').val(caseSheet.exam_gynae_via || '');
		$('#exam_gynae_vili').val(caseSheet.exam_gynae_vili || '');
		
		// Diagrams - show preview if they exist
		if (caseSheet.exam_breast_diagram) {
			$('#exam_breast_diagram').val(caseSheet.exam_breast_diagram);
			$('#breastDiagramPreview img').attr('src', 'data:image/png;base64,' + caseSheet.exam_breast_diagram);
			$('#breastDiagramPreview').show();
		}
		if (caseSheet.exam_pelvic_diagram) {
			$('#exam_pelvic_diagram').val(caseSheet.exam_pelvic_diagram);
			$('#pelvicDiagramPreview img').attr('src', 'data:image/png;base64,' + caseSheet.exam_pelvic_diagram);
			$('#pelvicDiagramPreview').show();
		}
		if (caseSheet.exam_gynae_via_diagram) {
			$('#exam_gynae_via_diagram').val(caseSheet.exam_gynae_via_diagram);
			$('#viaDiagramPreview img').attr('src', 'data:image/png;base64,' + caseSheet.exam_gynae_via_diagram);
			$('#viaDiagramPreview').show();
		}
		if (caseSheet.exam_gynae_vili_diagram) {
			$('#exam_gynae_vili_diagram').val(caseSheet.exam_gynae_vili_diagram);
			$('#viliDiagramPreview img').attr('src', 'data:image/png;base64,' + caseSheet.exam_gynae_vili_diagram);
			$('#viliDiagramPreview').show();
		}
		
		// Labs tab - Investigations
		$('#lab_hb_percentage').val(caseSheet.lab_hb_percentage || '');
		$('#lab_hb_gms').val(caseSheet.lab_hb_gms || '');
		$('#lab_fbs').val(caseSheet.lab_fbs || '');
		$('#lab_tsh').val(caseSheet.lab_tsh || '');
		$('#lab_sr_creatinine').val(caseSheet.lab_sr_creatinine || '');
		$('#lab_others').val(caseSheet.lab_others || '');
		
		// Labs tab - Cytology Report
		$('#cytology_papsmear').val(caseSheet.cytology_papsmear || 'NONE');
		$('#cytology_papsmear_notes').val(caseSheet.cytology_papsmear_notes || '');
		$('#cytology_colposcopy').val(caseSheet.cytology_colposcopy || 'NONE');
		$('#cytology_colposcopy_notes').val(caseSheet.cytology_colposcopy_notes || '');
		$('#cytology_biopsy').val(caseSheet.cytology_biopsy || 'NONE');
		$('#cytology_biopsy_notes').val(caseSheet.cytology_biopsy_notes || '');
		
		// Summary tab
		$('#summary_risk_level').val(caseSheet.summary_risk_level || '');
		$('#summary_referral').val(caseSheet.summary_referral || '');
		$('#summary_patient_acceptance').val(caseSheet.summary_patient_acceptance || '');
		$('#summary_doctor_summary').val(caseSheet.summary_doctor_summary || '');
	}
	
	// Edit Patient Button - Toggle edit mode
	$('#editPatientBtn').on('click', function() {
		isEditMode = !isEditMode;
		
		if (isEditMode) {
			// Enable edit mode
			$('#patientVerificationForm input[type="text"], #patientVerificationForm input[type="tel"], #patientVerificationForm input[type="number"], #patientVerificationForm input[type="date"]').prop('readonly', false);
			$('#patientVerificationForm select').prop('disabled', false);
			$('#editModeAlert').slideDown();
			$(this).html('<i class="fas fa-lock mr-1"></i>Lock Editing');
			$(this).removeClass('btn-outline-primary').addClass('btn-warning');
		} else {
			// Disable edit mode
			$('#patientVerificationForm input[type="text"], #patientVerificationForm input[type="tel"], #patientVerificationForm input[type="number"], #patientVerificationForm input[type="date"]').prop('readonly', true);
			$('#patientVerificationForm select').prop('disabled', true);
			$('#editModeAlert').slideUp();
			$(this).html('<i class="fas fa-edit mr-1"></i>Edit Information');
			$(this).removeClass('btn-warning').addClass('btn-outline-primary');
		}
	});
	
	// Fix 4: Auto-lock patient editing when switching away from verification tab
	$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
		var targetTab = $(e.target).attr('href'); // newly activated tab
		
		// If switching away from verification tab and edit mode is active, lock it
		if (targetTab !== '#verification' && isEditMode) {
			isEditMode = false;
			$('#patientVerificationForm input[type="text"], #patientVerificationForm input[type="tel"], #patientVerificationForm input[type="number"], #patientVerificationForm input[type="date"]').prop('readonly', true);
			$('#patientVerificationForm select').prop('disabled', true);
			$('#editModeAlert').slideUp();
			$('#editPatientBtn').html('<i class="fas fa-edit mr-1"></i>Edit Information');
			$('#editPatientBtn').removeClass('btn-warning').addClass('btn-outline-primary');
		}
	});
	
	// Auto-save for patient verification fields
	$('#patientVerificationForm').on('change', 'input, select', function() {
		if (!isEditMode) return; // Only save if in edit mode
		
		var fieldName = $(this).attr('name');
		var fieldValue = $(this).val();
		
		if (fieldName && fieldName !== 'patient_id') {
			savePatientField(patientId, fieldName, fieldValue);
		}
	});
	
	// Auto-save for all case sheet form fields
	$('.case-sheet-form').on('change', 'input, select, textarea', function() {
		var fieldName = $(this).attr('name');
		var fieldValue;
		var tableName = $(this).data('table');
		
		// Handle checkboxes differently
		if ($(this).attr('type') === 'checkbox') {
			fieldValue = $(this).is(':checked') ? 1 : 0;
		} else {
			fieldValue = $(this).val();
		}
		
		if (fieldName && tableName) {
			if (tableName === 'patients') {
				savePatientField(patientId, fieldName, fieldValue);
			} else if (tableName === 'case_sheets') {
				saveCaseSheetField(caseSheetId, fieldName, fieldValue);
			}
		}
	});
	
	// Load previous case sheet data for "Last General Exam" section
	function loadPreviousCaseSheetData() {
		if (!caseSheetId || !patientId) {
			return;
		}
		
		$.ajax({
			url: 'get_previous_case_sheet.php',
			type: 'GET',
			data: { 
				patient_id: patientId,
				current_case_sheet_id: caseSheetId
			},
			dataType: 'json',
			success: function(response) {
				if (response.success && response.previous_case_sheet) {
					displayPreviousCaseSheet(response.previous_case_sheet);
					$('#lastGeneralExamSection').slideDown();
					
					// Show last menstrual details if there's data and has_uterus = 1
					var prevCaseSheet = response.previous_case_sheet;
					var hasMenstrualData = prevCaseSheet.menstrual_cycle_frequency || 
					                       prevCaseSheet.menstrual_duration_of_flow || 
					                       prevCaseSheet.menstrual_lmp || 
					                       prevCaseSheet.menstrual_mh;
					
					console.log('Previous menstrual data check:', {
						hasMenstrualData: hasMenstrualData,
						cycle: prevCaseSheet.menstrual_cycle_frequency,
						duration: prevCaseSheet.menstrual_duration_of_flow,
						lmp: prevCaseSheet.menstrual_lmp,
						mh: prevCaseSheet.menstrual_mh
					});
					
					if (hasMenstrualData) {
						console.log('Showing lastMenstrualDetailsSection');
						$('#lastMenstrualDetailsSection').slideDown();
					} else {
						console.log('No menstrual data to show');
					}
				} else {
					// No previous case sheet exists
					$('#lastGeneralExamSection').hide();
					$('#lastMenstrualDetailsSection').hide();
					console.log('No previous case sheet found');
				}
			},
			error: function(xhr, status, error) {
				console.error('Error loading previous case sheet:', error);
			}
		});
	}
	
	// Display previous case sheet data
	function displayPreviousCaseSheet(prevCaseSheet) {
		// Pulse
		if (prevCaseSheet.general_pulse) {
			$('#prev_pulse').text(prevCaseSheet.general_pulse + ' /mt');
		}
		
		// Blood Pressure
		if (prevCaseSheet.general_bp_systolic && prevCaseSheet.general_bp_diastolic) {
			$('#prev_bp').text(prevCaseSheet.general_bp_systolic + '/' + prevCaseSheet.general_bp_diastolic + ' mm of Hg');
		}
		
		// Height
		if (prevCaseSheet.general_height) {
			$('#prev_height').text(prevCaseSheet.general_height + ' cm');
		}
		
		// Weight
		if (prevCaseSheet.general_weight) {
			$('#prev_weight').text(prevCaseSheet.general_weight + ' kgs');
		}
		
		// BMI
		if (prevCaseSheet.general_bmi) {
			$('#prev_bmi').text(prevCaseSheet.general_bmi);
		}
		
		// Obesity/Overweight
		if (prevCaseSheet.general_obesity_overweight == 1) {
			$('#prev_obesity').text('Yes');
		} else {
			$('#prev_obesity').text('No');
		}
		
		// Summary of last visit
		if (prevCaseSheet.summary_doctor_summary) {
			$('#prev_summary').text(prevCaseSheet.summary_doctor_summary);
		}
		
		// Previous Menstrual Details (show in Personal tab if has_uterus = 1)
		var hasMenstrualData = false;
		
		console.log('Populating previous menstrual details:', {
			cycle: prevCaseSheet.menstrual_cycle_frequency,
			duration: prevCaseSheet.menstrual_duration_of_flow,
			lmp: prevCaseSheet.menstrual_lmp,
			mh: prevCaseSheet.menstrual_mh
		});
		
		if (prevCaseSheet.menstrual_cycle_frequency) {
			$('#prev_menstrual_cycle_frequency').text(prevCaseSheet.menstrual_cycle_frequency + ' days');
			hasMenstrualData = true;
		}
		
		if (prevCaseSheet.menstrual_duration_of_flow) {
			$('#prev_menstrual_duration_of_flow').text(prevCaseSheet.menstrual_duration_of_flow + ' days');
			hasMenstrualData = true;
		}
		
		if (prevCaseSheet.menstrual_lmp) {
			$('#prev_menstrual_lmp').text(prevCaseSheet.menstrual_lmp);
			hasMenstrualData = true;
		}
		
		if (prevCaseSheet.menstrual_mh) {
			$('#prev_menstrual_mh').text(prevCaseSheet.menstrual_mh);
			hasMenstrualData = true;
		}
		
		console.log('Menstrual data populated, hasMenstrualData:', hasMenstrualData);
		
		// Note: Display of lastMenstrualDetailsSection is handled in loadPreviousCaseSheetData
	}
	
	// Load previous case sheet data when page loads
	if (caseSheetId) {
		loadPreviousCaseSheetData();
	}
	
	// Save individual patient field
	function savePatientField(patientId, field, value) {
		showAutoSaveIndicator('saving', 'Saving...');
		
		$.ajax({
			url: 'update_patient.php',
			type: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				patient_id: patientId,
				field: field,
				value: value
			}),
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showAutoSaveIndicator('saved', 'Saved');
				} else {
					showAutoSaveIndicator('error', 'Save failed: ' + response.message);
				}
			},
			error: function(xhr, status, error) {
				showAutoSaveIndicator('error', 'Error saving data');
				console.error('Save error:', error);
			}
		});
	}
	
	// Save individual case sheet field
	function saveCaseSheetField(caseSheetId, field, value) {
		// Don't try to save if we don't have a case sheet ID yet
		if (!caseSheetId) {
			console.log('Case sheet not created yet - skipping auto-save for:', field);
			return;
		}
		
		// Don't save if case sheet is CLOSED
		if (caseSheetStatus === 'CLOSED') {
			console.log('Case sheet is CLOSED - cannot save:', field);
			return;
		}
		
		showAutoSaveIndicator('saving', 'Saving...');
		
		$.ajax({
			url: 'update_case_sheet.php',
			type: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				case_sheet_id: caseSheetId,
				field: field,
				value: value
			}),
			dataType: 'json',
			success: function(response) {
				if (response.success) {
					showAutoSaveIndicator('saved', 'Saved');
				} else {
					showAutoSaveIndicator('error', 'Save failed: ' + response.message);
				}
			},
			error: function(xhr, status, error) {
				showAutoSaveIndicator('error', 'Error saving data');
				console.error('Save error:', error);
			}
		});
	}
	
	// Tab navigation - Next button
	$('[data-next-tab]').on('click', function() {
		var nextTab = $(this).data('next-tab');
		$('#' + nextTab + '-tab').tab('show');
		// Scroll to top of content
		$('.content-wrapper').animate({ scrollTop: 0 }, 300);
	});
	
	// Tab navigation - Previous button
	$('[data-prev-tab]').on('click', function() {
		var prevTab = $(this).data('prev-tab');
		$('#' + prevTab + '-tab').tab('show');
		// Scroll to top of content
		$('.content-wrapper').animate({ scrollTop: 0 }, 300);
	});
	
	// Save and Exit button
	$('#saveExitBtn').on('click', function() {
		// Check if case sheet is CLOSED
		if (caseSheetStatus === 'CLOSED') {
			alert('This case sheet is closed and cannot be saved. Please contact an admin to reopen it.');
			return;
		}
		
		// Show saving indicator
		showAutoSaveIndicator('saving', 'Saving...');
		
		// TODO: Implement actual save functionality for case sheet
		// For now, simulate save with timeout
		setTimeout(function() {
			showAutoSaveIndicator('saved', 'Saved successfully');
			setTimeout(function() {
				window.location.href = 'dashboard.php';
			}, 500);
		}, 1000);
	});
	
	// Final Submit button
	$('#finalSubmit').on('click', function() {
		// Show saving indicator
		showAutoSaveIndicator('saving', 'Submitting...');
		
		// TODO: Implement final submission
		setTimeout(function() {
			showAutoSaveIndicator('saved', 'Case sheet completed');
			setTimeout(function() {
				window.location.href = 'dashboard.php';
			}, 1000);
		}, 1000);
	});
	
	// Auto-save indicator function
	function showAutoSaveIndicator(type, message) {
		var $indicator = $('#autoSaveIndicator');
		$indicator.removeClass('saving error');
		
		if (type === 'saving') {
			$indicator.addClass('saving');
			$indicator.html('<i class="fas fa-spinner fa-spin"></i> ' + message);
		} else if (type === 'error') {
			$indicator.addClass('error');
			$indicator.html('<i class="fas fa-exclamation-circle"></i> ' + message);
		} else {
			$indicator.html('<i class="fas fa-check-circle"></i> ' + message);
		}
		
		$indicator.fadeIn(300);
		
		if (type !== 'saving') {
			setTimeout(function() {
				$indicator.fadeOut(300);
			}, 2000);
		}
	}
	
	// Diagram Editor Variables
	var currentDiagramType = null; // 'breast', 'pelvic', 'via', 'vili'
	var currentDiagramField = null; // ID of the hidden input field
	var currentDiagramPreview = null; // ID of the preview div
	var canvas = null;
	var ctx = null;
	var isDrawing = false;
	var currentTool = 'pen';
	var currentColor = '#000000';
	var currentThickness = 4;
	var drawHistory = [];
	var historyStep = -1;
	var templateImage = null;
	
	// Initialize canvas when modal is shown
	$('#diagramEditorModal').on('shown.bs.modal', function() {
		canvas = document.getElementById('diagramCanvas');
		ctx = canvas.getContext('2d');
		
		// Set canvas size (adjust as needed)
		canvas.width = 800;
		canvas.height = 600;
		
		// Load template if exists (placeholder for now - templates will be added later)
		loadDiagramTemplate();
		
		// Initialize history
		saveToHistory();
	});
	
	// Load diagram template based on type
	function loadDiagramTemplate() {
		// Clear canvas first
		ctx.fillStyle = 'white';
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		
		// Define template image paths
		var templatePaths = {
			'breast': 'assets/images/diagrams/BreastExaminationDiagram.png',
			'pelvic': 'assets/images/diagrams/PelvicExaminationDiagram.png',
			'via': 'assets/images/diagrams/VIAVILIDiagram.png',
			'vili': 'assets/images/diagrams/VIAVILIDiagram.png'
		};
		
		// Load template image
		var templateImg = new Image();
		templateImg.onload = function() {
			// Calculate aspect ratio to maintain proportions
			var imgWidth = templateImg.width;
			var imgHeight = templateImg.height;
			var canvasWidth = canvas.width;
			var canvasHeight = canvas.height;
			
			// Calculate scale to fit image within canvas while maintaining aspect ratio
			var scale = Math.min(canvasWidth / imgWidth, canvasHeight / imgHeight);
			
			// Calculate new dimensions
			var newWidth = imgWidth * scale;
			var newHeight = imgHeight * scale;
			
			// Calculate position to center the image
			var x = (canvasWidth - newWidth) / 2;
			var y = (canvasHeight - newHeight) / 2;
			
			// Draw template with maintained aspect ratio, centered
			ctx.drawImage(templateImg, x, y, newWidth, newHeight);
			
			// If there's existing diagram data, load it on top of template
			var existingData = $('#' + currentDiagramField).val();
			if (existingData) {
				var img = new Image();
				img.onload = function() {
					ctx.drawImage(img, 0, 0);
					saveToHistory();
				};
				img.src = 'data:image/png;base64,' + existingData;
			} else {
				saveToHistory();
			}
		};
		
		templateImg.onerror = function() {
			// If template fails to load, show error message
			ctx.fillStyle = '#f8d7da';
			ctx.fillRect(0, 0, canvas.width, canvas.height);
			ctx.fillStyle = '#721c24';
			ctx.font = '20px Arial';
			ctx.textAlign = 'center';
			ctx.fillText('Template image failed to load', canvas.width / 2, canvas.height / 2);
			ctx.fillText('(' + currentDiagramType.toUpperCase() + ' Diagram)', canvas.width / 2, canvas.height / 2 + 30);
			saveToHistory();
		};
		
		templateImg.src = templatePaths[currentDiagramType];
	}
	
	// Open diagram editor
	function openDiagramEditor(type, fieldId, previewId) {
		currentDiagramType = type;
		currentDiagramField = fieldId;
		currentDiagramPreview = previewId;
		
		// Update modal title
		var titles = {
			'breast': 'Breast Diagram',
			'pelvic': 'Pelvic Diagram',
			'via': 'VIA Diagram',
			'vili': 'VILI Diagram'
		};
		$('#diagramEditorTitle').text(titles[type] + ' Editor');
		
		// Show modal
		$('#diagramEditorModal').modal('show');
	}
	
	// Diagram button handlers
	$('#openBreastDiagramBtn').on('click', function() {
		openDiagramEditor('breast', 'exam_breast_diagram', 'breastDiagramPreview');
	});
	
	$('#openPelvicDiagramBtn').on('click', function() {
		openDiagramEditor('pelvic', 'exam_pelvic_diagram', 'pelvicDiagramPreview');
	});
	
	$('#openViaDiagramBtn').on('click', function() {
		openDiagramEditor('via', 'exam_gynae_via_diagram', 'viaDiagramPreview');
	});
	
	$('#openViliDiagramBtn').on('click', function() {
		openDiagramEditor('vili', 'exam_gynae_vili_diagram', 'viliDiagramPreview');
	});
	
	// Tool selection
	$('.diagram-toolbar button[data-tool]').on('click', function() {
		$('.diagram-toolbar button[data-tool]').removeClass('active');
		$(this).addClass('active');
		
		currentTool = $(this).data('tool');
		if ($(this).data('color')) {
			currentColor = $(this).data('color');
		}
		
		// Update cursor
		if (currentTool === 'eraser') {
			canvas.style.cursor = 'grab';
		} else {
			canvas.style.cursor = 'crosshair';
		}
	});
	
	// Line thickness change
	$('#lineThickness').on('change', function() {
		currentThickness = parseInt($(this).val());
	});
	
	// Canvas drawing events
	$('#diagramCanvas').on('mousedown', function(e) {
		isDrawing = true;
		var rect = canvas.getBoundingClientRect();
		var x = e.clientX - rect.left;
		var y = e.clientY - rect.top;
		
		ctx.beginPath();
		ctx.moveTo(x, y);
	});
	
	$('#diagramCanvas').on('mousemove', function(e) {
		if (!isDrawing) return;
		
		var rect = canvas.getBoundingClientRect();
		var x = e.clientX - rect.left;
		var y = e.clientY - rect.top;
		
		if (currentTool === 'pen') {
			ctx.strokeStyle = currentColor;
			ctx.lineWidth = currentThickness;
			ctx.lineCap = 'round';
			ctx.lineJoin = 'round';
			ctx.lineTo(x, y);
			ctx.stroke();
		} else if (currentTool === 'eraser') {
			ctx.clearRect(x - currentThickness/2, y - currentThickness/2, currentThickness, currentThickness);
		}
	});
	
	$('#diagramCanvas').on('mouseup', function() {
		if (isDrawing) {
			isDrawing = false;
			saveToHistory();
		}
	});
	
	$('#diagramCanvas').on('mouseleave', function() {
		if (isDrawing) {
			isDrawing = false;
			saveToHistory();
		}
	});
	
	// Save to history for undo/redo
	function saveToHistory() {
		historyStep++;
		if (historyStep < drawHistory.length) {
			drawHistory.length = historyStep;
		}
		drawHistory.push(canvas.toDataURL());
		
		// Limit history to 20 steps
		if (drawHistory.length > 20) {
			drawHistory.shift();
			historyStep--;
		}
	}
	
	// Undo
	$('#undoBtn').on('click', function() {
		if (historyStep > 0) {
			historyStep--;
			var img = new Image();
			img.onload = function() {
				ctx.clearRect(0, 0, canvas.width, canvas.height);
				ctx.drawImage(img, 0, 0);
			};
			img.src = drawHistory[historyStep];
		}
	});
	
	// Redo
	$('#redoBtn').on('click', function() {
		if (historyStep < drawHistory.length - 1) {
			historyStep++;
			var img = new Image();
			img.onload = function() {
				ctx.clearRect(0, 0, canvas.width, canvas.height);
				ctx.drawImage(img, 0, 0);
			};
			img.src = drawHistory[historyStep];
		}
	});
	
	// Clear canvas
	$('#clearCanvasBtn').on('click', function() {
		if (confirm('Are you sure you want to clear the entire diagram?')) {
			loadDiagramTemplate();
			drawHistory = [];
			historyStep = -1;
			saveToHistory();
		}
	});
	
	// Save diagram
	$('#saveDiagramBtn').on('click', function() {
		// Get Base64 data (remove "data:image/png;base64," prefix)
		var base64Data = canvas.toDataURL('image/png').split(',')[1];
		
		// Save to hidden input
		$('#' + currentDiagramField).val(base64Data);
		
		// Update preview
		$('#' + currentDiagramPreview + ' img').attr('src', 'data:image/png;base64,' + base64Data);
		$('#' + currentDiagramPreview).show();
		
		// Trigger auto-save
		saveCaseSheetField(caseSheetId, currentDiagramField, base64Data);
		
		// Close modal
		$('#diagramEditorModal').modal('hide');
		
		// Reset canvas state
		drawHistory = [];
		historyStep = -1;
	});
});
</script>
<!-- Diagram Editor Modal -->
<div class="modal fade" id="diagramEditorModal" tabindex="-1" role="dialog" aria-labelledby="diagramEditorTitle" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="diagramEditorTitle">Diagram Editor</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<!-- Toolbar -->
				<div class="diagram-toolbar mb-3 p-3 bg-light border rounded">
					<div class="row align-items-center">
						<div class="col-md-4">
							<label class="mb-1 small">Drawing Tool:</label>
							<div class="btn-group btn-group-sm d-block" role="group">
								<button type="button" class="btn btn-outline-dark active" id="penBlackBtn" data-tool="pen" data-color="#000000">
									<i class="fas fa-pen"></i> Black Pen
								</button>
								<button type="button" class="btn btn-outline-danger" id="penRedBtn" data-tool="pen" data-color="#FF0000">
									<i class="fas fa-pen"></i> Red Pen
								</button>
								<button type="button" class="btn btn-outline-secondary" id="eraserBtn" data-tool="eraser">
									<i class="fas fa-eraser"></i> Eraser
								</button>
							</div>
						</div>
						<div class="col-md-3">
							<label for="lineThickness" class="mb-1 small">Line Thickness:</label>
							<select class="form-control form-control-sm" id="lineThickness">
								<option value="2">Fine (2px)</option>
								<option value="4" selected>Normal (4px)</option>
								<option value="6">Medium (6px)</option>
								<option value="8">Thick (8px)</option>
								<option value="12">Very Thick (12px)</option>
							</select>
						</div>
						<div class="col-md-5 text-right">
							<button type="button" class="btn btn-sm btn-warning" id="undoBtn">
								<i class="fas fa-undo"></i> Undo
							</button>
							<button type="button" class="btn btn-sm btn-info" id="redoBtn">
								<i class="fas fa-redo"></i> Redo
							</button>
							<button type="button" class="btn btn-sm btn-danger" id="clearCanvasBtn">
								<i class="fas fa-trash"></i> Clear
							</button>
						</div>
					</div>
				</div>
				
				<!-- Canvas Container -->
				<div class="diagram-canvas-container text-center" style="background: #f8f9fa; padding: 20px; border: 2px solid #dee2e6; border-radius: 5px;">
					<canvas id="diagramCanvas" style="border: 1px solid #999; background: white; cursor: crosshair;"></canvas>
				</div>
				
				<div class="mt-3 text-muted small">
					<i class="fas fa-info-circle"></i> <strong>Instructions:</strong> Use the tools above to draw on the diagram. Click "Save" when finished to add the diagram to the case sheet.
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-success" id="saveDiagramBtn">
					<i class="fas fa-save"></i> Save Diagram
				</button>
			</div>
		</div>
	</div>
</div>

</body>
</html>