<?php
/**
 * press.php — photos and press coverage stored as public assets.
 *
 * Issue #9: show Press Coverage and operations Photos (Drive folders or
 * other public asset URLs). Categories: Testimonials, Press Coverage, Photos.
 */

require_once __DIR__ . '/app/config/session.php';
require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/permissions.php';

$role = $_SESSION['user_role'] ?? '';
if (!can($role, 'assets') && !can($role, 'events')) {
	$_SESSION['dashboard_notice'] = 'You do not have permission to access this page.';
	header('Location: dashboard.php');
	exit;
}

$pdo = getDBConnection();
$stmt = $pdo->query(
	"SELECT asset_id, title, description, asset_type, category, resource_url, notes
	   FROM assets
	  WHERE is_active = 1
	    AND is_public = 1
	    AND category IN ('Testimonials', 'Press Coverage', 'Photos')
	  ORDER BY FIELD(category, 'Press Coverage', 'Photos', 'Testimonials'), title"
);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped = [
	'Press Coverage' => [],
	'Photos'         => [],
	'Testimonials'   => [],
];
foreach ($items as $item) {
	$cat = $item['category'] ?? '';
	if (!isset($grouped[$cat])) {
		$grouped[$cat] = [];
	}
	$grouped[$cat][] = $item;
}

$canManageAssets = can($role, 'assets', 'W');

require __DIR__ . '/app/views/press.php';
