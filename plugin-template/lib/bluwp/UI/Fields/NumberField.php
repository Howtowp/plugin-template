<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Fields;

defined('ABSPATH') || exit;

class NumberField extends Field
{
    protected string $type = 'number';

    public function min(int|float $value): static
    {
        return $this->attr('min', $value);
    }
    public function max(int|float $value): static
    {
        return $this->attr('max', $value);
    }
    public function step(int|float $value): static
    {
        return $this->attr('step', $value);
    }
    protected function validateAttribute(string $key, mixed $value): void
    {
        parent::validateAttribute($key, $value);
        if (in_array($key, ['min', 'max', 'step'], true)
            && (!is_numeric($value) || !is_finite((float) $value) || ($key === 'step' && $value <= 0))) {
            throw new \InvalidArgumentException('Numeric constraints must be finite; step must be positive.');
        }
    }

    public function validate($value): ?string
    {
        $error = parent::validate($value);
        if (null !== $error || null === $value || '' === $value) {
            return $error;
        }
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            return __('Enter a valid number.', BLUWP);
        }
        $number = (float) $value;
        $min = $this->get('min');
        $max = $this->get('max');
        if ((null !== $min && $number < $min) || (null !== $max && $number > $max)) {
            return __('The number is outside the allowed range.', BLUWP);
        }
        $step = $this->get('step');
        if (null !== $step) {
            $steps = ($number - ($min ?? 0)) / $step;
            if (! is_finite($steps) || abs($steps - round($steps)) > 0.0000001) {
                return __('The number does not match the allowed step.', BLUWP);
            }
        }
        return null;
    }

    public function sanitize($value)
    {
        return null === $value || '' === $value ? null : $value + 0;
    }
}
