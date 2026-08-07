<?php
/**
 * Logout
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

Auth::logout();

// Start a fresh session for flash message
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}
Helper::setFlash('success', 'You have been logged out.');
Helper::redirect(BASE_URL . 'auth/login.php');
