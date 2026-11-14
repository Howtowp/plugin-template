<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation\Rules;

final class UrlRule extends TypeRule
{
    public function name(): string
    {
        return 'url';
    } protected function passes(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
