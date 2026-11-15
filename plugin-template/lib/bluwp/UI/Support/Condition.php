<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Support;

/** Value rules shared with Vue forms/conditions.js. */
final class Condition
{
    public static function matches(array $condition, array $data): bool
    {
        $value = self::value($data[$condition['field']] ?? null);
        return $value !== null && in_array($value, $condition['values'], true);
    }

    public static function value(mixed $value): ?string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_string($value) || is_int($value) || (is_float($value) && is_finite($value))) {
            return (string) $value;
        }
        return null;
    }

    public static function hasValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }
        $text = self::value($value);
        return $text !== null && trim($text) !== '';
    }
}
