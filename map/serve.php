<?php
/**
 * Auth-gated map asset server (keeps /map HTML private)
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once BASE_PATH . 'includes/auth_check.php';

$rel = (string) ($_GET['f'] ?? MAP_ENTRY);
$rel = str_replace(['\\', "\0"], ['/', ''], $rel);
$rel = ltrim($rel, '/');
if ($rel === '' || str_contains($rel, '..')) {
    http_response_code(400);
    exit('Bad request');
}

$full = realpath(MAP_DIR . $rel);
$root = realpath(MAP_DIR);
if ($full === false || $root === false || !str_starts_with($full, $root) || !is_file($full)) {
    http_response_code(404);
    exit('Not found');
}

$ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
$types = [
    'html' => 'text/html; charset=utf-8',
    'htm'  => 'text/html; charset=utf-8',
    'css'  => 'text/css; charset=utf-8',
    'js'   => 'application/javascript; charset=utf-8',
    'json' => 'application/json; charset=utf-8',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'svg'  => 'image/svg+xml',
    'webp' => 'image/webp',
    'woff' => 'font/woff',
    'woff2'=> 'font/woff2',
    'ttf'  => 'font/ttf',
    'map'  => 'application/json',
];
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('X-Content-Type-Options: nosniff');
readfile($full);
exit;
