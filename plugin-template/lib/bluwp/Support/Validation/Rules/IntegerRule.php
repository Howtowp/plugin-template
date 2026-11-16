<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

final class IntegerRule extends TypeRule
{
    public function name(): string
    {
        return 'integer';
    } protected function passes(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && (bool) preg_match('/^-?[0-9]+$/D', $value));
    }
}
