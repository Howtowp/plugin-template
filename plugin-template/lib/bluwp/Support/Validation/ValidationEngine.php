<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Support\Validation;

use Howtowp\Bluwp\Support\Validation\Contracts\ImplicitRule;
use Howtowp\Bluwp\Support\Validation\Contracts\MessageProvider;
use Howtowp\Bluwp\Support\Validation\Contracts\Rule;
use Howtowp\Bluwp\Support\Validation\Rules\Bail;
use Howtowp\Bluwp\Support\Validation\Rules\Nullable;

final class ValidationEngine
{
    public function __construct(
        private array $data,
        private array $definitions,
        private RuleParser $parser,
        private MessageProvider $provider,
        private array $messages = [],
        private array $attributes = []
    ) {
        foreach (array_keys($definitions) as $attribute) {
            if (!is_string($attribute) || !preg_match('/^[A-Za-z0-9_*]+(?:\.[A-Za-z0-9_*]+)*$/D', $attribute)) {
                throw new \InvalidArgumentException(__('Invalid validation attribute path.', BLUWP));
            }
        }
    }

    public function validate(): ValidationResult
    {
        $errors = [];
        $validated = [];
        $context = new ValidationContext($this->data);
        foreach ($this->definitions as $pattern => $definition) {
            $rules = $this->parser->parse($definition);
            $nullable = array_filter($rules, static fn (Rule $rule): bool => $rule instanceof Nullable) !== [];
            $bail = array_filter($rules, static fn (Rule $rule): bool => $rule instanceof Bail) !== [];
            foreach (DataPath::expand($this->data, $pattern) as $path) {
                $present = DataPath::has($this->data, $path);
                $value = DataPath::get($this->data, $path);
                $attribute = new Attribute($path, $value, $present);
                foreach ($rules as $rule) {
                    if ($rule instanceof Nullable || $rule instanceof Bail) {
                        continue;
                    }
                    if (!($rule instanceof ImplicitRule) && (!$present || ($nullable && $value === null))) {
                        continue;
                    }
                    $violation = $rule->validate($attribute, $context);
                    if ($violation === null) {
                        continue;
                    }
                    $errors[$path][] = $this->message($pattern, $path, $violation);
                    if ($bail) {
                        break;
                    }
                }
                if (!isset($errors[$path]) && $present) {
                    $validated[$path] = $value;
                }
            }
        }
        return new ValidationResult($errors, $errors === [] ? ValidatedData::project($validated, array_keys($this->definitions)) : []);
    }

    private function message(string $pattern, string $path, RuleViolation $violation): string
    {
        $template = $this->messages[$path . '.' . $violation->rule]
            ?? $this->messages[$pattern . '.' . $violation->rule]
            ?? $this->messages[$violation->rule]
            ?? $this->provider->message($violation->rule);
        $label = $this->attributes[$path] ?? $this->attributes[$pattern] ?? str_replace(['_', '.'], ' ', $path);
        $replace = ['attribute' => $label] + $violation->replacements;
        return strtr($template, array_combine(
            array_map(static fn (string $key): string => ':' . $key, array_keys($replace)),
            array_map(static fn ($value): string => (string) $value, array_values($replace))
        ));
    }
}
