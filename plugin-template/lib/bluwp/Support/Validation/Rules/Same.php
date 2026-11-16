<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

use Howtowp\Bluwp\Support\Validation\{Attribute, RuleViolation, ValidationContext};
use Howtowp\Bluwp\Support\Validation\Contracts\Rule;

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
