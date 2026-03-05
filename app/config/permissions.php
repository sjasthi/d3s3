<?php
/**
 * Centralized access matrix – DB-backed.
 *
 * Loads the permission matrix from the `role_permissions` table.
 * Falls back to hardcoded defaults if the DB is unavailable or the table
 * is empty (e.g. before migration 015 has been run).
 *
 * R  = read-only access
 * RW = full read + write access
 * N  = no access
 *
 * Resources
 *   assets        – asset library (assets.php)
 *   case_sheets   – clinical intake / case sheets (intake.php, review.php, case-sheet.php)
 *   events        – calendar / events (calendar.php)
 *   patient_data  – patient records (get_patient, update_patient, patient search)
 *   users         – user management (users.php)
 *   feedback      – feedback / grievances (feedback.php)
 *   messages      – internal messaging (messages.php)
 *   tasks         – to-do / task list (tasks.php)
 *   appointments  – appointment scheduling (appointments.php)
 *   labwork       – lab order placement and result recording (lab_results.php)
 */

require_once __DIR__ . '/database.php';

/**
 * Load the permission matrix from the DB.
 * Returns the hardcoded baseline if the DB call fails or returns nothing.
 */
function _load_permissions(): array
{
	// Hardcoded baseline – used as fallback if DB is unavailable.
	$defaults = [
		'SUPER_ADMIN' => [
			'assets'        => 'RW',
			'case_sheets'   => 'RW',
			'events'        => 'RW',
			'patient_data'  => 'RW',
			'users'         => 'RW',
			'feedback'      => 'RW',
			'messages'      => 'RW',
			'tasks'         => 'RW',
			'appointments'  => 'RW',
			'labwork'       => 'RW',
		],
		'ADMIN' => [
			'assets'        => 'RW',
			'case_sheets'   => 'RW',
			'events'        => 'RW',
			'patient_data'  => 'RW',
			'users'         => 'RW',
			'feedback'      => 'RW',
			'messages'      => 'RW',
			'tasks'         => 'RW',
			'appointments'  => 'RW',
			'labwork'       => 'RW',
		],
		'DOCTOR' => [
			'assets'        => 'R',
			'case_sheets'   => 'RW',
			'events'        => 'R',
			'patient_data'  => 'RW',
			'users'         => 'N',
			'feedback'      => 'R',
			'messages'      => 'RW',
			'tasks'         => 'RW',
			'appointments'  => 'R',
			'labwork'       => 'RW',
		],
		'TRIAGE_NURSE' => [
			'assets'        => 'R',
			'case_sheets'   => 'RW',
			'events'        => 'R',
			'patient_data'  => 'RW',
			'users'         => 'N',
			'feedback'      => 'R',
			'messages'      => 'RW',
			'tasks'         => 'RW',
			'appointments'  => 'RW',
			'labwork'       => 'RW',
		],
		'NURSE' => [
			'assets'        => 'R',
			'case_sheets'   => 'RW',
			'events'        => 'R',
			'patient_data'  => 'RW',
			'users'         => 'N',
			'feedback'      => 'R',
			'messages'      => 'RW',
			'tasks'         => 'RW',
			'appointments'  => 'RW',
			'labwork'       => 'RW',
		],
		'PARAMEDIC' => [
			'assets'        => 'R',
			'case_sheets'   => 'RW',
			'events'        => 'R',
			'patient_data'  => 'RW',
			'users'         => 'N',
			'feedback'      => 'R',
			'messages'      => 'RW',
			'tasks'         => 'RW',
			'appointments'  => 'R',
			'labwork'       => 'R',
		],
		'GRIEVANCE_OFFICER' => [
			'assets'        => 'N',
			'case_sheets'   => 'R',
			'events'        => 'N',
			'patient_data'  => 'R',
			'users'         => 'N',
			'feedback'      => 'RW',
			'messages'      => 'RW',
			'tasks'         => 'RW',
			'appointments'  => 'N',
			'labwork'       => 'N',
		],
		'EDUCATION_TEAM' => [
			'assets'        => 'RW',
			'case_sheets'   => 'N',
			'events'        => 'RW',
			'patient_data'  => 'N',
			'users'         => 'N',
			'feedback'      => 'R',
			'messages'      => 'RW',
			'tasks'         => 'RW',
			'appointments'  => 'N',
			'labwork'       => 'N',
		],
		'DATA_ENTRY_OPERATOR' => [
			'assets'        => 'R',
			'case_sheets'   => 'RW',
			'events'        => 'R',
			'patient_data'  => 'RW',
			'users'         => 'N',
			'feedback'      => 'N',
			'messages'      => 'RW',
			'tasks'         => 'RW',
			'appointments'  => 'N',
			'labwork'       => 'N',
		],
	];

	try {
		$pdo  = getDBConnection();
		$rows = $pdo->query(
			'SELECT role, resource, permission FROM role_permissions'
		)->fetchAll(PDO::FETCH_ASSOC);

		if (empty($rows)) {
			return $defaults;
		}

		$matrix = [];
		foreach ($rows as $row) {
			$matrix[$row['role']][$row['resource']] = $row['permission'];
		}
		return $matrix;

	} catch (Exception $e) {
		// DB unavailable – use hardcoded baseline.
		return $defaults;
	}
}

define('PERMISSIONS', _load_permissions());

/**
 * Check whether a role has access to a resource.
 *
 * @param string $role     One of the role constants (e.g. 'DOCTOR')
 * @param string $resource One of the resource keys above (e.g. 'case_sheets')
 * @param string $action   'R' to test for any access, 'W' to test for write access
 * @return bool
 */
function can(string $role, string $resource, string $action = 'R'): bool
{
	$perm = PERMISSIONS[$role][$resource] ?? 'N';
	if ($action === 'W') {
		return $perm === 'RW';
	}
	return $perm !== 'N';
}
