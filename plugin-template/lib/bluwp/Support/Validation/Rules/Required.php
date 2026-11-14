<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation\Rules;

use Forouzandea\Bluwp\Support\Validation\Attribute;
use Forouzandea\Bluwp\Support\Validation\Contracts\ImplicitRule;
use Forouzandea\Bluwp\Support\Validation\RuleViolation;
use Forouzandea\Bluwp\Support\Validation\ValidationContext;

final class Required implements ImplicitRule
{
    public function name(): string
    {
        return 'required';
    }

    public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation
    {
        return !$attribute->present || self::empty($attribute->value) ? new RuleViolation($this->name()) : null;
    }

    public static function empty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }
}
