<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Contracts;

use Howtowp\Bluwp\Support\Validation\Attribute;
use Howtowp\Bluwp\Support\Validation\RuleViolation;
use Howtowp\Bluwp\Support\Validation\ValidationContext;

interface Rule
{
    public function name(): string;

    public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation;
}
