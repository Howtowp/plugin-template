<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Schema;

use Forouzandea\Bluwp\Database\Connection\ConnectionInterface;
use Forouzandea\Bluwp\Database\Query\SqlCompiler;

final class ColumnDefinition
{
    public bool $isNullable = false;
    public bool $isUnsigned = false;
    public bool $isAutoIncrement = false;
    public bool $hasDefault = false;
    public mixed $defaultValue = null;
    public bool $currentDefault = false;
    public bool $currentOnUpdate = false;
    public bool $isChange = false;
    public ?string $afterColumn = null;
    public ?string $commentText = null;
    public ?array $enumValues = null;

    public function __construct(public readonly string $name, public readonly string $type, private Blueprint $blueprint)
    {
        SqlCompiler::identifier($name);
        if (!preg_match('/^(BIGINT|INT|SMALLINT|TINYINT(?:\(1\))?|VARCHAR\(\d+\)|CHAR\(\d+\)|TEXT|LONGTEXT|DECIMAL\(\d+,\d+\)|JSON|DATETIME|TIMESTAMP|DATE|ENUM)$/D', $type)) {
            throw new \InvalidArgumentException('Unsupported schema column type.');
        }
    }
    public function nullable(bool $value = true): self
    {
        $this->isNullable = $value;
        return $this;
    }
    public function unsigned(): self
    {
        $this->isUnsigned = true;
        return $this;
    }
    public function autoIncrement(): self
    {
        $this->isAutoIncrement = true;
        return $this;
    }
    public function default(mixed $value): self
    {
        SqlCompiler::placeholder($value);
        $this->hasDefault = true;
        $this->defaultValue = $value;
        $this->currentDefault = false;
        return $this;
    }
    public function useCurrent(): self
    {
        $this->currentDefault = true;
        $this->hasDefault = false;
        return $this;
    }
    public function useCurrentOnUpdate(): self
    {
        $this->currentOnUpdate = true;
        return $this;
    }
    public function comment(string $value): self
    {
        $this->commentText = $value;
        return $this;
    }
    public function after(string $column): self
    {
        SqlCompiler::identifier($column);
        $this->afterColumn = $column;
        return $this;
    }
    public function change(): self
    {
        $this->isChange = true;
        return $this;
    }
    public function primary(): self
    {
        $this->blueprint->primary($this->name);
        return $this;
    }
    public function unique(?string $name = null): self
    {
        $this->blueprint->unique($this->name, $name);
        return $this;
    }
    public function index(?string $name = null): self
    {
        $this->blueprint->index($this->name, $name);
        return $this;
    }
    public function constrained(string $table, string $column = 'id'): ForeignKeyDefinition
    {
        return $this->blueprint->foreign($this->name)->references($column)->on($table);
    }
    public function toSql(ConnectionInterface $connection): string
    {
        $type = $this->type;
        if ($type === 'ENUM') {
            if (!$this->enumValues) {
                throw new \InvalidArgumentException('Enum values cannot be empty.');
            }
            $type .= '(' . implode(', ', array_map([$connection, 'quote'], $this->enumValues)) . ')';
        }
        if ($this->isUnsigned && !preg_match('/^(BIGINT|INT|SMALLINT|TINYINT|DECIMAL)/', $type)) {
            throw new \LogicException('Unsigned requires a numeric column.');
        }
        if (($this->currentDefault || $this->currentOnUpdate) && !in_array($type, ['DATETIME', 'TIMESTAMP'], true)) {
            throw new \LogicException('Current timestamp requires a temporal column.');
        }
        $sql = SqlCompiler::identifier($this->name) . ' ' . $type . ($this->isUnsigned ? ' UNSIGNED' : '')
            . ($this->isNullable ? ' NULL' : ' NOT NULL');
        if ($this->isAutoIncrement) {
            $sql .= ' AUTO_INCREMENT';
        }
        if ($this->currentDefault) {
            $sql .= ' DEFAULT CURRENT_TIMESTAMP';
        } elseif ($this->hasDefault) {
            if ($this->defaultValue === null && !$this->isNullable) {
                throw new \LogicException('NULL default requires nullable().');
            }
            $value = $this->defaultValue;
            $sql .= ' DEFAULT ' . match (true) {
                $value === null => 'NULL',
                is_bool($value) => $value ? '1' : '0',
                is_int($value), is_float($value) => (string) $value,
                default => $connection->quote($value),
            };
        }
        if ($this->currentOnUpdate) {
            $sql .= ' ON UPDATE CURRENT_TIMESTAMP';
        }
        if ($this->commentText !== null) {
            $sql .= ' COMMENT ' . $connection->quote($this->commentText);
        }
        return $sql;
    }
}
