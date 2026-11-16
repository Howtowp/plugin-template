<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Fields;

defined('ABSPATH') || exit;

class SelectField extends Field
{
    protected string $type = 'select';

    public function noOptionsText(string $message): static
    {
        return $this->attr('noOptionsText', $message);
    }

    public function options(array $options): static
    {
        return $this->attr('options', array_map(
            static fn ($option) => $option instanceof Option ? $option->toArray() : $option,
            $options
        ));
    }

    public function distinct(string $group, string $message = ''): static
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_.-]*$/D', $group)) {
            throw new \InvalidArgumentException('A distinct option group needs a valid name.');
        }

        $this->attr('distinctGroup', $group);
        if ($message !== '') {
            $this->attr('distinctMessage', $message);
        }

        return $this;
    }

    protected function validateAttribute(string $key, mixed $value): void
    {
        parent::validateAttribute($key, $value);
        if ($key !== 'options') {
            return;
        }
        if (!is_array($value)) {
            throw new \InvalidArgumentException('Options must be an array.');
        }

        foreach ($value as $option) {
            Option::validateDefinition($option);
        }
    }

    public function validate($value): ?string
    {
        $error = parent::validate($value);
        if ($error !== null || $value === null || $value === '') {
            return $error;
        }

        $allowed = array_column($this->get('options') ?? [], 'value');

        return in_array((string) $value, $allowed, true)
            ? null
            : __('Choose an available option.', BLUWP);
    }

    public function validateContext(mixed $value, array $data, array $fields = []): ?string
    {
        $error = parent::validateContext($value, $data, $fields);
        if ($error !== null || $value === null || $value === '') {
            return $error;
        }

        foreach ($this->get('options') ?? [] as $option) {
            if (($option['value'] ?? null) !== (string) $value) {
                continue;
            }
            $error = Option::contextError($option, $data);
            if ($error !== null) {
                return $error;
            }
            break;
        }

        $group = $this->get('distinctGroup');
        if (!is_string($group) || $group === '') {
            return null;
        }

        foreach ($fields as $field) {
            if (!$field instanceof self || $field->name() === $this->name()
                || $field->get('distinctGroup') !== $group) {
                continue;
            }
            if (($data[$field->name()] ?? null) === $value) {
                $message = $this->get('distinctMessage');

                return is_string($message) && $message !== ''
                    ? $message
                    : __('This option has already been selected.', BLUWP);
            }
        }

        return null;
    }

    public function sanitize($value)
    {
        return $value === null ? '' : (string) $value;
    }
}
