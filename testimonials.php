<?php
/**
 * testimonials.php — public beneficiary testimonial videos (Issue #6).
 *
 * Lists assets tagged category = Testimonials and is_public = 1.
 * Videos stay on YouTube / Drive; the app stores metadata only.
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
	"SELECT asset_id, title, description, asset_type, category, resource_url, storage_type, notes
	   FROM assets
	  WHERE is_active = 1
	    AND is_public = 1
	    AND category = 'Testimonials'
	  ORDER BY FIELD(asset_type, 'VIDEO', 'OTHER', 'AUDIO', 'IMAGE'), title"
);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$canManageAssets = can($role, 'assets', 'W');

require __DIR__ . '/app/views/testimonials.php';
