<?php
/**
 * File-only application logger with Laravel-style error rendering
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
        $debug = defined('APP_DEBUG') && APP_DEBUG;

        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('display_startup_errors', $debug ? '1' : '0');
        ini_set('log_errors', '0');
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

            if (defined('APP_DEBUG') && APP_DEBUG) {
                $escalate = in_array($severity, [
                    E_ERROR,
                    E_USER_ERROR,
                    E_RECOVERABLE_ERROR,
                    E_WARNING,
                    E_USER_WARNING,
                ], true);

                if ($escalate) {
                    throw new ErrorException($message, 0, $severity, $file, $line);
                }
            }

            return !in_array($severity, [E_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_PARSE], true);
        });

        set_exception_handler(static function (Throwable $e): void {
            self::exception($e);
            ErrorRenderer::renderException($e, self::exceptionStatusCode($e));
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

            if (!defined('APP_DEBUG') || !APP_DEBUG) {
                return;
            }

            $exception = new ErrorException(
                (string) $err['message'],
                0,
                (int) $err['type'],
                (string) ($err['file'] ?? ''),
                (int) ($err['line'] ?? 0)
            );
            ErrorRenderer::renderException($exception, 500);
        });
    }

    private static function exceptionStatusCode(Throwable $e): int
    {
        $code = (int) $e->getCode();
        if ($code >= 400 && $code < 600) {
            return $code;
        }

        return 500;
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
