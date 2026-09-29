<?php
/**
 * Role-aware sidebar — Hostinger-inspired layout
 */
declare(strict_types=1);

$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
$role = Auth::role();

if (!function_exists('nav_active')) {
    function nav_active(string $script, array $matches): string
    {
        return in_array($script, $matches, true) ? ' active' : '';
    }
}
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <div class="brand-mark"><i class="bi bi-hospital"></i></div>
        <div class="brand-copy">
            <div class="brand-name"><?= Helper::e(APP_NAME) ?></div>
            <div class="brand-tagline"><?= Helper::e(APP_TAGLINE) ?></div>
        </div>
    </div>

    <nav class="sidebar-nav" id="sidebarNav">
        <?php if ($role === 'admin'): ?>
            <div class="nav-primary">
                <a class="nav-link<?= nav_active($script, ['dashboard.php']) ?>" href="<?= Helper::e(BASE_URL . 'admin/dashboard.php') ?>">
                    <i class="bi bi-house"></i>
                    <span>Home</span>
                </a>
                <a class="nav-link<?= nav_active($script, ['folders.php', 'member-gallery.php']) ?>" href="<?= Helper::e(BASE_URL . 'admin/folders.php') ?>">
                    <i class="bi bi-folder"></i>
                    <span>Image Manager</span>
                </a>
                <a class="nav-link<?= nav_active($script, ['members.php', 'member-form.php']) ?>" href="<?= Helper::e(BASE_URL . 'admin/members.php') ?>">
                    <i class="bi bi-people"></i>
                    <span>Members</span>
                </a>
                <a class="nav-link<?= nav_active($script, ['tasks.php']) ?>" href="<?= Helper::e(BASE_URL . 'admin/tasks.php') ?>">
                    <i class="bi bi-check2-square"></i>
                    <span>Task</span>
                </a>
                <a class="nav-link<?= nav_active($script, ['chat.php']) ?>" href="<?= Helper::e(BASE_URL . 'admin/chat.php') ?>">
                    <i class="bi bi-chat-dots"></i>
                    <span>Chat</span>
                </a>
                <a class="nav-link<?= nav_active($script, ['presentations.php']) ?>" href="<?= Helper::e(BASE_URL . 'admin/presentations.php') ?>">
                    <i class="bi bi-easel"></i>
                    <span>Presentations</span>
                </a>
                <a class="nav-link<?= nav_active($script, ['map.php', 'map-block.php', 'map-phc.php']) ?>" href="<?= Helper::e(BASE_URL . 'admin/map.php') ?>">
                    <i class="bi bi-map"></i>
                    <span>MAP</span>
                </a>
                <?php if (!HIDE_SETTINGS_BUTTON): ?>
                    <a class="nav-link<?= nav_active($script, ['settings.php']) ?>" href="<?= Helper::e(BASE_URL . 'admin/settings.php') ?>">
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                    </a>
                <?php endif; ?>
            </div>

            <div class="nav-group is-open" data-group="library">
                <button type="button" class="nav-group-toggle" aria-expanded="true">
                    <span class="nav-group-label">Library</span>
                    <i class="bi bi-chevron-down nav-group-chevron" aria-hidden="true"></i>
                </button>
                <div class="nav-group-body">
                    <a class="nav-link<?= nav_active($script, ['images.php']) ?>" href="<?= Helper::e(BASE_URL . 'admin/images.php') ?>">
                        <i class="bi bi-images"></i>
                        <span>All Images</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="nav-primary">
                <a class="nav-link<?= nav_active($script, ['dashboard.php']) ?>" href="<?= Helper::e(BASE_URL . 'member/dashboard.php') ?>">
                    <i class="bi bi-house"></i>
                    <span>Home</span>
                </a>
                <a class="nav-link<?= nav_active($script, ['upload.php']) ?>" href="<?= Helper::e(BASE_URL . 'member/upload.php') ?>">
                    <i class="bi bi-upload"></i>
                    <span>Upload</span>
                </a>
                <a class="nav-link<?= nav_active($script, ['gallery.php']) ?>" href="<?= Helper::e(BASE_URL . 'member/gallery.php') ?>">
                    <i class="bi bi-images"></i>
                    <span>Gallery</span>
                </a>
                <a class="nav-link<?= nav_active($script, ['tasks.php', 'task-view.php']) ?>" href="<?= Helper::e(BASE_URL . 'member/tasks.php') ?>">
                    <i class="bi bi-check2-square"></i>
                    <span>Task</span>
                </a>
                <a class="nav-link<?= nav_active($script, ['chat.php']) ?>" href="<?= Helper::e(BASE_URL . 'member/chat.php') ?>">
                    <i class="bi bi-chat-dots"></i>
                    <span>Chat</span>
                </a>
                <a class="nav-link<?= nav_active($script, ['presentations.php']) ?>" href="<?= Helper::e(BASE_URL . 'member/presentations.php') ?>">
                    <i class="bi bi-easel"></i>
                    <span>Presentations</span>
                </a>
                <a class="nav-link<?= nav_active($script, ['map.php', 'map-block.php', 'map-phc.php']) ?>" href="<?= Helper::e(BASE_URL . 'member/map.php') ?>">
                    <i class="bi bi-map"></i>
                    <span>MAP</span>
                </a>
            </div>
        <?php endif; ?>
    </nav>

    <?php $sidebarUser = Auth::user(); ?>
    <div class="sidebar-footer">
        <div class="sidebar-user-box">
            <img src="<?= Helper::e(Helper::profileUrl($sidebarUser['profile'] ?? null)) ?>"
                 alt="" class="avatar-sm sidebar-user-avatar">
            <div class="sidebar-user-meta min-w-0">
                <div class="sidebar-user-name"><?= Helper::e($sidebarUser['name'] ?? '') ?></div>
                <div class="sidebar-user-role text-capitalize"><?= Helper::e($sidebarUser['role'] ?? '') ?></div>
            </div>
            <a href="<?= Helper::e(BASE_URL . 'auth/logout.php') ?>"
               class="btn btn-icon sidebar-logout-btn"
               title="Logout"
               aria-label="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
        <small class="sidebar-version"><?= Helper::e(APP_NAME) ?> · v<?= Helper::e(APP_VERSION) ?></small>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
