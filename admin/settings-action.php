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

if (in_array($action, ['load_demo_data', 'load_demo_presentations'], true)) {
    $result = DemoData::seedAll();
    Helper::setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
    Helper::redirect(BASE_URL . 'admin/settings.php');
}

if (in_array($action, ['remove_demo_data', 'remove_demo_presentations'], true)) {
    $result = DemoData::removeDemo();
    Helper::setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
    Helper::redirect(BASE_URL . 'admin/settings.php');
}

if (in_array($action, ['clean_all_data', 'clean_all_presentations'], true)) {
    $confirm = trim((string) Helper::input('confirm_text', ''));
    if ($confirm !== 'DELETE ALL') {
        Helper::setFlash('danger', 'Confirmation text did not match. Type DELETE ALL to remove all system data.');
        Helper::redirect(BASE_URL . 'admin/settings.php');
    }

    $result = DemoData::cleanAll();
    Helper::setFlash($result['ok'] ? 'success' : 'danger', $result['message']);
    Helper::redirect(BASE_URL . 'admin/settings.php');
}

Helper::redirect(BASE_URL . 'admin/settings.php');
