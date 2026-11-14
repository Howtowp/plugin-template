<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Fields;

final class SwitchField extends Field
{
    protected string $type = 'switch';

    public function labels(string $on, string $off): static
    {
        if (trim($on) === '' || trim($off) === '') {
            throw new \InvalidArgumentException('Switch labels cannot be empty.');
        }
        return $this->attrs(['onLabel' => $on, 'offLabel' => $off]);
    }

    public function validate($value): ?string
    {
        if ($value === null || $value === '') {
            return $this->isRequired() ? __('This field is required.', BLUWP) : null;
        }
        return is_bool($value) || in_array($value, [0, 1, '0', '1'], true)
            ? null
            : __('Choose a valid switch state.', BLUWP);
    }

    public function sanitize($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
