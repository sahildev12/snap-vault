<?php
/**
 * Admin — view a single task
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

$id = (int) Helper::input('id', 0);
$task = Task::find($id);
if (!$task) {
    Helper::setFlash('danger', 'Task not found.');
    Helper::redirect(BASE_URL . 'admin/tasks.php');
}

$files = Task::files($id);
$members = User::allActiveMembers();

$pageTitle = 'Task details';
require BASE_PATH . 'includes/header.php';
?>

<div class="mb-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
    <a href="<?= Helper::e(BASE_URL . 'admin/tasks.php') ?>" class="text-decoration-none small">&larr; Back to tasks</a>
    <div class="d-flex gap-2">
        <?php if ($task['status'] === 'completed'): ?>
            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Completed tasks cannot be edited">
                <i class="bi bi-pencil"></i> Edit
            </button>
        <?php else: ?>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTaskModal">
                <i class="bi bi-pencil"></i> Edit
            </button>
        <?php endif; ?>
        <button type="button"
                class="btn btn-sm btn-outline-danger btn-confirm-delete"
                data-form-id="deleteTaskView"
                data-message="Delete this task?">
            <i class="bi bi-trash"></i> Delete
        </button>
        <form id="deleteTaskView" method="post" action="<?= Helper::e(BASE_URL . 'admin/task-action.php') ?>" class="d-none">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
        </form>
    </div>
</div>

<div class="card panel-card task-card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between gap-3 align-items-start mb-3">
            <div>
                <h2 class="h4 mb-1"><?= Helper::e($task['title']) ?></h2>
                <div class="text-muted small">Assigned by <?= Helper::e($task['assigner_name']) ?></div>
            </div>
            <?= Task::statusBadge($task['status']) ?>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-sm-4">
                <div class="stat-label">Member</div>
                <div class="fw-semibold"><?= Helper::e($task['assignee_name']) ?></div>
            </div>
            <div class="col-sm-4">
                <div class="stat-label">Deadline</div>
                <div class="fw-semibold"><?= Helper::e(Helper::formatDate($task['deadline'])) ?></div>
            </div>
            <div class="col-sm-4">
                <div class="stat-label">Time taken</div>
                <div class="fw-semibold">
                    <?php if ($task['status'] === 'completed'): ?>
                        <?= Helper::e(Helper::formatDuration($task['created_at'], $task['completed_at'])) ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if (!empty($task['description'])): ?>
            <p class="mb-0"><?= nl2br(Helper::e($task['description'])) ?></p>
        <?php else: ?>
            <p class="text-muted mb-0">No additional details.</p>
        <?php endif; ?>
        <?php if (!empty($task['completion_note'])): ?>
            <hr>
            <div class="stat-label">Completion note</div>
            <p class="mb-0"><?= nl2br(Helper::e($task['completion_note'])) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if ($files !== []): ?>
    <div class="card panel-card task-card mb-4">
        <div class="card-header bg-transparent border-0">
            <h3 class="h6 mb-0">Uploaded files</h3>
        </div>
        <div class="card-body pt-0">
            <ul class="mb-0 ps-3">
                <?php foreach ($files as $f): ?>
                    <li>
                        <a href="<?= Helper::e(TASK_UPLOAD_URL . $f['filename']) ?>" target="_blank" rel="noopener">
                            <?= Helper::e($f['original_name'] ?: $f['filename']) ?>
                        </a>
                        <span class="text-muted small">· <?= Helper::e(Helper::formatDate($f['created_at'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php if ($task['status'] !== 'completed'): ?>
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="post" action="<?= Helper::e(BASE_URL . 'admin/task-action.php') ?>" class="modal-content">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
            <div class="modal-header border-0">
                <h5 class="modal-title">Edit Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="editTitle">Title</label>
                    <input type="text" name="title" id="editTitle" class="form-control" required maxlength="200"
                           value="<?= Helper::e($task['title']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="editMember">Assign to</label>
                    <select name="assigned_to" id="editMember" class="form-select sv-select" required>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= (int) $m['id'] ?>" <?= (int) $m['id'] === (int) $task['assigned_to'] ? 'selected' : '' ?>>
                                <?= Helper::e($m['name']) ?> (@<?= Helper::e($m['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="editDeadline">Deadline</label>
                    <input type="datetime-local" name="deadline" id="editDeadline" class="form-control" required
                           value="<?= Helper::e(date('Y-m-d\TH:i', strtotime((string) $task['deadline']))) ?>">
                </div>
                <div class="mb-0">
                    <label class="form-label" for="editDescription">Details (optional)</label>
                    <textarea name="description" id="editDescription" class="form-control" rows="3"><?= Helper::e((string) ($task['description'] ?? '')) ?></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary btn-modal-action" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-modal-action">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require BASE_PATH . 'includes/footer.php'; ?>
