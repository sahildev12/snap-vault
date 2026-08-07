<?php
/**
 * Admin task actions — create / update / delete
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

if (!Helper::isPost()) {
    Helper::redirect(BASE_URL . 'admin/tasks.php');
}

Csrf::requireValid();
$action = (string) Helper::input('action', '');

function jammu_parse_task_deadline(string $deadlineRaw): ?string
{
    $deadlineTs = strtotime(str_replace('T', ' ', $deadlineRaw));
    if ($deadlineTs === false) {
        return null;
    }
    return date('Y-m-d H:i:s', $deadlineTs);
}

if ($action === 'create' || $action === 'update') {
    $title = trim((string) Helper::input('title', ''));
    $description = trim((string) Helper::input('description', ''));
    $assignedTo = (int) Helper::input('assigned_to', 0);
    $deadlineRaw = trim((string) Helper::input('deadline', ''));
    $id = (int) Helper::input('id', 0);

    if ($title === '' || $assignedTo < 1 || $deadlineRaw === '') {
        Helper::setFlash('danger', 'Title, member, and deadline are required.');
        Helper::redirect(BASE_URL . 'admin/tasks.php');
    }

    $member = User::findById($assignedTo);
    if (!$member || ($member['role'] ?? '') !== 'member') {
        Helper::setFlash('danger', 'Invalid member selected.');
        Helper::redirect(BASE_URL . 'admin/tasks.php');
    }

    $deadline = jammu_parse_task_deadline($deadlineRaw);
    if ($deadline === null) {
        Helper::setFlash('danger', 'Invalid deadline.');
        Helper::redirect(BASE_URL . 'admin/tasks.php');
    }

    $payload = [
        'title'       => $title,
        'description' => $description,
        'assigned_to' => $assignedTo,
        'deadline'    => $deadline,
    ];

    if ($action === 'create') {
        $payload['assigned_by'] = (int) Auth::id();
        Task::create($payload);
        Helper::setFlash('success', 'Task assigned to ' . $member['name'] . '.');
    } else {
        $existing = $id > 0 ? Task::find($id) : null;
        if (!$existing) {
            Helper::setFlash('danger', 'Task not found.');
            Helper::redirect(BASE_URL . 'admin/tasks.php');
        }
        if (($existing['status'] ?? '') === 'completed') {
            Helper::setFlash('danger', 'Completed tasks cannot be edited.');
            Helper::redirect(BASE_URL . 'admin/task-view.php?id=' . $id);
        }
        Task::update($id, $payload);
        Helper::setFlash('success', 'Task updated.');
        Helper::redirect(BASE_URL . 'admin/task-view.php?id=' . $id);
    }

    Helper::redirect(BASE_URL . 'admin/tasks.php');
}

if ($action === 'delete') {
    $id = (int) Helper::input('id', 0);
    if ($id > 0 && Task::find($id)) {
        Task::delete($id);
        Helper::setFlash('success', 'Task deleted.');
    } else {
        Helper::setFlash('danger', 'Task not found.');
    }
    Helper::redirect(BASE_URL . 'admin/tasks.php');
}

Helper::redirect(BASE_URL . 'admin/tasks.php');
