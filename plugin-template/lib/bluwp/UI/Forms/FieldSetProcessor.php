<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Forms;

use Forouzandea\Bluwp\UI\Fields\Field;

/** Shared validation for a form or one editable-table row. */
final class FieldSetProcessor
{
    /** @param list<Field> $fields
     *  @param null|callable(array): array $normalize
     */
    public function process(array $fields, array $input, ?callable $normalize = null): FormResult
    {
        $data = [];
        $errors = [];
        $values = [];
        $validation = [];
        foreach ($fields as $field) {
            $name = $field->name();
            $value = array_key_exists($name, $input) ? $input[$name] : $field->get('default');
            $fieldErrors = $field->validationErrors($value);
            if ($fieldErrors === []) {
                $value = $field->sanitize($value);
                $fieldErrors = $field->validationErrors($value);
            }
            $values[$name] = $fieldErrors === [] ? $value : null;
            $validation[$name] = $fieldErrors;
        }
        $allFields = $fields;
        do {
            $count = count($fields);
            $fields = array_values(array_filter($fields, static fn (Field $field): bool => $field->isVisible($values)));
            $values = array_intersect_key($values, array_flip(array_map(static fn (Field $field): string => $field->name(), $fields)));
        } while (count($fields) < $count);
        foreach ($fields as $field) {
            $name = $field->name();
            foreach ($validation[$name] as $path => $message) {
                $errors[$name . ($path === '' ? '' : '.' . $path)] = $message;
            }
            if ($validation[$name] === []) {
                $data[$name] = $values[$name];
            }
        }
        if ($errors === []) {
            $errors = $this->visibilityErrors($allFields, $data, $fields);
        }
        if ($errors === [] && $normalize !== null) {
            $data = $normalize($data);
            $result = $this->validateFinal($fields, $data);
            if (!$result->isValid()) {
                return $result;
            }
            $errors = $this->visibilityErrors($allFields, $data, $fields);
            if ($errors !== []) {
                return new FormResult([], $errors);
            }
            return $result;
        }
        if ($errors === []) {
            foreach ($fields as $field) {
                $error = $field->validateContext($data[$field->name()] ?? null, $data, $fields);
                if ($error !== null) {
                    $errors[$field->name()] = $error;
                }
            }
        }
        return new FormResult($errors === [] ? $data : [], $errors);
    }

    /** The sanitized and normalized data must describe the same visible field set. */
    private function visibilityErrors(array $allFields, array $data, array $visibleFields): array
    {
        $names = array_map(static fn (Field $field): string => $field->name(), $visibleFields);
        $errors = [];
        foreach ($allFields as $field) {
            if ($field->isVisible($data) !== in_array($field->name(), $names, true)) {
                $errors[$field->name()] = __('Invalid field value.', BLUWP);
            }
        }
        return $errors;
    }

    /** @param list<Field> $fields Already resolved visible fields; final output must contain exactly these keys. */
    public function validateFinal(array $fields, array $data): FormResult
    {
        $errors = [];
        $allowed = array_map(static fn (Field $field): string => $field->name(), $fields);
        foreach (array_diff(array_keys($data), $allowed) as $name) {
            $errors[$name] = __('Invalid field value.', BLUWP);
        }
        foreach ($fields as $field) {
            $name = $field->name();
            if (!array_key_exists($name, $data)) {
                $errors[$name] = __('Invalid field value.', BLUWP);
                continue;
            }
            foreach ($field->finalValidationErrors($data[$name]) as $path => $message) {
                $errors[$name . ($path === '' ? '' : '.' . $path)] = $message;
            }
        }
        if ($errors === []) {
            foreach ($fields as $field) {
                $error = $field->validateContext($data[$field->name()], $data, $fields);
                if ($error !== null) {
                    $errors[$field->name()] = $error;
                }
            }
        }
        return new FormResult($errors === [] ? $data : [], $errors);
    }
}
