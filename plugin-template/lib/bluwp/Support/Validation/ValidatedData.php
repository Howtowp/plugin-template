<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation;

/** Builds output from validated paths without copying undeclared siblings. */
final class ValidatedData
{
    public static function project(array $values, array $patterns): array
    {
        $patterns = array_map(static fn (string $pattern): array => explode('.', $pattern), $patterns);
        uksort($values, static fn (string $left, string $right): int => substr_count($left, '.') <=> substr_count($right, '.'));
        $result = [];
        foreach ($values as $path => $value) {
            $path = (string) $path;
            if (is_array($value) && self::hasDescendants(explode('.', $path), $patterns)) {
                $value = [];
            }
            DataPath::set($result, $path, $value);
        }
        return $result;
    }

    private static function hasDescendants(array $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (count($pattern) <= count($path)) {
                continue;
            }
            foreach ($path as $index => $segment) {
                if ($pattern[$index] !== '*' && $pattern[$index] !== $segment) {
                    continue 2;
                }
            }
            return true;
        }
        return false;
    }
}
