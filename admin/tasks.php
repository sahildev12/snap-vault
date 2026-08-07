<?php
/**
 * Admin — Tasks list + create / edit / delete
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

$status = (string) Helper::input('status', '');
$search = trim((string) Helper::input('q', ''));
$page = max(1, (int) Helper::input('page', 1));
$result = Task::listAll($status, $search, $page, TASKS_PER_PAGE);
$counts = Task::counts();
$members = User::allActiveMembers();
$baseUrl = BASE_URL . 'admin/tasks.php?' . http_build_query(array_filter([
    'status' => $status !== '' ? $status : null,
    'q' => $search !== '' ? $search : null,
])) . '&page=';

$pageTitle = 'Tasks';
require BASE_PATH . 'includes/header.php';
?>

<div class="page-toolbar">
    <form class="page-search" method="get" action="" role="search">
        <i class="bi bi-search page-search-icon" aria-hidden="true"></i>
        <input type="search" name="q" class="page-search-input"
               placeholder="Search tasks"
               value="<?= Helper::e($search) ?>"
               aria-label="Search tasks"
               autocomplete="off">
        <?php if ($status !== ''): ?>
            <input type="hidden" name="status" value="<?= Helper::e($status) ?>">
        <?php endif; ?>
        <button type="button" class="page-search-clear<?= $search === '' ? ' is-hidden' : '' ?>" title="Clear" aria-label="Clear">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </form>
    <div class="page-toolbar-meta">
        <?php if ($result['total'] > 0): ?>
            <span class="page-toolbar-count"><?= (int) $result['total'] ?> tasks</span>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
            <i class="bi bi-plus-lg me-1"></i>Assign Task
        </button>
    </div>
</div>

<div class="task-filters mb-3">
    <a class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= Helper::e(BASE_URL . 'admin/tasks.php' . ($search !== '' ? '?q=' . urlencode($search) : '')) ?>">All</a>
    <a class="btn btn-sm <?= $status === 'pending' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?status=pending<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">Pending (<?= (int) $counts['pending'] ?>)</a>
    <a class="btn btn-sm <?= $status === 'in_progress' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?status=in_progress<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">In progress (<?= (int) $counts['in_progress'] ?>)</a>
    <a class="btn btn-sm <?= $status === 'overdue' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?status=overdue<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">Overdue (<?= (int) $counts['overdue'] ?>)</a>
    <a class="btn btn-sm <?= $status === 'completed' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?status=completed<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">Completed (<?= (int) $counts['completed'] ?>)</a>
</div>

<?php if ($result['rows'] === []): ?>
    <div class="card panel-card task-board-card">
        <div class="card-body empty-state py-5">
            <i class="bi bi-check2-square"></i>
            <p><?= $search !== '' ? 'No tasks match your search.' : 'No tasks yet. Assign one to a member.' ?></p>
        </div>
    </div>
<?php else: ?>
    <div class="task-board">
        <?php foreach ($result['rows'] as $row): ?>
            <div class="card panel-card task-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-3 align-items-start">
                        <div class="min-w-0">
                            <div class="fw-semibold task-card-title"><?= Helper::e($row['title']) ?></div>
                            <?php if (!empty($row['description'])): ?>
                                <div class="small text-muted mt-1"><?= Helper::e(Helper::truncate($row['description'], 100)) ?></div>
                            <?php endif; ?>
                        </div>
                        <?= Task::statusBadge($row['status']) ?>
                    </div>
                    <div class="task-card-meta">
                        <span><i class="bi bi-person"></i> <?= Helper::e($row['assignee_name']) ?></span>
                        <span><i class="bi bi-calendar3"></i> <?= Helper::e(Helper::formatDate($row['deadline'])) ?></span>
                        <span>
                            <i class="bi bi-hourglass-split"></i>
                            <?php if ($row['status'] === 'completed'): ?>
                                <?= Helper::e(Helper::formatDuration($row['created_at'], $row['completed_at'])) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="task-card-actions">
                        <a href="<?= Helper::e(BASE_URL . 'admin/task-view.php?id=' . (int) $row['id']) ?>"
                           class="btn btn-sm btn-outline-secondary" title="View">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <?php if ($row['status'] === 'completed'): ?>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    disabled
                                    title="Completed tasks cannot be edited">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        <?php else: ?>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary btn-edit-task"
                                    data-id="<?= (int) $row['id'] ?>"
                                    data-title="<?= Helper::e($row['title']) ?>"
                                    data-description="<?= Helper::e((string) ($row['description'] ?? '')) ?>"
                                    data-assigned="<?= (int) $row['assigned_to'] ?>"
                                    data-deadline="<?= Helper::e(date('Y-m-d\TH:i', strtotime((string) $row['deadline']))) ?>"
                                    title="Edit">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        <?php endif; ?>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-confirm-delete"
                                data-form-id="deleteTask<?= (int) $row['id'] ?>"
                                data-message="Delete task “<?= Helper::e($row['title']) ?>”?"
                                title="Delete">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                        <form id="deleteTask<?= (int) $row['id'] ?>" method="post"
                              action="<?= Helper::e(BASE_URL . 'admin/task-action.php') ?>" class="d-none">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($result['total'] > TASKS_PER_PAGE): ?>
        <div class="mt-3">
            <?= Helper::pagination($result['total'], $page, TASKS_PER_PAGE, $baseUrl) ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="post" action="<?= Helper::e(BASE_URL . 'admin/task-action.php') ?>" class="modal-content">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-header border-0">
                <h5 class="modal-title">Assign Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="taskTitle">Title</label>
                    <input type="text" name="title" id="taskTitle" class="form-control" required maxlength="200">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="taskMember">Assign to</label>
                    <select name="assigned_to" id="taskMember" class="form-select sv-select" required>
                        <option value="">Select member…</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= (int) $m['id'] ?>"><?= Helper::e($m['name']) ?> (@<?= Helper::e($m['username']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="taskDeadline">Deadline</label>
                    <input type="datetime-local" name="deadline" id="taskDeadline" class="form-control" required>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="taskDescription">Details (optional)</label>
                    <textarea name="description" id="taskDescription" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary btn-modal-action" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-modal-action">Assign</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="post" action="<?= Helper::e(BASE_URL . 'admin/task-action.php') ?>" class="modal-content" id="editTaskForm">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="editTaskId" value="">
            <div class="modal-header border-0">
                <h5 class="modal-title">Edit Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="editTaskTitle">Title</label>
                    <input type="text" name="title" id="editTaskTitle" class="form-control" required maxlength="200">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="editTaskMember">Assign to</label>
                    <select name="assigned_to" id="editTaskMember" class="form-select" required>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= (int) $m['id'] ?>"><?= Helper::e($m['name']) ?> (@<?= Helper::e($m['username']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="editTaskDeadline">Deadline</label>
                    <input type="datetime-local" name="deadline" id="editTaskDeadline" class="form-control" required>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="editTaskDescription">Details (optional)</label>
                    <textarea name="description" id="editTaskDescription" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary btn-modal-action" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-modal-action">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php
$extraScripts = '<script>
document.querySelectorAll(".btn-edit-task").forEach((btn) => {
    btn.addEventListener("click", () => {
        document.getElementById("editTaskId").value = btn.dataset.id || "";
        document.getElementById("editTaskTitle").value = btn.dataset.title || "";
        document.getElementById("editTaskDescription").value = btn.dataset.description || "";
        document.getElementById("editTaskMember").value = btn.dataset.assigned || "";
        document.getElementById("editTaskDeadline").value = btn.dataset.deadline || "";
        bootstrap.Modal.getOrCreateInstance(document.getElementById("editTaskModal")).show();
    });
});
</script>';
require BASE_PATH . 'includes/footer.php';
?>
