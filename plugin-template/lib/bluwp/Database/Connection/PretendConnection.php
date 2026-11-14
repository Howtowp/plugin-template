<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Connection;

use Forouzandea\Bluwp\Database\Query\SqlCompiler;

/** SQL preview only: SELECT returns no rows; migration PHP must not have external side effects. */
final class PretendConnection implements ConnectionInterface
{
    private array $queries = [];
    private int $level = 0;
    public function __construct(private ConnectionInterface $connection)
    {
    }
    public function queries(): array
    {
        return $this->queries;
    }
    private function record(string $sql, array $bindings): void
    {
        $this->queries[] = ['sql' => $sql, 'bindings' => $bindings];
    }
    public function select(string $sql, array $bindings = []): array
    {
        $this->record($sql, $bindings);
        return [];
    }
    public function statement(string $sql, array $bindings = []): int
    {
        $this->record($sql, $bindings);
        return 0;
    }
    public function insert(string $table, array $data): int
    {
        [$sql, $bindings] = (new SqlCompiler())->insert($table, $data);
        $this->record($sql, $bindings);
        return 0;
    }
    public function update(string $table, array $data, array $where): int
    {
        $query = new \Forouzandea\Bluwp\Database\Query\Builder($this, $table);
        return $query->where($where)->update($data);
    }
    public function delete(string $table, array $where): int
    {
        $query = new \Forouzandea\Bluwp\Database\Query\Builder($this, $table);
        return $query->where($where)->delete();
    }
    public function getPrefix(): string
    {
        return $this->connection->getPrefix();
    }
    public function getCharsetCollate(): string
    {
        return $this->connection->getCharsetCollate();
    }
    public function quote(string $value): string
    {
        return $this->connection->quote($value);
    }
    public function beginTransaction(): void
    {
        ++$this->level;
    }
    public function commit(): void
    {
        if ($this->level < 1) {
            throw new \LogicException('No transaction.');
        } --$this->level;
    }
    public function rollBack(): void
    {
        $this->commit();
    }
    public function transactionLevel(): int
    {
        return $this->level;
    }
    public function transaction(callable $callback, int $attempts = 1): mixed
    {
        $this->beginTransaction();
        try {
            return $callback($this);
        } finally {
            $this->rollBack();
        }
    }
    public function afterCommit(callable $callback): void
    {
    }
}
