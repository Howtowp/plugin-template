<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Schema;

use Forouzandea\Bluwp\Database\Query\SqlCompiler;

final class ForeignKeyDefinition
{
    private ?string $targetTable = null;
    private array $targetColumns = ['id'];
    private string $deleteAction = 'RESTRICT';
    private string $updateAction = 'RESTRICT';

    public function __construct(private array $columns, private string $name, private string $prefix)
    {
    }
    public function references(string|array $columns): self
    {
        foreach ((array) $columns as $column) {
            SqlCompiler::identifier($column);
        }
        $this->targetColumns = (array) $columns;
        return $this;
    }
    public function on(string $table): self
    {
        SqlCompiler::identifier($this->prefix . $table);
        $this->targetTable = $this->prefix . $table;
        return $this;
    }
    private function action(string $action): string
    {
        $action = strtoupper($action);
        if (!in_array($action, ['CASCADE', 'RESTRICT', 'SET NULL', 'NO ACTION'], true)) {
            throw new \InvalidArgumentException('Invalid foreign key action.');
        }
        return $action;
    }
    public function onDelete(string $action): self
    {
        $this->deleteAction = $this->action($action);
        return $this;
    }
    public function onUpdate(string $action): self
    {
        $this->updateAction = $this->action($action);
        return $this;
    }
    public function cascadeOnDelete(): self
    {
        return $this->onDelete('CASCADE');
    }
    public function nullOnDelete(): self
    {
        return $this->onDelete('SET NULL');
    }
    public function toSql(): string
    {
        if ($this->targetTable === null || !$this->columns || count($this->columns) !== count($this->targetColumns)) {
            throw new \LogicException('Foreign key requires a target table and matching columns.');
        }
        return 'CONSTRAINT ' . SqlCompiler::identifier($this->name)
            . ' FOREIGN KEY (' . implode(', ', array_map([SqlCompiler::class, 'identifier'], $this->columns)) . ')'
            . ' REFERENCES ' . SqlCompiler::identifier($this->targetTable)
            . ' (' . implode(', ', array_map([SqlCompiler::class, 'identifier'], $this->targetColumns)) . ')'
            . ' ON DELETE ' . $this->deleteAction . ' ON UPDATE ' . $this->updateAction;
    }
}
