<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Schema;

use Howtowp\Bluwp\Database\Connection\ConnectionInterface;
use Howtowp\Bluwp\Database\Query\SqlCompiler;

final class Builder
{
    public function __construct(private ConnectionInterface $connection)
    {
    }
    private function tableName(string $table): string
    {
        $name = $this->connection->getPrefix() . $table;
        SqlCompiler::identifier($name);
        return $name;
    }
    private function assertOutsideTransaction(): void
    {
        if ($this->connection->transactionLevel() > 0) {
            throw new \LogicException('MySQL schema changes cannot be rolled back in a transaction.');
        }
    }
    public function create(string $table, callable $callback, bool $ifNotExists = false): void
    {
        $this->assertOutsideTransaction();
        $blueprint = new Blueprint($this->tableName($table), $this->connection->getPrefix());
        $callback($blueprint);
        $this->connection->statement($blueprint->toSql($this->connection, $ifNotExists));
    }
    public function table(string $table, callable $callback): void
    {
        $this->assertOutsideTransaction();
        $blueprint = new Blueprint($this->tableName($table), $this->connection->getPrefix());
        $callback($blueprint);
        $this->connection->statement($blueprint->toAlterSql($this->connection));
    }
    public function hasTable(string $table): bool
    {
        return $this->connection->select(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = %s LIMIT 1',
            [$this->tableName($table)]
        ) !== [];
    }
    public function hasColumn(string $table, string $column): bool
    {
        SqlCompiler::identifier($column);
        return $this->connection->select(
            'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = %s AND column_name = %s LIMIT 1',
            [$this->tableName($table), $column]
        ) !== [];
    }
    public function dropIfExists(string $table): void
    {
        $this->assertOutsideTransaction();
        $this->connection->statement('DROP TABLE IF EXISTS ' . SqlCompiler::identifier($this->tableName($table)));
    }
    public function getIndexes(string $table): array
    {
        $indexes = [];
        foreach ($this->connection->select('SHOW INDEX FROM ' . SqlCompiler::identifier($this->tableName($table))) as $row) {
            $indexes[$row->Key_name] ??= ['name' => $row->Key_name, 'unique' => !(bool) $row->Non_unique, 'columns' => []];
            $indexes[$row->Key_name]['columns'][(int) $row->Seq_in_index] = $row->Column_name;
        }
        foreach ($indexes as &$index) {
            ksort($index['columns']);
            $index['columns'] = array_values($index['columns']);
        }
        return array_values($indexes);
    }
}
