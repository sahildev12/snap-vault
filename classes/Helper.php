<?php
/**
 * Shared helpers
 */

declare(strict_types=1);

class Helper
{
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function getFlash(): ?array
    {
        if (!isset($_SESSION['flash'])) {
            return null;
        }
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    public static function asset(string $path): string
    {
        $rel = ltrim(str_replace('\\', '/', $path), '/');
        $url = BASE_URL . $rel;
        $full = BASE_PATH . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $ver = defined('APP_VERSION') ? (string) APP_VERSION : '1';
        if (is_file($full)) {
            $ver .= '.' . (string) filemtime($full);
        }
        return $url . '?v=' . rawurlencode($ver);
    }

    public static function uploadUrl(string $filename): string
    {
        return UPLOAD_URL . ltrim($filename, '/');
    }

    public static function profileUrl(?string $filename): string
    {
        if ($filename === null || $filename === '') {
            return BASE_URL . 'assets/images/avatar-placeholder.svg';
        }
        return PROFILE_URL . ltrim($filename, '/');
    }

    public static function relativeDate(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return 'No uploads yet';
        }

        $ts = strtotime($datetime);
        if ($ts === false) {
            return $datetime;
        }

        $today = strtotime('today');
        $yesterday = strtotime('yesterday');

        if ($ts >= $today) {
            return 'Today';
        }
        if ($ts >= $yesterday) {
            return 'Yesterday';
        }

        return date('d M Y', $ts);
    }

    public static function formatDate(?string $datetime, string $format = 'd M Y, h:i A'): string
    {
        if ($datetime === null || $datetime === '') {
            return '—';
        }
        $ts = strtotime($datetime);
        return $ts === false ? $datetime : date($format, $ts);
    }

    public static function flagBadge(string $flag): string
    {
        $labels = [
            'important'    => ['Important', 'badge-important'],
            'urgent'       => ['Urgent', 'badge-urgent'],
            'confidential' => ['Confidential', 'badge-confidential'],
            'normal'       => ['Normal', 'badge-normal'],
        ];
        $meta = $labels[$flag] ?? $labels['normal'];
        return '<span class="badge ' . $meta[1] . '">' . htmlspecialchars($meta[0], ENT_QUOTES, 'UTF-8') . '</span>';
    }

    public static function uploadFlags(): array
    {
        return defined('UPLOAD_FLAGS') ? UPLOAD_FLAGS : ['normal', 'important', 'urgent', 'confidential'];
    }

    public static function formatDuration(?string $from, ?string $to): string
    {
        if ($from === null || $from === '' || $to === null || $to === '') {
            return '—';
        }
        $start = strtotime($from);
        $end = strtotime($to);
        if ($start === false || $end === false || $end < $start) {
            return '—';
        }
        $seconds = $end - $start;
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $mins = intdiv($seconds % 3600, 60);
        $parts = [];
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0 || $days > 0) {
            $parts[] = $hours . 'h';
        }
        $parts[] = $mins . 'm';
        return implode(' ', $parts);
    }

    public static function statusBadge(int $status): string
    {
        if ($status === 1) {
            return '<span class="badge badge-normal">Active</span>';
        }
        return '<span class="badge" style="background:#f5f5f7;color:#7a7a7a;">Disabled</span>';
    }

    public static function jsonResponse(array $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public static function isPost(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function truncate(?string $text, int $length = 80): string
    {
        $text = (string) $text;
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '…';
    }

    public static function pagination(int $total, int $page, int $perPage, string $baseUrl): string
    {
        $totalPages = (int) ceil($total / max(1, $perPage));
        if ($totalPages <= 1) {
            return '';
        }

        $html = '<nav aria-label="Pagination"><ul class="pagination justify-content-center flex-wrap">';

        $prevDisabled = $page <= 1 ? ' disabled' : '';
        $prevPage = max(1, $page - 1);
        $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . self::e($baseUrl . $prevPage) . '">Prev</a></li>';

        for ($i = 1; $i <= $totalPages; $i++) {
            $active = $i === $page ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . self::e($baseUrl . $i) . '">' . $i . '</a></li>';
        }

        $nextDisabled = $page >= $totalPages ? ' disabled' : '';
        $nextPage = min($totalPages, $page + 1);
        $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . self::e($baseUrl . $nextPage) . '">Next</a></li>';
        $html .= '</ul></nav>';

        return $html;
    }

    public static function absoluteUrl(string $path = ''): string
    {
        $path = $path !== '' ? $path : BASE_URL;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on')
            || (str_contains(strtolower((string) ($_SERVER['HTTP_CF_VISITOR'] ?? '')), 'https'));

        if (!$https && defined('IS_LOCAL') && !IS_LOCAL) {
            $https = true;
        }

        $scheme = $https ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $normalized = '/' . ltrim(str_replace('\\', '/', $path), '/');

        return $scheme . '://' . $host . $normalized;
    }

    public static function isHttpsRequest(): bool
    {
        return str_starts_with(self::absoluteUrl('/'), 'https://');
    }
}
