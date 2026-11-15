<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database;

use Forouzandea\Bluwp\Database\Connection\ConnectionInterface;
use Forouzandea\Bluwp\Database\Query\Bindings;
use Forouzandea\Bluwp\Database\Query\Builder;
use Forouzandea\Bluwp\Database\Schema\Builder as SchemaBuilder;

/** Public connection API; wpdb-specific placeholders stay inside the adapter. */
final class Connection
{
    public function __construct(private ConnectionInterface $adapter)
    {
    }
    public function getAdapter(): ConnectionInterface
    {
        return $this->adapter;
    }
    public function getTablePrefix(): string
    {
        return $this->adapter->getPrefix();
    }
    public function table(string $table): Builder
    {
        return new Builder($this->adapter, $this->getTablePrefix() . $table);
    }
    public function getSchemaBuilder(): SchemaBuilder
    {
        return new SchemaBuilder($this->adapter);
    }
    public function select(string $query, array $bindings = []): array
    {
        [$sql, $values] = Bindings::positional($query, $bindings);
        return $this->adapter->select($sql, $values);
    }
    public function selectOne(string $query, array $bindings = []): ?object
    {
        return $this->select($query, $bindings)[0] ?? null;
    }
    public function scalar(string $query, array $bindings = []): mixed
    {
        $row = $this->selectOne($query, $bindings);
        if ($row === null) {
            return null;
        }
        $values = array_values((array) $row);
        if (count($values) !== 1) {
            throw new \LogicException('Scalar queries must select exactly one column.');
        }
        return $values[0];
    }
    public function affectingStatement(string $query, array $bindings = []): int
    {
        [$sql, $values] = Bindings::positional($query, $bindings);
        return $this->adapter->statement($sql, $values);
    }
    public function statement(string $query, array $bindings = []): bool
    {
        $this->affectingStatement($query, $bindings);
        return true;
    }
    public function insert(string $query, array $bindings = []): bool
    {
        return $this->statement($query, $bindings);
    }
    public function update(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }
    public function delete(string $query, array $bindings = []): int
    {
        return $this->affectingStatement($query, $bindings);
    }
    public function transaction(callable $callback, int $attempts = 1): mixed
    {
        return $this->adapter->transaction(fn () => $callback($this), $attempts);
    }
    public function afterCommit(callable $callback): void
    {
        $this->adapter->afterCommit($callback);
    }
    private function monitor(): Contracts\MonitorsQueries
    {
        if (!$this->adapter instanceof Contracts\MonitorsQueries) {
            throw new \LogicException('This database adapter does not support query monitoring.');
        }
        return $this->adapter;
    }
    public function supportsQueryMonitoring(): bool
    {
        return $this->adapter instanceof Contracts\MonitorsQueries;
    }
    public function listen(callable $callback): void
    {
        $this->monitor()->listen($callback);
    }
    public function whenQueryingForLongerThan(float $milliseconds, callable $callback): void
    {
        $this->monitor()->whenQueryingForLongerThan($milliseconds, $callback);
    }
    public function queryStats(): array
    {
        return $this->monitor()->queryStats();
    }
    public function resetQueryStats(): void
    {
        $this->monitor()->resetQueryStats();
    }
    public function clearQueryListeners(): void
    {
        $this->monitor()->clearQueryListeners();
    }
    public function beginTransaction(): void
    {
        $this->adapter->beginTransaction();
    }
    public function commit(): void
    {
        $this->adapter->commit();
    }
    public function rollBack(): void
    {
        $this->adapter->rollBack();
    }
    public function transactionLevel(): int
    {
        return $this->adapter->transactionLevel();
    }
}
