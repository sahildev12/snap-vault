<?php
/**
 * Member — Block information page
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'member';
require BASE_PATH . 'includes/role_check.php';

$slug = trim((string) Helper::input('slug', ''));
$view = trim((string) Helper::input('view', 'home'));
$allowed = ['home', 'phcs', 'gallery', 'infrastructure', 'equipments', 'roadmap', 'staff'];
if (!in_array($view, $allowed, true)) {
    $view = 'home';
}

$block = BlockMap::find($slug);
if ($block === null) {
    Helper::setFlash('danger', 'Block not found.');
    Helper::redirect(BASE_URL . 'member/map.php');
}

require BASE_PATH . 'includes/map-block-view.php';
