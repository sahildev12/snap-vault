<?php
/**
 * Application bootstrap
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

// Simple class autoloader for /classes
spl_autoload_register(static function (string $class): void {
    $path = BASE_PATH . 'classes' . DIRECTORY_SEPARATOR . $class . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

AppLog::registerHandlers();

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

// Ensure runtime directories exist
foreach ([UPLOAD_DIR, PROFILE_DIR, TASK_UPLOAD_DIR, MAP_DIR, LOG_DIR] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}
