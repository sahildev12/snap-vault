<?php
/**
 * Admin settings actions — one-click database update
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

if (HIDE_SETTINGS_BUTTON) {
    Helper::setFlash('danger', 'Settings are disabled.');
    Helper::redirect(BASE_URL . 'admin/dashboard.php');
}

if (!Helper::isPost()) {
    Helper::redirect(BASE_URL . 'admin/settings.php');
}

Csrf::requireValid();
$action = (string) Helper::input('action', '');

if ($action === 'update_database') {
    $result = SchemaMigrator::run();
    if ($result['ok']) {
        if ($result['ran'] > 0) {
            Helper::setFlash(
                'success',
                'Database updated. Applied ' . $result['ran'] . ' step' . ($result['ran'] === 1 ? '' : 's') . '.'
            );
        } else {
            Helper::setFlash('success', 'Database is already up to date.');
        }
    } else {
        $detail = $result['messages'][array_key_last($result['messages'])] ?? 'Update failed.';
        Helper::setFlash('danger', $detail);
    }
    Helper::redirect(BASE_URL . 'admin/settings.php');
}

Helper::redirect(BASE_URL . 'admin/settings.php');
