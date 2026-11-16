<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation;

use Howtowp\Bluwp\Support\Validation\Contracts\Rule;
use Howtowp\Bluwp\Support\Validation\Rules;

final class RuleRegistry
{
    /** @var array<string, callable(array): Rule> */
    private array $factories = [];

    public static function defaults(): self
    {
        $registry = new self();
        $registry->register('required', static fn (array $parameters): Rule => new Rules\Required());
        $registry->register('nullable', static fn (array $parameters): Rule => new Rules\Nullable());
        $registry->register('bail', static fn (array $parameters): Rule => new Rules\Bail());
        $registry->register('string', static fn (array $parameters): Rule => new Rules\StringRule());
        $registry->register('integer', static fn (array $parameters): Rule => new Rules\IntegerRule());
        $registry->register('numeric', static fn (array $parameters): Rule => new Rules\NumericRule());
        $registry->register('boolean', static fn (array $parameters): Rule => new Rules\BooleanRule());
        $registry->register('array', static fn (array $parameters): Rule => new Rules\ArrayRule($parameters === [] ? null : $parameters));
        $registry->register('email', static fn (array $parameters): Rule => new Rules\EmailRule());
        $registry->register('url', static fn (array $parameters): Rule => new Rules\UrlRule());
        $registry->register('uuid', static fn (array $parameters): Rule => new Rules\UuidRule());
        $registry->register('confirmed', static fn (array $parameters): Rule => new Rules\Confirmed());
        $registry->register('iranian_mobile', static fn (array $parameters): Rule => new Rules\Mobile());
        $registry->register('min', static fn (array $parameters): Rule => new Rules\Min(self::numericParameter('min', $parameters)));
        $registry->register('max', static fn (array $parameters): Rule => new Rules\Max(self::numericParameter('max', $parameters)));
        $registry->register('in', static fn (array $parameters): Rule => new Rules\InRule(self::requiredParameters('in', $parameters)));
        $registry->register('not_in', static fn (array $parameters): Rule => new Rules\NotInRule(self::requiredParameters('not_in', $parameters)));
        $registry->register('same', static fn (array $parameters): Rule => new Rules\Same(self::singleParameter('same', $parameters)));
        $registry->register('required_if', static fn (array $parameters): Rule => new Rules\RequiredIf(
            array_shift($parameters) ?? throw new \InvalidArgumentException(__('The required_if rule needs a field and value.', BLUWP)),
            self::requiredParameters('required_if', $parameters)
        ));
        return $registry;
    }

    public function register(string $name, callable $factory): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/D', $name) || isset($this->factories[$name])) {
            throw new \InvalidArgumentException(__('Invalid or duplicate validation rule name.', BLUWP));
        }
        $this->factories[$name] = $factory;
    }

    public function make(string $name, array $parameters = []): Rule
    {
        if (!isset($this->factories[$name])) {
            throw new \InvalidArgumentException(sprintf(__('Unknown validation rule: %s', BLUWP), $name));
        }
        $rule = ($this->factories[$name])($parameters);
        if ($rule->name() !== $name) {
            throw new \LogicException(__('A validation rule factory returned a mismatched rule.', BLUWP));
        }
        return $rule;
    }

    private static function requiredParameters(string $rule, array $parameters): array
    {
        if ($parameters === [] || in_array('', $parameters, true)) {
            throw new \InvalidArgumentException(sprintf(__('The %s rule has invalid parameters.', BLUWP), $rule));
        }
        return $parameters;
    }
    private static function singleParameter(string $rule, array $parameters): string
    {
        if (count($parameters) !== 1 || $parameters[0] === '') {
            throw new \InvalidArgumentException(sprintf(__('The %s rule requires one parameter.', BLUWP), $rule));
        }
        return $parameters[0];
    }
    private static function numericParameter(string $rule, array $parameters): int|float
    {
        $value = self::singleParameter($rule, $parameters);
        if (!preg_match('/\A[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?\z/', $value)) {
            throw new \InvalidArgumentException(sprintf(__('The %s rule requires a numeric parameter.', BLUWP), $rule));
        }
        if (!str_contains($value, '.') && stripos($value, 'e') === false) {
            $digits = ltrim(ltrim($value, '+-'), '0');
            $normalized = ($value[0] === '-' && $digits !== '' ? '-' : '') . ($digits === '' ? '0' : $digits);
            $number = filter_var($normalized, FILTER_VALIDATE_INT);
            if ($number !== false) {
                return $number;
            }
        } else {
            $number = (float) $value;
            $mantissa = preg_split('/[eE]/', $value)[0];
            if (is_finite($number) && ($number != 0.0 || !preg_match('/[1-9]/', $mantissa))) {
                return $number;
            }
        }
        throw new \InvalidArgumentException(sprintf(__('The %s rule requires a numeric parameter.', BLUWP), $rule));
    }
}
