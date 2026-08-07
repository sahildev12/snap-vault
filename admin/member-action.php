<?php
/**
 * Admin — Team member POST actions (save, delete, toggle, reset)
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

if (!Helper::isPost()) {
    Helper::redirect(BASE_URL . 'admin/members.php');
}

Csrf::requireValid();

$action = (string) Helper::input('action', '');
$id = (int) Helper::input('id', 0);

if ($action === 'save') {
    $isEdit = $id > 0;
    $member = $isEdit ? User::findById($id) : null;

    if ($isEdit && (!$member || $member['role'] !== 'member')) {
        Helper::setFlash('danger', 'Team member not found.');
        Helper::redirect(BASE_URL . 'admin/members.php');
    }

    $name = trim((string) Helper::input('name', ''));
    $username = trim((string) Helper::input('username', ''));
    $password = (string) Helper::input('password', '');
    $status = (int) Helper::input('status', 1) === 1 ? 1 : 0;
    $errors = [];

    if ($name === '') {
        $errors[] = 'Full name is required.';
    }
    if ($username === '' || !preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $username)) {
        $errors[] = 'Username must be 3–60 characters (letters, numbers, . _ -).';
    } elseif (User::usernameExists($username, $isEdit ? $id : null)) {
        $errors[] = 'Username is already taken.';
    }
    if (!$isEdit && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($isEdit && $password !== '' && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    $profileFilename = $member['profile'] ?? null;

    if (!empty($_FILES['profile']['name'])) {
        $stored = Upload::storeFile($_FILES['profile'], PROFILE_DIR);
        if (!$stored['success']) {
            $errors[] = $stored['message'];
        } else {
            if (!empty($profileFilename) && is_file(PROFILE_DIR . $profileFilename)) {
                @unlink(PROFILE_DIR . $profileFilename);
            }
            $profileFilename = $stored['filename'];
        }
    }

    if ($errors !== []) {
        Helper::setFlash('danger', implode(' ', $errors));
        Helper::redirect(BASE_URL . 'admin/members.php');
    }

    if ($isEdit) {
        $data = [
            'name'     => $name,
            'username' => $username,
            'status'   => $status,
            'profile'  => $profileFilename,
        ];
        if ($password !== '') {
            $data['password'] = $password;
        }
        User::update($id, $data);
        Helper::setFlash('success', 'Team member updated.');
    } else {
        User::create([
            'name'     => $name,
            'username' => $username,
            'password' => $password,
            'profile'  => $profileFilename,
            'role'     => 'member',
            'status'   => $status,
        ]);
        Helper::setFlash('success', 'Team member created.');
    }

    Helper::redirect(BASE_URL . 'admin/members.php');
}

if ($id <= 0) {
    Helper::setFlash('danger', 'Invalid member.');
    Helper::redirect(BASE_URL . 'admin/members.php');
}

$member = User::findById($id);
if (!$member || $member['role'] !== 'member') {
    Helper::setFlash('danger', 'Team member not found.');
    Helper::redirect(BASE_URL . 'admin/members.php');
}

switch ($action) {
    case 'delete':
        User::delete($id);
        Helper::setFlash('success', 'Team member deleted.');
        break;

    case 'toggle':
        User::toggleStatus($id);
        Helper::setFlash('success', 'Member status updated.');
        break;

    case 'reset':
        $password = (string) Helper::input('password', '');
        if (strlen($password) < 6) {
            Helper::setFlash('danger', 'Password must be at least 6 characters.');
            Helper::redirect(BASE_URL . 'admin/members.php');
        }
        User::resetPassword($id, $password);
        Helper::setFlash('success', 'Password reset successfully.');
        break;

    default:
        Helper::setFlash('danger', 'Unknown action.');
        break;
}

Helper::redirect(BASE_URL . 'admin/members.php');
