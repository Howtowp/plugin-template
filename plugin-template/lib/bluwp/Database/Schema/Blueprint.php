<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Schema;

use Forouzandea\Bluwp\Database\Connection\ConnectionInterface;
use Forouzandea\Bluwp\Database\Query\SqlCompiler;

final class Blueprint
{
    private array $columns = [];
    private array $indexes = [];
    private array $foreignKeys = [];
    private array $commands = [];

    public function __construct(private string $table, private string $prefix = '')
    {
        SqlCompiler::identifier($table);
    }
    private function addColumn(string $name, string $type): ColumnDefinition
    {
        if (isset($this->columns[$name])) {
            throw new \InvalidArgumentException('Duplicate column: ' . $name);
        }
        return $this->columns[$name] = new ColumnDefinition($name, $type, $this);
    }
    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->bigInteger($name)->unsigned()->autoIncrement()->primary();
    }
    public function uuid(string $name): ColumnDefinition
    {
        return $this->char($name, 36);
    }
    public function string(string $name, int $length = 255): ColumnDefinition
    {
        if ($length < 1 || $length > 65535) {
            throw new \InvalidArgumentException('Invalid string length.');
        }
        return $this->addColumn($name, 'VARCHAR(' . $length . ')');
    }
    public function char(string $name, int $length = 255): ColumnDefinition
    {
        if ($length < 1 || $length > 255) {
            throw new \InvalidArgumentException('Invalid char length.');
        }
        return $this->addColumn($name, 'CHAR(' . $length . ')');
    }
    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'INT');
    }
    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'BIGINT');
    }
    public function unsignedBigInteger(string $name): ColumnDefinition
    {
        return $this->bigInteger($name)->unsigned();
    }
    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        if ($precision < 1 || $precision > 65 || $scale < 0 || $scale > 30 || $scale > $precision) {
            throw new \InvalidArgumentException('Invalid decimal precision or scale.');
        }
        return $this->addColumn($name, 'DECIMAL(' . $precision . ',' . $scale . ')');
    }
    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TINYINT(1)');
    }
    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TEXT');
    }
    public function longText(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'LONGTEXT');
    }
    public function json(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'JSON');
    }
    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DATE');
    }
    public function dateTime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'DATETIME');
    }
    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TIMESTAMP');
    }
    public function timestamps(): void
    {
        $this->dateTime('created_at')->nullable();
        $this->dateTime('updated_at')->nullable();
    }
    public function softDeletes(string $name = 'deleted_at'): ColumnDefinition
    {
        return $this->dateTime($name)->nullable();
    }
    public function enum(string $name, array $values): ColumnDefinition
    {
        if (!$values || count(array_filter($values, 'is_string')) !== count($values)) {
            throw new \InvalidArgumentException('Enum requires non-empty string values.');
        }
        $column = $this->addColumn($name, 'ENUM');
        $column->enumValues = $values;
        return $column;
    }
    public function foreignId(string $name): ColumnDefinition
    {
        return $this->unsignedBigInteger($name);
    }
    public function foreignUuid(string $name): ColumnDefinition
    {
        return $this->uuid($name);
    }
    private function indexName(array $columns, string $suffix): string
    {
        $name = $this->table . '_' . implode('_', $columns) . '_' . $suffix;
        return strlen($name) > 64 ? substr($name, 0, 47) . '_' . substr(hash('sha256', $name), 0, 16) : $name;
    }
    private function addIndex(string $type, string|array $columns, ?string $name): void
    {
        $columns = (array) $columns;
        if (!$columns) {
            throw new \InvalidArgumentException('Index columns cannot be empty.');
        }
        foreach ($columns as $column) {
            SqlCompiler::identifier($column);
        }
        $name ??= $this->indexName($columns, strtolower($type));
        if (strlen($name) > 64) {
            throw new \InvalidArgumentException('Index name exceeds 64 characters.');
        }
        SqlCompiler::identifier($name);
        if (isset($this->indexes[$name])) {
            throw new \InvalidArgumentException('Duplicate index.');
        }
        $this->indexes[$name] = $type . ($type === 'PRIMARY KEY' ? '' : ' ' . SqlCompiler::identifier($name))
            . ' (' . implode(', ', array_map([SqlCompiler::class, 'identifier'], $columns)) . ')';
    }
    public function primary(string|array $columns): void
    {
        $this->addIndex('PRIMARY KEY', $columns, 'PRIMARY');
    }
    public function unique(string|array $columns, ?string $name = null): void
    {
        $this->addIndex('UNIQUE KEY', $columns, $name ?? $this->indexName((array) $columns, 'unique'));
    }
    public function index(string|array $columns, ?string $name = null): void
    {
        $this->addIndex('KEY', $columns, $name ?? $this->indexName((array) $columns, 'index'));
    }
    public function foreign(string|array $columns, ?string $name = null): ForeignKeyDefinition
    {
        $columns = (array) $columns;
        foreach ($columns as $column) {
            SqlCompiler::identifier($column);
        }
        $name ??= $this->indexName($columns, 'foreign');
        SqlCompiler::identifier($name);
        if (strlen($name) > 64 || isset($this->foreignKeys[$name])) {
            throw new \InvalidArgumentException('Invalid or duplicate foreign key name.');
        }
        return $this->foreignKeys[$name] = new ForeignKeyDefinition($columns, $name, $this->prefix);
    }
    public function dropColumn(string|array $columns): void
    {
        foreach ((array) $columns as $column) {
            $this->commands[] = 'DROP COLUMN ' . SqlCompiler::identifier($column);
        }
    }
    public function renameColumn(string $from, string $to): void
    {
        $this->commands[] = 'RENAME COLUMN ' . SqlCompiler::identifier($from) . ' TO ' . SqlCompiler::identifier($to);
    }
    public function dropIndex(string $name): void
    {
        $this->commands[] = 'DROP INDEX ' . SqlCompiler::identifier($name);
    }
    public function dropForeign(string $name): void
    {
        $this->commands[] = 'DROP FOREIGN KEY ' . SqlCompiler::identifier($name);
    }
    public function toSql(ConnectionInterface $connection, bool $ifNotExists = false): string
    {
        if (!$this->columns || $this->commands) {
            throw new \LogicException('Create requires columns and cannot contain alter commands.');
        }
        $parts = [];
        foreach ($this->columns as $column) {
            if ($column->isChange || $column->afterColumn !== null) {
                throw new \LogicException('change/after are only valid in alter.');
            }
            $parts[] = $column->toSql($connection);
        }
        array_push($parts, ...array_values($this->indexes));
        foreach ($this->foreignKeys as $foreign) {
            $parts[] = $foreign->toSql();
        }
        return 'CREATE TABLE ' . ($ifNotExists ? 'IF NOT EXISTS ' : '') . SqlCompiler::identifier($this->table)
            . " (\n" . implode(",\n", $parts) . "\n) ENGINE=InnoDB " . $connection->getCharsetCollate();
    }
    public function toAlterSql(ConnectionInterface $connection): string
    {
        $parts = $this->commands;
        foreach ($this->columns as $column) {
            $parts[] = ($column->isChange ? 'MODIFY COLUMN ' : 'ADD COLUMN ') . $column->toSql($connection)
                . ($column->afterColumn !== null ? ' AFTER ' . SqlCompiler::identifier($column->afterColumn) : '');
        }
        foreach ($this->indexes as $index) {
            $parts[] = 'ADD ' . $index;
        }
        foreach ($this->foreignKeys as $foreign) {
            $parts[] = 'ADD ' . $foreign->toSql();
        }
        if (!$parts) {
            throw new \LogicException('Empty table alteration.');
        }
        return 'ALTER TABLE ' . SqlCompiler::identifier($this->table) . ' ' . implode(', ', $parts);
    }
}
