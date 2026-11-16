<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation\Rules;

use Howtowp\Bluwp\Support\Validation\Attribute;
use Howtowp\Bluwp\Support\Validation\Contracts\Rule;
use Howtowp\Bluwp\Support\Validation\RuleViolation;
use Howtowp\Bluwp\Support\Validation\ValidationContext;

abstract class SizeRule implements Rule
{
    private bool $numeric = false;

    public function __construct(protected int|float $limit)
    {
        if (!is_finite((float) $limit)) {
            throw new \InvalidArgumentException(__('Validation size limits must be finite.', BLUWP));
        }
        if ($limit < 0) {
            throw new \InvalidArgumentException(__('Validation size limits cannot be negative.', BLUWP));
        }
    }
    final public function validate(Attribute $attribute, ValidationContext $context): ?RuleViolation
    {
        $size = $this->size($attribute->value);
        return $size !== null && is_finite((float) $size) && $this->passes($size) ? null : new RuleViolation($this->name(), [$this->name() => $this->limit]);
    }

    public function numeric(): static
    {
        $rule = clone $this;
        $rule->numeric = true;
        return $rule;
    }

    private function size(mixed $value): int|float|null
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        if ($this->numeric && is_string($value) && is_numeric($value)) {
            $integer = trim($value);
            if (preg_match('/^[+-]?[0-9]+$/D', $integer)) {
                $digits = ltrim(ltrim($integer, '+-'), '0');
                $integer = ($integer[0] === '-' && $digits !== '' ? '-' : '') . ($digits === '' ? '0' : $digits);
                $number = filter_var($integer, FILTER_VALIDATE_INT);
                // Out-of-range integer strings cannot be compared exactly as native ints.
                return $number === false ? null : $number;
            }
            return (float) $value;
        }
        if (is_string($value)) {
            return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        }
        if (is_array($value)) {
            return count($value);
        }
        return null;
    }
    abstract protected function passes(int|float $size): bool;
}
