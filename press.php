<?php
/**
 * press.php — public-facing photos and press coverage stored as assets.
 */

require_once __DIR__ . '/app/config/session.php';
require __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/permissions.php';

if (!can($_SESSION['user_role'] ?? '', 'assets') && !can($_SESSION['user_role'] ?? '', 'events')) {
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
	  ORDER BY category, title"
);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
require __DIR__ . '/app/views/press.php';
