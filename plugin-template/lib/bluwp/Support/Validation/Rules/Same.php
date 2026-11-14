<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Support\Validation\Rules;

use Forouzandea\Bluwp\Support\Validation\{Attribute, RuleViolation, ValidationContext};
use Forouzandea\Bluwp\Support\Validation\Contracts\Rule;

final class Same implements Rule
{
    public function __construct(private string $other)
    {
        if ($other === '') {
            throw new \InvalidArgumentException(__('The same rule needs another field.', BLUWP));
        }
    }
    public function name(): string
    {
        return 'same';
    }
    public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation
    {
        return $context->has($this->other) && $attribute->value === $context->get($this->other) ? null : new RuleViolation($this->name(), ['other' => $this->other]);
    }
}
