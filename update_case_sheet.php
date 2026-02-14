<?php
/**
 * update_case_sheet.php
 * API endpoint to update case sheet information
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

// TODO: Add authentication check here
// if (!isset($_SESSION['employee_id'])) {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit;
// }

// Database connection
require_once __DIR__ . '/app/config/database.php';
$pdo = getDBConnection();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$case_sheet_id = $input['case_sheet_id'] ?? null;
$field = $input['field'] ?? null;
$value = $input['value'] ?? null;

if (!$case_sheet_id || !$field) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Whitelist of allowed fields to update
$allowed_fields = [
    // Personal tab
    'visit_type',
    'symptoms_complaints',
    'duration_of_symptoms',
    'number_of_children',
    'type_of_delivery',
    'delivery_location',
    'delivery_source',
    'has_uterus',
    'menstrual_age_of_onset',
    'menstrual_cycle_frequency',
    'menstrual_duration_of_flow',
    'menstrual_lmp',
    'menstrual_mh',
    
    // History tab
    'condition_dm',
    'condition_htn',
    'condition_tsh',
    'condition_heart_disease',
    'condition_others',
    'surgical_history',
    'family_history_cancer',
    'family_history_tuberculosis',
    'family_history_diabetes',
    'family_history_bp',
    'family_history_thyroid',
    'family_history_other',
    
    // General tab
    'general_pulse',
    'general_bp_systolic',
    'general_bp_diastolic',
    'general_heart',
    'general_lungs',
    'general_liver',
    'general_spleen',
    'general_lymph_glands',
    'general_height',
    'general_weight',
    'general_bmi',
    'general_obesity_overweight',
    
    // Examinations tab
    'exam_mouth',
    'exam_lips',
    'exam_buccal_mucosa',
    'exam_teeth',
    'exam_tongue',
    'exam_oropharynx',
    'exam_hypo',
    'exam_naso_pharynx',
    'exam_larynx',
    'exam_nose',
    'exam_ears',
    'exam_neck',
    'exam_bones_joints',
    'exam_abdomen_genital',
    'exam_breast_left',
    'exam_breast_right',
    'exam_breast_axillary_nodes',
    'exam_breast_diagram',
    'exam_pelvic_cervix',
    'exam_pelvic_uterus',
    'exam_pelvic_ovaries',
    'exam_pelvic_adnexa',
    'exam_pelvic_diagram',
    'exam_rectal_skin',
    'exam_rectal_remarks',
    'exam_gynae_ps',
    'exam_gynae_pv',
    'exam_gynae_via',
    'exam_gynae_via_diagram',
    'exam_gynae_vili',
    'exam_gynae_vili_diagram',
    
    // Labs tab
    'lab_hb_percentage',
    'lab_hb_gms',
    'lab_fbs',
    'lab_tsh',
    'lab_sr_creatinine',
    'lab_others',
    'cytology_papsmear',
    'cytology_papsmear_notes',
    'cytology_colposcopy',
    'cytology_colposcopy_notes',
    'cytology_biopsy',
    'cytology_biopsy_notes',
    
    // Summary tab
    'summary_risk_level',
    'summary_referral',
    'summary_patient_acceptance',
    'summary_doctor_summary'
];

if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

try {
    // Prepare update query
    $sql = "UPDATE case_sheets SET $field = :value, updated_at = NOW() WHERE case_sheet_id = :case_sheet_id";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':value' => $value,
        ':case_sheet_id' => $case_sheet_id
    ]);
    
    // Log the change (optional but recommended)
    // TODO: Add audit log entry here
    
    echo json_encode([
        'success' => true,
        'message' => 'Case sheet updated successfully',
        'field' => $field,
        'value' => $value
    ]);
    
} catch (PDOException $e) {
    error_log('Database error in update_case_sheet.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
