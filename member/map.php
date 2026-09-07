<?php
/**
 * Member — Interactive MAP
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'member';
require BASE_PATH . 'includes/role_check.php';

$pageTitle = 'MAP';
require BASE_PATH . 'includes/header.php';
require BASE_PATH . 'includes/map-shell.php';
require BASE_PATH . 'includes/footer.php';
