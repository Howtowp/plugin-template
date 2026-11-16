<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

final class NumericRule extends TypeRule
{
    public function name(): string
    {
        return 'numeric';
    } protected function passes(mixed $value): bool
    {
        return (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)))
            && is_finite((float) $value);
    }
}
