<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation;

final class DataPath
{
    public static function has(array $data, string $path): bool
    {
        $cursor = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return false;
            }
            $cursor = $cursor[$segment];
        }
        return true;
    }

    public static function get(array $data, string $path, mixed $default = null): mixed
    {
        $cursor = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return $default;
            }
            $cursor = $cursor[$segment];
        }
        return $cursor;
    }

    public static function set(array &$data, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $cursor = &$data;
        foreach ($segments as $index => $segment) {
            if ($index === array_key_last($segments)) {
                $cursor[$segment] = $value;
                return;
            }
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }
    }

    /** @return string[] */
    public static function expand(array $data, string $pattern): array
    {
        if (!str_contains($pattern, '*')) {
            return [$pattern];
        }
        $paths = ['' => $data];
        foreach (explode('.', $pattern) as $segment) {
            $next = [];
            foreach ($paths as $path => $value) {
                if ($segment === '*') {
                    if (!is_array($value)) {
                        continue;
                    }
                    foreach ($value as $key => $child) {
                        if ((string) $key === '' || str_contains((string) $key, '.')) {
                            throw new \InvalidArgumentException('Wildcard validation keys must be nonempty and must not contain dots.');
                        }
                        $childPath = $path === '' ? (string) $key : $path . '.' . $key;
                        $next[$childPath] = $child;
                    }
                    continue;
                }
                $childPath = $path === '' ? $segment : $path . '.' . $segment;
                $next[$childPath] = is_array($value) && array_key_exists($segment, $value)
                    ? $value[$segment] : null;
            }
            $paths = $next;
        }
        return array_keys($paths);
    }
}
