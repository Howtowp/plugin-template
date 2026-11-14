<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation\Rules;

final class BooleanRule extends TypeRule
{
    public function name(): string
    {
        return 'boolean';
    } protected function passes(mixed $value): bool
    {
        return in_array($value, [true, false, 0, 1, '0', '1'], true);
    }
}
