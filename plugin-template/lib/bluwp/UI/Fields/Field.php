<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Fields;

use Howtowp\Bluwp\UI\Support\Condition;

/** @phpstan-consistent-constructor */
abstract class Field implements \JsonSerializable
{
    protected string $type;
    protected AttributeBag $attributes;

    public function __construct(protected string $name)
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $name)) {
            throw new \InvalidArgumentException('A field needs a valid name.');
        }
        $this->attributes = new AttributeBag();
    }

    public static function make(string $name): static
    {
        return new static($name);
    }
    public function name(): string
    {
        return $this->name;
    }
    public function type(): string
    {
        return $this->type;
    }
    public function label(string $label): static
    {
        return $this->attr('label', $label);
    }
    public function placeholder(string $value): static
    {
        return $this->attr('placeholder', $value);
    }
    public function description(string $value): static
    {
        return $this->attr('description', $value);
    }
    public function default(mixed $value): static
    {
        return $this->attr('default', $value);
    }
    public function required(bool $value = true): static
    {
        return $this->attr('required', $value);
    }

    public function requiredWhen(string $field, string|array $values): static
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $field)) {
            throw new \InvalidArgumentException('A required condition needs a valid field name.');
        }
        $values = is_array($values) ? array_values($values) : [$values];
        if ($values === [] || array_filter($values, static fn ($value): bool => !is_string($value)) !== []) {
            throw new \InvalidArgumentException('Required condition values must be strings.');
        }

        return $this->attr('requiredWhen', ['field' => $field, 'values' => array_values(array_unique($values))]);
    }

    public function dividerBefore(bool $value = true): static
    {
        return $this->attr('dividerBefore', $value);
    }

    public function startSection(string $title): static
    {
        if (trim($title) === '') {
            throw new \InvalidArgumentException('A field section title cannot be empty.');
        }

        return $this->dividerBefore()->attr('sectionTitle', $title);
    }

    public function visibleWhen(string $field, string|array $values): static
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $field)) {
            throw new \InvalidArgumentException('A visibility condition needs a valid field name.');
        }
        $values = is_array($values) ? array_values($values) : [$values];
        if ($values === [] || array_filter($values, static fn ($value): bool => !is_string($value)) !== []) {
            throw new \InvalidArgumentException('Visibility condition values must be strings.');
        }

        return $this->attr('visibleWhen', ['field' => $field, 'values' => array_values(array_unique($values))]);
    }

    public function fullWidth(bool $value = true): static
    {
        return $this->attr('fullWidth', $value);
    }

    /** Hide a field only when every supplied condition matches. */
    public function hiddenWhenAll(array $conditions): static
    {
        return $this->attr('hiddenWhenAll', $conditions);
    }

    public function isVisible(array $data): bool
    {
        $hidden = $this->get('hiddenWhenAll');
        if (is_array($hidden) && $hidden !== []) {
            $matches = true;
            foreach ($hidden as $item) {
                if (!Condition::matches($item, $data)) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return false;
            }
        }
        $condition = $this->get('visibleWhen');
        if (!is_array($condition)) {
            return true;
        }

        return Condition::matches($condition, $data);
    }

    public function requires(string ...$fields): static
    {
        foreach ($fields as $field) {
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $field)) {
                throw new \InvalidArgumentException('Field requirements need valid field names.');
            }
        }

        return $this->attr('requires', array_values(array_unique($fields)));
    }

    public function attr(string $key, mixed $value): static
    {
        if (in_array($key, ['name', 'type'], true)) {
            throw new \InvalidArgumentException('Field identity cannot be overridden by attributes.');
        }
        $this->validateAttribute($key, $value);
        $this->attributes->set($key, $value);
        return $this;
    }

    protected function validateAttribute(string $key, mixed $value): void
    {
        if ($key === 'hiddenWhenAll') {
            if (!is_array($value) || !array_is_list($value) || $value === []) {
                throw new \InvalidArgumentException('Hidden conditions must be a nonempty list.');
            }
            foreach ($value as $condition) {
                $this->validateAttribute('visibleWhen', $condition);
            }
        }
        if (in_array($key, ['required', 'dividerBefore', 'fullWidth'], true) && !is_bool($value)) {
            throw new \InvalidArgumentException('Boolean field attributes must be boolean.');
        }
        if (in_array($key, ['visibleWhen', 'requiredWhen'], true) && (!is_array($value)
            || !isset($value['field'], $value['values'])
            || !is_string($value['field'])
            || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $value['field'])
            || !is_array($value['values'])
            || $value['values'] === []
            || array_filter($value['values'], static fn ($item): bool => !is_string($item)) !== [])) {
            throw new \InvalidArgumentException('A field condition is invalid.');
        }        if ($key === 'requires' && (!is_array($value)
            || array_filter(
                $value,
                static fn ($field): bool =>
                !is_string($field) || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $field)
            ) !== [])) {
            throw new \InvalidArgumentException('Field requirements need valid field names.');
        }
    }

    public function attrs(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->attr((string) $key, $value);
        }
        return $this;
    }

    public function get(string $key): mixed
    {
        return match ($key) {
            'name' => $this->name,
            'type' => $this->type,
            default => $this->attributes->get($key),
        };
    }

    public function isRequired(array $data = []): bool
    {
        if ($this->get('required') === true) {
            return true;
        }
        $condition = $this->get('requiredWhen');
        return is_array($condition)
            && Condition::matches($condition, $data);
    }

    public function validate($value): ?string
    {
        if ($value !== null && !is_string($value) && !is_int($value) && !is_float($value)) {
            return __('Invalid field value.', BLUWP);
        }
        return $this->isRequired() && ($value === null || trim((string) $value) === '')
            ? __('This field is required.', BLUWP) : null;
    }

    /** @return array<string, string> Errors relative to this field; an empty key denotes the field itself. */
    public function validationErrors(mixed $value): array
    {
        $error = $this->validate($value);
        return $error === null ? [] : ['' => $error];
    }

    /** Validate final values without applying defaults or sanitizing again. */
    public function finalValidationErrors(mixed $value): array
    {
        return $this->validationErrors($value);
    }

    public function validateContext(mixed $value, array $data, array $fields = []): ?string
    {
        if ($this->isRequired($data) && ($value === null || $value === '' || $value === [])) {
            return __('This field is required.', BLUWP);
        }
        if ($value === null || $value === '' || $value === []) {
            return null;
        }
        foreach ($this->get('requires') ?? [] as $requiredField) {
            if (!Condition::hasValue($data[$requiredField] ?? null)) {
                return __('Complete the preceding fields first.', BLUWP);
            }
        }

        return null;
    }

    public function sanitize($value)
    {
        return sanitize_text_field((string) $value);
    }
    public function toArray(): array
    {
        return ['type' => $this->type, 'name' => $this->name] + $this->attributes->all();
    }
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
