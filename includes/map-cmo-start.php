<?php
/**
 * CMO map page chrome — start
 * Expects: $cmoPageTitle, $cmoSubtitle (optional), $cmoBackUrl (optional menu target)
 */
declare(strict_types=1);

$cmoPageTitle = $cmoPageTitle ?? 'MAP';
$cmoSubtitle = $cmoSubtitle ?? '';
$cmoDashboardUrl = BlockMap::dashboardUrl();
$cmoMapUrl = BlockMap::mapUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Helper::e($cmoPageTitle) ?> · <?= Helper::e(APP_NAME) ?></title>
    <?= Csrf::meta() ?>
    <link href="<?= Helper::asset('assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= Helper::asset('assets/css/map-pages.css') ?>" rel="stylesheet">
</head>
<body class="cmo-map-body">
<div class="cmo-shell">
    <header class="cmo-topbar">
        <div class="cmo-brand">
            <div class="cmo-brand-mark" aria-hidden="true"><i class="bi bi-hospital"></i></div>
            <div class="cmo-brand-copy">
                <div class="cmo-brand-title">Chief Medical Officer, Jammu</div>
                <?php if ($cmoSubtitle !== ''): ?>
                    <span class="cmo-brand-sep" aria-hidden="true"></span>
                    <div class="cmo-brand-block"><?= Helper::e($cmoSubtitle) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="cmo-top-actions">
            <a class="cmo-btn" href="<?= Helper::e($cmoDashboardUrl) ?>">
                <i class="bi bi-house-door"></i>
                <span>Dashboard</span>
            </a>
            <a class="cmo-btn cmo-btn-icon" href="<?= Helper::e($cmoMapUrl) ?>" title="Interactive Map" aria-label="Interactive Map">
                <i class="bi bi-list"></i>
            </a>
        </div>
    </header>
