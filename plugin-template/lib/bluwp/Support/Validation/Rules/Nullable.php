<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

use Howtowp\Bluwp\Support\Validation\Attribute;
use Howtowp\Bluwp\Support\Validation\Contracts\Rule;
use Howtowp\Bluwp\Support\Validation\RuleViolation;
use Howtowp\Bluwp\Support\Validation\ValidationContext;

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
