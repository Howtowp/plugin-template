<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation;

final class RuleViolation
{
    /** @param array<string, scalar> $replacements */
    public function __construct(public readonly string $rule, public readonly array $replacements = [])
    {
    }
}
