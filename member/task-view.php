<?php
/**
 * Member — task detail / complete
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'member';
require BASE_PATH . 'includes/role_check.php';

$id = (int) Helper::input('id', 0);
$task = Task::find($id);
if (!$task || (int) $task['assigned_to'] !== (int) Auth::id()) {
    Helper::setFlash('danger', 'Task not found.');
    Helper::redirect(BASE_URL . 'member/tasks.php');
}

$errors = [];

if (Helper::isPost()) {
    Csrf::requireValid();
    $action = (string) Helper::input('action', '');

    if ($action === 'start') {
        Task::start($id, (int) Auth::id());
        Helper::setFlash('success', 'Task marked in progress.');
        Helper::redirect(BASE_URL . 'member/task-view.php?id=' . $id);
    }

    if ($action === 'complete') {
        $note = trim((string) Helper::input('completion_note', ''));
        if (!empty($_FILES['task_file']['name'])) {
            $stored = Task::storeCompletionFile($_FILES['task_file']);
            if (!$stored['success']) {
                $errors[] = $stored['message'];
            } else {
                Task::addFile($id, (int) Auth::id(), $stored['filename'], $stored['original'] ?? null);
            }
        }
        if ($errors === []) {
            if (!Task::complete($id, (int) Auth::id(), $note)) {
                $errors[] = 'Unable to complete this task.';
            } else {
                Helper::setFlash('success', 'Task completed. Great work!');
                Helper::redirect(BASE_URL . 'member/tasks.php?status=completed');
            }
        }
    }
}

$task = Task::find($id);
$files = Task::files($id);
$canAct = in_array($task['status'], ['pending', 'in_progress', 'overdue'], true);

$pageTitle = 'Task';
require BASE_PATH . 'includes/header.php';
?>

<div class="mb-3">
    <a href="<?= Helper::e(BASE_URL . 'member/tasks.php') ?>" class="text-decoration-none small">&larr; Back to tasks</a>
</div>

<div class="card panel-card mb-4">
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
                <div class="stat-label">Deadline</div>
                <div class="fw-semibold"><?= Helper::e(Helper::formatDate($task['deadline'])) ?></div>
            </div>
            <div class="col-sm-4">
                <div class="stat-label">Created</div>
                <div class="fw-semibold"><?= Helper::e(Helper::formatDate($task['created_at'])) ?></div>
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
    <div class="card panel-card mb-4">
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

<?php if ($errors !== []): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= Helper::e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($canAct): ?>
    <div class="card panel-card">
        <div class="card-body">
            <?php if ($task['status'] === 'pending' || $task['status'] === 'overdue'): ?>
                <form method="post" class="mb-4">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="start">
                    <button type="submit" class="btn btn-outline-secondary">Mark as In Progress</button>
                </form>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="complete">
                <div class="mb-3">
                    <label class="form-label" for="completion_note">Completion note (optional)</label>
                    <textarea name="completion_note" id="completion_note" class="form-control" rows="3"
                              placeholder="What did you finish?"></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="taskFileTrigger">Upload proof (optional)</label>
                    <div class="file-field">
                        <div class="file-picker">
                            <input type="file" name="task_file" id="task_file" class="file-picker-input"
                                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip">
                            <button type="button" class="btn btn-outline-secondary file-picker-btn" id="taskFileTrigger">
                                Choose File
                            </button>
                            <span class="file-picker-name" id="taskFileName">No file chosen</span>
                        </div>
                    </div>
                    <div class="form-text">Images, PDF, Office docs, ZIP · max 10MB</div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">Mark Complete</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$extraScripts = '<script>
(function () {
    const input = document.getElementById("task_file");
    const trigger = document.getElementById("taskFileTrigger");
    const nameEl = document.getElementById("taskFileName");
    if (!input || !trigger || !nameEl) return;
    trigger.addEventListener("click", () => input.click());
    input.addEventListener("change", () => {
        const file = input.files && input.files[0];
        nameEl.textContent = file ? file.name : "No file chosen";
    });
})();
</script>';
require BASE_PATH . 'includes/footer.php';
?>
