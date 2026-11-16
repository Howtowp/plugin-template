<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Fields;

final class TagsField extends Field
{
    protected string $type = 'tags';

    public function pattern(string $pattern, string $message = ''): static
    {
        if (@preg_match($pattern, '') === false) {
            throw new \InvalidArgumentException('Tags pattern must be a valid regular expression.');
        }
        $this->attr('pattern', $pattern);
        return $message === '' ? $this : $this->attr('patternMessage', $message);
    }

    public function maxItems(int $value): static
    {
        if ($value < 1) {
            throw new \InvalidArgumentException('Tags maximum must be positive.');
        }
        return $this->attr('maxItems', $value);
    }

    public function validate($value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return $this->isRequired() ? __('This field is required.', BLUWP) : null;
        }
        if (!is_array($value) || array_filter($value, static fn ($item): bool => !is_string($item)) !== []) {
            return __('Enter valid values.', BLUWP);
        }
        if (count($value) > (int) ($this->get('maxItems') ?? PHP_INT_MAX)) {
            return __('Too many values were entered.', BLUWP);
        }
        $pattern = $this->get('pattern');
        foreach ($value as $item) {
            if ($item === '' || (is_string($pattern) && !preg_match($pattern, $item))) {
                $message = $this->get('patternMessage');
                return is_string($message) && $message !== '' ? $message : __('Enter a valid value.', BLUWP);
            }
        }
        return null;
    }

    public function sanitize($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map(
            static fn ($item): string => sanitize_text_field((string) $item),
            $value
        ))));
    }
}
