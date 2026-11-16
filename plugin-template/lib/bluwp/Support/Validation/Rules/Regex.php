<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

use Howtowp\Bluwp\Support\Validation\Attribute;
use Howtowp\Bluwp\Support\Validation\Contracts\Rule;
use Howtowp\Bluwp\Support\Validation\RuleViolation;
use Howtowp\Bluwp\Support\Validation\ValidationContext;

final class Regex implements Rule
{
    public function __construct(private string $pattern)
    {
        if ($pattern === '' || @preg_match($pattern, '') === false) {
            throw new \InvalidArgumentException(__('Invalid validation regular expression.', BLUWP));
        }
    }
    public function name(): string
    {
        return 'regex';
    }
    public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation
    {
        return is_string($attribute->value) && preg_match($this->pattern, $attribute->value) === 1 ? null : new RuleViolation($this->name());
    }
}
