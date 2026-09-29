<?php
/**
 * Admin — All presentations uploaded by members
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

$pageTitle = 'Presentations';
require BASE_PATH . 'includes/header.php';

if (!SchemaMigrator::tableExists('presentations')) {
    $featureLabel = 'Presentations';
    require BASE_PATH . 'includes/migration-required.php';
    require BASE_PATH . 'includes/footer.php';
    return;
}

$page = max(1, (int) Helper::input('page', 1));
$filters = [
    'period'    => (string) Helper::input('period', ''),
    'search'    => trim((string) Helper::input('search', '')),
    'from'      => (string) Helper::input('from', ''),
    'to'        => (string) Helper::input('to', ''),
    'member_id' => (string) Helper::input('member_id', ''),
];

$result = Presentation::listFiltered($filters, $page, PER_PAGE);
$membersList = User::listMembers('', 1, 500);

$query = http_build_query(array_filter($filters, static fn($v) => $v !== '' && $v !== null));
$baseUrl = BASE_URL . 'admin/presentations.php?' . ($query !== '' ? $query . '&' : '') . 'page=';

$actionUrl = BASE_URL . 'admin/presentations.php';
$showMemberFilter = true;
$members = $membersList['rows'];
require BASE_PATH . 'includes/presentation-filter-bar.php';
?>

<div class="toolbar-row mb-3">
    <div class="text-muted small">
        <strong><?= (int) $result['total'] ?></strong>
        presentation<?= $result['total'] === 1 ? '' : 's' ?>
    </div>
</div>

<?php
$presentations = $result['rows'];
$canDelete = true;
$showUploader = true;
require BASE_PATH . 'includes/presentation-list.php';
?>

<?php if ($result['total'] > PER_PAGE): ?>
    <div class="mt-4">
        <?= Helper::pagination($result['total'], $page, PER_PAGE, $baseUrl) ?>
    </div>
<?php endif; ?>

<?php require BASE_PATH . 'includes/footer.php'; ?>
