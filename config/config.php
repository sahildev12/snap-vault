<?php
/**
 * Jammu First application configuration
 * Auto-detects local (XAMPP) vs production (server) database settings.
 */

declare(strict_types=1);

// Absolute filesystem path to project root
define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

/**
 * Detect environment: local vs production
 * - Local: localhost / 127.0.0.1 / *.local / *.test / CLI without SNAPVAULT_ENV=production
 * - Production: any other host
 */
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));

$isLocal = (
    $host === ''
    || $host === 'localhost'
    || str_starts_with($host, 'localhost:')
    || $host === '127.0.0.1'
    || str_starts_with($host, '127.0.0.1:')
    || $host === '::1'
    || str_ends_with($host, '.local')
    || str_ends_with($host, '.test')
    || str_ends_with($host, '.localhost')
);

// CLI scripts (seed, migrate): default local; force production with SNAPVAULT_ENV=production
if (PHP_SAPI === 'cli') {
    $cliEnv = strtolower((string) (getenv('SNAPVAULT_ENV') ?: 'local'));
    $isLocal = ($cliEnv !== 'production');
}

// Optional override: set SNAPVAULT_ENV=local|production in server env / .htaccess
$forcedEnv = strtolower((string) (getenv('SNAPVAULT_ENV') ?: ''));
if ($forcedEnv === 'local') {
    $isLocal = true;
} elseif ($forcedEnv === 'production') {
    $isLocal = false;
}

define('APP_ENV', $isLocal ? 'local' : 'production');
define('IS_LOCAL', $isLocal);

/**
 * Web base URL — prefer path relative to document root when available
 */
$detectBaseUrl = static function () use ($isLocal): string {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
    $appRoot = realpath(dirname(__DIR__));

    if ($docRoot && $appRoot && str_starts_with($appRoot, $docRoot)) {
        $rel = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
        $rel = '/' . trim($rel, '/');
        return $rel === '/' ? '/' : $rel . '/';
    }

    return $isLocal ? '/snap-vault/' : '/';
};

define('BASE_URL', $detectBaseUrl());

define('APP_NAME', 'Jammu First');
define('APP_TAGLINE', 'Chief Medical Office - Jammu');
define('APP_VERSION', '1.2.0');

// Database — local XAMPP vs production Hostinger
if (IS_LOCAL) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'snap_vault');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u456613426_snapvault');
    define('DB_USER', 'u456613426_snapvault');
    define('DB_PASS', '?E>9d!&Gc3N');
}

define('DB_CHARSET', 'utf8mb4');

// File-only error log (never shown in the UI). Path: storage/logs/app-YYYY-MM-DD.log
define('LOG_DIR', BASE_PATH . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR);
define('LOG_ERRORS', true);

// Session
define('SESSION_NAME', 'snapvault_session');

// Uploads
define('UPLOAD_DIR', BASE_PATH . 'uploads' . DIRECTORY_SEPARATOR);
define('UPLOAD_URL', BASE_URL . 'uploads/');
define('PROFILE_DIR', UPLOAD_DIR . 'profiles' . DIRECTORY_SEPARATOR);
define('PROFILE_URL', UPLOAD_URL . 'profiles/');
define('TASK_UPLOAD_DIR', UPLOAD_DIR . 'tasks' . DIRECTORY_SEPARATOR);
define('TASK_UPLOAD_URL', UPLOAD_URL . 'tasks/');
define('UPLOAD_MAX_BYTES', 10 * 1024 * 1024); // 10MB

define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/webp',
]);

/** @var list<string> */
define('UPLOAD_FLAGS', ['normal', 'important', 'urgent', 'confidential']);

// Interactive map HTML entry (drop client files into /map/)
define('MAP_ENTRY', 'index.html');
define('MAP_DIR', BASE_PATH . 'map' . DIRECTORY_SEPARATOR);

// Webhooks (leave WEBHOOK_URL empty to disable outbound)
define('WEBHOOK_URL', getenv('JAMMU_WEBHOOK_URL') ?: '');
define('WEBHOOK_SECRET', getenv('JAMMU_WEBHOOK_SECRET') ?: 'jammu-first-webhook-secret');

// Chat polling
define('CHAT_POLL_SECONDS', 3);

// Pagination
define('PER_PAGE', 12);
define('MEMBERS_PER_PAGE', 10);
define('TASKS_PER_PAGE', 15);

/**
 * Settings (admin DB updater)
 * true  = hide Settings in the sidebar (and block the page)
 * false = show Settings for admins
 */
define('HIDE_SETTINGS_BUTTON', false);

// Timezone
date_default_timezone_set('Asia/Kolkata');
