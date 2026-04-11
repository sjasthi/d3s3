<?php
/**
 * assets.php – Asset Management entry-point.
 *
 * Routes:
 *   GET  (default)              → asset list (with optional filters)
 *   GET  ?action=create         → new asset form
 *   GET  ?action=edit&id=X      → edit asset form
 *   GET  ?action=download&id=X  → stream a locally stored file (staff or patient auth)
 *   POST form_action=create     → create asset (with optional file upload)
 *   POST form_action=edit       → update asset (with optional file replacement)
 *   POST form_action=delete     → delete asset (+ local file cleanup)
 *   POST form_action=send_to_patient  → send asset to a patient's portal
 *   POST form_action=remove_send      → remove asset from a patient's portal
 */

require_once __DIR__ . '/app/config/session.php';

if (!isset($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/app/controllers/AssetController.php';

$controller = new AssetController();

// File download is handled before any HTML output
if (($_GET['action'] ?? '') === 'download') {
	require_once __DIR__ . '/app/config/database.php';
	require_once __DIR__ . '/app/config/permissions.php';
	$controller->download();
	exit;
}

// All other actions require a staff session
require_once __DIR__ . '/app/middleware/auth.php';

$controller->index();
