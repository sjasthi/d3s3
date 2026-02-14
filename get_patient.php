<?php
/**
 * get_patient.php
 * API endpoint to fetch patient information for case sheet
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
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

if (!$patient_id) {
    echo json_encode(['success' => false, 'message' => 'Patient ID required']);
    exit;
}

try {
    $sql = "SELECT 
                patient_id,
                first_name,
                last_name,
                aadhaar_number,
                age_years,
                sex,
                date_of_birth,
                address_line1,
                address_line2,
                city,
                state_province,
                postal_code,
                phone_e164,
                emergency_contact_name,
                emergency_contact_phone,
                blood_group,
                allergies,
                medicine_sources,
                occupation,
                education,
                diet
            FROM patients 
            WHERE patient_id = :patient_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':patient_id' => $patient_id]);
    
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($patient) {
        echo json_encode([
            'success' => true,
            'patient' => $patient
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Patient not found'
        ]);
    }
    
} catch (PDOException $e) {
    error_log('Database error in get_patient.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
