<?php
/**
 * update_patient.php
 * API endpoint to update patient information from case sheet verification tab
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

$patient_id = $input['patient_id'] ?? null;
$field = $input['field'] ?? null;
$value = $input['value'] ?? null;

if (!$patient_id || !$field) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Whitelist of allowed fields to update
$allowed_fields = [
    'first_name',
    'last_name',
    'aadhaar_number',
    'age_years',
    'sex',
    'date_of_birth',
    'address_line1',
    'address_line2',
    'city',
    'state_province',
    'postal_code',
    'phone_e164',
    'emergency_contact_name',
    'emergency_contact_phone',
    'blood_group',
    'allergies',
    'medicine_sources',
    'occupation',
    'education',
    'diet'
];

if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

try {
    // Prepare update query
    $sql = "UPDATE patients SET $field = :value, updated_at = NOW() WHERE patient_id = :patient_id";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':value' => $value,
        ':patient_id' => $patient_id
    ]);
    
    // Log the change (optional but recommended)
    // TODO: Add audit log entry here
    
    echo json_encode([
        'success' => true,
        'message' => 'Patient information updated successfully',
        'field' => $field,
        'value' => $value
    ]);
    
} catch (PDOException $e) {
    error_log('Database error in update_patient.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
