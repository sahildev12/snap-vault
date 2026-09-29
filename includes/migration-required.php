<?php
/**
 * Shown when a feature needs a pending database migration.
 * Expects: $featureLabel (string)
 */
declare(strict_types=1);

$featureLabel = $featureLabel ?? 'This feature';
$pending = SchemaMigrator::pendingCount();
$migrateUrl = BASE_URL . 'migrate.php';
$settingsUrl = BASE_URL . 'admin/settings.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card panel-card border-warning-subtle">
            <div class="card-body p-4 p-md-5 text-center">
                <div class="display-6 text-warning mb-3"><i class="bi bi-database-exclamation"></i></div>
                <h2 class="h4 mb-2">Database update required</h2>
                <p class="text-muted mb-4">
                    <?= Helper::e($featureLabel) ?> needs the latest database schema.
                    <?php if ($pending > 0): ?>
                        <strong><?= (int) $pending ?></strong> migration step<?= $pending === 1 ? '' : 's' ?> are pending.
                    <?php else: ?>
                        The required tables may still be missing on this server.
                    <?php endif; ?>
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="<?= Helper::e($migrateUrl) ?>" class="btn btn-primary">
                        <i class="bi bi-database-up me-1"></i>Open Migration Tool
                    </a>
                    <a href="<?= Helper::e($settingsUrl) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-gear me-1"></i>Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
