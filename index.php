<?php
/**
 * index.php – application entry-point.
 *
 * Redirects authenticated users to the dashboard;
 * everyone else lands on the login page.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Location: ' . (isset($_SESSION['user_id']) ? 'dashboard.php' : 'login.php'));
exit;
?>
