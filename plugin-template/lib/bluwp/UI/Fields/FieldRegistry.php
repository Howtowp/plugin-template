<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Fields;

final class FieldRegistry
{
    private array $factories = [];

    public function text(string $name): TextField
    {
        return $this->typed('text', $name, TextField::class);
    }
    public function textarea(string $name): TextareaField
    {
        return $this->typed('textarea', $name, TextareaField::class);
    }
    public function number(string $name): NumberField
    {
        return $this->typed('number', $name, NumberField::class);
    }
    public function password(string $name): PasswordField
    {
        return $this->typed('password', $name, PasswordField::class);
    }
    public function select(string $name): SelectField
    {
        return $this->typed('select', $name, SelectField::class);
    }
    public function multiSelect(string $name): MultiSelectField
    {
        return $this->typed('multi-select', $name, MultiSelectField::class);
    }
    public function switch(string $name): SwitchField
    {
        return $this->typed('switch', $name, SwitchField::class);
    }
    public function tags(string $name): TagsField
    {
        return $this->typed('tags', $name, TagsField::class);
    }
    public function timeRange(string $name): TimeRangeField
    {
        return $this->typed('time-range', $name, TimeRangeField::class);
    }
    public function url(string $name): UrlField
    {
        return $this->typed('url', $name, UrlField::class);
    }
    public function editableTable(string $name): EditableTableField
    {
        return $this->typed('editable-table', $name, EditableTableField::class);
    }

    public static function defaults(): self
    {
        $registry = new self();
        foreach (['text' => TextField::class, 'textarea' => TextareaField::class, 'number' => NumberField::class,
            'select' => SelectField::class, 'multi-select' => MultiSelectField::class, 'switch' => SwitchField::class, 'tags' => TagsField::class, 'time-range' => TimeRangeField::class,
            'password' => PasswordField::class, 'url' => UrlField::class, 'editable-table' => EditableTableField::class] as $type => $class) {
            $registry->register($type, static fn (string $name): Field => new $class($name));
        }
        return $registry;
    }

    /**
     * @template T of Field
     * @param class-string<T> $expected
     * @return T
     */
    private function typed(string $type, string $name, string $expected): Field
    {
        $field = $this->make($type, $name);
        if (!$field instanceof $expected) {
            throw new \LogicException('Built-in field factories must preserve their concrete contract.');
        }
        return $field;
    }

    public function register(string $type, callable $factory): self
    {
        if ($type === '' || isset($this->factories[$type])) {
            throw new \InvalidArgumentException('Field types must be nonempty and unique.');
        }
        $this->factories[$type] = $factory;
        return $this;
    }

    public function make(string $type, string $name): Field
    {
        $factory = $this->factories[$type] ?? throw new \InvalidArgumentException('Unknown field type: ' . $type);
        $field = $factory($name);
        if (!$field instanceof Field || $field->type() !== $type || $field->name() !== $name) {
            throw new \LogicException('Field factories must preserve the requested type and name.');
        }
        return $field;
    }
}
