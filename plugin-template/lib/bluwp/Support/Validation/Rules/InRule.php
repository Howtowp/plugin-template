<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

use Howtowp\Bluwp\Support\Validation\{Attribute, RuleViolation, ValidationContext};
use Howtowp\Bluwp\Support\Validation\Contracts\Rule;

final class InRule implements Rule
{
    public function __construct(private array $values)
    {
        if ($values === []) {
            throw new \InvalidArgumentException(__('The in rule needs at least one allowed value.', BLUWP));
        }
    }
    public function name(): string
    {
        return 'in';
    }
    public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation
    {
        return in_array($attribute->value, $this->values, true) ? null : new RuleViolation($this->name());
    }
}
