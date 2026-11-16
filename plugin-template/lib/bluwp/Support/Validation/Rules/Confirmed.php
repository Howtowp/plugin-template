<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

use Howtowp\Bluwp\Support\Validation\{Attribute, RuleViolation, ValidationContext};
use Howtowp\Bluwp\Support\Validation\Contracts\Rule;

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
