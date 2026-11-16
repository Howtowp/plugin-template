<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

use Howtowp\Bluwp\Support\Phone\MobileNumber;
use Howtowp\Bluwp\Support\Validation\Attribute;
use Howtowp\Bluwp\Support\Validation\Contracts\Rule;
use Howtowp\Bluwp\Support\Validation\RuleViolation;
use Howtowp\Bluwp\Support\Validation\ValidationContext;

final class Mobile implements Rule
{
    public function name(): string
    {
        return 'iranian_mobile';
    }
    public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation
    {
        return is_string($attribute->value) && MobileNumber::normalize($attribute->value) !== null
            ? null : new RuleViolation($this->name());
    }
}
