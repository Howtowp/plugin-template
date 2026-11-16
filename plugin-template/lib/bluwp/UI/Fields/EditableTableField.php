<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Fields;

use Howtowp\Bluwp\UI\Forms\FieldSetProcessor;

final class EditableTableField extends Field
{
    protected string $type = 'editable-table';

    /** Values in each listed column must be unique across the table's rows. */
    public function uniqueColumns(string ...$names): static
    {
        foreach ($names as $name) {
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $name)) {
                throw new \InvalidArgumentException(__('A field needs a valid name.', BLUWP));
            }
        }
        return $this->attr('uniqueColumns', array_values(array_unique($names)));
    }

    public function rowKey(string $name): static
    {
        return $this->attr('rowKey', $name);
    }

    /** @param list<Field> $columns */
    public function columns(array $columns): static
    {
        return $this->attr('columns', $columns);
    }

    protected function validateAttribute(string $key, mixed $value): void
    {
        parent::validateAttribute($key, $value);
        if ($key === 'rowKey') {
            if (!is_string($value) || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $value)) {
                throw new \InvalidArgumentException(__('A field needs a valid name.', BLUWP));
            }
        }
        if ($key === 'columns') {
            if (!is_array($value) || $value === []) {
                throw new \InvalidArgumentException(__('An editable table requires columns.', BLUWP));
            }
            $names = [];
            foreach ($value as $column) {
                if (!$column instanceof Field || isset($names[$column->name()])) {
                    throw new \InvalidArgumentException(__('Table columns must be unique fields.', BLUWP));
                }
                $names[$column->name()] = true;
            }
        }
        if ($key === 'rowKey' || $key === 'columns') {
            $rowKey = $key === 'rowKey' ? $value : $this->get('rowKey');
            $columns = $key === 'columns' ? $value : ($this->get('columns') ?? []);
            foreach ($columns as $column) {
                if ($column->name() === $rowKey) {
                    throw new \InvalidArgumentException('Table row keys must be separate from data columns.');
                }
            }
        }
    }

    public function validate($value): ?string
    {
        $errors = $this->validationErrors($value);
        return $errors === [] ? null : reset($errors);
    }

    public function validationErrors(mixed $value): array
    {
        return $this->validateRows($value, false);
    }

    public function finalValidationErrors(mixed $value): array
    {
        return $this->validateRows($value, true);
    }

    private function validateRows(mixed $value, bool $final): array
    {
        if (!is_array($value) || !array_is_list($value) || count($value) > 100) {
            return ['' => __('Invalid table rows.', BLUWP)];
        }
        $columns = $this->get('columns');
        if (!is_array($columns) || $columns === []) {
            return ['' => __('Invalid table columns.', BLUWP)];
        }
        $errors = [];
        $unique = $this->get('uniqueColumns') ?? [];
        $columnNames = array_map(static fn (Field $field): string => $field->name(), $columns);
        if (!is_array($unique) || array_diff($unique, $columnNames) !== []) {
            return ['' => __('Invalid table columns.', BLUWP)];
        }
        $seen = [];
        $processor = new FieldSetProcessor();
        foreach ($value as $index => $row) {
            $allowed = array_map(static fn (Field $field): string => $field->name(), $columns);
            $rowKey = $this->get('rowKey');
            if (is_string($rowKey)) {
                $allowed[] = $rowKey;
            }
            if (!is_array($row) || array_diff(array_keys($row), $allowed) !== []) {
                $errors[(string) $index] = __('Invalid table row.', BLUWP);
                continue;
            }
            if (is_string($rowKey) && isset($row[$rowKey])
                && (!is_string($row[$rowKey]) || strlen($row[$rowKey]) > 100)) {
                $errors[(string) $index] = __('Invalid table row.', BLUWP);
                continue;
            }
            if ($final) {
                $visible = array_values(array_filter($columns, static fn (Field $field): bool => $field->isVisible($row)));
                $cells = $row;
                if (is_string($rowKey)) {
                    unset($cells[$rowKey]);
                }
                $result = $processor->validateFinal($visible, $cells);
            } else {
                $result = $processor->process($columns, $row);
            }
            foreach ($result->errors as $path => $message) {
                $errors[$index . '.' . $path] = $message;
            }
            if ($result->errors === []) {
                foreach ($unique as $name) {
                    $cell = $result->data[$name] ?? null;
                    if (!is_scalar($cell)) {
                        continue;
                    }
                    $key = is_bool($cell) ? ($cell ? 'true' : 'false') : trim((string) $cell);
                    if ($key === '') {
                        continue;
                    }
                    if (isset($seen[$name][$key])) {
                        $errors[$index . '.' . $name] = __('Table column values must be unique.', BLUWP);
                    }
                    $seen[$name][$key] = true;
                }
            }
        }
        return $errors;
    }

    public function sanitize($value): array
    {
        $result = [];
        foreach ($value as $row) {
            $clean = [];
            $rowKey = $this->get('rowKey');
            if (is_string($rowKey) && isset($row[$rowKey])) {
                $clean[$rowKey] = sanitize_text_field($row[$rowKey]);
            }
            $clean += (new FieldSetProcessor())->process($this->get('columns'), $row)->data;
            $result[] = $clean;
        }
        return $result;
    }

    public function toArray(): array
    {
        $result = parent::toArray();
        $result['columns'] = array_map(static fn (Field $field): array => $field->toArray(), $this->get('columns') ?? []);
        return $result;
    }
}
