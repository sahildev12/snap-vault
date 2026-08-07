<?php
/**
 * Admin — Gallery for a single member
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

$memberId = (int) Helper::input('id', 0);
$member = User::findById($memberId);

if (!$member || $member['role'] !== 'member') {
    Helper::setFlash('danger', 'Team member not found.');
    Helper::redirect(BASE_URL . 'admin/folders.php');
}

$page = max(1, (int) Helper::input('page', 1));
$filters = [
    'period' => (string) Helper::input('period', ''),
    'flag'   => (string) Helper::input('flag', ''),
    'search' => trim((string) Helper::input('search', '')),
    'from'   => (string) Helper::input('from', ''),
    'to'     => (string) Helper::input('to', ''),
];

$result = Upload::listByUser($memberId, $filters, $page, PER_PAGE);

$queryParams = array_filter($filters, static fn($v) => $v !== '' && $v !== null);
$queryParams['id'] = $memberId;
$query = http_build_query($queryParams);
$baseUrl = BASE_URL . 'admin/member-gallery.php?' . $query . '&page=';

$pageTitle = $member['name'] . ' — Gallery';
require BASE_PATH . 'includes/header.php';
?>

<div class="member-gallery-header card panel-card mb-4">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center">
        <img src="<?= Helper::e(Helper::profileUrl($member['profile'])) ?>" alt="" class="avatar-lg">
        <div class="flex-grow-1">
            <h2 class="h5 mb-1"><?= Helper::e($member['name']) ?></h2>
            <div class="text-muted small">
                @<?= Helper::e($member['username']) ?> ·
                <strong><?= (int) $result['total'] ?></strong> image<?= $result['total'] === 1 ? '' : 's' ?>
            </div>
        </div>
        <a href="<?= Helper::e(BASE_URL . 'admin/folders.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Folders
        </a>
    </div>
</div>

<?php
$actionUrl = BASE_URL . 'admin/member-gallery.php';
$hiddenId = $memberId;
$showMemberFilter = false;
require BASE_PATH . 'includes/filter-bar.php';

$images = $result['rows'];
$canDelete = true;
$showUploader = true;
require BASE_PATH . 'includes/image-grid.php';
?>

<?php if ($result['total'] > PER_PAGE): ?>
    <div class="mt-4">
        <?= Helper::pagination($result['total'], $page, PER_PAGE, $baseUrl) ?>
    </div>
<?php endif; ?>

<?php require BASE_PATH . 'includes/footer.php'; ?>
