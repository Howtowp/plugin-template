<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Fields;

final class TimeRangeField extends Field
{
    protected string $type = 'time-range';

    public function labels(string $start, string $end, string $apply, string $clear = ''): static
    {
        return $this->attrs(['startLabel' => $start, 'endLabel' => $end, 'applyLabel' => $apply, 'clearLabel' => $clear]);
    }

    public function hourOnly(bool $value = true): static
    {
        return $this->attr('hourOnly', $value);
    }

    public function validate($value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return $this->isRequired() ? __('This field is required.', BLUWP) : null;
        }
        if (!is_array($value) || !isset($value['start'], $value['end'])
            || !is_string($value['start']) || !is_string($value['end'])
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $value['start'])
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $value['end'])
            || $value['start'] >= $value['end']) {
            return __('Enter a valid time range.', BLUWP);
        }
        return null;
    }

    public function sanitize($value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }
        return is_array($value)
            ? ['start' => sanitize_text_field((string) ($value['start'] ?? '')), 'end' => sanitize_text_field((string) ($value['end'] ?? ''))]
            : [];
    }
}
