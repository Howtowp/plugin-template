<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation\Rules;

final class UuidRule extends TypeRule
{
    public function name(): string
    {
        return 'uuid';
    } protected function passes(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $value);
    }
}
