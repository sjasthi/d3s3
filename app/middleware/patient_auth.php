<?php
/**
 * app/middleware/patient_auth.php
 *
 * Session guard for the patient portal.
 * Require this file in every patient-portal entry point after session.php.
 * Redirects unauthenticated visitors to patient_login.php.
 */

if (empty($_SESSION['patient_account_id']) || empty($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}
