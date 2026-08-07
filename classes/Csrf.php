<?php
/**
 * CSRF token protection
 */

declare(strict_types=1);

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    public static function field(): string
    {
        $token = self::token();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function meta(): string
    {
        return '<meta name="csrf-token" content="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validate(?string $token): bool
    {
        if ($token === null || $token === '' || empty($_SESSION[self::TOKEN_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::TOKEN_KEY], $token);
    }

    public static function requireValid(?string $token = null): void
    {
        $token = $token ?? ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!self::validate($token)) {
            if (self::wantsJson()) {
                Helper::jsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
            }
            Helper::setFlash('danger', 'Invalid security token. Please try again.');
            Helper::redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL);
        }
    }

    private static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return str_contains($accept, 'application/json')
            || strcasecmp($xhr, 'XMLHttpRequest') === 0;
    }
}
