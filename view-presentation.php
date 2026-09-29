<?php
/**
 * Authenticated inline presentation view (PDF / fallback)
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';

if (!SchemaMigrator::tableExists('presentations')) {
    http_response_code(503);
    exit('Presentations are not available until the database is migrated.');
}

$id = (int) Helper::input('id', 0);
$row = Presentation::findById($id);

if (!$row || !Presentation::userCanAccess($row)) {
    http_response_code(403);
    exit('Forbidden.');
}

Presentation::sendFile($row, true);
