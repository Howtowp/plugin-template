<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Support;

use InvalidArgumentException;

final class DefinitionValidator
{
    public static function text(mixed $value, string $field): void
    {
        if (!is_string($value) || preg_match('/\S/u', $value) !== 1) {
            throw new InvalidArgumentException('Definition requires a nonempty string: ' . $field);
        }
    }

    public static function identity(mixed $value, string $field): void
    {
        if (!is_string($value) || !preg_match('/^[a-zA-Z0-9._-]+$/D', $value)) {
            throw new InvalidArgumentException('Invalid definition identifier: ' . $field);
        }
    }

    public static function capability(mixed $value): void
    {
        self::text($value, 'capability');
        if (preg_match('/\s/u', $value) !== 0) {
            throw new InvalidArgumentException('Capability must not contain whitespace.');
        }
    }
}
