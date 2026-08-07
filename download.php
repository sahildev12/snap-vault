<?php
/**
 * Secure image download
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';

$id = (int) Helper::input('id', 0);
$row = Upload::findById($id);

if (!$row) {
    http_response_code(404);
    exit('Image not found.');
}

// Members may only download their own images
if (Auth::isMember() && (int) $row['user_id'] !== (int) Auth::id()) {
    http_response_code(403);
    exit('Forbidden.');
}

$path = UPLOAD_DIR . $row['image'];
if (!is_file($path)) {
    http_response_code(404);
    exit('File missing.');
}

$mime = mime_content_type($path) ?: 'application/octet-stream';
$filename = basename($row['image']);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
