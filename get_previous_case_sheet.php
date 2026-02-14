<?php
/**
 * get_previous_case_sheet.php
 * API endpoint to fetch the most recent previous case sheet for a patient
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

$patient_id = $_GET['patient_id'] ?? null;
$current_case_sheet_id = $_GET['current_case_sheet_id'] ?? null;

if (!$patient_id || !$current_case_sheet_id) {
    echo json_encode(['success' => false, 'message' => 'Patient ID and current case sheet ID required']);
    exit;
}

try {
    // Get the most recent previous case sheet for this patient based on case_sheet_id sequence
    // Since case_sheet_id = patient_id + sequence (e.g., 131, 132, 133),
    // the previous case sheet will have a lower case_sheet_id
    $sql = "SELECT 
                case_sheet_id,
                visit_datetime,
                general_pulse,
                general_bp_systolic,
                general_bp_diastolic,
                general_height,
                general_weight,
                general_bmi,
                general_obesity_overweight,
                summary_doctor_summary,
                menstrual_cycle_frequency,
                menstrual_duration_of_flow,
                menstrual_lmp,
                menstrual_mh
            FROM case_sheets 
            WHERE patient_id = :patient_id 
            AND case_sheet_id < :current_case_sheet_id
            ORDER BY case_sheet_id DESC
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':patient_id' => $patient_id,
        ':current_case_sheet_id' => $current_case_sheet_id
    ]);
    
    $previousCaseSheet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($previousCaseSheet) {
        echo json_encode([
            'success' => true,
            'previous_case_sheet' => $previousCaseSheet
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No previous case sheet found'
        ]);
    }
    
} catch (PDOException $e) {
    error_log('Database error in get_previous_case_sheet.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
