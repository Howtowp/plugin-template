<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation\Rules;

use Forouzandea\Bluwp\Support\Validation\Attribute;
use Forouzandea\Bluwp\Support\Validation\Contracts\Rule;
use Forouzandea\Bluwp\Support\Validation\RuleViolation;
use Forouzandea\Bluwp\Support\Validation\ValidationContext;

final class Nullable implements Rule
{
    public function name(): string
    {
        return 'nullable';
    }
    public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation
    {
        return null;
    }
}
