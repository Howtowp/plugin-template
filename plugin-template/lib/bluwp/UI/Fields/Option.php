<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Fields;

use JsonSerializable;
use Forouzandea\Bluwp\UI\Support\Condition;

defined('ABSPATH') || exit;

final class Option implements JsonSerializable
{
    /** @var list<string> */
    private array $requirements = [];
    /** @var array{field: string, values: list<string>}|null */
    private ?array $visibility = null;

    public function __construct(private string $label, private string $value)
    {
        if (trim($label) === '' || $value === '') {
            throw new \InvalidArgumentException('An option requires a label and value.');
        }
    }

    public static function make(string $label, string $value): self
    {
        return new self($label, $value);
    }

    public function requires(string ...$fields): self
    {
        foreach ($fields as $field) {
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $field)) {
                throw new \InvalidArgumentException('Option requirements need valid field names.');
            }
            if (!in_array($field, $this->requirements, true)) {
                $this->requirements[] = $field;
            }
        }

        return $this;
    }

    public function visibleWhen(string $field, string|array $values): self
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $field)) {
            throw new \InvalidArgumentException('Option visibility needs a valid field name.');
        }
        $values = is_array($values) ? array_values($values) : [$values];
        if ($values === [] || array_filter($values, static fn ($value): bool => !is_string($value)) !== []) {
            throw new \InvalidArgumentException('Option visibility values must be strings.');
        }

        $this->visibility = ['field' => $field, 'values' => array_values(array_unique($values))];
        return $this;
    }

    /** @return array{label: string, value: string, requires?: list<string>} */
    public function toArray(): array
    {
        $option = [
            'label' => $this->label,
            'value' => $this->value,
        ];

        if ($this->requirements !== []) {
            $option['requires'] = $this->requirements;
        }
        if ($this->visibility !== null) {
            $option['visibleWhen'] = $this->visibility;
        }

        return $option;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** Validate definitions supplied through either options() or generic attributes. */
    public static function validateDefinition(mixed $option): void
    {
        if (!is_array($option) || !isset($option['label'], $option['value'])
            || !is_string($option['label']) || !is_string($option['value'])) {
            throw new \InvalidArgumentException('Select options require string labels and values.');
        }
        if (array_key_exists('disabled', $option) && !is_bool($option['disabled'])) {
            throw new \InvalidArgumentException('Option disabled state must be boolean.');
        }
        if (array_key_exists('requires', $option)
            && (!is_array($option['requires']) || !array_is_list($option['requires'])
                || array_filter($option['requires'], static fn ($field): bool =>
                    !is_string($field) || preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $field) !== 1) !== [])) {
            throw new \InvalidArgumentException('Option requirements need valid field names.');
        }
        if (array_key_exists('visibleWhen', $option)) {
            $condition = $option['visibleWhen'];
            if (!is_array($condition) || !isset($condition['field'], $condition['values'])
                || !is_string($condition['field'])
                || preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $condition['field']) !== 1
                || !is_array($condition['values']) || !array_is_list($condition['values'])
                || $condition['values'] === []
                || array_filter($condition['values'], static fn ($value): bool => !is_string($value)) !== []) {
                throw new \InvalidArgumentException('Option visibility condition is invalid.');
            }
        }
    }

    public static function contextError(array $option, array $data): ?string
    {
        if (($option['disabled'] ?? false) === true) {
            return __('Choose an available option.', BLUWP);
        }
        foreach ($option['requires'] ?? [] as $field) {
            if (!Condition::hasValue($data[$field] ?? null)) {
                return __('Complete the fields required by this option.', BLUWP);
            }
        }
        $condition = $option['visibleWhen'] ?? null;
        if ($condition !== null && !Condition::matches($condition, $data)) {
            return __('Choose an available option.', BLUWP);
        }
        return null;
    }
}
