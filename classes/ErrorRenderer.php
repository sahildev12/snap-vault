<?php
/**
 * Laravel-style exception and error pages
 */
declare(strict_types=1);

class ErrorRenderer
{
    public static function isDebug(): bool
    {
        return defined('APP_DEBUG') && APP_DEBUG;
    }

    public static function wantsJson(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $xhr = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

        return str_contains($accept, 'application/json')
            || $xhr === 'xmlhttprequest'
            || str_contains($uri, '/ajax/');
    }

    public static function renderException(Throwable $e, int $httpCode = 500): never
    {
        if (!headers_sent()) {
            http_response_code($httpCode);
        }

        if (self::wantsJson()) {
            self::renderJson($e, $httpCode);
        }

        if (self::isDebug()) {
            self::renderDebug($e, $httpCode);
        }

        self::renderGeneric(
            $httpCode,
            self::httpTitle($httpCode),
            'Something went wrong on our servers. The error has been logged and we are looking into it.'
        );
    }

    public static function renderGeneric(int $httpCode, string $title, string $message): never
    {
        if (!headers_sent()) {
            http_response_code($httpCode);
            header('Content-Type: text/html; charset=UTF-8');
        }

        $appName = defined('APP_NAME') ? APP_NAME : 'Application';
        $homeUrl = defined('BASE_URL') ? BASE_URL : '/';

        require BASE_PATH . 'includes/errors/generic.php';
        exit(1);
    }

    /** @return list<array{class:string,file:string,line:int,call:string}> */
    public static function traceFrames(Throwable $e): array
    {
        $frames = [];
        foreach ($e->getTrace() as $frame) {
            $class = '';
            if (!empty($frame['class'])) {
                $class = $frame['class'] . ($frame['type'] ?? '::');
            }
            $call = $class . ($frame['function'] ?? '');
            $file = (string) ($frame['file'] ?? '[internal]');
            $line = (int) ($frame['line'] ?? 0);
            $frames[] = [
                'class' => $class,
                'file'  => $file,
                'line'  => $line,
                'call'  => $call !== '' ? $call : '[internal]',
            ];
        }

        return $frames;
    }

    public static function highlightFile(string $file, int $line, int $padding = 6): string
    {
        if (!is_file($file) || !is_readable($file)) {
            return '';
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return '';
        }

        $start = max(1, $line - $padding);
        $end = min(count($lines), $line + $padding);
        $out = '';

        for ($i = $start; $i <= $end; $i++) {
            $text = htmlspecialchars((string) $lines[$i - 1], ENT_QUOTES, 'UTF-8');
            $isTarget = $i === $line;
            $out .= '<div class="err-line' . ($isTarget ? ' is-target' : '') . '">';
            $out .= '<span class="err-line-no">' . $i . '</span>';
            $out .= '<code class="err-line-code">' . $text . '</code>';
            $out .= '</div>';
        }

        return $out;
    }

    private static function renderDebug(Throwable $e, int $httpCode): never
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        $exception = $e;
        $statusCode = $httpCode;
        $httpTitle = self::httpTitle($httpCode);
        $frames = self::traceFrames($e);
        $snippet = self::highlightFile($e->getFile(), $e->getLine());

        require BASE_PATH . 'includes/errors/debug.php';
        exit(1);
    }

    private static function renderJson(Throwable $e, int $httpCode): never
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }

        $payload = [
            'success' => false,
            'message' => self::isDebug()
                ? $e->getMessage()
                : 'Something went wrong. Please try again later.',
            'code'    => $httpCode,
        ];

        if (self::isDebug()) {
            $payload['error'] = [
                'type'    => $e::class,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => self::traceFrames($e),
            ];
        }

        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit(1);
    }

    public static function httpTitle(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            419 => 'Page Expired',
            429 => 'Too Many Requests',
            500 => 'Server Error',
            503 => 'Service Unavailable',
            default => 'Error',
        };
    }
}
