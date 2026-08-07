<?php
/**
 * File-only application logger (never printed to the browser)
 */
declare(strict_types=1);

class AppLog
{
    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function exception(Throwable $e, string $prefix = 'Uncaught exception'): void
    {
        self::write('ERROR', $prefix . ': ' . $e->getMessage(), [
            'type'  => $e::class,
            'file'  => $e->getFile() . ':' . $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    public static function registerHandlers(): void
    {
        // Never leak errors to the panel / browser
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '0'); // we handle logging ourselves
        error_reporting(E_ALL);

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            $label = match ($severity) {
                E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR => 'ERROR',
                E_WARNING, E_USER_WARNING => 'WARNING',
                E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED => 'NOTICE',
                default => 'ERROR',
            };
            self::write($label, $message, ['file' => $file . ':' . $line, 'severity' => $severity]);
            // Fatal-class errors should still escalate
            return in_array($severity, [E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_PARSE], true) ? false : true;
        });

        set_exception_handler(static function (Throwable $e): void {
            self::exception($e);
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=UTF-8');
            }
            echo 'Something went wrong. Please try again later.';
            exit(1);
        });

        register_shutdown_function(static function (): void {
            $err = error_get_last();
            if ($err === null) {
                return;
            }
            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
            if (!in_array($err['type'], $fatalTypes, true)) {
                return;
            }
            self::write('FATAL', (string) $err['message'], [
                'file' => ($err['file'] ?? '') . ':' . ($err['line'] ?? 0),
            ]);
        });
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        if (!defined('LOG_ERRORS') || !LOG_ERRORS) {
            return;
        }

        $dir = defined('LOG_DIR') ? LOG_DIR : (dirname(__DIR__) . '/storage/logs/');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $line = sprintf(
            "[%s] %s %s",
            date('Y-m-d H:i:s'),
            $level,
            $message
        );

        if ($context !== []) {
            $encoded = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded !== false) {
                $line .= ' ' . $encoded;
            }
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? '');
        if ($uri !== '') {
            $line .= ' {' . $method . ' ' . $uri . '}';
        }

        $line .= PHP_EOL;
        @file_put_contents($dir . 'app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
