<?php
/**
 * Issue 16: a nurse sees only patient records that nurse created.
 * Issue 17: a triage nurse sees only case sheets that nurse created.
 * Doctors and admins are not limited by these rules.
 */

function scopeRole(): string
{
	return (string)($_SESSION['user_role'] ?? '');
}

function nurseOwnsPatient(?array $patient): bool
{
	if (scopeRole() !== 'NURSE') {
		return true;
	}
	if (!$patient) {
		return false;
	}
	return (int)($patient['created_by_user_id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0);
}

function triageOwnsCase(?array $caseSheet): bool
{
	if (scopeRole() !== 'TRIAGE_NURSE') {
		return true;
	}
	if (!$caseSheet) {
		return false;
	}
	return (int)($caseSheet['created_by_user_id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0);
}

function denyPatientUnlessNurseOwns(?array $patient): void
{
	if (nurseOwnsPatient($patient)) {
		return;
	}
	$_SESSION['patients_error'] = 'You can only open patient records that you created.';
	header('Location: patients.php');
	exit;
}

function denyCaseUnlessTriageOwns(?array $caseSheet): void
{
	if (triageOwnsCase($caseSheet)) {
		return;
	}
	$_SESSION['dashboard_notice'] = 'You can only open case sheets that you created.';
	header('Location: dashboard.php');
	exit;
}
