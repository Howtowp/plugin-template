<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation\Rules;

use Forouzandea\Bluwp\Support\Phone\MobileNumber;
use Forouzandea\Bluwp\Support\Validation\Attribute;
use Forouzandea\Bluwp\Support\Validation\Contracts\Rule;
use Forouzandea\Bluwp\Support\Validation\RuleViolation;
use Forouzandea\Bluwp\Support\Validation\ValidationContext;

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
