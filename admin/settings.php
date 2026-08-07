<?php
/**
 * Admin — Settings (one-click database update)
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';
$requiredRole = 'admin';
require BASE_PATH . 'includes/role_check.php';

if (HIDE_SETTINGS_BUTTON) {
    Helper::setFlash('danger', 'Settings are disabled.');
    Helper::redirect(BASE_URL . 'admin/dashboard.php');
}

$migrationStatus = SchemaMigrator::status();
$tables = SchemaMigrator::tablePresence();
$pending = array_values(array_filter($migrationStatus, static fn(array $m): bool => !$m['applied']));
$allTablesOk = !in_array(false, $tables, true);

$pageTitle = 'Settings';
require BASE_PATH . 'includes/header.php';
?>

<div class="page-toolbar">
    <div>
        <h2 class="h5 mb-1">Settings</h2>
        <p class="text-muted small mb-0">Update the server database schema in one click.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card panel-card mb-3">
            <div class="card-header bg-transparent border-0">
                <h3 class="h6 mb-0">Database update</h3>
            </div>
            <div class="card-body pt-0">
                <p class="text-muted small">
                    Applies pending Jammu First schema changes (tasks, chat, notifications, upload flags)
                    to <strong><?= Helper::e(DB_NAME) ?></strong> on this server.
                    Already-applied steps are skipped automatically.
                </p>

                <?php if ($pending === []): ?>
                    <div class="alert alert-success mb-3 py-2">
                        <i class="bi bi-check-circle me-1"></i> Schema is up to date.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-3 py-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <?= count($pending) ?> pending update<?= count($pending) === 1 ? '' : 's' ?>.
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= Helper::e(BASE_URL . 'admin/settings-action.php') ?>" id="dbUpdateForm">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="update_database">
                    <button type="submit"
                            class="btn btn-primary"
                            id="btnUpdateDb"
                            <?= $pending === [] ? 'disabled' : '' ?>>
                        <i class="bi bi-database-up me-1"></i>
                        Update Database
                    </button>
                </form>
            </div>
        </div>

        <div class="card panel-card">
            <div class="card-header bg-transparent border-0">
                <h3 class="h6 mb-0">Migration steps</h3>
            </div>
            <div class="card-body pt-0">
                <ul class="list-unstyled settings-migration-list mb-0">
                    <?php foreach ($migrationStatus as $step): ?>
                        <li class="settings-migration-item">
                            <?php if ($step['applied']): ?>
                                <i class="bi bi-check-circle-fill text-success"></i>
                            <?php else: ?>
                                <i class="bi bi-circle text-muted"></i>
                            <?php endif; ?>
                            <span><?= Helper::e($step['label']) ?></span>
                            <span class="badge <?= $step['applied'] ? 'badge-normal' : 'bg-warning-subtle text-warning-emphasis' ?>">
                                <?= $step['applied'] ? 'Applied' : 'Pending' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card panel-card mb-3">
            <div class="card-header bg-transparent border-0">
                <h3 class="h6 mb-0">Environment</h3>
            </div>
            <div class="card-body pt-0">
                <dl class="settings-meta mb-0">
                    <div><dt>App</dt><dd><?= Helper::e(APP_NAME) ?> v<?= Helper::e(APP_VERSION) ?></dd></div>
                    <div><dt>Environment</dt><dd class="text-capitalize"><?= Helper::e(APP_ENV) ?></dd></div>
                    <div><dt>Database</dt><dd><code><?= Helper::e(DB_NAME) ?></code></dd></div>
                    <div><dt>Host</dt><dd><code><?= Helper::e(DB_HOST) ?></code></dd></div>
                </dl>
            </div>
        </div>

        <div class="card panel-card">
            <div class="card-header bg-transparent border-0">
                <h3 class="h6 mb-0">Tables</h3>
            </div>
            <div class="card-body pt-0">
                <?php if ($allTablesOk): ?>
                    <p class="small text-success mb-2"><i class="bi bi-check-circle me-1"></i>All expected tables present.</p>
                <?php else: ?>
                    <p class="small text-warning mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Some tables are missing — run Update Database.</p>
                <?php endif; ?>
                <ul class="list-unstyled settings-table-list mb-0">
                    <?php foreach ($tables as $name => $exists): ?>
                        <li>
                            <?php if ($exists): ?>
                                <i class="bi bi-check-lg text-success"></i>
                            <?php else: ?>
                                <i class="bi bi-x-lg text-danger"></i>
                            <?php endif; ?>
                            <code><?= Helper::e($name) ?></code>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script>
document.getElementById("dbUpdateForm")?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const form = e.target;
    const ok = await (window.SnapVault?.confirm
        ? SnapVault.confirm("Update the database schema on this server now?")
        : Promise.resolve(window.confirm("Update the database schema on this server now?")));
    if (!ok) return;
    const btn = document.getElementById("btnUpdateDb");
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = \'<span class="spinner-border spinner-border-sm me-1"></span>Updating…\';
    }
    form.submit();
});
</script>';
require BASE_PATH . 'includes/footer.php';
?>
