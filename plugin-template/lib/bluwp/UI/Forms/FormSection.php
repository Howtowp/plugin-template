<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\UI\Forms;

final class FormSection implements \JsonSerializable
{
    /** @param list<string> $fields */
    public function __construct(private string $id, private string $title, private array $fields, private string $column = 'large')
    {
        if (!in_array($column, ['large', 'small'], true)) {
            throw new \InvalidArgumentException('Form section column must be large or small.');
        }
        if (!preg_match('/^[a-z][a-z0-9-]*$/D', $id) || trim($title) === '' || $fields === []) {
            throw new \InvalidArgumentException('A form section requires a valid ID, title and fields.');
        }
        foreach ($fields as $name) {
            if (!is_string($name) || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $name)) {
                throw new \InvalidArgumentException('Form section fields must be valid field names.');
            }
        }
        if (count(array_unique($fields)) !== count($fields)) {
            throw new \InvalidArgumentException('Form section field names must be unique.');
        }
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'fields' => $this->fields, 'column' => $this->column];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
