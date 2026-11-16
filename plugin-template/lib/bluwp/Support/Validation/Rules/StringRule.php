<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

final class StringRule extends TypeRule
{
    public function name(): string
    {
        return 'string';
    } protected function passes(mixed $value): bool
    {
        return is_string($value);
    }
}
