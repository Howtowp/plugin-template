<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation\Rules;

/** A positive integer representable without lossy conversion to a native int. */
final class PositiveInteger extends TypeRule
{
    public function name(): string
    {
        return 'integer';
    }

    protected function passes(mixed $value): bool
    {
        return (is_int($value) && $value > 0)
            || (is_string($value) && preg_match('/^[1-9][0-9]*$/D', $value)
                && filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false);
    }
}
