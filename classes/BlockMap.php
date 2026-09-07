<?php
/**
 * Interactive MAP helpers — blocks & PHCs
 */
declare(strict_types=1);

class BlockMap
{
    /** @var array<string, array>|null */
    private static ?array $blocks = null;

    /** @return array<string, array> */
    public static function all(): array
    {
        if (self::$blocks === null) {
            $path = MAP_DIR . 'data' . DIRECTORY_SEPARATOR . 'blocks.php';
            self::$blocks = is_file($path) ? (require $path) : [];
            if (!is_array(self::$blocks)) {
                self::$blocks = [];
            }
        }
        return self::$blocks;
    }

    public static function find(string $slug): ?array
    {
        $blocks = self::all();
        if (!isset($blocks[$slug])) {
            return null;
        }
        $block = $blocks[$slug];
        $block['slug'] = $slug;
        return $block;
    }

    public static function findPhc(string $blockSlug, string $phcSlug): ?array
    {
        $block = self::find($blockSlug);
        if ($block === null) {
            return null;
        }
        foreach ($block['phcs'] ?? [] as $phc) {
            if (($phc['slug'] ?? '') === $phcSlug) {
                $phc['block_slug'] = $blockSlug;
                $phc['block_name'] = $block['name'];
                return $phc;
            }
        }
        return null;
    }

    public static function photoUrl(?string $path, string $fallback = 'assets/images/avatar-placeholder.svg'): string
    {
        if ($path === null || $path === '') {
            return Helper::asset($fallback);
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return Helper::asset(ltrim($path, '/'));
    }

    public static function heroUrl(?string $path): string
    {
        return self::photoUrl($path, 'assets/images/map/hero-placeholder.svg');
    }

    /** Role-aware MAP URLs under admin/ or member/ */
    public static function baseUrl(): string
    {
        $role = Auth::role();
        return BASE_URL . ($role === 'admin' ? 'admin/' : 'member/');
    }

    public static function mapUrl(): string
    {
        return self::baseUrl() . 'map.php';
    }

    public static function blockUrl(string $slug, string $view = ''): string
    {
        $url = self::baseUrl() . 'map-block.php?slug=' . rawurlencode($slug);
        if ($view !== '') {
            $url .= '&view=' . rawurlencode($view);
        }
        return $url;
    }

    public static function phcUrl(string $blockSlug, string $phcSlug, string $view = ''): string
    {
        $url = self::baseUrl() . 'map-phc.php?block=' . rawurlencode($blockSlug) . '&phc=' . rawurlencode($phcSlug);
        if ($view !== '') {
            $url .= '&view=' . rawurlencode($view);
        }
        return $url;
    }

    public static function dashboardUrl(): string
    {
        return self::baseUrl() . 'dashboard.php';
    }
}
