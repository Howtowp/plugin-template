<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation;

use Howtowp\Bluwp\Support\Validation\Contracts\Rule;
use Howtowp\Bluwp\Support\Validation\Rules\IntegerRule;
use Howtowp\Bluwp\Support\Validation\Rules\NumericRule;
use Howtowp\Bluwp\Support\Validation\Rules\SizeRule;

final class RuleParser
{
    public function __construct(private RuleRegistry $registry)
    {
    }

    /** @return Rule[] */
    public function parse(string|array|Rule $definition): array
    {
        $definitions = is_string($definition) ? explode('|', $definition) : (is_array($definition) ? $definition : [$definition]);
        $rules = [];
        foreach ($definitions as $item) {
            if ($item instanceof Rule) {
                $rules[] = $item;
                continue;
            }
            if (!is_string($item) || trim($item) === '') {
                throw new \InvalidArgumentException(__('Validation rules must be strings or Rule objects.', BLUWP));
            }
            [$name, $parameterList] = array_pad(explode(':', trim($item), 2), 2, '');
            $parameters = $parameterList === '' ? [] : array_map('trim', explode(',', $parameterList));
            $rules[] = $this->registry->make($name, $parameters);
        }
        if ($rules === []) {
            throw new \InvalidArgumentException(__('At least one validation rule is required.', BLUWP));
        }
        $numeric = array_filter($rules, static fn (Rule $rule): bool => $rule instanceof IntegerRule || $rule instanceof NumericRule) !== [];
        if ($numeric) {
            $rules = array_map(static fn (Rule $rule): Rule => $rule instanceof SizeRule ? $rule->numeric() : $rule, $rules);
        }
        return $rules;
    }
}
