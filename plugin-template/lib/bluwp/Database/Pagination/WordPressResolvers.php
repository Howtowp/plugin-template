<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Pagination;

final class WordPressResolvers
{
    private static bool $registered = false;
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;
        Paginator::currentPageResolver(static function (string $name): int {
            $value = $_GET[$name] ?? 1;
            return is_scalar($value) ? (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1) : 1;
        });
        Paginator::queryStringResolver(static fn (): array => function_exists('wp_unslash') ? wp_unslash($_GET) : $_GET);
        Paginator::currentPathResolver(static function (): string {
            if (!function_exists('home_url')) {
                return '/';
            }
            $base = parse_url(home_url('/'));
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $url = ($base['scheme'] ?? 'https') . '://' . ($base['host'] ?? 'localhost')
                . (isset($base['port']) ? ':' . $base['port'] : '') . '/' . ltrim($path, '/');
            // Preserve WordPress route selectors; they are not pagination state.
            $route = [];
            if (isset($_GET['rest_route']) && is_string($_GET['rest_route'])) {
                $route['rest_route'] = wp_unslash($_GET['rest_route']);
            }
            if (str_ends_with($path, '/admin.php') && isset($_GET['page']) && is_string($_GET['page']) && !ctype_digit($_GET['page'])) {
                $route['page'] = $_GET['page'];
            }
            return $url . ($route ? '?' . http_build_query($route, '', '&', PHP_QUERY_RFC3986) : '');
        });
        CursorPaginator::currentCursorResolver(static fn (string $name) => is_string($_GET[$name] ?? null) ? $_GET[$name] : null);
    }
}
