<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Forms;

use Forouzandea\Bluwp\UI\Contracts\Schema;
use Forouzandea\Bluwp\UI\Fields\Field;

abstract class FormSchema implements Schema
{
    abstract public function title(): string;
    /** @return list<Field> */
    abstract public function fields(): array;
    abstract public function submit(): array;

    public function columns(): int
    {
        return 1;
    }

    /** @return list<FormSection> */
    public function sections(): array
    {
        return [];
    }

    public function sectionLayout(): string
    {
        return 'tabs';
    }

    public function accordionAutoClose(): bool
    {
        return true;
    }

    public function accordionDefaultOpen(): ?bool
    {
        return null;
    }

    public function sectionColumns(): int
    {
        return 1;
    }

    /**
     * Normalize values of the resolved visible fields, preserving their keys.
     * Derived top-level fields require a separate output contract outside this form.
     * The result is validated again without sanitizing or restoring defaults.
     */
    public function normalizeData(array $data): array
    {
        return $data;
    }

    public function validateForm(array $data): array
    {
        return [];
    }

    /** @return list<Field> */
    final public function resolvedFields(): array
    {
        $fields = [];
        foreach ($this->fields() as $field) {
            if (!$field instanceof Field || isset($fields[$field->name()])) {
                throw new \LogicException('Form fields must be Field objects with unique names.');
            }
            $fields[$field->name()] = $field;
        }
        return array_values($fields);
    }

    public function toArray(): array
    {
        $schema = [
            'title' => $this->title(),
            'type' => 'form',
            'layout' => ['columns' => max(1, min(2, $this->columns()))],
            'fields' => array_map(static fn (Field $field): array => $field->toArray(), $this->resolvedFields()),
            'submit' => $this->submit(),
        ];
        $sections = [];
        $assigned = [];
        $names = array_column($schema['fields'], 'name');
        foreach ($this->sections() as $section) {
            if (!$section instanceof FormSection) {
                throw new \LogicException('Form sections must be FormSection objects.');
            }
            $data = $section->toArray();
            if (isset($sections[$data['id']])) {
                throw new \LogicException('Form section IDs must be unique.');
            }
            foreach ($data['fields'] as $name) {
                if (!in_array($name, $names, true) || isset($assigned[$name])) {
                    throw new \LogicException('Every section field must exist and belong to only one section.');
                }
                $assigned[$name] = true;
            }
            $sections[$data['id']] = $data;
        }
        if ($sections !== []) {
            if (count($assigned) !== count($names)) {
                throw new \LogicException('Every form field must belong to a section in a sectioned form.');
            }
            $schema['sections'] = array_values($sections);
            $layout = $this->sectionLayout();
            if (!in_array($layout, ['tabs', 'accordion'], true)) {
                throw new \LogicException('Form section layout must be tabs or accordion.');
            }
            $schema['layout']['sectionLayout'] = $layout;
            $columns = $this->sectionColumns();
            if (!in_array($columns, [1, 2], true) || ($layout !== 'accordion' && $columns !== 1)) {
                throw new \LogicException('Form sections require one column, or two columns with accordion layout.');
            }
            $schema['layout']['sectionColumns'] = $columns;
            $schema['layout']['accordionAutoClose'] = $this->accordionAutoClose();
            if ($this->accordionDefaultOpen() !== null) {
                $schema['layout']['accordionDefaultOpen'] = $this->accordionDefaultOpen();
            }
        }
        return $schema;
    }
}
