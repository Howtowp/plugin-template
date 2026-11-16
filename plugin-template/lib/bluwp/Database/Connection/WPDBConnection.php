<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Connection;

use Howtowp\Bluwp\Database\Exceptions\QueryException;
use Howtowp\Bluwp\Database\Query\Builder;
use Howtowp\Bluwp\Database\Query\SqlCompiler;

final class WPDBConnection implements ConnectionInterface, \Howtowp\Bluwp\Database\Contracts\MonitorsQueries
{
    use MonitorsQueries;
    private array $afterCommitCallbacks = [];
    private int $lastInsertId = 0;
    private int $level = 0;
    private bool $transactionFailed = false;
    private bool $managingTransaction = false;

    public function __construct(private \wpdb $db)
    {
    }

    public function select(string $sql, array $bindings = []): array
    {
        $this->assertUsable();
        $this->assertTransactionSafe($sql);
        $prepared = $this->prepare($sql, $bindings);
        $started = hrtime(true);
        $errorCode = null;
        $previous = $this->db->suppress_errors(true);
        try {
            $rows = $this->db->get_results($prepared, OBJECT);
            if ($this->db->last_error !== '') {
                $error = $this->queryException($sql, $bindings);
                $errorCode = $error->getDriverCode();
                throw $error;
            }
            return $rows ?? [];
        } finally {
            $this->db->suppress_errors($previous);
            $this->recordQuery($sql, $bindings, $started, $errorCode, !$this->managingTransaction);
        }
    }

    public function statement(string $sql, array $bindings = []): int
    {
        $this->assertUsable();
        $this->assertTransactionSafe($sql);
        $prepared = $this->prepare($sql, $bindings);
        $started = hrtime(true);
        $errorCode = null;
        $insertId = 0;
        $previous = $this->db->suppress_errors(true);
        try {
            $result = $this->db->query($prepared);
            $insertId = (int) $this->db->insert_id;
            if ($result === false) {
                $error = $this->queryException($sql, $bindings);
                $errorCode = $error->getDriverCode();
                throw $error;
            }
            return (int) $result;
        } finally {
            $this->db->suppress_errors($previous);
            $this->recordQuery($sql, $bindings, $started, $errorCode, !$this->managingTransaction);
            $this->lastInsertId = $insertId;
        }
    }

    private function prepare(string $sql, array $bindings): string
    {
        if (!$bindings) {
            return $sql;
        }
        foreach ($bindings as $value) {
            SqlCompiler::placeholder($value);
        }
        $prepared = $this->db->prepare($sql, array_values($bindings));
        if (!is_string($prepared) || $prepared === '') {
            throw new \InvalidArgumentException('Invalid SQL bindings.');
        }
        return $prepared;
    }
    private function queryException(string $sql, array $bindings): QueryException
    {
        $handle = $this->db->__get('dbh');
        return new QueryException(
            $sql,
            $bindings,
            $this->db->last_error,
            $handle instanceof \mysqli ? mysqli_errno($handle) : 0,
            $handle instanceof \mysqli ? mysqli_sqlstate($handle) : ''
        );
    }
    public function afterCommit(callable $callback): void
    {
        if ($this->level === 0) {
            $callback();
        } else {
            $this->afterCommitCallbacks[$this->level][] = $callback;
        }
    }
    private function assertUsable(): void
    {
        if ($this->transactionFailed) {
            throw new \LogicException('Connection is unusable after a failed rollback; end this request.');
        }
    }
    private function assertTransactionSafe(string $sql): void
    {
        if ($this->level === 0 || $this->managingTransaction) {
            return;
        }
        // Strip ordinary leading comments, but never MySQL executable comments.
        $sql = (string) preg_replace('~\\A(?:\\s+|/\\*(?![!])[^*]*\\*+(?:[^/*][^*]*\\*+)*/|\\#[^\\r\\n]*(?:\\r?\\n|$)|--\\s[^\\r\\n]*(?:\\r?\\n|$))*~', '', $sql);
        if (!preg_match('/^(SELECT|INSERT|UPDATE|DELETE|REPLACE|WITH)\\b/i', $sql)) {
            throw new \LogicException('Only data queries are allowed inside a managed transaction; use the transaction methods for transaction control.');
        }
    }
    private function transactionStatement(string $sql): void
    {
        $this->managingTransaction = true;
        try {
            $this->statement($sql);
        } finally {
            $this->managingTransaction = false;
        }
    }

    public function insert(string $table, array $data): int
    {
        [$sql, $bindings] = (new SqlCompiler())->insert($table, $data);
        $this->statement($sql, $bindings);
        return $this->lastInsertId;
    }

    public function update(string $table, array $data, array $where): int
    {
        $query = new Builder($this, $table);
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }
        return $query->update($data);
    }

    public function delete(string $table, array $where): int
    {
        $query = new Builder($this, $table);
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }
        return $query->delete();
    }

    public function getPrefix(): string
    {
        return $this->db->prefix;
    }
    public function getCharsetCollate(): string
    {
        return $this->db->get_charset_collate();
    }
    public function quote(string $value): string
    {
        return $this->db->remove_placeholder_escape($this->db->prepare('%s', $value));
    }
    public function transactionLevel(): int
    {
        return $this->level;
    }

    public function beginTransaction(): void
    {
        $this->transactionStatement($this->level === 0 ? 'START TRANSACTION' : 'SAVEPOINT bluwp_' . ($this->level + 1));
        ++$this->level;
    }

    public function commit(): void
    {
        if ($this->level === 0) {
            throw new \LogicException('No transaction to commit.');
        }
        $this->transactionStatement($this->level === 1 ? 'COMMIT' : 'RELEASE SAVEPOINT bluwp_' . $this->level);
        $callbacks = $this->afterCommitCallbacks[$this->level] ?? [];
        unset($this->afterCommitCallbacks[$this->level]);
        --$this->level;
        if ($this->level > 0) {
            $this->afterCommitCallbacks[$this->level] = array_merge($this->afterCommitCallbacks[$this->level] ?? [], $callbacks);
        } else {
            foreach ($callbacks as $callback) {
                $callback();
            }
        }
    }

    public function rollBack(): void
    {
        if ($this->level === 0) {
            throw new \LogicException('No transaction to roll back.');
        }
        try {
            $this->transactionStatement($this->level === 1 ? 'ROLLBACK' : 'ROLLBACK TO SAVEPOINT bluwp_' . $this->level);
        } catch (\Throwable $error) {
            $this->transactionFailed = true;
            $this->level = 0;
            $this->afterCommitCallbacks = [];
            throw $error;
        }
        unset($this->afterCommitCallbacks[$this->level]);
        --$this->level;
    }

    public function transaction(callable $callback, int $attempts = 1): mixed
    {
        $start = $this->level;
        if ($attempts < 1 || ($start > 0 && $attempts !== 1)) {
            throw new \InvalidArgumentException('Retries require an outermost transaction and positive attempts.');
        }
        for ($attempt = 1; $attempt <= $attempts; ++$attempt) {
            $this->beginTransaction();
            $committing = false;
            try {
                $result = $callback($this);
                if ($this->level !== $start + 1) {
                    throw new \LogicException('Unbalanced transaction operations inside callback.');
                }
                $committing = true;
                $this->commit();
                return $result;
            } catch (\Throwable $exception) {
                // Callback failure after a successful COMMIT must never retry committed work.
                if ($committing && $this->level === $start) {
                    throw $exception;
                }
                $deadlock = $exception instanceof QueryException && $exception->isDeadlock();
                if ($deadlock) {
                    try {
                        $this->transactionStatement('ROLLBACK');
                    } catch (\Throwable $rollbackError) {
                        $this->transactionFailed = true;
                        throw $rollbackError;
                    }
                    $this->level = 0;
                    $this->afterCommitCallbacks = [];
                } else {
                    while ($this->level > $start) {
                        $this->rollBack();
                    }
                }
                if (!$deadlock || $start > 0 || $attempt === $attempts) {
                    throw $exception;
                }
                usleep(min(100000, 10000 * $attempt));
            }
        }
        throw new \LogicException('Transaction retry exhausted.');
    }
}
