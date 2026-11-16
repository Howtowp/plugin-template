<?php

declare(strict_types=1);
/**
 * Text field.
 *
 * @package Howtowp\Bluwp\UI\Fields
 */

namespace Howtowp\Bluwp\UI\Fields;

defined('ABSPATH') || exit;

class TextField extends Field
{
    protected string $type = 'text';

    public function inputType(string $type): static
    {
        if (!in_array($type, ['text', 'tel', 'time'], true)) {
            throw new \InvalidArgumentException('Unsupported text input type.');
        }

        return $this->attr('inputType', $type);
    }

    public function validate($value): ?string
    {
        $error = parent::validate($value);
        if ($error !== null || $value === null || $value === '' || $this->get('inputType') !== 'time') {
            return $error;
        }

        return preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/D', (string) $value)
            ? null
            : __('Enter a valid time.', BLUWP);
    }
}
