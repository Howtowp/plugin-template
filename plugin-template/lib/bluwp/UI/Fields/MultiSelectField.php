<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Fields;

final class MultiSelectField extends Field
{
    protected string $type = 'multi-select';

    public function noOptionsText(string $message): static
    {
        return $this->attr('noOptionsText', $message);
    }

    public function options(array $options): static
    {
        $options = array_map(
            static fn ($option) => $option instanceof Option ? $option->toArray() : $option,
            $options
        );
        return $this->attr('options', $options);
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

    public function validateContext(mixed $value, array $data, array $fields = []): ?string
    {
        $error = parent::validateContext($value, $data, $fields);
        if ($error !== null || $value === null || $value === '' || $value === []) {
            return $error;
        }
        foreach ($this->get('options') ?? [] as $option) {
            if (in_array($option['value'], $value, true)) {
                $error = Option::contextError($option, $data);
                if ($error !== null) {
                    return $error;
                }
            }
        }
        return null;
    }

    public function validate($value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return $this->isRequired() ? __('This field is required.', BLUWP) : null;
        }
        if (!is_array($value) || !array_is_list($value)) {
            return __('Choose available options.', BLUWP);
        }
        $allowed = array_column($this->get('options') ?? [], 'value');
        if (count($value) > count($allowed)) {
            return __('Choose available options.', BLUWP);
        }
        foreach ($value as $item) {
            if (!is_string($item) || !in_array($item, $allowed, true)) {
                return __('Choose available options.', BLUWP);
            }
        }
        return null;
    }

    public function sanitize($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        return array_values(array_unique($value));
    }
}
