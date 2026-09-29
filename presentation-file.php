<?php
/**
 * Token-based presentation file access for in-browser PPT preview (Office Online embed)
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
    header('Access-Control-Max-Age: 86400');
    exit;
}

if (!SchemaMigrator::tableExists('presentations')) {
    http_response_code(503);
    exit('Not available.');
}

$token = (string) Helper::input('token', '');
$id = Presentation::validateViewToken($token);
if ($id === null) {
    http_response_code(403);
    exit('Invalid or expired link.');
}

$row = Presentation::findById($id);
if (!$row) {
    http_response_code(404);
    exit('Not found.');
}

Presentation::sendFile($row, true, true);
