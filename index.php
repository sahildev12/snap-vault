<?php
/**
 * SnapVault - Entry point
 * Redirects guests to login; authenticated users to role dashboard.
 */
require_once __DIR__ . '/includes/init.php';

if (!Auth::check()) {
    Helper::redirect(BASE_URL . 'auth/login.php');
}

$user = Auth::user();
if ($user && $user['role'] === 'admin') {
    Helper::redirect(BASE_URL . 'admin/dashboard.php');
}

Helper::redirect(BASE_URL . 'member/dashboard.php');
