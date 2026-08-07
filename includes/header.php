<?php
/**
 * Shared page header
 * Expects optional: $pageTitle, $bodyClass, $hideSidebar
 */
declare(strict_types=1);

$currentUser = Auth::user();
$pageTitle = $pageTitle ?? APP_NAME;
$hideSidebar = $hideSidebar ?? false;
$bodyClass = $bodyClass ?? '';
$flash = Helper::getFlash();

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helper::e($pageTitle) ?> · <?= Helper::e(APP_NAME) ?></title>
    <?= Csrf::meta() ?>
    <link href="<?= Helper::asset('assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= Helper::asset('assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= Helper::asset('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="<?= Helper::e($bodyClass) ?><?= $hideSidebar ? ' auth-body' : ' app-body' ?>">
<?php if (!$hideSidebar): ?>
<div class="app-shell">
    <?php require BASE_PATH . 'includes/sidebar.php'; ?>
    <div class="app-main">
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <button type="button" class="btn btn-icon d-lg-none" id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div class="top-navbar-titles min-w-0">
                    <div class="org-line"><?= Helper::e(APP_TAGLINE) ?></div>
                    <h1 class="page-heading mb-0"><?= Helper::e($pageTitle) ?></h1>
                </div>
            </div>
            <div class="top-navbar-user d-flex align-items-center gap-2">
                <div class="notif-wrap dropdown">
                    <button type="button"
                            class="btn btn-icon position-relative"
                            id="notifNavBtn"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            aria-expanded="false"
                            title="Notifications"
                            aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="nav-unread-badge d-none" id="notifUnreadBadge">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notif-dropdown" aria-labelledby="notifNavBtn">
                        <div class="notif-dropdown-head">
                            <strong>Notifications</strong>
                            <button type="button" class="btn btn-sm btn-link notif-mark-all" id="notifMarkAll">Mark all read</button>
                        </div>
                        <div class="notif-dropdown-list" id="notifList">
                            <div class="notif-empty text-muted small">Loading…</div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <main class="app-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= Helper::e($flash['type']) ?> alert-dismissible fade show js-auto-alert" role="alert">
                    <?= Helper::e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
<?php else: ?>
    <?php if ($flash): ?>
        <div class="flash-toast-wrap">
            <div class="alert alert-<?= Helper::e($flash['type']) ?> alert-dismissible fade show js-auto-alert" role="alert">
                <?= Helper::e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
