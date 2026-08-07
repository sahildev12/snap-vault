<?php
/**
 * Admin — Folder cards (Image Manager home)
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

$search = trim((string) Helper::input('q', ''));
$folders = User::membersWithStats($search);
$folderCount = count($folders);
$hasSearch = $search !== '';

$pageTitle = 'Image Manager';
require BASE_PATH . 'includes/header.php';
?>

<section class="page-intro">
    <p class="page-intro-text">Browse uploads by team member — each folder opens that member’s gallery.</p>
</section>

<div class="page-toolbar">
    <form class="page-search" method="get" action="" role="search">
        <i class="bi bi-search page-search-icon" aria-hidden="true"></i>
        <input type="search" name="q" class="page-search-input"
               placeholder="Search members"
               value="<?= Helper::e($search) ?>"
               aria-label="Search members">
        <?php if ($hasSearch): ?>
            <a href="<?= Helper::e(BASE_URL . 'admin/folders.php') ?>" class="page-search-clear" title="Clear search" aria-label="Clear search">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
    </form>
    <div class="page-toolbar-meta">
        <?php if ($folders !== [] || $hasSearch): ?>
            <span class="page-toolbar-count">
                <?= (int) $folderCount ?> folder<?= $folderCount === 1 ? '' : 's' ?>
            </span>
        <?php endif; ?>
        <a href="<?= Helper::e(BASE_URL . 'admin/images.php') ?>" class="text-link">All Images</a>
    </div>
</div>

<?php if ($folders === []): ?>
    <div class="empty-panel">
        <div class="empty-panel-icon" aria-hidden="true">
            <i class="bi bi-folder"></i>
        </div>
        <?php if ($hasSearch): ?>
            <h2 class="empty-panel-title">No matches</h2>
            <p class="empty-panel-text">No members match “<?= Helper::e($search) ?>”.</p>
            <a href="<?= Helper::e(BASE_URL . 'admin/folders.php') ?>" class="btn btn-primary">Clear Search</a>
        <?php else: ?>
            <h2 class="empty-panel-title">No folders yet</h2>
            <p class="empty-panel-text">Create a team member to start collecting and organizing uploads.</p>
            <a href="<?= Helper::e(BASE_URL . 'admin/members.php') ?>" class="btn btn-primary">Create Team Member</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="folder-grid">
        <?php foreach ($folders as $folder): ?>
            <a href="<?= Helper::e(BASE_URL . 'admin/member-gallery.php?id=' . (int) $folder['id']) ?>"
               class="folder-card">
                <div class="folder-card-top">
                    <img src="<?= Helper::e(Helper::profileUrl($folder['profile'])) ?>"
                         alt="" class="folder-avatar">
                    <?php if ((int) $folder['status'] !== 1): ?>
                        <span class="folder-status">Disabled</span>
                    <?php endif; ?>
                </div>
                <div class="folder-name"><?= Helper::e($folder['name']) ?></div>
                <div class="folder-meta">
                    <?= (int) $folder['total_images'] ?> image<?= (int) $folder['total_images'] === 1 ? '' : 's' ?>
                </div>
                <div class="folder-meta folder-meta-last">
                    Last upload · <?= Helper::e(Helper::relativeDate($folder['last_upload'])) ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require BASE_PATH . 'includes/footer.php'; ?>
