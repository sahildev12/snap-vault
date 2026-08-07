<?php
/**
 * Member — Own gallery
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'member';
require BASE_PATH . 'includes/role_check.php';

$userId = (int) Auth::id();
$page = max(1, (int) Helper::input('page', 1));

$filters = [
    'period' => (string) Helper::input('period', ''),
    'flag'   => (string) Helper::input('flag', ''),
    'search' => trim((string) Helper::input('search', '')),
    'from'   => (string) Helper::input('from', ''),
    'to'     => (string) Helper::input('to', ''),
];

$result = Upload::listByUser($userId, $filters, $page, PER_PAGE);

$query = http_build_query(array_filter($filters, static fn($v) => $v !== '' && $v !== null));
$baseUrl = BASE_URL . 'member/gallery.php?' . ($query !== '' ? $query . '&' : '') . 'page=';

$pageTitle = 'My Gallery';
require BASE_PATH . 'includes/header.php';

$actionUrl = BASE_URL . 'member/gallery.php';
$showMemberFilter = false;
require BASE_PATH . 'includes/filter-bar.php';
?>

<div class="toolbar-row mb-3">
    <div class="text-muted small">
        <strong><?= (int) $result['total'] ?></strong> image<?= $result['total'] === 1 ? '' : 's' ?>
    </div>
    <a href="<?= Helper::e(BASE_URL . 'member/upload.php') ?>" class="btn btn-primary filter-btn">
        Upload
    </a>
</div>

<?php
$images = $result['rows'];
$canDelete = true;
$showUploader = false;
require BASE_PATH . 'includes/image-grid.php';
?>

<?php if ($result['total'] > PER_PAGE): ?>
    <div class="mt-4">
        <?= Helper::pagination($result['total'], $page, PER_PAGE, $baseUrl) ?>
    </div>
<?php endif; ?>

<?php require BASE_PATH . 'includes/footer.php'; ?>
