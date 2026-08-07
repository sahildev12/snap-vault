<?php
/**
 * Legacy form URL — redirects to members list (modals handle create/edit).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

Helper::redirect(BASE_URL . 'admin/members.php');
