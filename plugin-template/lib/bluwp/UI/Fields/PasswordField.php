<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Fields;

defined('ABSPATH') || exit;

final class PasswordField extends Field
{
    protected string $type = 'password';

    public function validate($value): ?string
    {
        if ($value !== null && !is_string($value)) {
            return __('Invalid field value.', BLUWP);
        }
        return $this->isRequired() && ($value === null || $value === '')
            ? __('This field is required.', BLUWP) : null;
    }

    public function sanitize($value): string
    {
        return $value ?? '';
    }
}
