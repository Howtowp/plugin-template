<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation\Rules;

use Forouzandea\Bluwp\Support\Validation\{Attribute, RuleViolation, ValidationContext};
use Forouzandea\Bluwp\Support\Validation\Contracts\Rule;

abstract class TypeRule implements Rule
{
    final public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation
    {
        return $this->passes($attribute->value) ? null : new RuleViolation($this->name());
    }
    abstract protected function passes(mixed $value): bool;
}
