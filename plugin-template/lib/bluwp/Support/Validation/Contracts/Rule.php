<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation\Contracts;

use Forouzandea\Bluwp\Support\Validation\Attribute;
use Forouzandea\Bluwp\Support\Validation\RuleViolation;
use Forouzandea\Bluwp\Support\Validation\ValidationContext;

interface Rule
{
    public function name(): string;

    public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation;
}
