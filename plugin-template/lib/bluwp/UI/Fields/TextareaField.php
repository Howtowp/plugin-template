<?php

declare(strict_types=1);
/**
 * Textarea field.
 *
 * @package Howtowp\Bluwp\UI\Fields
 */

namespace Howtowp\Bluwp\UI\Fields;

defined('ABSPATH') || exit;

class TextareaField extends Field
{
    protected string $type = 'textarea';

    public function rows(int $rows): static
    {
        return $this->attr('rows', $rows);
    }
    public function shortcodes(array $shortcodes): static
    {
        foreach ($shortcodes as $shortcode) {
            if (!is_array($shortcode) || !isset($shortcode['code'], $shortcode['label'])
                || !is_string($shortcode['code']) || !is_string($shortcode['label'])
                || !preg_match('/^\\{[a-z][a-z0-9_]*\\}$/D', $shortcode['code'])
                || trim($shortcode['label']) === '') {
                throw new \InvalidArgumentException('Textarea shortcodes require a valid code and label.');
            }

            if (isset($shortcode['visibleWhen']) && !$this->validVisibilityConditions($shortcode['visibleWhen'])) {
                throw new \InvalidArgumentException('Textarea shortcode visibility conditions are invalid.');
            }
        }

        return $this->attr('shortcodes', array_values($shortcodes));
    }

    public function showShortcodesWhen(string $field, string|array $values): static
    {
        $values = is_array($values) ? array_values($values) : [$values];
        if (trim($field) === '' || $values === []) {
            throw new \InvalidArgumentException('Shortcode visibility requires a field and at least one value.');
        }

        return $this->attr('shortcodesWhen', ['field' => $field, 'values' => $values]);
    }

    public function smsCounter(array $labels): static
    {
        foreach (['characters', 'remaining', 'parts', 'encoding', 'unicode', 'gsm'] as $key) {
            if (!isset($labels[$key]) || !is_string($labels[$key]) || trim($labels[$key]) === '') {
                throw new \InvalidArgumentException('SMS counter labels are incomplete.');
            }
        }

        return $this->attr('smsCounter', $labels);
    }

    protected function validateAttribute(string $key, mixed $value): void
    {
        parent::validateAttribute($key, $value);
        if ($key === 'rows' && (!is_int($value) || $value < 1)) {
            throw new \InvalidArgumentException('Rows must be a positive integer.');
        }
    }

    public function sanitize($value)
    {
        return sanitize_textarea_field((string) $value);
    }

    private function validVisibilityConditions(mixed $conditions): bool
    {
        if (!is_array($conditions) || $conditions === []) {
            return false;
        }

        if (isset($conditions['field'], $conditions['values'])) {
            $conditions = [$conditions];
        }

        foreach ($conditions as $condition) {
            if (!is_array($condition)
                || !isset($condition['field'], $condition['values'])
                || !is_string($condition['field'])
                || trim($condition['field']) === ''
                || !is_array($condition['values'])
                || $condition['values'] === []) {
                return false;
            }
        }

        return true;
    }
}
