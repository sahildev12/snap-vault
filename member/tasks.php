<?php
/**
 * Member — my tasks
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'member';
require BASE_PATH . 'includes/role_check.php';

$status = (string) Helper::input('status', '');
$search = trim((string) Helper::input('q', ''));
$page = max(1, (int) Helper::input('page', 1));
$result = Task::listForMember((int) Auth::id(), $status, $search, $page, TASKS_PER_PAGE);
$baseUrl = BASE_URL . 'member/tasks.php?' . http_build_query(array_filter([
    'status' => $status !== '' ? $status : null,
    'q' => $search !== '' ? $search : null,
])) . '&page=';

$pageTitle = 'My Tasks';
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
    <?php if ($result['total'] > 0): ?>
        <div class="page-toolbar-meta">
            <span class="page-toolbar-count"><?= (int) $result['total'] ?> tasks</span>
        </div>
    <?php endif; ?>
</div>

<div class="task-filters mb-3">
    <a class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= Helper::e(BASE_URL . 'member/tasks.php' . ($search !== '' ? '?q=' . urlencode($search) : '')) ?>">All</a>
    <a class="btn btn-sm <?= $status === 'pending' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?status=pending<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">Pending</a>
    <a class="btn btn-sm <?= $status === 'in_progress' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?status=in_progress<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">In progress</a>
    <a class="btn btn-sm <?= $status === 'overdue' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?status=overdue<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">Overdue</a>
    <a class="btn btn-sm <?= $status === 'completed' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="?status=completed<?= $search !== '' ? '&q=' . urlencode($search) : '' ?>">Completed</a>
</div>

<?php if ($result['rows'] === []): ?>
    <div class="card panel-card task-board-card">
        <div class="card-body empty-state py-5">
            <i class="bi bi-check2-square"></i>
            <p><?= $search !== '' ? 'No tasks match your search.' : 'No tasks assigned to you.' ?></p>
        </div>
    </div>
<?php else: ?>
    <div class="task-board">
        <?php foreach ($result['rows'] as $row): ?>
            <a href="<?= Helper::e(BASE_URL . 'member/task-view.php?id=' . (int) $row['id']) ?>"
               class="card panel-card task-card text-decoration-none text-reset">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-3 align-items-start">
                        <div>
                            <div class="fw-semibold task-card-title"><?= Helper::e($row['title']) ?></div>
                            <div class="task-card-meta mb-0">
                                <span><i class="bi bi-calendar3"></i> <?= Helper::e(Helper::formatDate($row['deadline'])) ?></span>
                                <span><i class="bi bi-person"></i> <?= Helper::e($row['assigner_name']) ?></span>
                            </div>
                        </div>
                        <?= Task::statusBadge($row['status']) ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php if ($result['total'] > TASKS_PER_PAGE): ?>
        <div class="mt-3">
            <?= Helper::pagination($result['total'], $page, TASKS_PER_PAGE, $baseUrl) ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require BASE_PATH . 'includes/footer.php'; ?>
