<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

use Howtowp\Bluwp\Support\Validation\Attribute;
use Howtowp\Bluwp\Support\Validation\Contracts\ImplicitRule;
use Howtowp\Bluwp\Support\Validation\RuleViolation;
use Howtowp\Bluwp\Support\Validation\ValidationContext;

final class RequiredIf implements ImplicitRule
{
    public function __construct(private string $other, private array $values)
    {
        if ($other === '' || $values === []) {
            throw new \InvalidArgumentException(__('The required_if rule needs a field and value.', BLUWP));
        }
    }
    public function name(): string
    {
        return 'required_if';
    }
    public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation
    {
        $required = in_array($context->get($this->other), $this->values, true);
        return $required && (!$attribute->present || Required::empty($attribute->value))
            ? new RuleViolation($this->name(), ['other' => $this->other, 'value' => implode(', ', $this->values)]) : null;
    }
}
