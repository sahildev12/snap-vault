<?php
/**
 * Member — PHC information page
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'member';
require BASE_PATH . 'includes/role_check.php';

$blockSlug = trim((string) Helper::input('block', ''));
$phcSlug = trim((string) Helper::input('phc', ''));
$view = trim((string) Helper::input('view', 'home'));
$allowed = ['home', 'details', 'gallery', 'infrastructure', 'equipments', 'roadmap', 'staff'];
if (!in_array($view, $allowed, true)) {
    $view = 'home';
}

$block = BlockMap::find($blockSlug);
$phc = BlockMap::findPhc($blockSlug, $phcSlug);
if ($block === null || $phc === null) {
    Helper::setFlash('danger', 'PHC not found.');
    Helper::redirect(BASE_URL . 'member/map.php');
}

require BASE_PATH . 'includes/map-phc-view.php';
