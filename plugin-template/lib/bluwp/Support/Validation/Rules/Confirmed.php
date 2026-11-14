<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation\Rules;

use Forouzandea\Bluwp\Support\Validation\{Attribute, RuleViolation, ValidationContext};
use Forouzandea\Bluwp\Support\Validation\Contracts\Rule;

final class Confirmed implements Rule
{
    public function name(): string
    {
        return 'confirmed';
    }
    public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation
    {
        return $context->has($attribute->name . '_confirmation') && $attribute->value === $context->get($attribute->name . '_confirmation') ? null : new RuleViolation($this->name());
    }
}
