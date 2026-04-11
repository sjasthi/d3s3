<?php
/**
 * analytics.php – Analytics & Reporting entry-point.
 *
 * GET  analytics.php                         → renders the page shell
 * GET  analytics.php?action=data&tab=X&...  → returns an HTML fragment for that tab (AJAX)
 *
 * Valid tab values: overview | caseload | outcomes | satisfaction | trends
 */

require_once __DIR__ . '/app/config/session.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/app/middleware/auth.php';
require_once __DIR__ . '/app/controllers/AnalyticsController.php';

$controller = new AnalyticsController();

$action = $_GET['action'] ?? 'page';

if ($action === 'data') {
    $tab       = $_GET['tab'] ?? 'overview';
    $validTabs = ['overview', 'caseload', 'outcomes', 'satisfaction', 'trends'];

    if (!in_array($tab, $validTabs, true)) {
        http_response_code(400);
        exit('Invalid tab.');
    }

    $method = 'data' . ucfirst($tab);
    $controller->$method();

} elseif ($action === 'print') {
    $controller->printReport();

} else {
    $controller->index();
}
