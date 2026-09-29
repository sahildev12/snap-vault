<?php
/**
 * Seed full demo data from CLI.
 * Run: php database/seed_demo.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$result = SchemaMigrator::run();
if (!$result['ok']) {
    fwrite(STDERR, 'Migration failed. Fix database schema first.' . PHP_EOL);
    exit(1);
}

$result = DemoData::seedAll();
echo $result['message'] . PHP_EOL;
exit($result['ok'] ? 0 : 1);
