<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Connection;

interface ConnectionInterface
{
    public function select(string $sql, array $bindings = []): array;
    public function statement(string $sql, array $bindings = []): int;
    public function insert(string $table, array $data): int;
    public function update(string $table, array $data, array $where): int;
    public function delete(string $table, array $where): int;
    public function getPrefix(): string;
    public function getCharsetCollate(): string;
    public function quote(string $value): string;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollBack(): void;
    public function transactionLevel(): int;
    public function transaction(callable $callback, int $attempts = 1): mixed;
    public function afterCommit(callable $callback): void;
}
