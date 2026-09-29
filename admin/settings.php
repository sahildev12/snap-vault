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
$demoCounts = DemoData::counts();
$hasDemoData = DemoData::hasDemo();
$demoColumnsReady = SchemaMigrator::tableExists('users')
    && SchemaMigrator::tableExists('uploads')
    && SchemaMigrator::tableExists('tasks');

$pageTitle = 'Settings';
require BASE_PATH . 'includes/header.php';
?>

<div class="page-toolbar">
    <div>
        <h2 class="h5 mb-1">Settings</h2>
        <p class="text-muted small mb-0">Update the server database schema in one click.</p>
    </div>
    <a href="<?= Helper::e(BASE_URL . 'migrate.php') ?>" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-database-up me-1"></i>Migration Tool
    </a>
</div>

<?php if (!SchemaMigrator::tableExists('presentations')): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        The <code>presentations</code> table is missing. Use the migration tool to apply pending updates.
        <a href="<?= Helper::e(BASE_URL . 'migrate.php') ?>" class="alert-link">Open migrate.php</a>
    </div>
<?php endif; ?>

<div class="row g-4 settings-page">
    <div class="col-lg-7 settings-stack">
        <div class="card panel-card settings-section-card">
            <div class="card-header bg-transparent border-0">
                <h3 class="h6 mb-0">Database update</h3>
            </div>
            <div class="card-body">
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

        <div class="card panel-card settings-section-card">
            <div class="card-header bg-transparent border-0">
                <h3 class="h6 mb-0">Demo data</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small settings-section-lead">
                    Load sample data across the whole system — members, images, tasks, presentations, chat, and notifications.
                    Demo records are tagged with <code>is_demo</code> so only demo data is removed when you click
                    <strong>Remove Demo Data</strong>.
                </p>

                <?php if (!$demoColumnsReady): ?>
                    <div class="alert alert-warning py-2">
                        Run <strong>Update Database</strong> first to enable system-wide demo data.
                    </div>
                <?php endif; ?>

                <div class="table-responsive settings-demo-table-wrap">
                    <table class="table table-sm align-middle settings-demo-table mb-0">
                        <thead>
                            <tr>
                                <th>Area</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Demo</th>
                                <th class="text-end">Real</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $labels = [
                                'members'       => 'Members',
                                'uploads'         => 'Images',
                                'tasks'           => 'Tasks',
                                'presentations'   => 'Presentations',
                                'messages'        => 'Chat messages',
                                'notifications'   => 'Notifications',
                            ];
                            foreach ($labels as $key => $label):
                                $bucket = $demoCounts[$key] ?? ['total' => 0, 'demo' => 0, 'real' => 0];
                            ?>
                                <tr>
                                    <td><?= Helper::e($label) ?></td>
                                    <td class="text-end"><?= (int) $bucket['total'] ?></td>
                                    <td class="text-end"><?= (int) $bucket['demo'] ?></td>
                                    <td class="text-end"><?= (int) $bucket['real'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="text-muted small mt-3 mb-0">
                    Demo login example after loading: <code>demo_rahul</code> / <code>Member@123</code>
                </p>

                <div class="settings-demo-actions d-flex flex-wrap mt-4">
                    <form method="post" action="<?= Helper::e(BASE_URL . 'admin/settings-action.php') ?>" class="settings-demo-form" data-confirm="Load full demo data now? This adds sample members, images, tasks, presentations, chat, and notifications.">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="load_demo_data">
                        <button type="submit" class="btn btn-outline-primary" <?= ($hasDemoData || !$demoColumnsReady) ? 'disabled' : '' ?>>
                            <i class="bi bi-cloud-download me-1"></i>Load Demo Data
                        </button>
                    </form>

                    <form method="post" action="<?= Helper::e(BASE_URL . 'admin/settings-action.php') ?>" class="settings-demo-form" data-confirm="Remove demo data only? Real members, uploads, tasks, and presentations will not be deleted.">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="remove_demo_data">
                        <button type="submit" class="btn btn-outline-secondary" <?= !$hasDemoData ? 'disabled' : '' ?>>
                            <i class="bi bi-eraser me-1"></i>Remove Demo Data
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card panel-card settings-section-card settings-danger-card border-danger-subtle">
            <div class="card-header bg-transparent border-0">
                <h3 class="h6 mb-0 text-danger">Danger zone</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small settings-section-lead">
                    Permanently delete <strong>all</strong> system data — every member (except admin), image, task,
                    presentation, chat message, and notification. This cannot be undone.
                </p>

                <form method="post" action="<?= Helper::e(BASE_URL . 'admin/settings-action.php') ?>" id="cleanAllDataForm" class="settings-danger-form">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="clean_all_data">
                    <div>
                        <label class="form-label" for="confirmCleanAll">Type <code>DELETE ALL</code> to confirm</label>
                        <input type="text" name="confirm_text" id="confirmCleanAll" class="form-control"
                               placeholder="DELETE ALL" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-danger" id="btnCleanAllData">
                        <i class="bi bi-trash3 me-1"></i>Clean All Data
                    </button>
                </form>
            </div>
        </div>

        <div class="card panel-card settings-section-card">
            <div class="card-header bg-transparent border-0">
                <h3 class="h6 mb-0">Migration steps</h3>
            </div>
            <div class="card-body">
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

    <div class="col-lg-5 settings-stack">
        <div class="card panel-card settings-section-card">
            <div class="card-header bg-transparent border-0">
                <h3 class="h6 mb-0">Environment</h3>
            </div>
            <div class="card-body">
                <dl class="settings-meta mb-0">
                    <div><dt>App</dt><dd><?= Helper::e(APP_NAME) ?> v<?= Helper::e(APP_VERSION) ?></dd></div>
                    <div><dt>Environment</dt><dd class="text-capitalize"><?= Helper::e(APP_ENV) ?></dd></div>
                    <div><dt>Debug mode</dt><dd><?= APP_DEBUG ? 'On (detailed errors)' : 'Off (safe pages)' ?></dd></div>
                    <div><dt>Database</dt><dd><code><?= Helper::e(DB_NAME) ?></code></dd></div>
                    <div><dt>Host</dt><dd><code><?= Helper::e(DB_HOST) ?></code></dd></div>
                </dl>
            </div>
        </div>

        <div class="card panel-card settings-section-card">
            <div class="card-header bg-transparent border-0">
                <h3 class="h6 mb-0">Tables</h3>
            </div>
            <div class="card-body">
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

document.querySelectorAll(".settings-demo-form").forEach((form) => {
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const message = form.dataset.confirm || "Continue?";
        const ok = await (window.SnapVault?.confirm
            ? SnapVault.confirm(message)
            : Promise.resolve(window.confirm(message)));
        if (!ok) return;
        form.submit();
    });
});

document.getElementById("cleanAllDataForm")?.addEventListener("submit", async (e) => {
    e.preventDefault();
    const form = e.target;
    const input = document.getElementById("confirmCleanAll");
    if (!input || input.value.trim() !== "DELETE ALL") {
        window.SnapVault?.toast("Type DELETE ALL to confirm.", "danger");
        return;
    }

    const ok = await (window.SnapVault?.confirm
        ? SnapVault.confirm("This will permanently delete ALL system data (members, images, tasks, presentations, chat, notifications). Admin account is kept. Are you absolutely sure?")
        : Promise.resolve(window.confirm("This will permanently delete ALL system data. Admin account is kept. Are you absolutely sure?")));
    if (!ok) return;

    const btn = document.getElementById("btnCleanAllData");
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = \'<span class="spinner-border spinner-border-sm me-1"></span>Deleting…\';
    }
    form.submit();
});
</script>';
require BASE_PATH . 'includes/footer.php';
?>
