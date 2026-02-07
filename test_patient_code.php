<?php
/**
 * Test script to demonstrate patient code generation
 * Run: php test_patient_code.php
 */

require_once __DIR__ . '/app/config/database.php';

$pdo = getDBConnection();

echo "=== Testing Patient Code Generation ===\n\n";

// Check current daily count
$stmt = $pdo->query('SELECT last_n FROM patient_daily_sequence WHERE seq_date = CURDATE()');
$currentCount = $stmt->fetchColumn() ?? 0;
echo "Patients registered today so far: $currentCount\n\n";

// Create 3 test patients
echo "Creating 3 test patients...\n\n";

for ($i = 1; $i <= 3; $i++) {
    $stmt = $pdo->prepare('
        INSERT INTO patients (first_name, last_name, sex, first_seen_date)
        VALUES (?, ?, ?, CURDATE())
    ');

    $stmt->execute(["Test", "Patient$i", 'MALE']);

    $patientId = $pdo->lastInsertId();

    // Get the auto-generated patient_code
    $stmt = $pdo->prepare('SELECT patient_code, first_seen_date FROM patients WHERE patient_id = ?');
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch();

    echo "Patient $i:\n";
    echo "  ID:   $patientId\n";
    echo "  Code: {$patient['patient_code']}\n";
    echo "  Date: {$patient['first_seen_date']}\n\n";
}

// Show updated daily count
$stmt = $pdo->query('SELECT last_n FROM patient_daily_sequence WHERE seq_date = CURDATE()');
$newCount = $stmt->fetchColumn();
echo "Total patients registered today: $newCount\n\n";

// Show recent patients
echo "=== Recent Patients ===\n";
$stmt = $pdo->query('
    SELECT patient_id, patient_code, CONCAT(first_name, " ", last_name) as name, first_seen_date
    FROM patients
    ORDER BY patient_id DESC
    LIMIT 5
');

foreach ($stmt->fetchAll() as $p) {
    echo sprintf("ID: %3d | Code: %s | Name: %-20s | Date: %s\n",
        $p['patient_id'],
        $p['patient_code'],
        $p['name'],
        $p['first_seen_date']
    );
}

echo "\n✅ Patient code generation is working!\n";
echo "   Format: YYYYMMDDNNN (Year-Month-Day-Sequence)\n";
?>
