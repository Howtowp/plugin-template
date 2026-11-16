<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

final class EmailRule extends TypeRule
{
    public function name(): string
    {
        return 'email';
    } protected function passes(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
