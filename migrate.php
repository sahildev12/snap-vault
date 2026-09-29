<?php
/**
 * Standalone database migration runner.
 *
 * Web:  /migrate.php  (admin login required)
 * CLI:  php migrate.php
 *       php migrate.php run
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$isCli = PHP_SAPI === 'cli';
$autoRun = $isCli && in_array('run', $argv ?? [], true);

if ($isCli) {
    $result = SchemaMigrator::run();
    foreach ($result['messages'] as $message) {
        echo $message . PHP_EOL;
    }
    echo PHP_EOL . ($result['ok'] ? 'Migration finished successfully.' : 'Migration failed.') . PHP_EOL;
    exit($result['ok'] ? 0 : 1);
}

require_once BASE_PATH . 'includes/auth_check.php';
if (!Auth::isAdmin()) {
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Migration</title></head><body>';
    echo '<p>Admin login required. <a href="' . htmlspecialchars(BASE_URL . 'auth/login.php', ENT_QUOTES, 'UTF-8') . '">Sign in</a> first, then open this page again.</p>';
    echo '</body></html>';
    exit;
}

$messages = [];
$ran = false;
$runOk = true;

if (Helper::isPost()) {
    Csrf::requireValid();
    $action = (string) Helper::input('action', '');
    if ($action === 'run_migrations') {
        $result = SchemaMigrator::run();
        $messages = $result['messages'];
        $ran = true;
        $runOk = $result['ok'];
    }
}

$migrationStatus = SchemaMigrator::status();
$tables = SchemaMigrator::tablePresence();
$pending = array_values(array_filter($migrationStatus, static fn(array $m): bool => !$m['applied']));
$pendingCount = count($pending);
$allTablesOk = !in_array(false, $tables, true);

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration · <?= Helper::e(APP_NAME) ?></title>
    <?= Csrf::meta() ?>
    <link href="<?= Helper::asset('assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= Helper::asset('assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= Helper::asset('assets/css/app.css') ?>" rel="stylesheet">
    <style>
        body { background: #f4f5f7; }
        .migrate-wrap { max-width: 900px; margin: 40px auto; padding: 0 16px; }
        .migrate-card { background: #fff; border: 1px solid rgba(0,0,0,.08); border-radius: 16px; padding: 24px; margin-bottom: 16px; }
        .migrate-list li { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid rgba(0,0,0,.06); }
        .migrate-list li:last-child { border-bottom: 0; }
        .migrate-list .badge { margin-left: auto; }
    </style>
</head>
<body>
<div class="migrate-wrap">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Database Migration</h1>
            <p class="text-muted mb-0">Apply pending schema updates for <?= Helper::e(APP_NAME) ?>.</p>
        </div>
        <a href="<?= Helper::e(BASE_URL . 'admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">Back to dashboard</a>
    </div>

    <?php if ($ran): ?>
        <div class="alert <?= ($runOk ?? false) ? 'alert-success' : 'alert-danger' ?>">
            <?php foreach ($messages as $message): ?>
                <div><?= Helper::e($message) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="migrate-card">
        <h2 class="h5">Environment</h2>
        <dl class="row mb-0 small">
            <dt class="col-sm-3">Database</dt><dd class="col-sm-9"><code><?= Helper::e(DB_NAME) ?></code></dd>
            <dt class="col-sm-3">Environment</dt><dd class="col-sm-9 text-capitalize"><?= Helper::e(APP_ENV) ?></dd>
            <dt class="col-sm-3">Debug</dt><dd class="col-sm-9"><?= APP_DEBUG ? 'On (detailed errors)' : 'Off (safe pages)' ?></dd>
            <dt class="col-sm-3">Pending steps</dt><dd class="col-sm-9"><?= (int) $pendingCount ?></dd>
        </dl>
    </div>

    <div class="migrate-card">
        <h2 class="h5">Run migration</h2>
        <p class="text-muted small">
            Safe to run multiple times. Already-applied steps are skipped automatically.
        </p>

        <?php if ($pendingCount === 0 && $allTablesOk): ?>
            <div class="alert alert-success py-2 mb-3">
                <i class="bi bi-check-circle me-1"></i>Database is up to date.
            </div>
        <?php elseif ($pendingCount > 0): ?>
            <div class="alert alert-warning py-2 mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <?= (int) $pendingCount ?> pending update<?= $pendingCount === 1 ? '' : 's' ?>.
            </div>
        <?php else: ?>
            <div class="alert alert-warning py-2 mb-3">
                <i class="bi bi-exclamation-triangle me-1"></i>Some tables are still missing.
            </div>
        <?php endif; ?>

        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="run_migrations">
            <button type="submit" class="btn btn-primary" <?= ($pendingCount === 0 && $allTablesOk) ? 'disabled' : '' ?>>
                <i class="bi bi-database-up me-1"></i>Update Database
            </button>
        </form>
    </div>

    <div class="migrate-card">
        <h2 class="h5">Migration steps</h2>
        <ul class="list-unstyled migrate-list mb-0">
            <?php foreach ($migrationStatus as $step): ?>
                <li>
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

    <div class="migrate-card">
        <h2 class="h5">Tables</h2>
        <ul class="list-unstyled migrate-list mb-0">
            <?php foreach ($tables as $name => $exists): ?>
                <li>
                    <?php if ($exists): ?>
                        <i class="bi bi-check-lg text-success"></i>
                    <?php else: ?>
                        <i class="bi bi-x-lg text-danger"></i>
                    <?php endif; ?>
                    <code><?= Helper::e($name) ?></code>
                    <span class="badge <?= $exists ? 'badge-normal' : 'bg-danger-subtle text-danger-emphasis' ?>">
                        <?= $exists ? 'Present' : 'Missing' ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
</body>
</html>
